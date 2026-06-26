<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * WTR config — removed. Use Config\Fbk instead.
 * Stub retained in case any remnant code loads this class.
 */
class Wtr extends BaseConfig
{
    public string $businessName  = 'Flora by Klo';
    public string $businessEmail = 'kloe@florabyklo.com';
    public string $squareEnv     = 'sandbox';
    public string $squareAppId   = '';
    public string $squareAccessToken = '';
    public string $squareLocationId  = '';

    public function squareConfigured(): bool { return false; }
}
