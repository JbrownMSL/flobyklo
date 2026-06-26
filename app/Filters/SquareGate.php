<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Pre-launch gate. Until Square is configured (Config\Fbk::squareConfigured()),
 * the site shows a holding page. Bypassed for admin routes so staff can log in.
 * The gate lifts automatically once fbk.square.* keys are set in .env.
 */
class SquareGate implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (config('Fbk')->squareConfigured()) {
            return;
        }

        $path = trim($request->getUri()->getPath(), '/');

        foreach (['login', 'logout', 'admin', 'auth', 'magic-link', 'forgot', 'reset', 'change-password'] as $allow) {
            if ($path === $allow || str_starts_with($path, $allow . '/')) {
                return;
            }
        }

        helper('fbk');
        if (fbk_is_admin()) {
            return;
        }

        return service('response')->setBody(view('comingsoon'))->setStatusCode(200);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
