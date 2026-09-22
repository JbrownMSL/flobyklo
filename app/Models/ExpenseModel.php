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
        'category' => 'required|in_list[flowers,supplies,fuel,rent,labor,marketing,other,owner_draw]',
    ];

    /**
     * #2950 (Jason 2026-09-21): an OWNER DISTRIBUTION is not a business cost — it must never reach
     * the P&L. It is stored in `expenses` (category owner_draw) and every AGGREGATE reads the view
     * `expenses_pnl` (= expenses minus these categories), so a new report is correct by default.
     * ⚠️ Any new sum over expenses must read expenses_pnl, not expenses. Writes stay on `expenses`.
     */
    public const NON_PNL_CATEGORIES = ['owner_draw'];

    /** Totals by category for a given month (YYYY-MM). */
    public function monthlyByCategory(string $month): array
    {
        return $this->select('category, SUM(amount) AS total')
            ->whereNotIn('category', self::NON_PNL_CATEGORIES)
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
