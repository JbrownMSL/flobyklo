<?php

namespace App\Models;

use CodeIgniter\Model;

class InvoiceModel extends Model
{
    protected $table         = 'invoices';
    protected $allowedFields = ['quote_id', 'client_id', 'number', 'status', 'subtotal', 'tax', 'total', 'amount_paid', 'balance_due', 'due_date'];
    protected $useTimestamps = true;

    /** Generate next invoice number and create invoice row from an accepted quote. */
    public function createFromQuote(array $quote): int
    {
        $year  = date('Y');
        $count = (int) $this->db->table('invoices')
            ->like('number', 'FBK-' . $year . '-', 'after')
            ->countAllResults();

        $number = sprintf('FBK-%d-%04d', $year, $count + 1);

        $this->insert([
            'quote_id'    => $quote['id'],
            'client_id'   => $quote['client_id'],
            'number'      => $number,
            'status'      => 'draft',
            'subtotal'    => $quote['subtotal'],
            'tax'         => $quote['tax'],
            'total'       => $quote['total'],
            'amount_paid' => 0,
            'balance_due' => $quote['total'],
            'due_date'    => date('Y-m-d', strtotime('+30 days')),
        ]);

        return (int) $this->getInsertID();
    }
}
