<?php

namespace Leantime\Domain\Users\Repositories;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Schema;
use Leantime\Core\Configuration\Environment;
use Leantime\Core\Db\DatabaseHelper;
use Leantime\Core\Db\Db as DbCore;
use Leantime\Domain\Files\Repositories\Files;
use SVG\SVG;

class Users
{
    private ConnectionInterface $connection;

    public string $user;

    public string $lastname;

    public string $firstname;

    public int $role;

    public string $jobTitle;

    public string $jobLevel;

    public string $department;

    public int $id;

    public array $adminRoles = [40, 50];

    public array $status = ['active' => 'label.active', 'inactive' => 'label.inactive', 'invited' => 'label.invited'];

    private ?bool $userClientRelationTableExists = null;

    /**
     * __construct - neu db connection
     */
    public function __construct(
        protected Environment $config,
        protected DbCore $db,
        protected DatabaseHelper $dbHelper,
        protected Files $files
    ) {
        $this->connection = $db->getConnection();
    }

    /**
     * getUser - get on user from db
     */
    public function getUser($id): array|bool
    {
        $result = $this->connection->table('zp_user')
            ->where('id', $id)
            ->limit(1)
            ->first();

        return $result ? (array) $result : false;
    }

    public function getUserClientIds(int $userId): array
    {
        $legacyUser = $this->getUser($userId);
        $legacyClientId = (int) ($legacyUser['clientId'] ?? 0);

        if (! $this->hasUserClientRelationTable()) {
            return $legacyClientId > 0 ? [$legacyClientId] : [];
        }

        $rows = $this->connection->table('zp_relationuserclient')
            ->select('clientId')
            ->where('userId', $userId)
            ->orderBy('clientId')
            ->get();

        $clientIds = [];
        foreach ($rows as $row) {
            $id = (int) ($row->clientId ?? 0);
            if ($id > 0) {
                $clientIds[] = $id;
            }
        }

        if (count($clientIds) === 0 && $legacyClientId > 0) {
            return [$legacyClientId];
        }

        return array_values(array_unique($clientIds));
    }

    /**
     * getUser - get on user from db
     *
     * @return mixed
     */
    public function getUserBySha($hash): array|false
    {
        $result = $this->connection->table('zp_user')
            ->whereRaw('SHA1(CONCAT(id, ?)) = ?', [$this->config->sessionPassword, $hash])
            ->first();

        return $result ? (array) $result : false;
    }

    /**
     * getLastLogin - get the date of the last login of any user
     *
     * @return string|null returns datetime string with last login or null if nothing could be found
     */
    public function getLastLogin(): ?string
    {
        $result = $this->connection->table('zp_user')
            ->select('lastlogin')
            ->orderByDesc('lastlogin')
            ->limit(1)
            ->first();

        return $result->lastlogin ?? null;
    }

    /**
     * getUserByEmail - get on user from db
     */
    public function getUserByEmail(string $email, string $status = 'a'): array|false
    {
        $query = $this->connection->table('zp_user')
            ->where('username', $email);

        if ($status === 'a') {
            $query->whereRaw('LOWER(status) = ?', ['a']);
        }

        if ($status === 'i') {
            $query->whereRaw('LOWER(status) = ?', ['i']);
        }

        $result = $query->limit(1)->first();

        return $result ? (array) $result : false;
    }

    public function getNumberOfUsers($activeOnly = false, $includeApi = true): int
    {
        $query = $this->connection->table('zp_user')
            ->selectRaw('COUNT(id) AS '.$this->dbHelper->wrapColumn('userCount'));

        if ($activeOnly) {
            $query->where('status', 'a');
        }

        if ($includeApi) {
            $query->where(function ($q) {
                $q->where('source', '!=', 'api')
                    ->orWhereNull('source');
            });
        }

        $result = $query->first();

        return $result->userCount ?? 0;
    }

    /**
     * getEmployees - get all employees
     */
    public function getEmployees(): array
    {
        $results = $this->connection->table('zp_user')
            ->select([
                'zp_user.id',
                'zp_user.lastname',
                'zp_user.jobTitle',
                'zp_user.jobLevel',
                'zp_user.department',
                'zp_user.modified',
            ])
            ->selectRaw('COALESCE(zp_user.firstname, zp_user.username) AS firstname')
            ->orderBy('lastname')
            ->get();

        return array_map(fn ($item) => (array) $item, $results->toArray());
    }

