<?php

namespace App\Controllers\Admin;

/**
 * Plaid bank connection + transaction categorization.
 * TODO P3: port App\Libraries\Plaid from DMS + plaid/link view.
 * Keys: fbk.plaid.clientId / fbk.plaid.secret / fbk.plaid.env in vader .env.
 */
class Bank extends BaseAdmin
{
    public function index()
    {
        if ($r = $this->guard()) { return $r; }
        $db          = db_connect();
        $connections = $db->table('plaid_connections')->get()->getResultArray();
        $transactions = $connections
            ? $db->table('plaid_transactions')->orderBy('date', 'DESC')->limit(100)->get()->getResultArray()
            : [];
        return view('admin/bank/index', [
            'title'        => 'Bank',
            'connections'  => $connections,
            'transactions' => $transactions,
        ]);
    }

    public function linkToken()
    {
        if ($r = $this->guard()) { return $r; }
        // TODO P3: call Plaid /link/token/create, return JSON
        return $this->response->setJSON(['error' => 'Plaid module not yet implemented.'])->setStatusCode(501);
    }

    public function exchange()
    {
        if ($r = $this->guard()) { return $r; }
        // TODO P3: exchange public_token → access_token, persist plaid_connections
        return redirect()->to('/admin/bank')->with('error', 'Plaid module not yet implemented.');
    }

    public function sync()
    {
        if ($r = $this->guard()) { return $r; }
        // TODO P3: fetch transactions, upsert plaid_transactions
        return redirect()->to('/admin/bank')->with('error', 'Plaid module not yet implemented.');
    }

    public function toExpense(int $txnId)
    {
        if ($r = $this->guard()) { return $r; }
        // TODO P3: create expense from plaid_transaction
        return redirect()->to('/admin/expenses/new')->with('error', 'Plaid → Expense not yet implemented.');
    }
}
