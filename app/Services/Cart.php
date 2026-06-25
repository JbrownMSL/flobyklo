<?php

namespace App\Services;

use App\Models\EquipmentModel;

/**
 * Session-backed rental cart. One date range applies to the whole cart (MVP).
 * Auto-adds a transport trailer for items that require one (decision: default-on,
 * removable). Computes per-line subtotal + tax (by class) and grand totals.
 */
class Cart
{
    private const KEY = 'wtr_cart';

    private \CodeIgniter\Session\Session $session;
    private EquipmentModel $equipment;
    private Pricing $pricing;
    private Tax $tax;

    public function __construct()
    {
        $this->session   = session();
        $this->equipment = new EquipmentModel();
        $this->pricing   = new Pricing();
        $this->tax       = new Tax();
    }

    private function data(): array
    {
        return $this->session->get(self::KEY) ?? ['items' => [], 'removed_trailers' => [], 'start' => null, 'end' => null];
    }

    private function save(array $d): void
    {
        $this->session->set(self::KEY, $d);
    }

    public function setDates(string $start, string $end): void
    {
        $d = $this->data();
        $d['start'] = $start;
        $d['end']   = $end;
        $this->save($d);
    }

    public function dates(): array
    {
        $d = $this->data();
        return [$d['start'], $d['end']];
    }

    public function add(int $equipmentId, int $qty = 1): void
    {
        $d = $this->data();
        $d['items'][$equipmentId] = ($d['items'][$equipmentId] ?? 0) + max(1, $qty);
        $this->save($d);
    }

    public function remove(int $equipmentId): void
    {
        $d = $this->data();
        unset($d['items'][$equipmentId]);
        $this->save($d);
    }

    /** Remove or restore an auto-added trailer for a parent item. */
    public function toggleTrailer(int $parentEquipmentId, bool $remove): void
    {
        $d = $this->data();
        $set = array_flip($d['removed_trailers'] ?? []);
        if ($remove) {
            $set[$parentEquipmentId] = true;
        } else {
            unset($set[$parentEquipmentId]);
        }
        $d['removed_trailers'] = array_keys($set);
        $this->save($d);
    }

    public function clear(): void
    {
        $this->session->remove(self::KEY);
    }

    public function isEmpty(): bool
    {
        return empty($this->data()['items']);
    }

    /**
     * Expand the cart to priced, taxed lines including auto-added trailers.
     * Returns ['lines'=>[...], 'days'=>int, 'start'=>.., 'end'=>..,
     *          'rental_subtotal'=>.., 'tax_by_class'=>[..], 'tax_total'=>..,
     *          'damage_deposit'=>.., 'waiver'=>..].
     */
    public function summary(bool $waiver = false): array
    {
        $d = $this->data();
        [$start, $end] = [$d['start'], $d['end']];
        $days  = ($start && $end) ? Pricing::days($start, $end) : 0;
        $lines = [];
        $removed = array_flip($d['removed_trailers'] ?? []);
        $wtr = config('Wtr');

        foreach ($d['items'] as $eqId => $qty) {
            $eq = $this->equipment->find((int) $eqId);
            if (! $eq) {
                continue;
            }
            $lines[] = $this->line($eq, $days, (int) $qty, false);

            // auto-trailer
            if (! empty($eq['requires_trailer_id']) && ! isset($removed[(int) $eqId])) {
                $tr = $this->equipment->find((int) $eq['requires_trailer_id']);
                if ($tr) {
                    $line = $this->line($tr, $days, (int) $qty, true);
                    $line['parent_id'] = (int) $eqId;
                    $lines[] = $line;
                }
            }
        }

        $rentalSubtotal = array_sum(array_column($lines, 'line_subtotal'));
        $taxByClass     = $this->tax->byClass($lines);
        $taxTotal       = array_sum($taxByClass);

        // damage deposit = sum of per-item deposit (explicit, else % of that line)
        $deposit = 0.0;
        foreach ($lines as $ln) {
            $deposit += $ln['damage_deposit'];
        }
        // Optional 12% damage waiver (Andrew): when elected it replaces the
        // refundable security-deposit hold.
        $waiverAmt = ($waiver && $wtr->damageWaiverEnabled)
            ? round($rentalSubtotal * $wtr->damageWaiverPct, 2) : 0.0;

        $grand = round($rentalSubtotal + $taxTotal + $waiverAmt, 2);

        return [
            'start' => $start, 'end' => $end, 'days' => $days,
            'lines' => $lines,
            'rental_subtotal' => round($rentalSubtotal, 2),
            'tax_by_class' => $taxByClass,
            'tax_total' => round($taxTotal, 2),
            'damage_deposit' => $waiver ? 0.0 : round($deposit, 2),  // waiver replaces the deposit hold
            'waiver' => $waiverAmt,
            'grand_total' => $grand,
            'booking_charge' => round($grand * $wtr->bookingChargePct, 2),  // 50% at booking
            'balance_due'    => round($grand - round($grand * $wtr->bookingChargePct, 2), 2),
        ];
    }

    private function line(array $eq, int $days, int $qty, bool $isTrailer): array
    {
        $sub = $days > 0 ? $this->pricing->lineSubtotal($eq, $days, $qty) : 0.0;
        $wtr = config('Wtr');
        // Security deposit per the rental packet: $300 (<$10k) / $500 (>=$10k)
        // by the item's value, times qty. An explicit per-item override wins.
        $perUnitDep = $eq['damage_deposit'] !== null
            ? (float) $eq['damage_deposit']
            : $wtr->depositFor(isset($eq['cost']) ? (float) $eq['cost'] : null);
        $dep = $perUnitDep * $qty;
        return [
            'equipment_id'  => (int) $eq['id'],
            'name'          => $eq['name'],
            'qty'           => $qty,
            'days'          => $days,
            'rate_snapshot' => $days > 0 ? $this->pricing->unitCost($eq, $days) : 0.0,
            'line_subtotal' => $sub,
            'tax_class'     => $eq['tax_class'],
            'tax_amount'    => $this->tax->taxFor($eq['tax_class'], $sub),
            'is_trailer'    => $isTrailer ? 1 : 0,
            'auto_added'    => $isTrailer ? 1 : 0,
            'damage_deposit'=> $dep,
        ];
    }
}
