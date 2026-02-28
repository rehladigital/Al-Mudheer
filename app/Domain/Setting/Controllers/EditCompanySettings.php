<?php

namespace Leantime\Domain\Setting\Controllers;

use Leantime\Core\Configuration\Environment;
use Leantime\Core\Controller\Controller;
use Leantime\Core\Controller\Frontcontroller;
use Leantime\Core\Mailer;
use Leantime\Core\UI\Theme;
use Leantime\Domain\Api\Services\Api as ApiService;
use Leantime\Domain\Auth\Models\Roles;
use Leantime\Domain\Auth\Services\Auth;
use Leantime\Domain\Notifications\Models\Notification;
use Leantime\Domain\Reports\Services\Reports as ReportService;
use Leantime\Domain\Setting\Repositories\Setting as SettingRepository;
use Leantime\Domain\Setting\Services\Setting as SettingService;

class EditCompanySettings extends Controller
{
    private SettingRepository $settingsRepo;

    private ApiService $APIService;

    private SettingService $settingsSvc;

    private Theme $theme;

    private Environment $config;

    /**
     * constructor - initialize private variables
     */
    public function init(
        SettingRepository $settingsRepo,
        ApiService $APIService,
        SettingService $settingsSvc,
        Theme $theme,
        Environment $config,

    ) {
        Auth::authOrRedirect([Roles::$owner, Roles::$admin], true);

        $this->settingsRepo = $settingsRepo;
        $this->APIService = $APIService;
        $this->settingsSvc = $settingsSvc;
        $this->theme = $theme;
        $this->config = $config;
    }

