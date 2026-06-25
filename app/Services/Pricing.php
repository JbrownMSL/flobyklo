<?php

namespace App\Services;

use App\Models\EquipmentModel;

/**
 * Rental pricing (decision #2). Daily rate primary; weekly rate (explicit or
 * 6× daily) applied for 7+ day spans. Inclusive day count (start..end).
 */
class Pricing
{
    /** Inclusive number of rental days between two Y-m-d dates (min 1). */
    public static function days(string $start, string $end): int
    {
        $s = new \DateTimeImmutable($start);
        $e = new \DateTimeImmutable($end);
        $d = (int) $s->diff($e)->days + 1;
        return max(1, $d);
    }

    /** Per-unit cost for an equipment row over $days. */
    public function unitCost(array $equipment, int $days): float
    {
        $daily  = (float) $equipment['daily_rate'];
        $weekly = (new EquipmentModel())->weeklyRate($equipment);

        if ($days < 7) {
            return round($daily * $days, 2);
        }
        $weeks = intdiv($days, 7);
        $rem   = $days % 7;
        // Remainder days never cost more than another full week.
        $remCost = min($rem * $daily, $weekly);
        return round($weeks * $weekly + $remCost, 2);
    }

    /** Line subtotal = unit cost × qty. */
    public function lineSubtotal(array $equipment, int $days, int $qty): float
    {
        return round($this->unitCost($equipment, $days) * max(1, $qty), 2);
    }
}
