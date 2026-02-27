<?php

namespace Leantime\Domain\Install\Controllers;

use Illuminate\Http\Exceptions\HttpResponseException;
use Leantime\Core\Controller\Controller;
use Leantime\Core\Controller\Frontcontroller as FrontcontrollerCore;
use Leantime\Domain\Install\Repositories\Install as InstallRepository;
use Leantime\Domain\Users\Repositories\Users as UserRepository;
use Symfony\Component\HttpFoundation\Response;

class Index extends Controller
{
    private InstallRepository $installRepo;
    private UserRepository $userRepo;

    /**
     * init - initialize private variables
     *
     * @throws HttpResponseException
     */
    public function init(InstallRepository $installRepo, UserRepository $userRepo)
    {
        $this->installRepo = $installRepo;
        $this->userRepo = $userRepo;

        if ($this->installRepo->checkIfInstalled()) {
            return FrontcontrollerCore::redirect(BASE_URL.'/');
        }
    }

    /**
     * get - handle get requests
     *
     * @param  $params  parameters or body of the request
     */
    public function get($params)
    {
        return $this->tpl->display('install.new', 'entry');
    }

    public function post($params): Response
    {
        $values = [
            'email' => '',
            'password' => '',
            'password2' => '',
            'firstname' => '',
            'lastname' => '',
        ];

        if (isset($_POST['install'])) {
            $values = [
                'email' => ($params['email']),
                'password' => ($params['password'] ?? ''),
                'password2' => ($params['password2'] ?? ''),
                'firstname' => ($params['firstname']),
                'lastname' => ($params['lastname']),
                'company' => ($params['company']),
            ];

            $notificationSet = false; // Track whether a notification has been set

            if (empty($params['email'])) {
                $this->tpl->setNotification('notification.enter_email', 'error');
                $notificationSet = true;
            }

            if (empty($params['firstname']) && ! $notificationSet) {
                $this->tpl->setNotification('notification.enter_firstname', 'error');
                $notificationSet = true;
            }

            if (empty($params['lastname']) && ! $notificationSet) {
                $this->tpl->setNotification('notification.enter_lastname', 'error');
                $notificationSet = true;
            }

            if (empty($params['company']) && ! $notificationSet) {
                $this->tpl->setNotification('notification.enter_company', 'error');
                $notificationSet = true;
            }

            if (empty($params['password']) && ! $notificationSet) {
                $this->tpl->setNotification('Please enter an admin password.', 'error');
                $notificationSet = true;
            }

            if (
                ! $notificationSet
                && isset($params['password'], $params['password2'])
                && $params['password'] !== $params['password2']
            ) {
                $this->tpl->setNotification('Admin password and confirm password do not match.', 'error');
                $notificationSet = true;
            }

            if (! $notificationSet) {
                // No notifications were set, all fields are valid
                if ($this->installRepo->setupDB($values)) {

                    // During installation, email is the admin username.
                    $newAdmin = $this->userRepo->getUserByEmail($values['email'], '');
                    if ($newAdmin === false) {
                        $newAdmin = $this->userRepo->getUser(1);
                    }

                    if (is_array($newAdmin) && isset($newAdmin['id'])) {
                        $this->userRepo->patchUser((int) $newAdmin['id'], [
                            'username' => $values['email'],
                            'password' => $values['password'],
                            'status' => 'a',
                        ]);
                    }

                    $this->tpl->setNotification(sprintf($this->language->__('notifications.installation_success_setup_account'), BASE_URL), 'success');

                    return FrontcontrollerCore::redirect(BASE_URL.'/auth/login');
                } else {
                    $this->tpl->setNotification($this->language->__('notification.error_installing'), 'error');
                }
            }
        }

        return FrontcontrollerCore::redirect(BASE_URL.'/install');
    }
}
