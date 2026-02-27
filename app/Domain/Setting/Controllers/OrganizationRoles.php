<?php

namespace Leantime\Domain\Setting\Controllers;

use Leantime\Core\Configuration\Environment;
use Leantime\Core\Controller\Controller;
use Leantime\Core\Controller\Frontcontroller;
use Leantime\Domain\Auth\Models\Roles;
use Leantime\Domain\Auth\Services\Auth;
use Leantime\Domain\Setting\Repositories\Setting as SettingRepository;
use Leantime\Domain\Users\Repositories\Users as UserRepository;

class OrganizationRoles extends Controller
{
    private SettingRepository $settingsRepo;

    private UserRepository $userRepo;

    private Environment $config;

    public function init(
        SettingRepository $settingsRepo,
        UserRepository $userRepo,
        Environment $config
    ) {
        $this->settingsRepo = $settingsRepo;
        $this->userRepo = $userRepo;
        $this->config = $config;

        Auth::authOrRedirect([Roles::$owner, Roles::$admin], true);
    }

    public function get($params)
    {
        $users = $this->userRepo->getAll(true);
        $departments = $this->getDepartmentsFromSettingsOrUsers($users);
        $departmentManagers = $this->decodeJsonSetting('companysettings.departmentManagers', []);
        $customRoles = $this->decodeJsonSetting('companysettings.customRoles', []);

        $smtp = [
            'enabled' => $this->toBool($this->settingsRepo->getSetting('companysettings.smtp.enabled', $this->config->useSMTP ? 'true' : 'false')),
            'host' => (string) ($this->settingsRepo->getDecryptedSetting('companysettings.smtp.host', $this->config->smtpHosts) ?? ''),
            'port' => (int) ($this->settingsRepo->getSetting('companysettings.smtp.port', (string) $this->config->smtpPort) ?? $this->config->smtpPort),
            'username' => (string) ($this->settingsRepo->getDecryptedSetting('companysettings.smtp.username', $this->config->smtpUsername) ?? ''),
            'passwordSet' => ((string) ($this->settingsRepo->getDecryptedSetting('companysettings.smtp.password', '') ?? '')) !== '',
            'fromEmail' => (string) ($this->settingsRepo->getDecryptedSetting('companysettings.smtp.fromEmail', $this->config->email) ?? ''),
            'secure' => (string) ($this->settingsRepo->getSetting('companysettings.smtp.secure', $this->config->smtpSecure) ?? ''),
            'auth' => $this->toBool($this->settingsRepo->getSetting('companysettings.smtp.auth', $this->config->smtpAuth ? 'true' : 'false')),
            'autoTls' => $this->toBool($this->settingsRepo->getSetting('companysettings.smtp.autoTls', $this->config->smtpAutoTLS ? 'true' : 'false')),
        ];

        $this->tpl->assign('users', $users);
        $this->tpl->assign('departments', $departments);
        $this->tpl->assign('departmentManagers', is_array($departmentManagers) ? $departmentManagers : []);
        $this->tpl->assign('customRoles', is_array($customRoles) ? $customRoles : []);
        $this->tpl->assign('smtp', $smtp);
        $this->tpl->assign('cronCommand', '* * * * * cd '.APP_ROOT.' && php bin/leantime cron:run >> /dev/null 2>&1');

        return $this->tpl->display('setting.organizationRoles');
    }

