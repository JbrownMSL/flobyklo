<?php

namespace App\Services;

use App\Models\TaxRateModel;

/**
 * Two-class Utah tax (equipment_rental vs motor_vehicle_rental). Each cart line
 * is taxed by its own class so the invoice shows separate tax lines and the
 * remittance report can split them.
 */
class Tax
{
    private array $rates;

    public function __construct()
    {
        $this->rates = (new TaxRateModel())->map();
    }

    public function rateFor(string $taxClass): float
    {
        return $this->rates[$taxClass] ?? 0.0;
    }

    public function taxFor(string $taxClass, float $amount): float
    {
        return round($amount * $this->rateFor($taxClass), 2);
    }

    /** Sum tax across lines [['tax_class'=>..,'line_subtotal'=>..], ...] grouped by class. */
    public function byClass(array $lines): array
    {
        $out = [];
        foreach ($lines as $ln) {
            $cls = $ln['tax_class'] ?? 'equipment_rental';
            $out[$cls] = ($out[$cls] ?? 0) + $this->taxFor($cls, (float) $ln['line_subtotal']);
        }
        return array_map(static fn ($v) => round($v, 2), $out);
    }
}
