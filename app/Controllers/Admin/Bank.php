<?php

namespace App\Controllers\Admin;

use App\Libraries\Plaid;
use App\Models\PlaidConnectionModel;
use App\Models\PlaidTransactionModel;

class Bank extends BaseAdmin
{
    public function index()
    {
        if ($r = $this->guard()) { return $r; }

        $connModel   = new PlaidConnectionModel();
        $txnModel    = new PlaidTransactionModel();
        $connections = $connModel->findAll();
        $transactions = $connections
            ? $txnModel->orderBy('date', 'DESC')->limit(100)->findAll()
            : [];

        return view('admin/bank/index', [
            'title'        => 'Bank / Plaid',
            'connections'  => $connections,
            'transactions' => $transactions,
        ]);
    }

    public function linkToken()
    {
        if ($r = $this->guard()) { return $r; }

        if (ob_get_length()) { ob_clean(); }

        try {
            $userId   = (string) auth()->id();
            $plaid    = new Plaid();
            $response = $plaid->createLinkToken($userId);

            if (empty($response['link_token'])) {
                log_message('error', 'Plaid linkToken missing link_token: ' . json_encode($response));
                return $this->response->setStatusCode(500)->setJSON(['error' => 'Failed to get link_token']);
            }

            return $this->response->setJSON(['link_token' => $response['link_token']]);
        } catch (\Throwable $e) {
            log_message('error', 'Plaid linkToken: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['error' => $e->getMessage()]);
        }
    }

    public function exchange()
    {
        if ($r = $this->guard()) { return $r; }

        $input       = $this->request->getJSON(true) ?? [];
        $publicToken = $input['public_token'] ?? $this->request->getPost('public_token');

        if (empty($publicToken) || strlen((string) $publicToken) < 20) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid public_token']);
        }

        try {
            $plaid    = new Plaid();
            $exchange = $plaid->exchangePublicToken($publicToken);

            if (empty($exchange['access_token']) || empty($exchange['item_id'])) {
                return $this->response->setStatusCode(500)->setJSON(['error' => 'Token exchange failed']);
            }

            $accountsResp = $plaid->getAccounts($exchange['access_token']);
            $firstAccount = $accountsResp['accounts'][0] ?? [];

            $connModel = new PlaidConnectionModel();
            $existing  = $connModel->where('item_id', $exchange['item_id'])->first();

            $row = [
                'item_id'      => $exchange['item_id'],
                'access_token' => $exchange['access_token'],
                'account_id'   => $firstAccount['account_id'] ?? '',
                'name'         => $firstAccount['name'] ?? 'Unknown Bank',
                'mask'         => $firstAccount['mask'] ?? null,
                'created_at'   => date('Y-m-d H:i:s'),
            ];

            if ($existing) {
                $connModel->update($existing['id'], $row);
            } else {
                $connModel->insert($row);
            }

            return $this->response->setJSON(['success' => true, 'institution' => $row['name']]);
        } catch (\Throwable $e) {
            log_message('error', 'Plaid exchange: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['error' => $e->getMessage()]);
        }
    }

    public function sync()
    {
        if ($r = $this->guard()) { return $r; }

        try {
            $connModel   = new PlaidConnectionModel();
            $connections = $connModel->findAll();

            if (empty($connections)) {
                return $this->response->setJSON(['success' => false, 'error' => 'No bank connections']);
            }

            $plaid     = new Plaid();
            $db        = db_connect();
            $startDate = date('Y-m-d', strtotime('-30 days'));
            $endDate   = date('Y-m-d');
            $newCount  = 0;

            foreach ($connections as $conn) {
                try {
                    $resp = $plaid->getTransactions($conn['access_token'], $startDate, $endDate);
                    foreach ($resp['transactions'] ?? [] as $txn) {
                        $category = $txn['personal_finance_category']['detailed']
                            ?? ($txn['category'][0] ?? null);

                        $db->table('plaid_transactions')->ignore(true)->insert([
                            'plaid_connection_id' => $conn['id'],
                            'txn_id'              => $txn['transaction_id'],
                            'date'                => $txn['date'],
                            'amount'              => $txn['amount'],
                            'name'                => $txn['name'] ?? null,
                            'category'            => $category,
                            'pending'             => $txn['pending'] ? 1 : 0,
                            'created_at'          => date('Y-m-d H:i:s'),
                        ]);
                        if ($db->affectedRows()) {
                            $newCount++;
                        }
                    }
                } catch (\Throwable $e) {
                    log_message('error', 'Plaid sync conn ' . $conn['id'] . ': ' . $e->getMessage());
                }
            }

            return $this->response->setJSON(['success' => true, 'synced' => $newCount]);
        } catch (\Throwable $e) {
            log_message('error', 'Plaid sync: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['error' => $e->getMessage()]);
        }
    }

    public function toExpense(int $txnId)
    {
        if ($r = $this->guard()) { return $r; }

        // #2946: this used to stash a 'plaid_prefill' flash that NOTHING read, and redirect to a
        // bare /expenses/new — so the button always landed on an EMPTY form (Jason 2026-09-21).
        // Expenses::form pre-fills from ?plaid_txn_id, so pass it.
        return redirect()->to('/admin/expenses/new?plaid_txn_id=' . (int) $txnId);
    }

    public function toIncome(int $txnId)
    {
        if ($r = $this->guard()) { return $r; }
        return redirect()->to('/admin/income/new?plaid_txn_id=' . (int) $txnId);
    }

    /**
     * POST /admin/bank/txn/(:num)/expense-photo — #2948 one-tap: snap the receipt on
     * the bank row and get a booked expense with the photo already on it.
     *
     * The plain "+ Expense" button only pre-fills the form, so a photo chosen there
     * would be lost on the redirect. This books the expense from the transaction
     * immediately, attaches the photo, and lands on the edit form for category/event
     * — so the receipt is captured at the moment she is looking at the charge.
     */
    public function toExpenseWithPhoto(int $txnId)
    {
        if ($r = $this->guard()) { return $r; }

        $file = $this->request->getFile('receipt');
        if (! $file || $file->getError() === UPLOAD_ERR_NO_FILE) {
            // No photo picked — behave exactly like the plain button.
            return redirect()->to('/admin/expenses/new?plaid_txn_id=' . $txnId);
        }

        $db  = db_connect();
        $txn = $db->table('plaid_transactions')->where('id', $txnId)->get()->getRowArray();
        if (! $txn) {
            return redirect()->to('/admin/bank')->with('error', 'Transaction not found.');
        }

        // Don't book the same transaction twice — reuse the expense if one exists.
        $existing = $db->table('expenses')->where('plaid_txn_id', $txnId)->get()->getRowArray();
        if ($existing) {
            $expenseId = (int) $existing['id'];
        } else {
            $db->table('expenses')->insert([
                'date'         => $txn['date'],
                'vendor'       => $txn['name'],
                'category'     => 'other',
                'amount'       => abs((float) $txn['amount']),
                'plaid_txn_id' => $txnId,
                'notes'        => '',
                'created_at'   => date('Y-m-d H:i:s'),
            ]);
            $expenseId = (int) $db->insertID();
        }

        $store = new \App\Libraries\ReceiptStore();
        $row   = $store->store($file, $expenseId);

        $to = redirect()->to('/admin/expenses/' . $expenseId);
        if (is_string($row)) {
            // The expense is booked either way — losing the photo must not lose the entry.
            return $to->with('error', $row)
                      ->with('msg', 'Expense booked from the bank transaction, but the photo did not save.');
        }

        $db->table('expense_receipts')->insert($row);

        return $to->with('msg', 'Expense booked from the bank transaction with its receipt photo. Set the category below.');
    }
}
