<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/** Flora by Klo — business configuration. Values env-overridable via fbk.* */
class Fbk extends BaseConfig
{
    public string $businessName  = 'Flora by Klo';
    public string $businessEmail = 'kloe@florabyklo.com';

    /** $/hr labor rate used by the stem-cost engine + margins. */
    public float $laborRate = 40.0;

    /** Max events per weekend (Sat/Sun) before the bookings module warns. */
    public int $capacityPerWeekend = 2;

    /** Square (graceful/simulated when blank). Keys from .env fbk.square.* */
    public string $squareEnv         = 'sandbox';
    public string $squareAppId       = '';
    public string $squareAccessToken = '';
    public string $squareLocationId  = '';

    public function __construct()
    {
        parent::__construct();
        $this->squareEnv          = (string) env('fbk.square.env', $this->squareEnv);
        $this->squareAppId        = (string) env('fbk.square.appId', $this->squareAppId);
        $this->squareAccessToken  = (string) env('fbk.square.accessToken', $this->squareAccessToken);
        $this->squareLocationId   = (string) env('fbk.square.locationId', $this->squareLocationId);
        $this->laborRate          = (float)  env('fbk.laborRate', $this->laborRate);
        $this->capacityPerWeekend = (int)    env('fbk.capacityPerWeekend', $this->capacityPerWeekend);
    }

    public function squareConfigured(): bool
    {
        return $this->squareAccessToken !== '' && $this->squareLocationId !== '' && $this->squareAppId !== '';
    }
}
