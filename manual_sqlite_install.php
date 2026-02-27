<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Str;
use Illuminate\Contracts\Console\Kernel;
use Leantime\Domain\Install\Services\SchemaBuilder;
use Leantime\Domain\Setting\Services\Setting as SettingService;
use Leantime\Domain\Users\Repositories\Users;

require __DIR__.'/vendor/autoload.php';

if (!defined('BASE_URL')) {
    define('BASE_URL', '');
}
if (!defined('CURRENT_URL')) {
    define('CURRENT_URL', '');
}

$app = require __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
Facade::setFacadeApplication($app);

$email = getenv('ALMUDHEER_ADMIN_EMAIL') ?: 'admin@example.com';
$password = getenv('ALMUDHEER_ADMIN_PASSWORD') ?: 'ChangeMe123!';
$firstName = getenv('ALMUDHEER_ADMIN_FIRSTNAME') ?: 'Admin';
$lastName = getenv('ALMUDHEER_ADMIN_LASTNAME') ?: 'User';
$company = getenv('ALMUDHEER_COMPANY_NAME') ?: 'Rehla Digital Inc';
$country = getenv('ALMUDHEER_COMPANY_COUNTRY') ?: 'Canada';
$currencyCode = getenv('ALMUDHEER_CURRENCY_CODE') ?: 'CAD';
$timezone = getenv('ALMUDHEER_TIMEZONE') ?: 'America/Regina'; // CST in Canada (no DST)

$schemaBuilder = $app->make(SchemaBuilder::class);

if (!Schema::hasTable('zp_user')) {
    $schemaBuilder->createAllTables();
    $schemaBuilder->insertInitialData(
        [
            'email' => $email,
            'firstname' => $firstName,
            'lastname' => $lastName,
            'company' => $company,
        ],
        Str::random(32)
    );
}

$usersRepo = $app->make(Users::class);
$user = $usersRepo->getUserByEmail($email, '');

if (!is_array($user) || !isset($user['id'])) {
    // If DB already existed with a different login, reuse user #1 as admin.
    $fallbackUser = $usersRepo->getUser(1);
    if (is_array($fallbackUser) && isset($fallbackUser['id'])) {
        $user = $fallbackUser;
    }
}

if (is_array($user) && isset($user['id'])) {
    $userId = (int) $user['id'];
    $userSettings = [];
    if (!empty($user['settings']) && is_string($user['settings'])) {
        $unserialized = @unserialize($user['settings']);
        if (is_array($unserialized)) {
            $userSettings = $unserialized;
        }
    }

    if (!isset($userSettings['modals']) || !is_array($userSettings['modals'])) {
        $userSettings['modals'] = [];
    }

    // Hide onboarding helper popups and tours permanently for this admin.
    $userSettings['modals']['home'] = 1;
    $userSettings['modals']['homeDashboardTour'] = 1;

    $usersRepo->patchUser($userId, [
        'username' => $email,
        'firstname' => $firstName,
        'lastname' => $lastName,
        'password' => $password,
        'status' => 'a',
        'settings' => serialize($userSettings),
    ]);

    // Persist user timezone setting for first login/session defaults.
    $settingsService = $app->make(SettingService::class);
    $settingsService->saveSetting('usersettings.'.$userId.'.timezone', $timezone);
    $settingsService->saveSetting('user.'.$userId.'.firstLoginCompleted', true);
    $settingsService->saveSetting('companysettings.completedOnboarding', true);

    // Persist optional company-level settings requested by user.
    $settingsService->saveSetting('companysettings.currencyCode', $currencyCode);

    // Set the default client country field used by client records.
    $app->make('db')->table('zp_clients')
        ->where('id', 1)
        ->update(['country' => $country]);
}

echo "SQLite install bootstrap completed for {$email} ({$company}).\n";
