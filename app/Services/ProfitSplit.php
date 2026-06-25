<?php

namespace App\Services;

/**
 * Allocates rental income per equipment ownership (decision: profit-split).
 * On a confirmed reservation, each item's line_subtotal is split across the
 * item's owners by owned_pct into income_allocations. Items with no ownership
 * row default to 100% 'WTR'.
 */
class ProfitSplit
{
    public function allocateReservation(int $reservationId): void
    {
        $db = db_connect();
        $items = $db->table('reservation_items')->where('reservation_id', $reservationId)->get()->getResultArray();
        $now = date('Y-m-d H:i:s');

        foreach ($items as $it) {
            $eqId   = (int) $it['equipment_id'];
            $income = (float) $it['line_subtotal'];
            if ($income <= 0) {
                continue;
            }
            $owners = $db->table('equipment_ownership')->where('equipment_id', $eqId)->get()->getResultArray();
            if (! $owners) {
                $owners = [['owner_entity' => 'WTR', 'owned_pct' => 100]];
            }
            // Avoid double-allocating if re-run.
            $db->table('income_allocations')
                ->where('reservation_item_id', (int) $it['id'])->delete();

            foreach ($owners as $o) {
                $amt = round($income * ((float) $o['owned_pct'] / 100), 2);
                $db->table('income_allocations')->insert([
                    'reservation_item_id' => (int) $it['id'],
                    'equipment_id'        => $eqId,
                    'owner_entity'        => $o['owner_entity'],
                    'amount'              => $amt,
                    'created_at'          => $now,
                ]);
            }
        }
    }
}