    /**
     * get - handle get requests
     */
    public function get($params)
    {
        if (! Auth::userIsAtLeast(Roles::$owner)) {
            return $this->tpl->display('errors.error403', responseCode: 403);
        }

        if (isset($_GET['resetLogo'])) {
            $this->settingsSvc->resetLogo();

            return Frontcontroller::redirect(BASE_URL.'/setting/editCompanySettings#look');
        }

        $companySettings = [
            'logo' => $this->theme->getLogoUrl(),
            'primarycolor' => session('companysettings.primarycolor') ?? '',
            'secondarycolor' => session('companysettings.secondarycolor') ?? '',
            'name' => session('companysettings.sitename'),
            'language' => session('companysettings.language'),
            'telemetryActive' => true,
            'messageFrequency' => '',
            'microsoftAuth' => [
                'enabled' => $this->config->oidcEnable,
                'issuer' => $this->config->oidcProviderUrl,
                'clientId' => $this->config->oidcClientId,
                'allowPublicRegistration' => $this->config->oidcCreateUser,
                'defaultRole' => $this->config->oidcDefaultRole,
                'hasClientSecret' => ! empty($this->config->oidcClientSecret),
            ],
        ];

        $msEnabled = $this->settingsRepo->getSetting('companysettings.microsoftAuth.enabled');
        if ($msEnabled !== false) {
            $companySettings['microsoftAuth']['enabled'] = $this->toBool($msEnabled);
        }

        $msIssuer = $this->settingsRepo->getDecryptedSetting('companysettings.microsoftAuth.issuer');
        if ($msIssuer !== false) {
            $companySettings['microsoftAuth']['issuer'] = $msIssuer;
        }

        $msClientId = $this->settingsRepo->getDecryptedSetting('companysettings.microsoftAuth.clientId');
        if ($msClientId !== false) {
            $companySettings['microsoftAuth']['clientId'] = $msClientId;
        }

        $msAllowRegistration = $this->settingsRepo->getSetting('companysettings.microsoftAuth.allowPublicRegistration');
        if ($msAllowRegistration !== false) {
            $companySettings['microsoftAuth']['allowPublicRegistration'] = $this->toBool($msAllowRegistration);
        }

        $msDefaultRole = $this->settingsRepo->getSetting('companysettings.microsoftAuth.defaultRole');
        if ($msDefaultRole !== false) {
            $companySettings['microsoftAuth']['defaultRole'] = (int) $msDefaultRole;
        }

        $msClientSecret = $this->settingsRepo->getDecryptedSetting('companysettings.microsoftAuth.clientSecret');
        if ($msClientSecret !== false) {
            $companySettings['microsoftAuth']['hasClientSecret'] = ! empty($msClientSecret);
        }

        $mainColor = $this->settingsRepo->getSetting('companysettings.mainColor');
        if ($mainColor !== false) {
            $companySettings['primarycolor'] = '#'.$mainColor;
            $companySettings['secondarycolor'] = '#'.$mainColor;
        }

        $primaryColor = $this->settingsRepo->getSetting('companysettings.primarycolor');
        if ($primaryColor !== false) {
            $companySettings['primarycolor'] = $primaryColor;
        }

        $secondaryColor = $this->settingsRepo->getSetting('companysettings.secondarycolor');
        if ($secondaryColor !== false) {
            $companySettings['secondarycolor'] = $secondaryColor;
        }

        $sitename = $this->settingsRepo->getSetting('companysettings.sitename');
        if ($sitename !== false) {
            $companySettings['name'] = $sitename;
        }

        $language = $this->settingsRepo->getSetting('companysettings.language');
        if ($language !== false) {
            $companySettings['language'] = $language;
        }

        $messageFrequency = $this->settingsRepo->getSetting('companysettings.messageFrequency');
        if ($messageFrequency !== false) {
            $companySettings['messageFrequency'] = $messageFrequency;
        }

        // Load default notification event types
        $defaultNotificationTypes = $this->settingsRepo->getSetting('companysettings.defaultNotificationEventTypes');
        $allCategories = array_keys(Notification::NOTIFICATION_CATEGORIES);
        if ($defaultNotificationTypes) {
            $defaultNotificationTypes = json_decode($defaultNotificationTypes, true);
        }
        if (! is_array($defaultNotificationTypes)) {
            $defaultNotificationTypes = $allCategories;
        }

        // Load default notification relevance level
        $defaultRelevance = $this->settingsRepo->getSetting('companysettings.defaultNotificationRelevance');
        if (! $defaultRelevance || ! Notification::isValidRelevanceLevel($defaultRelevance)) {
            $defaultRelevance = Notification::RELEVANCE_ALL;
        }

        $apiKeys = $this->APIService->getAPIKeys();

        $smtp = [
            'fromEmail' => (string) ($this->settingsRepo->getSetting('companysettings.smtp.fromEmail') ?: $this->config->email),
            'useSMTP' => $this->toBool($this->settingsRepo->getSetting('companysettings.smtp.useSMTP') ?: ($this->config->useSMTP ? 'true' : 'false')),
            'hosts' => (string) ($this->settingsRepo->getSetting('companysettings.smtp.hosts') ?: $this->config->smtpHosts),
            'auth' => $this->toBool($this->settingsRepo->getSetting('companysettings.smtp.auth') ?: ($this->config->smtpAuth ? 'true' : 'false')),
            'username' => (string) ($this->settingsRepo->getDecryptedSetting('companysettings.smtp.username') ?: $this->config->smtpUsername),
            'password' => (string) ($this->settingsRepo->getDecryptedSetting('companysettings.smtp.password') ?: ''),
            'secure' => (string) ($this->settingsRepo->getSetting('companysettings.smtp.secure') ?: $this->config->smtpSecure),
            'port' => (string) ($this->settingsRepo->getSetting('companysettings.smtp.port') ?: $this->config->smtpPort),
            'autoTLS' => $this->toBool($this->settingsRepo->getSetting('companysettings.smtp.autoTLS') ?: (($this->config->smtpAutoTLS ?? true) ? 'true' : 'false')),
            'sslNoVerify' => $this->toBool($this->settingsRepo->getSetting('companysettings.smtp.sslNoVerify') ?: (($this->config->smtpSSLNoverify ?? false) ? 'true' : 'false')),
        ];

        $cronCommand = (is_file('/opt/alt/php83/usr/bin/php') ? '/opt/alt/php83/usr/bin/php' : 'php').' '.APP_ROOT.'/bin/leantime schedule:run';

        $this->tpl->assign('apiKeys', $apiKeys);
        $this->tpl->assign('languageList', $this->language->getLanguageList());
        $this->tpl->assign('companySettings', $companySettings);
        $this->tpl->assign('smtpSettings', $smtp);
        $this->tpl->assign('cronCommand', $cronCommand);
        $this->tpl->assign('notificationCategories', Notification::NOTIFICATION_CATEGORIES);
        $this->tpl->assign('defaultNotificationTypes', $defaultNotificationTypes);
        $this->tpl->assign('defaultRelevance', $defaultRelevance);
        $this->tpl->assign('relevanceLevels', [
            Notification::RELEVANCE_ALL => 'label.notifications_all_activity',
            Notification::RELEVANCE_MY_WORK => 'label.notifications_my_work',
        ]);

        return $this->tpl->display('setting.editCompanySettings');
    }

