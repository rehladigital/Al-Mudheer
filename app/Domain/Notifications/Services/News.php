<?php

namespace Leantime\Domain\Notifications\Services;

use Leantime\Core\Db\Db as DbCore;
use Leantime\Core\Language as LanguageCore;
use Leantime\Domain\Notifications\Repositories\Notifications as NotificationRepository;
use Leantime\Domain\Setting\Services\Setting;
use Leantime\Domain\Users\Repositories\Users as UserRepository;

/**
 * @api
 */
class News
{
    private DbCore $db;

    private NotificationRepository $notificationsRepo;

    private UserRepository $userRepository;

    private LanguageCore $language;

    private Setting $settingService;

    /**
     * __construct - get database connection
     *
     *
     * @api
     */
    public function __construct(
        DbCore $db,
        NotificationRepository $notificationsRepo,
        UserRepository $userRepository,
        LanguageCore $language,
        Setting $settingService
    ) {
        $this->db = $db;
        $this->notificationsRepo = $notificationsRepo;
        $this->userRepository = $userRepository;
        $this->language = $language;
        $this->settingService = $settingService;
    }

    public function getLatest(int $userId): false|\SimpleXMLElement
    {
        // External update/news checks are disabled in this installation.
        return false;

    }

    public function hasNews(int $userId): bool
    {
        return false;

    }

    /**
     * getFeed - Fetches the feed from a remote URL and returns the contents as a SimpleXMLElement object
     *
     * @return \SimpleXMLElement - The parsed XML content as a SimpleXMLElement object
     *
     * @throws \Exception - If the simplexml_load_string function doesn't exist
     *
     * @api
     */
    public function getFeed()
    {
        return false;
    }
}
