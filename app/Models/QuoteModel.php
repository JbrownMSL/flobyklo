<?php

namespace App\Models;

use CodeIgniter\Model;

class QuoteModel extends Model
{
    protected $table         = 'quotes';
    protected $allowedFields = ['client_id', 'event_id', 'status', 'subtotal', 'tax', 'total', 'deposit_pct', 'valid_until'];
    protected $useTimestamps = true;

    public function withClient(int $id): ?array
    {
        $row = $this->db->table('quotes q')
            ->select('q.*, c.name AS client_name, c.email AS client_email, c.phone AS client_phone, e.type AS event_type, e.event_date, e.venue')
            ->join('clients c', 'c.id = q.client_id', 'left')
            ->join('events e', 'e.id = q.event_id', 'left')
            ->where('q.id', $id)
            ->get()->getRowArray();
        return $row ?: null;
    }

    /** Recalculate subtotal/tax/total from quote_items. taxRatePct e.g. 8.5 for 8.5%. */
    public function recalcTotals(int $quoteId, float $taxRatePct = 0.0): void
    {
        $subtotal = (float) ($this->db->table('quote_items')
            ->selectSum('line_total')
            ->where('quote_id', $quoteId)
            ->get()->getRowArray()['line_total'] ?? 0);

        $tax   = round($subtotal * ($taxRatePct / 100.0), 2);
        $total = round($subtotal + $tax, 2);

        $this->update($quoteId, [
            'subtotal' => $subtotal,
            'tax'      => $tax,
            'total'    => $total,
        ]);
    }
}
