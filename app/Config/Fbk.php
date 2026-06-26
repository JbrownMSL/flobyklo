<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Flora by Klo — business configuration.
 * All values are env-overridable via `fbk.*`.
 */
class Fbk extends BaseConfig
{
    public string $businessName  = 'Flora by Klo';
    public string $businessEmail = 'kloe@florabyklo.com';

    /** Square. Keys from .env (fbk.square.*). */
    public string $squareEnv         = 'sandbox';
    public string $squareAppId       = '';
    public string $squareAccessToken = '';
    public string $squareLocationId  = '';

    public function __construct()
    {
        parent::__construct();
        $this->squareEnv         = (string) env('fbk.square.env', $this->squareEnv);
        $this->squareAppId       = (string) env('fbk.square.appId', $this->squareAppId);
        $this->squareAccessToken = (string) env('fbk.square.accessToken', $this->squareAccessToken);
        $this->squareLocationId  = (string) env('fbk.square.locationId', $this->squareLocationId);
    }

    public function squareConfigured(): bool
    {
        return $this->squareAccessToken !== '' && $this->squareLocationId !== '' && $this->squareAppId !== '';
    }
}