    public function post($params)
    {
        $users = $this->userRepo->getAll(true);
        $userIds = array_map(fn (array $user): int => (int) ($user['id'] ?? 0), $users);

        $departments = $this->parseDepartments((string) ($params['departments'] ?? ''));
        $managerMap = [];
        $postedManagers = $params['managerByDepartment'] ?? [];
        if (is_array($postedManagers)) {
            foreach ($departments as $department) {
                $managerId = (int) ($postedManagers[$department] ?? 0);
                if ($managerId > 0 && in_array($managerId, $userIds, true)) {
                    $managerMap[$department] = $managerId;
                }
            }
        }

        $customRoles = $this->parseCustomRoles((string) ($params['customRolesText'] ?? ''));

        $this->settingsRepo->saveSetting('companysettings.departments', json_encode($departments));
        $this->settingsRepo->saveSetting('companysettings.departmentManagers', json_encode($managerMap));
        $this->settingsRepo->saveSetting('companysettings.customRoles', json_encode($customRoles));
        $this->settingsRepo->saveSetting('companysettings.singleRolePerUser', 'true');

        if (isset($params['saveSmtpSettings'])) {
            $enabled = isset($params['smtpEnabled']) ? 'true' : 'false';
            $auth = isset($params['smtpAuth']) ? 'true' : 'false';
            $autoTls = isset($params['smtpAutoTls']) ? 'true' : 'false';
            $secure = trim((string) ($params['smtpSecure'] ?? ''));
            $port = max(1, (int) ($params['smtpPort'] ?? 587));
            $host = trim((string) ($params['smtpHost'] ?? ''));
            $username = trim((string) ($params['smtpUsername'] ?? ''));
            $password = trim((string) ($params['smtpPassword'] ?? ''));
            $fromEmail = trim((string) ($params['smtpFromEmail'] ?? ''));

            $this->settingsRepo->saveSetting('companysettings.smtp.enabled', $enabled);
            $this->settingsRepo->saveSetting('companysettings.smtp.auth', $auth);
            $this->settingsRepo->saveSetting('companysettings.smtp.autoTls', $autoTls);
            $this->settingsRepo->saveSetting('companysettings.smtp.secure', $secure);
            $this->settingsRepo->saveSetting('companysettings.smtp.port', (string) $port);
            $this->settingsRepo->saveEncryptedSetting('companysettings.smtp.host', $host);
            $this->settingsRepo->saveEncryptedSetting('companysettings.smtp.username', $username);
            $this->settingsRepo->saveEncryptedSetting('companysettings.smtp.fromEmail', $fromEmail);
            if ($password !== '') {
                $this->settingsRepo->saveEncryptedSetting('companysettings.smtp.password', $password);
            }
        }

        $this->tpl->setNotification('Organization role, department, and SMTP settings saved successfully.', 'success');

        return Frontcontroller::redirect(BASE_URL.'/setting/organizationRoles');
    }

    private function parseDepartments(string $rawDepartments): array
    {
        $parts = preg_split('/\r\n|\r|\n/', $rawDepartments) ?: [];
        $departments = [];
        foreach ($parts as $part) {
            $department = trim($part);
            if ($department !== '') {
                $departments[] = $department;
            }
        }

        return array_values(array_unique($departments));
    }

    private function parseCustomRoles(string $rawCustomRoles): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $rawCustomRoles) ?: [];
        $roles = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $parts = array_map('trim', explode('|', $line));
            $code = $parts[0] ?? '';
            $name = $parts[1] ?? '';
            $level = isset($parts[2]) ? (int) $parts[2] : 0;

            if ($code === '' || $name === '' || $level <= 0) {
                continue;
            }

            $roles[] = [
                'code' => $code,
                'name' => $name,
                'level' => $level,
            ];
        }

        return $roles;
    }

    private function decodeJsonSetting(string $key, mixed $default): mixed
    {
        $raw = $this->settingsRepo->getSetting($key);
        if (! is_string($raw) || $raw === '') {
            return $default;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : $default;
    }

    private function getDepartmentsFromSettingsOrUsers(array $users): array
    {
        $stored = $this->decodeJsonSetting('companysettings.departments', []);
        if (is_array($stored) && count($stored) > 0) {
            return array_values(array_unique(array_map('strval', $stored)));
        }

        $departments = [];
        foreach ($users as $user) {
            $department = trim((string) ($user['department'] ?? ''));
            if ($department !== '') {
                $departments[] = $department;
            }
        }

        return array_values(array_unique($departments));
    }

    private function toBool(mixed $value): bool
    {
        return in_array(strtolower((string) $value), ['1', 'true', 'on', 'yes'], true);
    }
}
