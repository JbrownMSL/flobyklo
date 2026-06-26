<?php

namespace App\Controllers\Admin;

use App\Models\ExpenseModel;

class Expenses extends BaseAdmin
{
    private const CATEGORIES = ['flowers', 'supplies', 'fuel', 'rent', 'labor', 'marketing', 'other'];

    public function index()
    {
        if ($r = $this->guard()) { return $r; }

        $db      = db_connect();
        $builder = $db->table('expenses e')
            ->select('e.*, ev.event_date, ev.venue, ev.type AS event_type')
            ->join('events ev', 'ev.id = e.event_id', 'left');

        $cat   = (string) $this->request->getGet('category');
        $evId  = (int)    $this->request->getGet('event_id');
        $from  = (string) $this->request->getGet('from');
        $to    = (string) $this->request->getGet('to');

        if ($cat && in_array($cat, self::CATEGORIES, true)) { $builder->where('e.category', $cat); }
        if ($evId)  { $builder->where('e.event_id', $evId); }
        if ($from)  { $builder->where('e.date >=', $from); }
        if ($to)    { $builder->where('e.date <=', $to); }

        $expenses = $builder->orderBy('e.date', 'DESC')->get()->getResultArray();
        $total    = array_sum(array_column($expenses, 'amount'));

        $events = $db->table('events')
            ->select('id, event_date, venue, type')
            ->orderBy('event_date', 'DESC')
            ->get()->getResultArray();

        return view('admin/expenses/index', [
            'title'    => 'Expenses',
            'expenses' => $expenses,
            'events'   => $events,
            'total'    => $total,
            'filters'  => ['cat' => $cat, 'evId' => $evId, 'from' => $from, 'to' => $to],
        ]);
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
                    'category'     => 'other',
                    'amount'       => abs((float) $plaidTxn['amount']),
                    'event_id'     => null,
                    'plaid_txn_id' => $plaidTxn['id'],
                    'notes'        => '',
                ];
            }
        }

        $events = $db->table('events')
            ->select('id, event_date, venue, type')
            ->orderBy('event_date', 'DESC')
            ->get()->getResultArray();

        return view('admin/expenses/form', [
            'title'    => $id ? 'Edit Expense' : 'New Expense',
            'expense'  => $expense,
            'events'   => $events,
            'plaidTxn' => $plaidTxn,
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
            $msg = 'Expense saved.';
        }

        return redirect()->to('/admin/expenses')->with('msg', $msg);
    }
}
