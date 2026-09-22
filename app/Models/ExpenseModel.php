<?php

namespace App\Models;

use CodeIgniter\Model;

class ExpenseModel extends Model
{
    protected $table          = 'expenses';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = true;
    protected $createdField   = 'created_at';
    protected $updatedField   = '';   // expenses table has no updated_at column

    protected $allowedFields = [
        'date', 'vendor', 'category', 'amount', 'event_id', 'plaid_txn_id', 'notes',
        'receipt_waived', 'receipt_waived_reason',   // #2948
    ];

    protected $validationRules = [
        'date'     => 'required|valid_date[Y-m-d]',
        'amount'   => 'required|decimal|greater_than[0]',
        'category' => 'required|in_list[flowers,supplies,fuel,rent,labor,marketing,other]',
    ];

    /** Totals by category for a given month (YYYY-MM). */
    public function monthlyByCategory(string $month): array
    {
        return $this->select('category, SUM(amount) AS total')
            ->where('date >=', $month . '-01')
            ->where('date <=', $month . '-31')
            ->groupBy('category')
            ->findAll();
    }

    /** All expenses linked to a specific event (COGS). */
    public function forEvent(int $eventId): array
    {
        return $this->where('event_id', $eventId)->orderBy('date')->findAll();
    }

    /** Sum of all expenses for an event (total COGS). */
    public function sumForEvent(int $eventId): float
    {
        $row = $this->selectSum('amount')->where('event_id', $eventId)->first();
        return (float) ($row['amount'] ?? 0);
    }
}
