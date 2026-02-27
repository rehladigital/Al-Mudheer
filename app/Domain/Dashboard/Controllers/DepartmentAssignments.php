<?php

namespace Leantime\Domain\Dashboard\Controllers;

use Leantime\Core\Controller\Controller;
use Leantime\Domain\Auth\Models\Roles;
use Leantime\Domain\Auth\Services\Auth;
use Leantime\Domain\Setting\Repositories\Setting as SettingRepository;
use Leantime\Domain\Tickets\Services\Tickets as TicketService;
use Leantime\Domain\Users\Repositories\Users as UserRepository;

class DepartmentAssignments extends Controller
{
    private SettingRepository $settingsRepo;

    private UserRepository $userRepo;

    private TicketService $ticketService;

    public function init(
        SettingRepository $settingsRepo,
        UserRepository $userRepo,
        TicketService $ticketService
    ) {
        $this->settingsRepo = $settingsRepo;
        $this->userRepo = $userRepo;
        $this->ticketService = $ticketService;

        Auth::authOrRedirect([Roles::$owner, Roles::$admin, Roles::$manager], true);
    }

    public function get($params)
    {
        $managerMap = $this->decodeJsonSetting('companysettings.departmentManagers', []);
        $departmentNames = $this->decodeJsonSetting('companysettings.departments', []);
        $currentUserId = (int) (session('userdata.id') ?? 0);
        $isAdmin = Auth::userIsAtLeast(Roles::$admin, true);

        $managedDepartments = [];
        foreach ($departmentNames as $department) {
            $managerId = (int) ($managerMap[$department] ?? 0);
            if ($isAdmin || $managerId === $currentUserId) {
                $managedDepartments[] = $department;
            }
        }

        if (! $isAdmin && count($managedDepartments) === 0) {
            return $this->tpl->display('errors.error403', responseCode: 403);
        }

        $departmentAssignments = [];
        foreach ($managedDepartments as $department) {
            $users = $this->userRepo->getAllByDepartment($department, true);
            $rows = [];

            foreach ($users as $user) {
                $tickets = $this->ticketService->getAllOpenUserTickets((int) $user['id']);
                $rows[] = [
                    'user' => $user,
                    'openCount' => count($tickets),
                    'tickets' => array_slice($tickets, 0, 8),
                ];
            }

            $departmentAssignments[] = [
                'department' => $department,
                'rows' => $rows,
            ];
        }

        $this->tpl->assign('departmentAssignments', $departmentAssignments);

        return $this->tpl->display('dashboard.departmentAssignments');
    }

    private function decodeJsonSetting(string $key, array $default): array
    {
        $raw = $this->settingsRepo->getSetting($key);
        if (! is_string($raw) || $raw === '') {
            return $default;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : $default;
    }
}
