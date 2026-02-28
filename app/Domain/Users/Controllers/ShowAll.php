<?php

namespace Leantime\Domain\Users\Controllers;

use Leantime\Core\Controller\Controller;
use Leantime\Core\Controller\Frontcontroller;
use Leantime\Domain\Auth\Models\Roles;
use Leantime\Domain\Auth\Services\Auth;
use Leantime\Domain\Clients\Repositories\Clients as ClientRepository;
use Leantime\Domain\Ldap\Services\Ldap as LdapService;
use Leantime\Domain\Setting\Repositories\Organization as OrganizationRepository;
use Leantime\Domain\Users\Repositories\Users as UserRepository;
use Symfony\Component\HttpFoundation\Response;

class ShowAll extends Controller
{
    private UserRepository $userRepo;

    private LdapService $ldapService;

    private OrganizationRepository $organizationRepo;

    private ClientRepository $clientRepo;

    public function init(
        UserRepository $userRepo,
        LdapService $ldapService,
        OrganizationRepository $organizationRepo,
        ClientRepository $clientRepo
    ): void
    {
        $this->userRepo = $userRepo;
        $this->ldapService = $ldapService;
        $this->organizationRepo = $organizationRepo;
        $this->clientRepo = $clientRepo;
    }

    /**
     * @throws \Exception
     */
    public function get(): Response
    {
        Auth::authOrRedirect([Roles::$owner, Roles::$admin], true);

        // Only Admins
        if (Auth::userIsAtLeast(Roles::$admin)) {
            if (Auth::userIsAtLeast(Roles::$admin)) {
                $this->tpl->assign('allUsers', $this->userRepo->getAll());
            } else {
                $this->tpl->assign('allUsers', $this->userRepo->getAllClientUsers(Auth::getUserClientId()));
            }

            $roles = $this->organizationRepo->getRoles();
            $mappingRoles = $this->organizationRepo->getDepartmentRoles();
            $units = $this->organizationRepo->getDepartments();
            $userRoleMap = $this->organizationRepo->getUserRoleMap();
            $userClientMap = $this->organizationRepo->getUserClientMap();
            $userUnitMap = $this->organizationRepo->getUserDepartmentMap();
            $roleUsageCounts = $this->organizationRepo->getRoleUsageCounts();
            $unitUsageCounts = $this->organizationRepo->getDepartmentUsageCounts();
            $clients = $this->clientRepo->getAll();

            $roleNamesByUser = [];
            $rolesById = [];
            foreach ($roles as $role) {
                $rolesById[(int) $role['id']] = (string) ($role['name'] ?? '');
            }
            foreach ($userRoleMap as $uid => $rid) {
                $roleNamesByUser[(int) $uid] = $rolesById[(int) $rid] ?? '';
            }

            $this->tpl->assign('admin', true);
            $this->tpl->assign('roles', Roles::getRoles());
            $this->tpl->assign('orgRoles', $roles);
            $this->tpl->assign('orgMappingRoles', $mappingRoles);
            $this->tpl->assign('orgUnits', $units);
            $this->tpl->assign('orgUserRoleMap', $userRoleMap);
            $this->tpl->assign('orgUserClientMap', $userClientMap);
            $this->tpl->assign('orgUserUnitMap', $userUnitMap);
            $this->tpl->assign('orgRoleUsageCounts', $roleUsageCounts);
            $this->tpl->assign('orgUnitUsageCounts', $unitUsageCounts);
            $this->tpl->assign('orgClients', $clients);
            $this->tpl->assign('orgRoleNamesByUser', $roleNamesByUser);

            return $this->tpl->display('users.showAll');
        } else {
            return $this->tpl->display('errors.error403');
        }
    }

    public function post($params): Response
    {
        Auth::authOrRedirect([Roles::$owner, Roles::$admin], true);

        if (! Auth::userIsAtLeast(Roles::$admin)) {
            return $this->tpl->display('errors.error403', responseCode: 403);
        }

        $redirectTo = BASE_URL.'/users/showAll#rbacUnitManagement';

        if (isset($params['addUnit'])) {
            $name = trim((string) ($params['unitName'] ?? ''));
            if ($name === '') {
                $this->tpl->setNotification('Unit name is required.', 'error');
            } else {
                $this->organizationRepo->addDepartment($name);
                $this->tpl->setNotification('Unit added successfully.', 'success');
            }

            return Frontcontroller::redirect($redirectTo);
        }

        if (isset($params['addRole'])) {
            $name = trim((string) ($params['roleName'] ?? ''));
            $systemRole = (int) ($params['roleSystemRole'] ?? 20);
            if ($name === '') {
                $this->tpl->setNotification('Role name is required.', 'error');
            } else {
                $this->organizationRepo->addRole($name, $systemRole);
                $this->tpl->setNotification('Role added successfully.', 'success');
            }

            return Frontcontroller::redirect($redirectTo);
        }

        if (isset($params['deleteRole'])) {
            $roleId = (int) ($params['roleId'] ?? 0);
            if ($roleId <= 0 || ! $this->organizationRepo->deleteRole($roleId)) {
                $this->tpl->setNotification('Role cannot be deleted while mapped to users or protected.', 'error');
            } else {
                $this->tpl->setNotification('Role deleted successfully.', 'success');
            }

            return Frontcontroller::redirect($redirectTo);
        }

        if (isset($params['deleteUnit'])) {
            $unitId = (int) ($params['unitId'] ?? 0);
            if ($unitId <= 0 || ! $this->organizationRepo->deleteDepartment($unitId)) {
                $this->tpl->setNotification('Unit cannot be deleted while mapped to users/clients/projects.', 'error');
            } else {
                $this->tpl->setNotification('Unit deleted successfully.', 'success');
            }

            return Frontcontroller::redirect($redirectTo);
        }

        if (isset($params['saveUserMappings'])) {
            try {
                $roleByUser = (array) ($params['userBusinessRole'] ?? []);
                $clientsByUser = (array) ($params['userClients'] ?? []);
                $unitsByUser = (array) ($params['userUnits'] ?? []);
                $this->organizationRepo->saveUserAccessMappings($roleByUser, $clientsByUser, $unitsByUser);
                $this->tpl->setNotification('User role/client/unit mappings updated.', 'success');
            } catch (\Throwable $e) {
                report($e);
                $this->tpl->setNotification('Could not save user mappings. Please check logs.', 'error');
            }

            return Frontcontroller::redirect($redirectTo);
        }

        return Frontcontroller::redirect(BASE_URL.'/users/showAll');
    }
}
