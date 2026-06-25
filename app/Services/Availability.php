<?php

namespace App\Services;

use App\Models\BlackoutModel;
use App\Models\EquipmentModel;

/**
 * Date-range availability engine. An item is available for a range if
 * (quantity − committed units over the overlapping range) >= requested qty.
 * Committed = booked + maintenance blackouts (BlackoutModel).
 */
class Availability
{
    public function unitsAvailable(int $equipmentId, string $start, string $end): int
    {
        $eq = (new EquipmentModel())->find($equipmentId);
        if (! $eq || ! $eq['active']) {
            return 0;
        }
        $committed = (new BlackoutModel())->committedQty($equipmentId, $start, $end);
        return max(0, (int) $eq['quantity'] - $committed);
    }

    public function isAvailable(int $equipmentId, string $start, string $end, int $qty = 1): bool
    {
        if (strtotime($end) < strtotime($start)) {
            return false;
        }
        return $this->unitsAvailable($equipmentId, $start, $end) >= max(1, $qty);
    }

    /** Reserve units by writing a 'booked' blackout row. */
    public function book(int $equipmentId, string $start, string $end, int $qty, int $reservationId): void
    {
        (new BlackoutModel())->insert([
            'equipment_id'   => $equipmentId,
            'qty'            => max(1, $qty),
            'start_date'     => $start,
            'end_date'       => $end,
            'reason'         => 'booked',
            'reservation_id' => $reservationId,
        ]);
    }

    /** Release a reservation's blackouts (on cancel). */
    public function release(int $reservationId): void
    {
        (new BlackoutModel())->where('reservation_id', $reservationId)->where('reason', 'booked')->delete();
    }
}
