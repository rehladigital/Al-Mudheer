<?php

namespace Leantime\Domain\Help\Controllers;

use Leantime\Core\Controller\Controller;
use Leantime\Core\Controller\Frontcontroller;

class FirstLogin extends Controller
{
    /**
     * get - handle get requests
     */
    public function get($params)
    {
        return Frontcontroller::redirect(BASE_URL.'/dashboard/home');
    }

    /**
     * post - handle post requests
     */
    public function post($params)
    {
        return Frontcontroller::redirect(BASE_URL.'/dashboard/home');
    }
}
