<?php

namespace Config;

use CodeIgniter\Config\BaseService;

/**
 * Services Configuration file.
 *
 * Application-specific services / overrides. WTR uses stock services; the
 * email dev-lockout is handled in app/Helpers/wtr_helper.php (wtr_send_email)
 * rather than a service override.
 */
class Services extends BaseService
{
}