    /**
     * post - handle post requests
     */
    public function post($params)
    {
        if (isset($params['saveSmtpSettings'])) {
            $this->settingsRepo->saveSetting('companysettings.smtp.fromEmail', trim((string) ($params['smtpFromEmail'] ?? '')));
            $this->settingsRepo->saveSetting('companysettings.smtp.useSMTP', isset($params['smtpUseSMTP']) ? 'true' : 'false');
            $this->settingsRepo->saveSetting('companysettings.smtp.hosts', trim((string) ($params['smtpHosts'] ?? '')));
            $this->settingsRepo->saveSetting('companysettings.smtp.auth', isset($params['smtpAuth']) ? 'true' : 'false');
            $this->settingsRepo->saveEncryptedSetting('companysettings.smtp.username', trim((string) ($params['smtpUsername'] ?? '')));
            $smtpPassword = trim((string) ($params['smtpPassword'] ?? ''));
            if ($smtpPassword !== '') {
                $this->settingsRepo->saveEncryptedSetting('companysettings.smtp.password', $smtpPassword);
            }
            $this->settingsRepo->saveSetting('companysettings.smtp.secure', trim((string) ($params['smtpSecure'] ?? 'tls')));
            $this->settingsRepo->saveSetting('companysettings.smtp.port', (int) ($params['smtpPort'] ?? 587));
            $this->settingsRepo->saveSetting('companysettings.smtp.autoTLS', isset($params['smtpAutoTLS']) ? 'true' : 'false');
            $this->settingsRepo->saveSetting('companysettings.smtp.sslNoVerify', isset($params['smtpSSLNoVerify']) ? 'true' : 'false');

            $this->tpl->setNotification('SMTP settings saved successfully.', 'success');

            return Frontcontroller::redirect(BASE_URL.'/setting/editCompanySettings#smtpSettings');
        }

        if (isset($params['testSmtpSettings'])) {
            $to = trim((string) ($params['smtpTestTo'] ?? ''));
            if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $this->tpl->setNotification('Please provide a valid test recipient email.', 'error');

                return Frontcontroller::redirect(BASE_URL.'/setting/editCompanySettings#smtpSettings');
            }

            try {
                $mailer = app()->make(Mailer::class);
                $mailer->setSubject('SMTP test from Al Mudheer');
                $mailer->setHtml('SMTP configuration test completed successfully.');
                $mailer->sendMail([$to], 'Al Mudheer');
                $this->tpl->setNotification('Test email sent successfully.', 'success');
            } catch (\Throwable $e) {
                report($e);
                $this->tpl->setNotification('Failed to send test email. Please verify SMTP settings.', 'error');
            }

            return Frontcontroller::redirect(BASE_URL.'/setting/editCompanySettings#smtpSettings');
        }

        // Look & feel updates
        if (isset($params['primarycolor']) && $params['primarycolor'] != '') {
            $this->settingsRepo->saveSetting('companysettings.primarycolor', htmlentities(addslashes($params['primarycolor'])));
            $this->settingsRepo->saveSetting('companysettings.secondarycolor', htmlentities(addslashes($params['secondarycolor'])));

            // Check if main color is still in the system
            // if so remove. This call should be removed in a few versions.
            $mainColor = $this->settingsRepo->getSetting('companysettings.mainColor');
            if ($mainColor !== false) {
                $this->settingsRepo->deleteSetting('companysettings.mainColor');
            }

            session(['companysettings.primarycolor' => htmlentities(addslashes($params['primarycolor']))]);
            session(['companysettings.secondarycolor' => htmlentities(addslashes($params['secondarycolor']))]);

            $this->tpl->setNotification($this->language->__('notifications.company_settings_edited_successfully'), 'success');
        }

