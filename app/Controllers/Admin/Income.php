<?php

namespace App\Controllers\Admin;

/**
 * Income (board #2946) — the money-IN twin of Expenses. Jason 2026-09-21: bank transactions get an
 * Income button and an Expense button that "load automatically ... not load me to an empty form".
 */
class Income extends BaseAdmin
{
    private const CATEGORIES = ['sales', 'market', 'deposit', 'pop-up', 'refund', 'other'];

    public function index()
    {
        if ($r = $this->guard()) { return $r; }
        $db   = db_connect();
        $from = (string) $this->request->getGet('from');
        $to   = (string) $this->request->getGet('to');
        $b    = $db->table('income i')->select('i.*, c.name AS client_name')
                   ->join('clients c', 'c.id = i.client_id', 'left');
        if ($from) { $b->where('i.date >=', $from); }
        if ($to)   { $b->where('i.date <=', $to); }
        $rows = $b->orderBy('i.date', 'DESC')->get()->getResultArray();

        return view('admin/income/index', [
            'title'   => 'Income',
            'rows'    => $rows,
            'total'   => array_sum(array_column($rows, 'amount')),
            'filters' => ['from' => $from, 'to' => $to],
        ]);
    }

    public function form($id = null)
    {
        if ($r = $this->guard()) { return $r; }
        $db  = db_connect();
        $row = $id ? $db->table('income')->where('id', (int) $id)->get()->getRowArray() : null;

        $plaidTxn   = null;
        $plaidTxnId = (int) $this->request->getGet('plaid_txn_id');
        if (! $row && $plaidTxnId) {
            $plaidTxn = $db->table('plaid_transactions')->where('id', $plaidTxnId)->get()->getRowArray();
            if ($plaidTxn) {
                $row = [
                    'id' => null, 'date' => $plaidTxn['date'], 'payer' => $plaidTxn['name'],
                    'category' => $this->suggestCategory($db, $plaidTxn),
                    'amount' => abs((float) $plaidTxn['amount']),
                    'method' => 'transfer', 'client_id' => null, 'event_id' => null, 'plaid_txn_id' => $plaidTxn['id'], 'notes' => '',
                ];
            }
        }

        return view('admin/income/form', [
            'title'      => $id ? 'Edit Income' : 'New Income',
            'row'        => $row,
            'plaidTxn'   => $plaidTxn,
            'categories' => self::CATEGORIES,
            'clients'    => $db->table('clients')->select('id, name')->orderBy('name')->get()->getResultArray(),
            'events'     => $db->table('events')->select('id, event_date, venue, type')->orderBy('event_date', 'DESC')->get()->getResultArray(),
        ]);
    }

    /**
     * #2949 (Jason 2026-09-21): "if you import several Venmo incomes all in the same day, tag them as
     * income from market." A SUGGESTION only — she can change it (two wedding instalments on one day
     * would trip the same test). Venmo wins over Plaid's label: Plaid tags every Venmo row
     * TRANSFER_*_ACCOUNT_TRANSFER, so treating transfers as 'other' would hide her market revenue.
     * Credits and debits are counted separately (a same-day -150/+150 Venmo wash is not a market).
     */
    private function suggestCategory($db, array $txn): string
    {
        if (stripos((string) $txn['name'], 'venmo') !== false) {
            $sameDay = $db->table('plaid_transactions')->where('date', $txn['date'])
                ->like('name', 'venmo', 'both', null, true)->where('amount <', 0)->countAllResults();
            return $sameDay >= 2 ? 'market' : 'sales';
        }
        // A plain account-to-account move of her own money is not sales.
        return str_starts_with(strtoupper((string) $txn['category']), 'TRANSFER_IN') ? 'other' : 'sales';
    }

    public function save()
    {
        if ($r = $this->guard()) { return $r; }
        if (! $this->validate([
            'date'     => 'required|valid_date[Y-m-d]',
            'amount'   => 'required|decimal|greater_than[0]',
            'category' => 'required|in_list[' . implode(',', self::CATEGORIES) . ']',
        ])) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }
        $id   = (int) $this->request->getPost('id');
        $data = [
            'date'         => $this->request->getPost('date'),
            'payer'        => trim((string) $this->request->getPost('payer')),
            'category'     => $this->request->getPost('category'),
            'amount'       => (float) $this->request->getPost('amount'),
            'method'       => in_array($this->request->getPost('method'), ['cash', 'check', 'card', 'transfer', 'other'], true)
                                ? $this->request->getPost('method') : 'other',
            'client_id'    => (int) $this->request->getPost('client_id') ?: null,
            'event_id'     => (int) $this->request->getPost('event_id') ?: null,
            'plaid_txn_id' => (int) $this->request->getPost('plaid_txn_id') ?: null,
            'notes'        => trim((string) $this->request->getPost('notes')),
        ];
        $db = db_connect();
        if ($id) {
            $db->table('income')->where('id', $id)->update($data);
            $msg = 'Income updated.';
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $db->table('income')->insert($data);
            $msg = 'Income saved.';
        }
        return redirect()->to('/admin/income')->with('msg', $msg);
    }
}
