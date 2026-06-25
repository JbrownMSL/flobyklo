<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Pre-launch gate. Until Square is configured (Config\Wtr::squareConfigured()),
 * the public storefront shows a "launching soon" holding page so the site can
 * be stood up live (DNS/SSL) without taking real bookings before payments work.
 *
 * Bypassed for: admins (so they can preview + finish setup), the auth routes
 * (so an admin can log in), and the admin area itself. The gate lifts
 * automatically the moment wtr.square.* keys land in .env — no redeploy.
 */
class SquareGate implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (config('Wtr')->squareConfigured()) {
            return; // launched — normal site
        }

        $path = trim($request->getUri()->getPath(), '/');

        // Always allow auth + admin so staff can log in and prepare the site.
        foreach (['login', 'register', 'logout', 'admin', 'auth', 'magic-link', 'forgot', 'reset', 'change-password'] as $allow) {
            if ($path === $allow || str_starts_with($path, $allow . '/')) {
                return;
            }
        }

        // Logged-in admins (incl. hidden system_admin) preview the real site.
        helper('wtr');
        if (wtr_is_admin()) {
            return;
        }

        return service('response')->setBody(view('comingsoon'))->setStatusCode(200);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