        // Main Details
        if (isset($params['name']) && $params['name'] != '' && isset($params['language']) && $params['language'] != '') {
            $this->settingsRepo->saveSetting('companysettings.sitename', htmlspecialchars(addslashes($params['name'])));
            $this->settingsRepo->saveSetting('companysettings.language', htmlentities(addslashes($params['language'])));
            $this->settingsRepo->saveSetting('companysettings.messageFrequency', (int) $params['messageFrequency']);

            // Clear the localization cache so middleware re-fetches on next request
            session()->forget('localization.cached');

            // Save default notification event types
            $defaultEventTypes = $params['defaultNotificationEventTypes'] ?? [];
            if (! is_array($defaultEventTypes)) {
                $defaultEventTypes = [];
            }
            $validCategories = array_keys(Notification::NOTIFICATION_CATEGORIES);
            $defaultEventTypes = array_values(array_intersect($defaultEventTypes, $validCategories));
            $this->settingsRepo->saveSetting(
                'companysettings.defaultNotificationEventTypes',
                json_encode($defaultEventTypes)
            );

            // Save default notification relevance level
            $defaultRelevance = $params['defaultNotificationRelevance'] ?? Notification::RELEVANCE_ALL;
            if (! Notification::isValidRelevanceLevel($defaultRelevance)) {
                $defaultRelevance = Notification::RELEVANCE_ALL;
            }
            $this->settingsRepo->saveSetting('companysettings.defaultNotificationRelevance', $defaultRelevance);

            session(['companysettings.sitename' => htmlspecialchars(addslashes($params['name']))]);
            session(['companysettings.language' => htmlentities(addslashes($params['language']))]);

            if (isset($_POST['telemetryActive'])) {
                $this->settingsRepo->saveSetting('companysettings.telemetry.active', 'true');
            } else {
                // Set remote telemetry to false:
                app()->make(ReportService::class)->optOutTelemetry();
            }

            $microsoftAuthEnabled = isset($params['microsoftAuthEnabled']) ? 'true' : 'false';
            $this->settingsRepo->saveSetting('companysettings.microsoftAuth.enabled', $microsoftAuthEnabled);

            $microsoftIssuer = trim((string) ($params['microsoftAuthIssuer'] ?? ''));
            $this->settingsRepo->saveEncryptedSetting('companysettings.microsoftAuth.issuer', $microsoftIssuer);

            $microsoftClientId = trim((string) ($params['microsoftAuthClientId'] ?? ''));
            $this->settingsRepo->saveEncryptedSetting('companysettings.microsoftAuth.clientId', $microsoftClientId);

            $microsoftAllowRegistration = isset($params['microsoftAuthAllowPublicRegistration']) ? 'true' : 'false';
            $this->settingsRepo->saveSetting(
                'companysettings.microsoftAuth.allowPublicRegistration',
                $microsoftAllowRegistration
            );

            $microsoftDefaultRole = (int) ($params['microsoftAuthDefaultRole'] ?? 20);
            $validRoles = [5, 10, 20, 30, 40, 50];
            if (! in_array($microsoftDefaultRole, $validRoles, true)) {
                $microsoftDefaultRole = 20;
            }
            $this->settingsRepo->saveSetting('companysettings.microsoftAuth.defaultRole', $microsoftDefaultRole);

            $microsoftClientSecret = trim((string) ($params['microsoftAuthClientSecret'] ?? ''));
            if ($microsoftClientSecret !== '') {
                $this->settingsRepo->saveEncryptedSetting(
                    'companysettings.microsoftAuth.clientSecret',
                    $microsoftClientSecret
                );
            }

            $this->tpl->setNotification($this->language->__('notifications.company_settings_edited_successfully'), 'success');
        }

        return Frontcontroller::redirect(BASE_URL.'/setting/editCompanySettings');
    }

    /**
     * put - handle put requests
     */
    public function put($params) {}

    /**
     * delete - handle delete requests
     */
    public function delete($params) {}

    private function toBool(mixed $value): bool
    {
        return in_array(strtolower((string) $value), ['1', 'true', 'on', 'yes'], true);
    }
}