    /**
     * getAll - get all user
     *
     * @param  bool  $activeOnly
     */
    public function getAll($activeOnly = false): array
    {
        $query = $this->connection->table('zp_user')
            ->select([
                'zp_user.id',
                'lastname',
                'role',
                'profileId',
                'status',
                'username',
                'twoFAEnabled',
                'clientId',
                'zp_clients.name as clientName',
                'jobTitle',
                'jobLevel',
                'department',
                'zp_user.modified',
            ])
            ->selectRaw("CASE WHEN firstname <> '' THEN firstname ELSE username END AS firstname")
            ->leftJoin('zp_clients', 'zp_clients.id', '=', 'zp_user.clientId')
            ->where(function ($q) {
                $q->whereNull('source')
                    ->orWhere('source', '!=', 'api');
            });

        if ($activeOnly) {
            $query->where('status', 'like', 'a');
        }

        $results = $query->orderBy('lastname')->get();

        return array_map(fn ($item) => (array) $item, $results->toArray());
    }

    public function getAllBySource($source): false|array
    {
        $query = $this->connection->table('zp_user')
            ->select([
                'zp_user.id',
                'lastname',
                'firstname',
                'role',
                'profileId',
                'status',
                'username',
                'lastlogin',
                'createdOn',
                'jobTitle',
                'jobLevel',
                'department',
                'modified',
            ]);

        if ($source === null) {
            $query->whereNull('source');
        } else {
            $query->where('source', $source);
        }

        $results = $query->orderBy('lastname')->get();

        return array_map(fn ($item) => (array) $item, $results->toArray());
    }

    public function getAllByDepartment(string $department, bool $activeOnly = true): array
    {
        $query = $this->connection->table('zp_user')
            ->select([
                'id',
                'lastname',
                'firstname',
                'role',
                'status',
                'username',
                'department',
                'jobTitle',
                'modified',
            ])
            ->whereRaw('LOWER(COALESCE(department, "")) = ?', [mb_strtolower(trim($department))])
            ->where(function ($q) {
                $q->whereNull('source')
                    ->orWhere('source', '!=', 'api');
            })
            ->orderBy('lastname');

        if ($activeOnly) {
            $query->where('status', 'a');
        }

        $results = $query->get();

        return array_map(fn ($item) => (array) $item, $results->toArray());
    }

    /**
     * getAll - get all user
     */
    public function getAllClientUsers($clientId): array
    {
        $query = $this->connection->table('zp_user')
            ->select([
                'zp_user.id',
                'lastname',
                'firstname',
                'role',
                'profileId',
                'status',
                'username',
                'twoFAEnabled',
                'zp_clients.name as clientName',
                'jobTitle',
                'jobLevel',
                'department',
                'modified',
            ])
            ->leftJoin('zp_clients', 'zp_clients.id', '=', 'zp_user.clientId')
            ->where(function ($query) use ($clientId) {
                $query->where('zp_user.clientId', $clientId);
                if ($this->hasUserClientRelationTable()) {
                    $query->orWhereExists(function ($subQuery) use ($clientId) {
                        $subQuery->selectRaw('1')
                            ->from('zp_relationuserclient')
                            ->whereColumn('zp_relationuserclient.userId', 'zp_user.id')
                            ->where('zp_relationuserclient.clientId', $clientId);
                    });
                }
            })
            ->orderBy('lastname');

        $results = $query->get();

        return array_map(fn ($item) => (array) $item, $results->toArray());
    }

    public function isAdmin($userId): bool
    {
        $result = $this->connection->table('zp_user')
            ->select('role')
            ->where('id', $userId)
            ->limit(1)
            ->first();

        if ($result && in_array($result->role, $this->adminRoles)) {
            return true;
        }

        return false;
    }

