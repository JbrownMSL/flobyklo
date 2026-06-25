<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Admin base — gates the whole /admin area to the Shield 'admin' group.
 * The `session` filter (in Routes) already requires login; this adds the
 * group check.
 */
abstract class BaseAdmin extends BaseController
{
    protected bool $denied = false;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);
        helper('wtr');
        if (! wtr_is_admin()) {
            $this->denied = true;
        }
    }

    protected function guard(): ?ResponseInterface
    {
        if ($this->denied) {
            return redirect()->to('/')->with('error', 'Admin access required.');
        }
        return null;
    }
}
