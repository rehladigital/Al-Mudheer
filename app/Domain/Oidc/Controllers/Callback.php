<?php

namespace Leantime\Domain\Oidc\Controllers;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Exception\HttpResponseException;
use Leantime\Core\Controller\Controller;
use Leantime\Core\Controller\Frontcontroller;
use Leantime\Domain\Oidc\Services\Oidc as OidcService;
use Symfony\Component\HttpFoundation\Response;

class Callback extends Controller
{
    private OidcService $oidc;

    public function init(OidcService $oidc): void
    {
        $this->oidc = $oidc;
    }

    /**
     * @throws GuzzleException|HttpResponseException
     */
    public function get($params): Response
    {
        if (isset($_GET['error'])) {
            $errorDescription = $_GET['error_description'] ?? $_GET['error'];
            $this->tpl->setNotification($errorDescription, 'danger', 'oidc_error');

            return Frontcontroller::redirect(BASE_URL.'/auth/login');
        }

        $code = $_GET['code'] ?? null;
        $state = $_GET['state'] ?? null;

        if (! $code || ! $state) {
            $this->tpl->setNotification('OIDC callback did not contain the expected response data.', 'danger', 'oidc_error');

            return Frontcontroller::redirect(BASE_URL.'/auth/login');
        }

        try {
            return $this->oidc->callback($code, $state);
        } catch (\Exception $e) {
            $this->tpl->setNotification($e->getMessage(), 'danger', 'oidc_error');

            return Frontcontroller::redirect(BASE_URL.'/auth/login');
        }
    }
}