    /**
     * editUSer - edit user
     */
    public function editUser(array $values, $id): bool
    {
        $updateData = [
            'firstname' => $values['firstname'],
            'lastname' => $values['lastname'],
            'username' => $values['user'],
            'phone' => $values['phone'] ?? '',
            'status' => $values['status'],
            'role' => $values['role'],
            'hours' => $values['hours'] ?? 0,
            'wage' => $values['wage'] ?? 0,
            'clientId' => $values['clientId'],
            'jobTitle' => $values['jobTitle'] ?? '',
            'jobLevel' => $values['jobLevel'] ?? '',
            'department' => $values['department'] ?? '',
            'modified' => now(),
        ];

        if (isset($values['password']) && $values['password'] != '') {
            $updateData['password'] = password_hash($values['password'], PASSWORD_DEFAULT);
        }

        $updated = $this->connection->table('zp_user')
            ->where('id', $id)
            ->limit(1)
            ->update($updateData);

        if (isset($values['clientIds']) && is_array($values['clientIds'])) {
            $this->setUserClientRelations((int) $id, $values['clientIds']);
        }

        return $updated;
    }

    /**
     * usernameExist - Check if a username is already in db
     */
    public function usernameExist($username, string $userId = ''): bool
    {
        $query = $this->connection->table('zp_user')
            ->selectRaw('COUNT(username) AS '.$this->dbHelper->wrapColumn('numUser'))
            ->where('username', $username);

        if ($userId != '') {
            $query->where('id', '!=', $userId);
        }

        $result = $query->limit(1)->first();

        return (int) $result->numUser === 1;
    }

    /**
     * removeFromClient - Remove user from client by setting clientId to null
     *
     * @param  int  $userId  User ID to remove from client
     * @return bool Success status
     */
    public function removeFromClient(int $userId): bool
    {
        return $this->connection->table('zp_user')
            ->where('id', $userId)
            ->limit(1)
            ->update([
                'clientId' => null,
                'modified' => now(),
            ]);
    }

    /**
     * editOwn - Edit own Userdates
     */
    public function editOwn($values, $id): void
    {
        $updateData = [
            'lastname' => $values['lastname'],
            'firstname' => $values['firstname'],
            'username' => $values['user'],
            'phone' => $values['phone'],
            'notifications' => $values['notifications'],
            'modified' => now(),
        ];

        if (isset($values['password']) && $values['password'] != '') {
            $updateData['password'] = password_hash($values['password'], PASSWORD_DEFAULT);
        }

        $this->connection->table('zp_user')
            ->where('id', $id)
            ->limit(1)
            ->update($updateData);
    }

    /**
     * addUser - add User to db
     */
    public function addUser(array $values): false|string
    {
        $userId = $this->connection->table('zp_user')->insertGetId([
            'firstname' => $values['firstname'] ?? '',
            'lastname' => $values['lastname'] ?? '',
            'phone' => $values['phone'] ?? '',
            'username' => $values['user'],
            'role' => $values['role'],
            'notifications' => 1,
            'clientId' => $values['clientId'] ?? '',
            'password' => password_hash($values['password'], PASSWORD_DEFAULT),
            'source' => $values['source'] ?? '',
            'pwReset' => $values['pwReset'] ?? '',
            'status' => $values['status'] ?? '',
            'createdOn' => now(),
            'jobTitle' => $values['jobTitle'] ?? '',
            'jobLevel' => $values['jobLevel'] ?? '',
            'department' => $values['department'] ?? '',
            'modified' => now(),
        ]);

        if ($userId !== false && isset($values['clientIds']) && is_array($values['clientIds'])) {
            $this->setUserClientRelations((int) $userId, $values['clientIds']);
        }

        return $userId !== false ? (string) $userId : false;
    }

    public function setUserClientRelations(int $userId, array $clientIds): void
    {
        $normalizedClientIds = $this->normalizeClientIds($clientIds);
        $primaryClientId = count($normalizedClientIds) > 0 ? $normalizedClientIds[0] : 0;

        $this->connection->table('zp_user')
            ->where('id', $userId)
            ->limit(1)
            ->update([
                'clientId' => $primaryClientId,
                'modified' => now(),
            ]);

        if (! $this->hasUserClientRelationTable()) {
            return;
        }

        $this->connection->table('zp_relationuserclient')
            ->where('userId', $userId)
            ->delete();

        foreach ($normalizedClientIds as $clientId) {
            $this->connection->table('zp_relationuserclient')->insert([
                'userId' => $userId,
                'clientId' => $clientId,
            ]);
        }
    }

