<?php

namespace Leantime\Domain\Help\Controllers;

use Leantime\Core\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class ShowOnboardingDialog extends Controller
{
    /**
     * get - handle get requests
     */
    public function get($params): Response
    {
        return new Response('', 204);

    }
}
