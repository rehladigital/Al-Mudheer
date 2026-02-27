<?php

namespace Leantime\Domain\Help\Composers;

use Illuminate\Contracts\Container\BindingResolutionException;
use Leantime\Core\Controller\Composer;
use Leantime\Domain\Auth\Services\Auth;
use Leantime\Domain\Notifications\Services\Notifications as NotificationService;
use Leantime\Domain\Setting\Repositories\Setting;

class Helpermodal extends Composer
{
    private Setting $settingsRepo;

    private Auth $authService;

    private NotificationService $notificationService;

    public static array $views = [
        'help::helpermodal',
    ];

    public function init(
        Setting $settingsRepo,
        Auth $authService,
        NotificationService $notificationService
    ): void {
        $this->settingsRepo = $settingsRepo;
        $this->authService = $authService;
        $this->notificationService = $notificationService;
    }

    /**
     * @throws BindingResolutionException
     */
    public function with(): array
    {
        $userId = (int) $this->authService->getUserId();
        if ($userId > 0) {
            $noticeKey = 'usersettings.'.$userId.'.tipsDisabledNotificationSent';
            $noticeAlreadySent = $this->settingsRepo->getSetting($noticeKey);

            if ($noticeAlreadySent === false) {
                $this->notificationService->addNotifications([[
                    'userId' => $userId,
                    'read' => '0',
                    'type' => 'system',
                    'module' => 'help',
                    'moduleId' => 0,
                    'message' => 'Tips and onboarding popups are disabled. Updates are now shared through notifications.',
                    'datetime' => date('Y-m-d H:i:s'),
                    'url' => BASE_URL.'/dashboard/home',
                    'authorId' => $userId,
                ]]);
                $this->settingsRepo->saveSetting($noticeKey, '1');
            }
        }

        return [
            'completedOnboarding' => true,
            'showHelperModal' => false,
            'currentModal' => [],
            'isFirstLogin' => false,
        ];
    }
}