    private function hasUserClientRelationTable(): bool
    {
        if ($this->userClientRelationTableExists !== null) {
            return $this->userClientRelationTableExists;
        }

        try {
            $this->userClientRelationTableExists = Schema::hasTable('zp_relationuserclient');
        } catch (\Throwable $e) {
            $this->userClientRelationTableExists = false;
        }

        return $this->userClientRelationTableExists;
    }

    private function normalizeClientIds(array $clientIds): array
    {
        $normalized = [];
        foreach ($clientIds as $clientId) {
            $id = (int) $clientId;
            if ($id > 0) {
                $normalized[] = $id;
            }
        }

        $normalized = array_values(array_unique($normalized));
        sort($normalized);

        return $normalized;
    }

    /**
     * deleteUser - delete user from db
     */
    public function deleteUser($id): void
    {
        $this->connection->table('zp_user')
            ->where('zp_user.id', $id)
            ->delete();
    }

    /**
     * setPicture - set the profile picture for an individual
     *
     * @throws BindingResolutionException
     */
    public function setPicture($fileId, $id): void
    {
        $this->connection->table('zp_user')
            ->where('id', $id)
            ->update([
                'profileId' => $fileId,
                'modified' => dtHelper()->dbNow()->formatDateTimeForDb(),
            ]);
    }

    /**
     * @return string[]|SVG
     *
     * @throws BindingResolutionException
     */
    public function getProfilePicture($id): array|false
    {
        if ($id === false) {
            return false;
        }

        $result = $this->connection->table('zp_user')
            ->select(['profileId', 'firstname', 'lastname'])
            ->where('id', $id)
            ->limit(1)
            ->first();

        return $result ? (array) $result : false;
    }

    public function patchUser($id, $params): bool
    {
        $updates = [];
        foreach ($params as $key => $value) {
            $cleanKey = DbCore::sanitizeToColumnString($key);
            if ($cleanKey === 'password') {
                $updates[$cleanKey] = password_hash($value, PASSWORD_DEFAULT);
            } else {
                $updates[$cleanKey] = $value;
            }
        }

        $updates['modified'] = now();

        return $this->connection->table('zp_user')
            ->where('id', $id)
            ->limit(1)
            ->update($updates);
    }

    /**
     * getUserIdByName - Get Author/User Id by first- and lastname
     *
     * @param  string  $lastname  Lastname
     * @return int|bool Identifier of user or false, if not found
     */
    public function getUserIdByName(string $firstname, string $lastname): int|bool
    {
        $result = $this->connection->table('zp_user')
            ->select('profileId')
            ->where('firstname', $firstname)
            ->where('lastname', $lastname)
            ->first();

        return $result->profileId ?? false;
    }

    /**
     * Get user settings - retrieves and deserializes user settings
     *
     * @param  int  $userId  The user ID to get settings for
     * @param  string|null  $settingPath  Optional dot notation path to retrieve specific setting (e.g. 'onboarding.firstLoginCompleted')
     * @return mixed The requested settings or specific setting value, empty array if no settings exist
     */
    public function getUserSettings(int $userId, ?string $settingPath = null): mixed
    {
        $result = $this->connection->table('zp_user')
            ->select('settings')
            ->where('id', $userId)
            ->limit(1)
            ->first();

        // If no settings exist yet, return empty array
        if (! $result || empty($result['settings'])) {
            return [];
        }

        // Try to unserialize the settings
        try {
            $settings = unserialize($result['settings']);

            // If we have a specific path to retrieve
            if ($settingPath !== null) {
                return $this->getNestedSetting($settings, $settingPath);
            }

            return $settings;
        } catch (\Exception $e) {
            // If there's an error unserializing, return empty array
            return [];
        }
    }

    /**
     * Helper method to get a nested setting using dot notation
     *
     * @param  array  $settings  The settings array
     * @param  string  $path  Dot notation path (e.g. 'onboarding.firstLoginCompleted')
     * @return mixed The setting value or null if not found
     */
    private function getNestedSetting(array $settings, string $path)
    {
        $keys = explode('.', $path);
        $current = $settings;

        foreach ($keys as $key) {
            if (! is_array($current) || ! isset($current[$key])) {
                return null;
            }
            $current = $current[$key];
        }

        return $current;
    }
}
