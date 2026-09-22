<?php

namespace App\Controllers\Admin;

use App\Libraries\ReceiptStore;
use App\Models\ExpenseModel;

class Expenses extends BaseAdmin
{
    private const CATEGORIES = ['flowers', 'supplies', 'fuel', 'rent', 'labor', 'marketing', 'other', 'owner_draw'];

    public function index()
    {
        if ($r = $this->guard()) { return $r; }

        $db      = db_connect();
        $builder = $db->table('expenses e')
            ->select('e.*, ev.event_date, ev.venue, ev.type AS event_type,
                      (SELECT COUNT(*) FROM expense_receipts r WHERE r.expense_id = e.id) AS receipt_count,
                      (SELECT r2.id FROM expense_receipts r2 WHERE r2.expense_id = e.id ORDER BY r2.id LIMIT 1) AS receipt_id')
            ->join('events ev', 'ev.id = e.event_id', 'left');

        $cat   = (string) $this->request->getGet('category');
        $evId  = (int)    $this->request->getGet('event_id');
        $from  = (string) $this->request->getGet('from');
        $to    = (string) $this->request->getGet('to');
        $rcpt  = (string) $this->request->getGet('receipt');

        if ($cat && in_array($cat, self::CATEGORIES, true)) { $builder->where('e.category', $cat); }
        if ($evId)  { $builder->where('e.event_id', $evId); }
        if ($from)  { $builder->where('e.date >=', $from); }
        if ($to)    { $builder->where('e.date <=', $to); }

        // #2948 — the missing-receipt worklist. Fuel never needs one, and a waived row
        // has been dismissed on purpose, so neither counts as missing.
        if ($rcpt === 'missing') {
            $builder->whereNotIn('e.category', ['fuel', 'owner_draw'])   // no receipt for fuel or a draw to herself
                    ->where('e.receipt_waived', 0)
                    ->where('(SELECT COUNT(*) FROM expense_receipts r3 WHERE r3.expense_id = e.id) = 0', null, false);
        } elseif ($rcpt === 'attached') {
            $builder->where('(SELECT COUNT(*) FROM expense_receipts r3 WHERE r3.expense_id = e.id) > 0', null, false);
        }

        $expenses = $builder->orderBy('e.date', 'DESC')->get()->getResultArray();
        $total    = array_sum(array_column($expenses, 'amount'));

        $events = $db->table('events')
            ->select('id, event_date, venue, type')
            ->orderBy('event_date', 'DESC')
            ->get()->getResultArray();

        return view('admin/expenses/index', [
            'title'         => 'Expenses',
            'expenses'      => $expenses,
            'events'        => $events,
            'total'         => $total,
            'missingCount'  => $this->missingReceiptCount(),
            'filters'       => ['cat' => $cat, 'evId' => $evId, 'from' => $from, 'to' => $to, 'rcpt' => $rcpt],
        ]);
    }

    /** How many expenses still owe a receipt photo, fleet-wide for this app. */
    private function missingReceiptCount(): int
    {
        return (int) db_connect()->table('expenses e')
            ->whereNotIn('e.category', ['fuel', 'owner_draw'])   // no receipt for fuel or a draw to herself
            ->where('e.receipt_waived', 0)
            ->where('(SELECT COUNT(*) FROM expense_receipts r WHERE r.expense_id = e.id) = 0', null, false)
            ->countAllResults();
    }

    public function form($id = null)
    {
        if ($r = $this->guard()) { return $r; }

        $db      = db_connect();
        $expense = $id ? $db->table('expenses')->where('id', (int) $id)->get()->getRowArray() : null;

        // Pre-fill from a Plaid transaction when creating new
        $plaidTxn   = null;
        $plaidTxnId = (int) $this->request->getGet('plaid_txn_id');
        if (! $expense && $plaidTxnId) {
            $plaidTxn = $db->table('plaid_transactions')
                ->where('id', $plaidTxnId)->get()->getRowArray();
            if ($plaidTxn) {
                $expense = [
                    'id'           => null,
                    'date'         => $plaidTxn['date'],
                    'vendor'       => $plaidTxn['name'],
                    'category'     => self::guessCategory((string) $plaidTxn['category'], (string) $plaidTxn['name']),
                    'amount'       => abs((float) $plaidTxn['amount']),
                    'event_id'     => null,
                    'plaid_txn_id' => $plaidTxn['id'],
                    'notes'        => '',
                ];
            }
        }

        $receipts = $id
            ? $db->table('expense_receipts')->where('expense_id', (int) $id)->orderBy('id')->get()->getResultArray()
            : [];

        $events = $db->table('events')
            ->select('id, event_date, venue, type')
            ->orderBy('event_date', 'DESC')
            ->get()->getResultArray();

        return view('admin/expenses/form', [
            'title'    => $id ? 'Edit Expense' : 'New Expense',
            'expense'  => $expense,
            'events'   => $events,
            'plaidTxn' => $plaidTxn,
            'receipts' => $receipts,
        ]);
    }

    public function save()
    {
        if ($r = $this->guard()) { return $r; }

        if (! $this->validate([
            'date'     => 'required|valid_date[Y-m-d]',
            'amount'   => 'required|decimal|greater_than[0]',
            'category' => 'required|in_list[' . implode(',', self::CATEGORIES) . ']',
        ])) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $id      = (int) $this->request->getPost('id');
        $eventId = (int) $this->request->getPost('event_id') ?: null;
        $plaidId = (int) $this->request->getPost('plaid_txn_id') ?: null;

        $data = [
            'date'         => $this->request->getPost('date'),
            'vendor'       => trim((string) $this->request->getPost('vendor')),
            'category'     => $this->request->getPost('category'),
            'amount'       => (float) $this->request->getPost('amount'),
            'event_id'     => $eventId,
            'plaid_txn_id' => $plaidId,
            'notes'        => trim((string) $this->request->getPost('notes')),
        ];

        $db = db_connect();
        if ($id) {
            $db->table('expenses')->where('id', $id)->update($data);
            $msg = 'Expense updated.';
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $db->table('expenses')->insert($data);
            $id  = (int) $db->insertID();
            $msg = 'Expense saved.';
        }

        // #2948 — a photo chosen on the form is attached in the same submit, so the
        // receipt never depends on a second trip she might not make. A failure here
        // does NOT fail the save: an expense with no receipt still beats no expense.
        $receiptError = null;
        $attached     = 0;
        $files        = $this->request->getFileMultiple('receipts') ?: [];
        $files        = array_filter($files, static fn ($f) => $f && $f->getError() !== UPLOAD_ERR_NO_FILE);

        if ($files) {
            $store = new ReceiptStore();
            foreach ($files as $file) {
                $row = $store->store($file, $id);
                if (is_string($row)) { $receiptError = $row; continue; }
                $db->table('expense_receipts')->insert($row);
                $attached++;
            }
            if ($attached) {
                $msg .= ' ' . $attached . ' receipt photo' . ($attached === 1 ? '' : 's') . ' attached.';
                $db->table('expenses')->where('id', $id)
                   ->update(['receipt_waived' => 0, 'receipt_waived_reason' => null]);
            }
        }

        $stillMissing = ! $attached
            && ReceiptStore::requiredFor((string) $data['category'])
            && ! (int) $db->table('expense_receipts')->where('expense_id', $id)->countAllResults()
            && ! (int) ($db->table('expenses')->select('receipt_waived')->where('id', $id)->get()->getRowArray()['receipt_waived'] ?? 0);

        // Land back on the expense, not the list, when it still owes a receipt — the
        // nudge is the redirect, not a save-blocker.
        $to = $stillMissing
            ? redirect()->to('/admin/expenses/' . $id)
            : redirect()->to('/admin/expenses');

        $to = $to->with('msg', $msg . ($stillMissing ? ' This one still needs a receipt photo.' : ''));
        if ($receiptError) { $to = $to->with('error', $receiptError); }
        return $to;
    }

    /**
     * #2949 cheap win: Plaid already sends personal_finance_category (detailed, e.g.
     * GENERAL_MERCHANDISE_ONLINE_MARKETPLACES) and Bank::sync stores it in plaid_transactions.category,
     * but nothing read it. Map it to her 7 categories so the pre-filled form starts on a sensible one.
     * A vendor that looks like a flower wholesaler wins over the Plaid guess (Plaid files wholesalers
     * under general merchandise). Only a suggestion — she can change it before saving.
     */
    public static function guessCategory(string $plaidCat, string $vendor): string
    {
        if (preg_match('/flor|flower|bloom|wholesale|dutch|mayesh|stem|petal|greenhouse|nursery/i', $vendor)) { return 'flowers'; }
        $c = strtoupper($plaidCat);
        if (str_starts_with($c, 'TRANSPORTATION_GAS'))       { return 'fuel'; }
        if (str_starts_with($c, 'RENT_AND_UTILITIES'))       { return 'rent'; }
        if (str_contains($c, 'ADVERTISING') || str_contains($c, 'MARKETING')) { return 'marketing'; }
        if (str_starts_with($c, 'GENERAL_MERCHANDISE') || str_starts_with($c, 'HOME_IMPROVEMENT')) { return 'supplies'; }
        return 'other';
    }
}
