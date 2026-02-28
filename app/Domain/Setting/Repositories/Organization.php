<?php

namespace Leantime\Domain\Setting\Repositories;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Leantime\Core\Db\Db as DbCore;

class Organization
{
    private ConnectionInterface $db;

    public function __construct(DbCore $db)
    {
        $this->db = $db->getConnection();
    }

    public function getDepartments(): array
    {
        $this->ensureRbacSchema();

        if (! Schema::hasTable('zp_org_departments')) {
            return [];
        }

        $departments = $this->db->table('zp_org_departments')
            ->select(['id', 'name', 'slug', 'isActive'])
            ->orderBy('name')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();

        if ($departments === [] && Schema::hasTable('zp_user') && Schema::hasColumn('zp_user', 'department')) {
            $legacyDepartments = $this->db->table('zp_user')
                ->select(['department'])
                ->whereNotNull('department')
                ->get();

            foreach ($legacyDepartments as $row) {
                $name = trim((string) ($row->department ?? ''));
                if ($name === '') {
                    continue;
                }
                $this->addDepartment($name);
            }

            $departments = $this->db->table('zp_org_departments')
                ->select(['id', 'name', 'slug', 'isActive'])
                ->orderBy('name')
                ->get()
                ->map(fn ($row) => (array) $row)
                ->toArray();
        }

        return $departments;
    }

    public function getRoles(): array
    {
        $this->ensureRbacSchema();

        if (! Schema::hasTable('zp_org_roles')) {
            return [];
        }

        $this->ensureCoreRoles();

        return $this->db->table('zp_org_roles')
            ->select(['id', 'name', 'slug', 'systemRole', 'isProtected'])
            ->orderBy('name')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    public function getDepartmentRoles(): array
    {
        $roles = $this->getRoles();
        if ($roles === []) {
            return [];
        }

        $allowedSlugs = [
            'department-manager',
            'department-editor',
            'department-commentor',
            'department-readonly',
        ];

        $filtered = [];
        foreach ($allowedSlugs as $slug) {
            foreach ($roles as $role) {
                if ((string) ($role['slug'] ?? '') === $slug) {
                    $filtered[] = $role;
                    break;
                }
            }
        }

        return $filtered;
    }

    public function roleNameExists(string $name): bool
    {
        if (! Schema::hasTable('zp_org_roles')) {
            return false;
        }

        $normalized = mb_strtolower(trim($name));
        if ($normalized === '') {
            return false;
        }

        return $this->db->table('zp_org_roles')
            ->whereRaw('LOWER(TRIM(name)) = ?', [$normalized])
            ->exists();
    }

    public function getRoleById(int $roleId): ?array
    {
        if ($roleId <= 0 || ! Schema::hasTable('zp_org_roles')) {
            return null;
        }

        $row = $this->db->table('zp_org_roles')
            ->where('id', $roleId)
            ->first();

        return $row ? (array) $row : null;
    }

    public function getDepartmentById(int $departmentId): ?array
    {
        if ($departmentId <= 0 || ! Schema::hasTable('zp_org_departments')) {
            return null;
        }

        $row = $this->db->table('zp_org_departments')
            ->where('id', $departmentId)
            ->first();

        return $row ? (array) $row : null;
    }

    public function addDepartment(string $name): int
    {
        $this->ensureRbacSchema();

        if (! Schema::hasTable('zp_org_departments')) {
            return 0;
        }

        $name = trim($name);
        if ($name === '') {
            return 0;
        }

        $slug = $this->slugify($name);
        $existing = $this->db->table('zp_org_departments')->where('slug', $slug)->value('id');
        if ($existing) {
            return (int) $existing;
        }

        return (int) $this->db->table('zp_org_departments')->insertGetId([
            'name' => $name,
            'slug' => $slug,
            'isActive' => 1,
            'createdOn' => date('Y-m-d H:i:s'),
            'updatedOn' => date('Y-m-d H:i:s'),
        ]);
    }

    public function addRole(string $name, int $systemRole = 20): int
    {
        $this->ensureRbacSchema();

        if (! Schema::hasTable('zp_org_roles')) {
            return 0;
        }

        $name = trim($name);
        if ($name === '') {
            return 0;
        }

        $slug = $this->slugify($name);
        $existing = $this->db->table('zp_org_roles')->where('slug', $slug)->value('id');
        if ($existing) {
            return (int) $existing;
        }

        return (int) $this->db->table('zp_org_roles')->insertGetId([
            'name' => $name,
            'slug' => $slug,
            'systemRole' => $systemRole,
            'isProtected' => 0,
            'createdOn' => date('Y-m-d H:i:s'),
            'updatedOn' => date('Y-m-d H:i:s'),
        ]);
    }

    private function ensureCoreRoles(): void
    {
        if (! Schema::hasTable('zp_org_roles')) {
            return;
        }

        $seedRoles = [
            ['name' => 'Department Manager', 'slug' => 'department-manager', 'systemRole' => 30, 'isProtected' => 1],
            ['name' => 'Department Editor', 'slug' => 'department-editor', 'systemRole' => 20, 'isProtected' => 1],
            ['name' => 'Department Commentor', 'slug' => 'department-commentor', 'systemRole' => 10, 'isProtected' => 1],
            ['name' => 'Department ReadOnly', 'slug' => 'department-readonly', 'systemRole' => 5, 'isProtected' => 1],
        ];

        foreach ($seedRoles as $role) {
            $exists = $this->db->table('zp_org_roles')->where('slug', $role['slug'])->exists();
            if ($exists) {
                continue;
            }

            $this->db->table('zp_org_roles')->insert([
                'name' => $role['name'],
                'slug' => $role['slug'],
                'systemRole' => $role['systemRole'],
                'isProtected' => $role['isProtected'],
                'createdOn' => date('Y-m-d H:i:s'),
                'updatedOn' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function ensureRbacSchema(): void
    {
        if (! Schema::hasTable('zp_org_departments')) {
            Schema::create('zp_org_departments', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150);
                $table->string('slug', 180)->unique();
                $table->tinyInteger('isActive')->default(1);
                $table->dateTime('createdOn')->nullable();
                $table->dateTime('updatedOn')->nullable();
            });
        }

        if (! Schema::hasTable('zp_org_roles')) {
            Schema::create('zp_org_roles', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150);
                $table->string('slug', 180)->unique();
                $table->integer('systemRole')->default(20);
                $table->tinyInteger('isProtected')->default(0);
                $table->dateTime('createdOn')->nullable();
                $table->dateTime('updatedOn')->nullable();
            });
        }

        if (! Schema::hasTable('zp_org_user_roles')) {
            Schema::create('zp_org_user_roles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('userId');
                $table->unsignedBigInteger('roleId');
                $table->dateTime('updatedOn')->nullable();
                $table->unique(['userId'], 'ux_org_user_role');
            });
        }

        if (! Schema::hasTable('zp_org_user_clients')) {
            Schema::create('zp_org_user_clients', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('userId');
                $table->unsignedBigInteger('clientId');
                $table->dateTime('createdOn')->nullable();
                $table->unique(['userId', 'clientId'], 'ux_org_user_client');
            });
        }

