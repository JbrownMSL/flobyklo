<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Weekend Tool Rentals — business configuration.
 *
 * Values are sourced from Andrew's official RENTAL PACKET (2026-08) where the
 * contract is authoritative; remaining judgment calls are noted. All env-
 * overridable via `wtr.*`.
 */
class Wtr extends BaseConfig
{
    /** Business identity (from the rental packet). */
    public string $businessName  = 'Weekend Tool Rentals LLC';
    public string $businessEmail = 'andrew@weekendtoolrentals.com';  // Google Workspace (jnbgroup additional domain)
    public string $businessPhone = '801-979-6027';
    public string $pickupAddress = '2313 W Mountainside Circle, Bluffdale, UT 84065';
    public string $pickupHours   = 'By appointment during normal business hours';

    /**
     * Pricing. Sheet carries 4-hour, daily, and weekly rates per item.
     * Booking is currently day-based (date range); the 4-hour rate is stored +
     * displayed but sub-day booking is a future enhancement (flagged to Andrew).
     * Weekly rate is explicit per item (from the sheet); falls back to 6× daily.
     */
    public int   $minRentalDays       = 1;
    public float $weeklyDayMultiplier = 6.0;

    /**
     * Security deposit (RENTAL PACKET): $300 for equipment valued under $10,000,
     * $500 for equipment valued $10,000+. Refundable, auth-held at booking,
     * released within 5 business days of a clean return.
     */
    public float $depositThreshold = 10000.0;
    public float $depositUnder     = 300.0;
    public float $depositAtOrOver  = 500.0;

    /**
     * Cancellation — ⚠ UNRESOLVED CONFLICT:
     *   • Rental packet (signed contract): "Cancellations or no-shows result in
     *     forfeiture of the rental deposit."
     *   • Andrew's voice brief: tiered refund 75/50/25.
     * Implemented = tiered (his later explicit decision); flagged to Andrew to
     * reconcile with the contract before launch.
     */
    public array $cancellationTiers = [
        48 => 0.75,
        24 => 0.50,
        0  => 0.25,
    ];

    /**
     * Payment timing (Andrew 2026-06-09): charge 50% of the total at booking,
     * the remaining 50% collected at pickup. A refundable security deposit
     * ($300/$500) is still authorized at booking (unless the waiver is taken).
     */
    public float $bookingChargePct = 0.50;

    /**
     * Optional Damage Waiver (Andrew confirmed): a 12% add-on the renter may
     * elect INSTEAD of carrying their own insurance; when taken it waives the
     * refundable security-deposit hold. The packet's insurance/self-insure
     * terms still apply for renters who decline the waiver.
     */
    public bool  $damageWaiverEnabled = true;
    public float $damageWaiverPct     = 0.12;

    /** Fees (RENTAL PACKET). */
    public float $lateFeeHourlyPctOfDaily = 0.25;   // 25% of daily rate per hour retained beyond term
    public float $lateFeeFlat             = 100.0;  // + $100 late return fee
    public float $cleaningFee             = 75.0;   // if returned dirty
    public float $refuelPerGallon         = 6.0;    // if returned under full
    public bool  $deliveryOffered         = true;   // delivery & pickup fee, each way (quoted per job)
    public int   $engineHoursPerDay       = 8;      // overage = (daily ÷ 8) × hours over

    /** Two Utah tax classes (trailers taxed separately as motor vehicles). */
    public array $taxClasses = [
        'equipment_rental'     => 'Equipment Rental Tax',
        'motor_vehicle_rental' => 'Utah Motor-Vehicle Rental Tax (trailers)',
    ];

    /** Renter eligibility (packet): 18+, valid driver's license + second ID at pickup. */
    public int $minRenterAge = 18;

    /** Square (packet confirms Square + card-on-file up to 14 days post-return). Keys from .env. */
    public string $squareEnv         = 'sandbox';
    public string $squareAppId       = '';
    public string $squareAccessToken = '';
    public string $squareLocationId  = '';

    /**
     * Analytics / marketing tags — set in .env; the tags emit only when present
     * (safe to leave blank). See app/Views/partials/seo_head.php.
     *   wtr.ga4Id           Google Analytics 4 measurement id (G-XXXXXXXXXX)
     *   wtr.googleAdsId     Google Ads tag id (AW-XXXXXXXXX) for conversion tracking
     *   wtr.metaPixelId     Meta (Facebook/Instagram) Pixel id
     *   wtr.gscVerification Google Search Console meta-tag verification token
     */
    public string $ga4Id           = '';
    public string $googleAdsId     = '';
    public string $metaPixelId     = '';
    public string $gscVerification = '';

    /**
     * BaseConfig env auto-binding only matches FLAT property names, but our .env
     * uses the dotted convention (wtr.square.appId, like wtr.devLockout). Read
     * those explicitly so the keys actually land — and pull the analytics ids too.
     */
    public function __construct()
    {
        parent::__construct();
        $this->squareEnv         = (string) env('wtr.square.env', $this->squareEnv);
        $this->squareAppId       = (string) env('wtr.square.appId', $this->squareAppId);
        $this->squareAccessToken = (string) env('wtr.square.accessToken', $this->squareAccessToken);
        $this->squareLocationId  = (string) env('wtr.square.locationId', $this->squareLocationId);
        $this->ga4Id             = (string) env('wtr.ga4Id', $this->ga4Id);
        $this->googleAdsId       = (string) env('wtr.googleAdsId', $this->googleAdsId);
        $this->metaPixelId       = (string) env('wtr.metaPixelId', $this->metaPixelId);
        $this->gscVerification   = (string) env('wtr.gscVerification', $this->gscVerification);
    }

    public function squareConfigured(): bool
    {
        return $this->squareAccessToken !== '' && $this->squareLocationId !== '' && $this->squareAppId !== '';
    }

    /** Parse pickupAddress into schema.org PostalAddress parts (best-effort). */
    public function addressParts(): array
    {
        $parts = array_map('trim', explode(',', $this->pickupAddress));
        if (count($parts) < 3) {
            return [];
        }
        $rp = preg_split('/\s+/', trim($parts[2]));
        return array_filter([
            'streetAddress'   => $parts[0],
            'addressLocality' => $parts[1],
            'addressRegion'   => $rp[0] ?? '',
            'postalCode'      => $rp[1] ?? '',
        ]);
    }

    /** Security deposit for an equipment row, by its cost (packet rule). */
    public function depositFor(?float $cost): float
    {
        if ($cost === null) {
            return $this->depositUnder;
        }
        return $cost >= $this->depositThreshold ? $this->depositAtOrOver : $this->depositUnder;
    }
}