        if (! Schema::hasTable('zp_org_user_departments')) {
            Schema::create('zp_org_user_departments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('userId');
                $table->unsignedBigInteger('departmentId');
                $table->dateTime('createdOn')->nullable();
                $table->unique(['userId', 'departmentId'], 'ux_org_user_department');
            });
        }

        if (! Schema::hasTable('zp_org_department_clients')) {
            Schema::create('zp_org_department_clients', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('departmentId');
                $table->unsignedBigInteger('clientId');
                $table->dateTime('createdOn')->nullable();
                $table->unique(['departmentId', 'clientId'], 'ux_org_department_client');
            });
        }

        if (! Schema::hasTable('zp_org_project_departments')) {
            Schema::create('zp_org_project_departments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('projectId');
                $table->unsignedBigInteger('departmentId');
                $table->dateTime('updatedOn')->nullable();
                $table->unique(['projectId'], 'ux_org_project_department_project');
            });
        }
    }

    public function getRoleUsageCounts(): array
    {
        if (! Schema::hasTable('zp_org_user_roles')) {
            return [];
        }

        $rows = $this->db->table('zp_org_user_roles')
            ->selectRaw('roleId, COUNT(*) as cnt')
            ->groupBy('roleId')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row->roleId] = (int) $row->cnt;
        }

        return $result;
    }

    public function getDepartmentUsageCounts(): array
    {
        if (! Schema::hasTable('zp_org_user_departments')) {
            return [];
        }

        $rows = $this->db->table('zp_org_user_departments')
            ->selectRaw('departmentId, COUNT(*) as cnt')
            ->groupBy('departmentId')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row->departmentId] = (int) $row->cnt;
        }

        return $result;
    }

    public function canDeleteRole(int $roleId): bool
    {
        if (! Schema::hasTable('zp_org_roles') || ! Schema::hasTable('zp_org_user_roles')) {
            return false;
        }

        $role = $this->db->table('zp_org_roles')->where('id', $roleId)->first();
        if (! $role) {
            return false;
        }

        if ((int) $role->isProtected === 1) {
            return false;
        }

        $mappedUsers = (int) $this->db->table('zp_org_user_roles')->where('roleId', $roleId)->count();

        return $mappedUsers === 0;
    }

    public function deleteRole(int $roleId): bool
    {
        if (! $this->canDeleteRole($roleId)) {
            return false;
        }

        return (bool) $this->db->table('zp_org_roles')
            ->where('id', $roleId)
            ->delete();
    }

    public function canDeleteDepartment(int $departmentId): bool
    {
        if (! Schema::hasTable('zp_org_departments')) {
            return false;
        }

        $mappedUsers = (int) $this->db->table('zp_org_user_departments')->where('departmentId', $departmentId)->count();
        $mappedClients = (int) $this->db->table('zp_org_department_clients')->where('departmentId', $departmentId)->count();
        $mappedProjects = (int) $this->db->table('zp_org_project_departments')->where('departmentId', $departmentId)->count();

        return $mappedUsers === 0 && $mappedClients === 0 && $mappedProjects === 0;
    }

    public function deleteDepartment(int $departmentId): bool
    {
        if (! $this->canDeleteDepartment($departmentId)) {
            return false;
        }

        return (bool) $this->db->table('zp_org_departments')
            ->where('id', $departmentId)
            ->delete();
    }

    public function getUserRoleMap(): array
    {
        if (! Schema::hasTable('zp_org_user_roles')) {
            return [];
        }

        $rows = $this->db->table('zp_org_user_roles')
            ->select(['userId', 'roleId'])
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row->userId] = (int) $row->roleId;
        }

        return $result;
    }

    public function getUserClientMap(): array
    {
        if (! Schema::hasTable('zp_org_user_clients')) {
            return [];
        }

        $rows = $this->db->table('zp_org_user_clients')
            ->select(['userId', 'clientId'])
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $uid = (int) $row->userId;
            $result[$uid] ??= [];
            $result[$uid][] = (int) $row->clientId;
        }

        return $result;
    }

    public function getUserDepartmentMap(): array
    {
        if (! Schema::hasTable('zp_org_user_departments')) {
            return [];
        }

        $rows = $this->db->table('zp_org_user_departments')
            ->select(['userId', 'departmentId'])
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $uid = (int) $row->userId;
            $result[$uid] ??= [];
            $result[$uid][] = (int) $row->departmentId;
        }

        return $result;
    }

    public function saveUserAccessMappings(array $roleByUser, array $clientsByUser, array $departmentsByUser, array $userIds = []): void
    {
        $this->ensureRbacSchema();

        if (! Schema::hasTable('zp_org_user_roles')
            || ! Schema::hasTable('zp_org_user_clients')
            || ! Schema::hasTable('zp_org_user_departments')) {
            return;
        }

        $this->db->beginTransaction();
        try {
            $targetUserIds = array_values(array_unique(array_merge(
                array_map('intval', array_keys($roleByUser)),
                array_map('intval', array_keys($clientsByUser)),
                array_map('intval', array_keys($departmentsByUser)),
                array_map('intval', $userIds)
            )));
            $targetUserIds = array_values(array_filter($targetUserIds, fn ($id) => $id > 0));

            foreach ($targetUserIds as $uid) {
                $rid = (int) ($roleByUser[$uid] ?? 0);
                if ($rid > 0) {
                    $this->db->table('zp_org_user_roles')->updateOrInsert(
                        ['userId' => $uid],
                        ['roleId' => $rid, 'updatedOn' => date('Y-m-d H:i:s')]
                    );
                } else {
                    $this->db->table('zp_org_user_roles')->where('userId', $uid)->delete();
                }

                $this->db->table('zp_org_user_clients')->where('userId', $uid)->delete();
                foreach ((array) ($clientsByUser[$uid] ?? []) as $clientId) {
                    $cid = (int) $clientId;
                    if ($cid <= 0) {
                        continue;
                    }
                    $this->db->table('zp_org_user_clients')->insert([
                        'userId' => $uid,
                        'clientId' => $cid,
                        'createdOn' => date('Y-m-d H:i:s'),
                    ]);
                }

                $this->db->table('zp_org_user_departments')->where('userId', $uid)->delete();
                foreach ((array) ($departmentsByUser[$uid] ?? []) as $departmentId) {
                    $did = (int) $departmentId;
                    if ($did <= 0) {
                        continue;
                    }
                    $this->db->table('zp_org_user_departments')->insert([
                        'userId' => $uid,
                        'departmentId' => $did,
                        'createdOn' => date('Y-m-d H:i:s'),
                    ]);
                }
            }

            // Fallback for direct calls that do not pass explicit target user IDs.
            if ($targetUserIds === []) {
            foreach ($roleByUser as $userId => $roleId) {
                $uid = (int) $userId;
                $rid = (int) $roleId;
                if ($uid <= 0 || $rid <= 0) {
                    continue;
                }

                $this->db->table('zp_org_user_roles')->updateOrInsert(
                    ['userId' => $uid],
                    ['roleId' => $rid, 'updatedOn' => date('Y-m-d H:i:s')]
                );
            }

            foreach ($clientsByUser as $userId => $clientIds) {
                $uid = (int) $userId;
                if ($uid <= 0) {
                    continue;
                }

                $this->db->table('zp_org_user_clients')->where('userId', $uid)->delete();
                foreach ((array) $clientIds as $clientId) {
                    $cid = (int) $clientId;
                    if ($cid <= 0) {
                        continue;
                    }
                    $this->db->table('zp_org_user_clients')->insert([
                        'userId' => $uid,
                        'clientId' => $cid,
                        'createdOn' => date('Y-m-d H:i:s'),
                    ]);
                }
            }

            foreach ($departmentsByUser as $userId => $departmentIds) {
                $uid = (int) $userId;
                if ($uid <= 0) {
                    continue;
                }

                $this->db->table('zp_org_user_departments')->where('userId', $uid)->delete();
                foreach ((array) $departmentIds as $departmentId) {
                    $did = (int) $departmentId;
                    if ($did <= 0) {
                        continue;
                    }
                    $this->db->table('zp_org_user_departments')->insert([
                        'userId' => $uid,
                        'departmentId' => $did,
                        'createdOn' => date('Y-m-d H:i:s'),
                    ]);
                }
            }
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getUserBusinessRole(int $userId): ?array
    {
        if (! Schema::hasTable('zp_org_user_roles') || ! Schema::hasTable('zp_org_roles')) {
            return null;
        }

        $row = $this->db->table('zp_org_user_roles as ur')
            ->join('zp_org_roles as r', 'r.id', '=', 'ur.roleId')
            ->where('ur.userId', $userId)
            ->select(['r.id', 'r.name', 'r.slug', 'r.systemRole'])
            ->first();

        return $row ? (array) $row : null;
    }

    public function getUserClientIds(int $userId): array
    {
        if (! Schema::hasTable('zp_org_user_clients')) {
            return [];
        }

        return $this->db->table('zp_org_user_clients')
            ->where('userId', $userId)
            ->pluck('clientId')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }

    public function getUserDepartmentIds(int $userId): array
    {
        if (! Schema::hasTable('zp_org_user_departments')) {
            return [];
        }

        return $this->db->table('zp_org_user_departments')
            ->where('userId', $userId)
            ->pluck('departmentId')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }

    public function linkProjectDepartment(int $projectId, int $departmentId): void
    {
        $this->ensureRbacSchema();

        if (! Schema::hasTable('zp_org_project_departments')) {
            return;
        }

        if ($projectId <= 0 || $departmentId <= 0) {
            return;
        }

        $this->db->table('zp_org_project_departments')->updateOrInsert(
            ['projectId' => $projectId],
            ['departmentId' => $departmentId, 'updatedOn' => date('Y-m-d H:i:s')]
        );
    }

    public function getProjectDepartmentId(int $projectId): int
    {
        if (! Schema::hasTable('zp_org_project_departments') || $projectId <= 0) {
            return 0;
        }

        $departmentId = $this->db->table('zp_org_project_departments')
            ->where('projectId', $projectId)
            ->value('departmentId');

        return (int) ($departmentId ?? 0);
    }

    public function getDepartmentsForUser(int $userId): array
    {
        if (! Schema::hasTable('zp_org_departments') || ! Schema::hasTable('zp_org_user_departments')) {
            return [];
        }

        return $this->db->table('zp_org_departments as d')
            ->join('zp_org_user_departments as ud', 'ud.departmentId', '=', 'd.id')
            ->where('ud.userId', $userId)
            ->orderBy('d.name')
            ->select(['d.id', 'd.name'])
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    private function slugify(string $input): string
    {
        $slug = strtolower(trim($input));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'item-'.time();
    }
}
