<?php

namespace App\Controllers;

use App\Libraries\Plaid;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;

class Plaid_ctl extends ResourceController
{
    use ResponseTrait;

    protected $plaid;

    public function __construct()
    {
        $this->plaid = new Plaid();
    }

    // GET /plaid/link → show Plaid Link page
    public function index()
    {
        return view('plaid/plaid_view');
    }
    public function plaidSidebar()
    {
        return view('plaid/plaid_sidebar');
    }

    // TEST 1: Verify access token works + get accounts
    public function test_accounts()
    {
        $db = db_connect();
        $conn = $db->table('gl.plaid_connections')
            ->orderBy('id', 'DESC')
            ->limit(1)
            ->get()
            ->getRowArray();

        if (!$conn) {
            return $this->response->setJSON(['error' => 'No bank connected yet']);
        }

        try {
            $plaid = new \App\Libraries\Plaid();
            $result = $plaid->getAccounts($conn['access_token']);

            return $this->response->setJSON([
                'institution' => $conn['institution_name'],
                'item_id'     => $conn['item_id'],
                'accounts'    => $result['accounts'],
                'item'        => $result['item']
            ], JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'error' => 'Accounts/get failed',
                'message' => $e->getMessage()
            ]);
        }
    }

    // TEST 2: Test Transactions Enrich endpoint (real-time categorization)
    public function test_enrich()
    {
        $db = db_connect();
        $conn = $db->table('gl.plaid_connections')
            ->orderBy('id', 'DESC')
            ->limit(1)
            ->get()
            ->getRowArray();

        if (!$conn) {
            return $this->response->setJSON(['error' => 'No bank connected yet']);
        }

        try {
            $plaid = new \App\Libraries\Plaid();

            // Get recent transactions first
            $txns = $plaid->getTransactions($conn['access_token'], date('Y-m-d', strtotime('-7 days')), date('Y-m-d'));

            if (empty($txns['transactions'])) {
                return $this->response->setJSON(['warning' => 'No recent transactions to enrich']);
            }

            // Pick the first 5 for testing
            $sample = array_slice($txns['transactions'], 0, 5);
            $preset = [
                [
                    'id' => '106',
                    'description' => 'PURCHASE WM SUPERCENTER #1700',  // EXACT FROM CSV
                    'amount' => 72.1,
                    'direction' => 'OUTFLOW',
                    'iso_currency_code' => 'USD',
                    'location' => ['city' => 'POWAY', 'region' => 'CA'],
                    'date_posted' => date('Y-m-d', strtotime('-3 days')),
                ],
                [
                    'id' => '117',
                    'description' => 'DD DOORDASH BURGERKIN 855-123-4567 CA',  // EXACT
                    'amount' => 28.34,
                    'direction' => 'OUTFLOW',
                    'iso_currency_code' => 'USD',
                    'date_posted' => date('Y-m-d', strtotime('-2 days')),
                ],
                [
                    'id' => '101',
                    'description' => 'GRUBHUBCHICKFILA',  // EXACT — no spaces!
                    'amount' => 30.28,
                    'direction' => 'OUTFLOW',
                    'iso_currency_code' => 'USD',
                ],
            ];

            $enrichResponse = $plaid->enrichTransactions($preset);
            //$enrichResponse = $plaid->enrichTransactions($sample);

            return $this->response->setJSON([
                'institution' => $conn['institution_name'],
                'enriched_count' => count($enrichResponse['enriched_transactions'] ?? []),
                'enriched' => $enrichResponse
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'error' => 'Enrich failed',
                'message' => $e->getMessage()
            ]);
        }
    }

    // Connect Bank Account - Load Plaid Link view
    public function plaid_connect()
    {
        return view('plaid/plaid_view');
    }
    public function plaid_delete_all()
    {
        $db = db_connect();
        $userId = session()->get('user_id');

        // Delete everything — forever
        $db->table('gl.plaid_connections')->where('user_id', $userId)->delete();
        $db->table('gl.plaid_transactions')->whereIn('connection_id',
            function($builder) use ($userId) {
                $builder->select('id')->from('gl.plaid_connections')->where('user_id', $userId);
            })->delete();

        log_message('critical', "User {$userId} requested full Plaid data deletion");

        return $this->respond(['success' => true, 'message' => 'All your bank data has been permanently deleted.']);
    }
    // ONE-CLICK MANUAL DOWNLOAD
    public function plaid_sync_now()
    {
        try {
            $result = $this->runDailySync();  // Reuse same logic as cron
            return $this->respond([
                'success' => true,
                'message' => "Sync complete! {$result['total']} new transactions downloaded.",
                'details' => $result
            ]);
        } catch (\Exception $e) {
            return $this->respond(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    /**
     * Runs the daily sync — used by both cron and "Download Now" button
     * @return array Summary of sync results
     */
    private function runDailySync()
    {
        $this->ensurePlaidTablesExist();

        $db = db_connect();
        $userId = session()->get('user_id') ?? null;

        $connections = $userId
            ? $db->table('gl.plaid_connections')->where('user_id', $userId)->get()->getResultArray()
            : $db->table('gl.plaid_connections')->get()->getResultArray();

        if (empty($connections)) {
            return ['total' => 0, 'message' => 'No bank connections'];
        }

        $totalNew = 0;

        foreach ($connections as $conn) {
            $hasData = $db->table('gl.plaid_transactions')
                    ->where('connection_id', $conn['id'])
                    ->countAllResults() > 0;

            $startDate = $hasData
                ? date('Y-m-d', strtotime('-1 day'))
                : date('Y-m-d', strtotime('-30 days'));
            $endDate = date('Y-m-d');

            try {
                $response = $this->plaid->getTransactions($conn['access_token'], $startDate, $endDate);
                $txns = $response['transactions'] ?? [];

                foreach ($txns as $txn) {
                    $data = [
                        'connection_id'        => $conn['id'],
                        'account_id'           => $txn['account_id'],
                        'transaction_id'       => $txn['transaction_id'],
                        'amount'               => $txn['amount'],
                        'date'                 => $txn['date'],
                        'authorized_date'      => $txn['authorized_date'] ?? null,
                        'name'                 => $txn['name'] ?? null,
                        'merchant_name'        => $txn['merchant_name'] ?? null,
                        'original_description' => $txn['original_description'] ?? null,
                        'payment_channel'      => $txn['payment_channel'] ?? null,
                        'category_detailed'    => $txn['personal_finance_category']['detailed'] ?? null,
                        'logo_url'             => $txn['logo_url'] ?? null,
                        'pending'              => $txn['pending'] ? 1 : 0,
                        'cleared'              => 0,
                    ];

                    $db->table('gl.plaid_transactions')
                        ->ignore(true)
                        ->insert($data);

                    if ($db->affectedRows()) $totalNew++;
                }
            } catch (\Exception $e) {
                log_message('error', "Sync failed: " . $e->getMessage());
            }
        }

        $msg = "Sync complete! {$totalNew} new transactions downloaded.";
        return ['total' => $totalNew, 'message' => $msg, 'details' => ['total' => $totalNew]];
    }

// SHOW LAST SYNC TIME + COUNT
    public function plaid_sync_status()
    {
        $db = db_connect();
        $last = $db->table('gl.plaid_transactions')
            ->select('COUNT(*) as count, MAX(created_at) as last_sync')
            ->get()
            ->getRowArray();

        return view('plaid/sync_status', [
            'total_transactions' => $last['count'] ?? 0,
            'last_sync' => $last['last_sync'] ?? 'Never'
        ]);
    }
    // Disconnect All Banks - Invalidate access tokens and delete from DB
    public function plaid_disconnect()
    {
        $db = db_connect();
        $connections = $db->table('gl.plaid_connections')
            ->where('user_id', session()->get('user_id'))
            ->get()
            ->getResultArray();

        foreach ($connections as $conn) {
            try {
                // THIS IS THE REQUIRED CALL
                $this->plaid->removeItem($conn['access_token']);
                log_message('info', "Plaid Item {$conn['item_id']} removed via /item/remove");
            } catch (\Exception $e) {
                // Even if it fails, token is likely dead anyway
                log_message('warning', "Failed to remove item {$conn['item_id']}: " . $e->getMessage());
            }
        }

        // Clean up your DB
        $db->table('gl.plaid_connections')
            ->where('user_id', session()->get('user_id'))
            ->delete();

        $db->table('gl.plaid_transactions')
            ->whereIn('connection_id', array_column($connections, 'id'))
            ->delete();

        return $this->respond(['success' => true, 'message' => 'All banks disconnected and removed from Plaid']);
    }

    // Manual Sync Now - Run transaction pull for all connections


    // Reconcile Uncleared Transactions - Load view with uncleared txns
    public function plaid_reconcile()
    {
        $db = db_connect();
        $uncleared = $db->table('gl.plaid_transactions t')
            ->select('t.*, c.institution_name')
            ->join('gl.plaid_connections c', 't.connection_id = c.id')
            ->where('t.cleared', 0)
            ->where('c.user_id', session()->get('user_id'))
            ->orderBy('t.date', 'DESC')
            ->get()
            ->getResultArray();

        return view('plaid/reconcile_view', ['uncleared' => $uncleared]);
    }

    // Manage Rules - List and edit rules
    public function plaid_rules()
    {
        $db = db_connect();
        $rules = $db->table('gl.plaid_rules')
            ->where('user_id', session()->get('user_id'))
            ->get()
            ->getResultArray();

        return view('plaid/rules_view', ['rules' => $rules]);
    }

    // View Connections - List all connected banks
    public function plaid_connections()
    {
        $db = db_connect();
        $connections = $db->table('gl.plaid_connections')
            ->where('user_id', session()->get('user_id'))
            ->get()
            ->getResultArray();

        return view('plaid/connections_view', ['connections' => $connections]);
    }

    // Raw Transactions - List all transactions
    public function plaid_raw_transactions()
    {
        $db = db_connect();
        $txns = $db->table('gl.plaid_transactions t')
            ->select('t.*, c.institution_name')
            ->join('gl.plaid_connections c', 't.connection_id = c.id')
            ->where('c.user_id', session()->get('user_id'))
            ->orderBy('t.date', 'DESC')
            ->limit(100) // Limit for performance
            ->get()
            ->getResultArray();

        return view('plaid/raw_txns_view', ['txns' => $txns]);
    }

    // View Sync Logs - Read CI log files or DB logs
    public function plaid_logs()
    {
        // Assuming CI logs in writable/logs/
        $logPath = WRITEPATH . 'logs/log-' . date('Y-m-d') . '.php';
        if (file_exists($logPath)) {
            $logs = file_get_contents($logPath);
            $logs = preg_replace('/^<\?php exit;/', '', $logs); // Clean header
            $logs = nl2br(esc($logs));
        } else {
            $logs = "No logs found for today.";
        }

        return view('plaid/log_view', ['logs' => $logs]);
    }
    // POST /plaid/create-link-token
    public function createLinkToken()
    {
        // Make sure no HTML/debug output leaks
        if (ob_get_length()) ob_clean();

        try {
            $userId = session()->get('user_id') ?? 'user_' . uniqid();

            $response = (new \App\Libraries\Plaid())->createLinkToken($userId);

            if (empty($response['link_token'])) {
                log_message('error', 'Plaid createLinkToken failed: ' . json_encode($response));
                return $this->response->setStatusCode(500)->setJSON(['error' => 'Failed to get link_token']);
            }

            return $this->response->setJSON(['link_token' => $response['link_token']]);

        } catch (\Throwable $e) {
            log_message('error', 'Plaid createLinkToken exception: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Server error']);
        }
    }

    // POST /plaid/exchange-token
    public function exchangeToken()
    {
        // READ JSON FIRST — THIS IS THE ONLY SOURCE OF TRUTH
        $input       = $this->request->getJSON(true);
        $publicToken = $input['public_token'] ?? null;

        // Fallback only if JSON failed (very rare)
        if (!$publicToken) {
            $publicToken = $this->request->getPost('public_token');
        }

        // STOP HERE — DO NOT RE-READ FROM POST LATER
        if (!$publicToken || strlen($publicToken) < 20) {
            log_message('error', 'Missing public_token. Raw input: ' . json_encode($this->request->getJSON()));
            return $this->fail('Invalid public_token', 400);
        }

        log_message('debug', 'Received valid public_token: ' . substr($publicToken, 0, 40) . '...');

        $userId = session()->get('user_id') ?? null;
        if (!$userId) {
            return $this->failUnauthorized('User not logged in');
        }

        try {
            $exchange = $this->plaid->exchangePublicToken($publicToken);

            if (empty($exchange['access_token']) || empty($exchange['item_id'])) {
                return $this->failServerError('Token exchange failed');
            }

            // FETCH FULL DETAILS TO GET INSTITUTION NAME
            $accountsResponse = $this->plaid->getAccounts($exchange['access_token']);
            $fullMetadata = [
                'institution' => [
                    'name' => $accountsResponse['item']['institution_name'] ?? $exchange['metadata']['institution']['name'] ?? 'Unknown Bank'
                ],
                'accounts' => $accountsResponse['accounts'] ?? $exchange['metadata']['accounts'] ?? []
            ];

            $this->storePlaidConnection(
                userId: $userId,
                accessToken: $exchange['access_token'],
                itemId: $exchange['item_id'],
                metadata: $fullMetadata  // Now has real name
            );

            return $this->respond(['success' => true, 'message' => 'Bank connected successfully', 'institution' => $fullMetadata['institution']['name']]);

        } catch (\Exception $e) {
            log_message('error', 'Exchange failed: ' . $e->getMessage());
            return $this->failServerError('Exchange failed: ' . $e->getMessage());
        }
    }

    // POST /plaid/transactions (for testing or manual pull)
    public function getTransactions()
    {
        $userId = session()->get('user_id');
        if (!$userId) return $this->failUnauthorized();

        $connections = $this->getUserConnections($userId);

        $allTransactions = [];
        $startDate = $this->request->getPost('start_date') ?? date('Y-m-d', strtotime('-2 days'));
        $endDate   = $this->request->getPost('end_date')   ?? date('Y-m-d');

        foreach ($connections as $conn) {
            try {
                $txns = $this->plaid->getTransactions($conn['access_token'], $startDate, $endDate);
                foreach ($txns['transactions'] as $txn) {
                    $txn['institution_name'] = $conn['institution_name'] ?? 'Unknown';
                    $allTransactions[] = $txn;
                }
            } catch (\Exception $e) {
                log_message('error', "Failed to fetch txns for item {$conn['item_id']}: " . $e->getMessage());
            }
        }

        return $this->respond(['transactions' => $allTransactions]);
    }

    // ================== PRIVATE HELPERS ==================

    private function storePlaidConnection($userId, $accessToken, $itemId, $metadata = null)
    {
        $this->ensurePlaidTablesExist();

        $db = db_connect();

        // Use real institution name from metadata
        $institutionName = $metadata['institution']['name'] ?? 'Unknown Bank';

        // Add account mask if available
        $mask = '';
        if (!empty($metadata['accounts'][0]['mask'])) {
            $mask = $metadata['accounts'][0]['mask'];
            $institutionName .= " (*{$mask})";
        }

        $data = [
            'user_id'          => $userId,
            'access_token'     => $accessToken,
            'item_id'          => $itemId,
            'institution_name' => $institutionName,
            'account_mask'     => $mask ?: null,
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ];

        $db->table('gl.plaid_connections')
            ->upsert($data, 'user_id, item_id');

        log_message('info', "Saved connection: {$institutionName} for user {$userId}");
    }

    private function getUserConnections($userId)
    {
        $db = db_connect();
        return $db->table('plaid_connections')
            ->where('user_id', $userId)
            ->get()
            ->getResultArray();
    }

    // GET /plaid/reconcile → Show uncleared txns view
    public function reconcile()
    {
        $userId = session()->get('user_id');
        if (!$userId) return $this->failUnauthorized();

        $db = db_connect();
        $uncleared = $db->table('plaid_transactions t')
            ->select('t.*, c.institution_name')
            ->join('plaid_connections c', 't.connection_id = c.id')
            ->where('t.cleared', 0)
            ->where('c.user_id', $userId)
            ->orderBy('t.date', 'DESC')
            ->get()
            ->getResultArray();

        return view('plaid/reconcile_view', ['uncleared' => $uncleared]);
    }

// POST /plaid/process-txn → Handle auto/manual/rule for a txn
    public function processTxn()
    {
        $rules = [
            'txn_id' => 'required|integer',
            'action' => 'required|in_list[auto,manual,rule,ignore]'
        ];

        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $txnId = $this->request->getPost('txn_id');
        $action = $this->request->getPost('action');
        $db = db_connect();

        $txn = $db->table('plaid_transactions')->where('id', $txnId)->get()->getRowArray();
        if (!$txn) return $this->failNotFound('Transaction not found');

        // Fetch rules
        $userRules = $db->table('plaid_rules')->where('user_id', session()->get('user_id'))->get()->getResultArray();

        $cleared = false;
        $ruleId = null;
        $manualData = null;

        if ($action === 'auto' || $action === 'rule') {
            foreach ($userRules as $rule) {
                $pattern = $rule['pattern'];
                $matchField = match ($rule['pattern_type']) {
                    'merchant' => $txn['merchant_name'],
                    'name' => $txn['name'],
                    'description' => $txn['original_description'],
                    'category' => $txn['category_detailed'],
                    default => null
                };

                if ($matchField && preg_match($pattern, $matchField)) {
                    // Apply rule action (e.g., post to GL)
                    $this->applyRuleAction($txn, $rule);  // Implement your GL posting logic here, similar to limestone_cc
                    $cleared = true;
                    $ruleId = $rule['id'];
                    break;
                }
            }
        } elseif ($action === 'manual') {
            // Get manual data from POST (e.g., gl_account, notes)
            $manualData = $this->request->getPost('manual_data');  // Expect JSON-like array
            $this->applyManualEntry($txn, $manualData);  // Implement posting
            $cleared = true;
        } elseif ($action === 'ignore') {
            $cleared = true;  // Just mark as cleared, no posting
        }

        if ($cleared) {
            $db->table('plaid_transactions')
                ->where('id', $txnId)
                ->update([
                    'cleared' => 1,
                    'reconciled_at' => date('Y-m-d H:i:s'),
                    'rule_id' => $ruleId,
                    'manual_entry' => $manualData ? json_encode($manualData) : null
                ]);
            return $this->respond(['success' => true, 'message' => 'Transaction cleared']);
        }

        return $this->respond(['success' => false, 'message' => 'No matching rule found']);
    }

// POST /plaid/create-rule → Add new rule
    public function createRule()
    {
        $rules = [
            'pattern_type' => 'required|in_list[merchant,name,description,category]',
            'pattern' => 'required',
            'action_type' => 'required|in_list[gl_post,ignore,flag]',
            'action_data' => 'required'  // JSON string
        ];

        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $data = [
            'user_id' => session()->get('user_id'),
            'pattern_type' => $this->request->getPost('pattern_type'),
            'pattern' => $this->request->getPost('pattern'),
            'action_type' => $this->request->getPost('action_type'),
            'action_data' => $this->request->getPost('action_data')
        ];

        db_connect()->table('plaid_rules')->insert($data);
        return $this->respondCreated(['message' => 'Rule created']);
    }

// Helpers: Customize these to your GL system (like in Jason_ctl's financial funcs)
    private function applyRuleAction($txn, $rule)
    {
        // e.g., Post to GL: Use $rule['action_data']['gl_account'], amount = $txn['amount'], etc.
        // Similar to your get_fin_info or limestone_cc logic
        log_message('info', "Auto-posting txn {$txn['transaction_id']} to GL via rule {$rule['id']}");
        // Insert into gl.history or whatever your table is
    }

    private function applyManualEntry($txn, $data)
    {
        // Similar, but use $data['gl_account'], etc.
        log_message('info', "Manual posting txn {$txn['transaction_id']}");
    }
    public function ensurePlaidTablesExist()
    {
        $db = db_connect();

        // 1. gl.plaid_connections — with all fields
        $db->query("CREATE TABLE IF NOT EXISTS `gl`.`plaid_connections` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `access_token` VARCHAR(512) NOT NULL,
        `item_id` VARCHAR(100) NOT NULL,
        `institution_name` VARCHAR(255) NULL,
        `account_mask` VARCHAR(20) NULL,
        `created_at` DATETIME NULL,
        `updated_at` DATETIME NULL,
        INDEX `idx_user_id` (`user_id`),
        UNIQUE KEY `uniq_user_item` (`user_id`, `item_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 2. gl.plaid_transactions — FULL schema with ALL fields
        $db->query("CREATE TABLE IF NOT EXISTS `gl`.`plaid_transactions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `connection_id` INT NOT NULL,
        `account_id` VARCHAR(255) NOT NULL,
        `transaction_id` VARCHAR(255) NOT NULL,
        `amount` DECIMAL(15,2) NOT NULL,
        `date` DATE NOT NULL,
        `authorized_date` DATE NULL,
        `name` VARCHAR(255) NULL,
        `merchant_name` VARCHAR(255) NULL,
        `original_description` TEXT NULL,
        `payment_channel` VARCHAR(50) NULL,
        `category_detailed` VARCHAR(255) NULL,
        `logo_url` VARCHAR(512) NULL,
        `pending` TINYINT(1) DEFAULT 0,
        `cleared` TINYINT(1) DEFAULT 0,
        `reconciled_at` DATETIME NULL,
        `rule_id` INT NULL,
        `manual_entry` JSON NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `uniq_conn_txn` (`connection_id`, `transaction_id`),
        INDEX `idx_date` (`date`),
        INDEX `idx_cleared` (`cleared`),
        INDEX `idx_connection` (`connection_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 3. gl.plaid_rules — for auto-matching
        $db->query("CREATE TABLE IF NOT EXISTS `gl`.`plaid_rules` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `pattern_type` ENUM('merchant','name','description','category') NOT NULL,
        `pattern` VARCHAR(255) NOT NULL,
        `action_type` ENUM('gl_post','ignore','flag') NOT NULL,
        `action_data` JSON NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        log_message('info', 'Plaid tables verified/created in gl schema');
    }

    // ── Flow-B review queue (in-DMS) ─────────────────────────────────────
    // Renders PlaidLedgerMatcher dry-run buckets per mapped account with Apply /
    // Reverse controls. Apply + reverse call the DOUBLE-GATED library methods, so
    // the browser cannot post until APPLY_ENABLED (code const) AND the account's
    // apply_enabled (DB) are both on. Admin / Office Mgr (ident 0/40) only.

    private function _plaidAdmin(): bool
    {
        helper('auth');
        return is_admin() || (int) user_ident() === 40;
    }

    /** Connect to another tenant's gl schema by sql_ip (same creds as the default group). */
    private function tenantConnect(string $sqlIp)
    {
        $d = config(\Config\Database::class)->default;
        return \Config\Database::connect([
            'hostname' => $sqlIp,
            'username' => $d['username'],
            'password' => $d['password'],
            'database' => 'gl',
            'DBDriver' => 'MySQLi',
            'DBPrefix' => '',
            'pConnect' => false,
            'DBDebug'  => false,
            'charset'  => 'utf8mb4',
            'DBCollat' => 'utf8mb4_general_ci',
        ], false);
    }

    /**
     * Resolve the DB for an apply/reverse action. Blank/same company → current tenant.
     * A different company (from the mslrv master view) → cross-tenant connect, OWNER ONLY.
     * Returns null if a non-owner attempts cross-tenant.
     */
    private function _companyDb(int $company)
    {
        helper(['auth', 'useful']);
        $cur = (int) (get_company()['company'] ?? 0);
        if ($company === 0 || $company === $cur) return db_connect();
        if (!is_admin()) return null;                      // cross-tenant = owner only
        $row = \Config\Database::connect('company')
            ->query("SELECT sql_ip FROM login.Company_names WHERE company = ?", [$company])
            ->getRowArray();
        return $row ? $this->tenantConnect($row['sql_ip']) : null;
    }

    /** Build the per-account dry-run list for one tenant DB (or [] if no map/accounts). */
    private function _queueForDb($db, int $window): array
    {
        if (!$db->query("SHOW TABLES IN gl LIKE 'plaid_account_map'")->getRowArray()) return [];
        $accts = $db->query(
            "SELECT plaid_account_id, account_label, target_gl_account, flow_type, apply_enabled
               FROM gl.plaid_account_map WHERE active = 1 ORDER BY id"
        )->getResultArray();
        if (!$accts) return [];
        $matcher = new \App\Libraries\PlaidLedgerMatcher($db);
        $list = [];
        foreach ($accts as $a) {
            $a['recent_batches'] = $db->query(
                "SELECT batch, MAX(created_at) ts, COUNT(*) n FROM gl.plaid_recon_log
                  WHERE account_id = ? AND action IN ('clear','create','clear_item')
                  GROUP BY batch ORDER BY ts DESC LIMIT 5",
                [$a['plaid_account_id']]
            )->getResultArray();
            $list[] = ['map' => $a, 'res' => $matcher->dryRunAccount(
                $a['plaid_account_id'], (int) $a['target_gl_account'], $a['flow_type'], $window
            )];
        }
        return $list;
    }

    /**
     * Review queue — ONE company at a time. Owner gets a company selector (switch
     * to any tenant); a non-owner (Office Mgr) is pinned to their own company.
     */
    public function reconcileQueue()
    {
        if (!$this->_plaidAdmin()) {
            return $this->response->setStatusCode(403)->setBody('<div class="alert alert-danger m-3">Not authorized.</div>');
        }
        helper('useful');
        $cinfo  = get_company();
        $cur    = (int) ($cinfo['company'] ?? 0);
        $window = (int) ($this->request->getGet('window') ?: 3);

        // Owner may switch companies; everyone else is locked to their own.
        $companies = [];
        if (is_admin()) {
            $companies = \Config\Database::connect('company')
                ->query("SELECT company, name FROM login.Company_names WHERE company <> 99 ORDER BY company")
                ->getResultArray();
            $sel = (int) ($this->request->getGet('company') ?: $cur);
        } else {
            $sel = $cur;
        }

        // Resolve the selected company's DB (own tenant, or owner cross-tenant).
        $name = $cinfo['name'] ?? 'this company';
        $db   = db_connect();
        if ($sel !== $cur) {
            $alt = $this->_companyDb($sel);
            if ($alt === null) { $sel = $cur; }
            else {
                $db = $alt;
                foreach ($companies as $c) if ((int) $c['company'] === $sel) $name = $c['name'];
            }
        }

        $accounts = [];
        try { $accounts = $this->_queueForDb($db, $window); }
        catch (\Throwable $e) { log_message('error', 'reconcileQueue co ' . $sel . ': ' . $e->getMessage()); }

        return view('plaid/reconcile_queue', [
            'accounts'      => $accounts,
            'company'       => $sel,
            'companyName'   => $name,
            'companies'     => $companies,   // [] for non-owner → no selector
            'window'        => $window,
            'apply_enabled' => \App\Libraries\PlaidLedgerMatcher::APPLY_ENABLED,
        ]);
    }

    public function applyQueue()
    {
        if (!$this->_plaidAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'error' => 'Not authorized']);
        }
        $accountId = (string) $this->request->getPost('account_id');
        $company   = (int) ($this->request->getPost('company') ?: 0);
        $db = $this->_companyDb($company);
        if ($db === null) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Cross-company apply is owner-only']);
        }
        $map = $db->query(
            "SELECT account_label, target_gl_account, flow_type FROM gl.plaid_account_map WHERE plaid_account_id = ? AND active = 1",
            [$accountId]
        )->getRowArray();
        if (!$map) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Unknown account']);
        }
        $matcher = new \App\Libraries\PlaidLedgerMatcher($db);
        try {
            $res = $matcher->applyAccount(
                $accountId, (int) $map['target_gl_account'], $map['flow_type'],
                (int) user_id(), (int) ($this->request->getPost('window') ?: 3)
            );
            return $this->response->setJSON(['ok' => true] + $res);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['ok' => false, 'gated' => true, 'error' => $e->getMessage()]);
        }
    }

    public function reverseQueue()
    {
        if (!$this->_plaidAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'error' => 'Not authorized']);
        }
        $batch   = trim((string) $this->request->getPost('batch'));
        $company = (int) ($this->request->getPost('company') ?: 0);
        if ($batch === '') {
            return $this->response->setJSON(['ok' => false, 'error' => 'No batch id']);
        }
        $db = $this->_companyDb($company);
        if ($db === null) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Cross-company reverse is owner-only']);
        }
        try {
            $matcher = new \App\Libraries\PlaidLedgerMatcher($db);
            $res = $matcher->reverseBatch($batch, (int) user_id());
            return $this->response->setJSON(['ok' => true] + $res);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    /** Per-item Clear/Post — apply ONE bank txn (matcher decides clear-vs-post by its bucket). Double-gated. */
    public function applyItem()
    {
        if (!$this->_plaidAdmin()) return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'error' => 'Not authorized']);
        $accountId = (string) $this->request->getPost('account_id');
        $txnId     = (int) $this->request->getPost('txn_id');
        $company   = (int) ($this->request->getPost('company') ?: 0);
        $db = $this->_companyDb($company);
        if ($db === null) return $this->response->setJSON(['ok' => false, 'error' => 'Cross-company apply is owner-only']);
        $map = $db->query(
            "SELECT target_gl_account, flow_type FROM gl.plaid_account_map WHERE plaid_account_id = ? AND active = 1",
            [$accountId]
        )->getRowArray();
        if (!$map) return $this->response->setJSON(['ok' => false, 'error' => 'Unknown account']);
        try {
            $matcher = new \App\Libraries\PlaidLedgerMatcher($db);
            $res = $matcher->applyOne(
                $accountId, (int) $map['target_gl_account'], $map['flow_type'], $txnId,
                (int) user_id(), (int) ($this->request->getPost('window') ?: 3)
            );
            return $this->response->setJSON(['ok' => true] + $res);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['ok' => false, 'gated' => true, 'error' => $e->getMessage()]);
        }
    }

    /** Per-item "Post once" — manual one-off post of a txn to a chosen offset GL. Double-gated. */
    public function postOnceItem()
    {
        if (!$this->_plaidAdmin()) return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'error' => 'Not authorized']);
        $accountId = (string) $this->request->getPost('account_id');
        $txnId     = (int) $this->request->getPost('txn_id');
        $offsetGl  = (int) $this->request->getPost('offset_gl');
        $company   = (int) ($this->request->getPost('company') ?: 0);
        if ($offsetGl <= 0) return $this->response->setJSON(['ok' => false, 'error' => 'Offset GL account required']);
        $db = $this->_companyDb($company);
        if ($db === null) return $this->response->setJSON(['ok' => false, 'error' => 'Cross-company apply is owner-only']);
        $map = $db->query(
            "SELECT target_gl_account FROM gl.plaid_account_map WHERE plaid_account_id = ? AND active = 1",
            [$accountId]
        )->getRowArray();
        if (!$map) return $this->response->setJSON(['ok' => false, 'error' => 'Unknown account']);
        try {
            $matcher = new \App\Libraries\PlaidLedgerMatcher($db);
            $res = $matcher->postManual($accountId, (int) $map['target_gl_account'], $txnId, $offsetGl, (int) user_id());
            return $this->response->setJSON(['ok' => true] + $res);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['ok' => false, 'gated' => true, 'error' => $e->getMessage()]);
        }
    }

    /** Define a reusable Flow-B merchant rule (gl.plaid_merchant_rules). NOT gated — seeds a rule, posts nothing. */
    public function defineRule()
    {
        if (!$this->_plaidAdmin()) return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'error' => 'Not authorized']);
        $company = (int) ($this->request->getPost('company') ?: 0);
        $db = $this->_companyDb($company);
        if ($db === null) return $this->response->setJSON(['ok' => false, 'error' => 'Cross-company rule is owner-only']);
        $pattern = trim((string) $this->request->getPost('pattern'));
        $target  = (int) $this->request->getPost('target_gl_account');
        if ($pattern === '' || $target <= 0) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Pattern + target GL account required']);
        }
        $field  = (string) $this->request->getPost('match_field');
        $pm     = (string) $this->request->getPost('pattern_match');
        $sign   = (string) $this->request->getPost('amount_sign');
        $conf   = (string) $this->request->getPost('confidence');
        $vendor = (int) $this->request->getPost('ap_vendor_account');
        $db->table('gl.plaid_merchant_rules')->insert([
            'pattern'           => substr($pattern, 0, 120),
            'pattern_match'     => in_array($pm, ['LIKE', 'EXACT', 'REGEX'], true) ? $pm : 'LIKE',
            'match_field'       => in_array($field, ['name', 'merchant_name', 'either', 'category'], true) ? $field : 'either',
            'amount_sign'       => in_array($sign, ['debit', 'credit', 'any'], true) ? $sign : 'any',
            'target_gl_account' => $target,
            'ap_vendor_account' => $vendor > 0 ? $vendor : null,
            'priority'          => (int) ($this->request->getPost('priority') ?: 100),
            'confidence'        => in_array($conf, ['high', 'medium', 'low'], true) ? $conf : 'medium',
            'active'            => 1,
            'created_at'        => date('Y-m-d H:i:s'),
        ]);
        return $this->response->setJSON(['ok' => true, 'rule_id' => (int) $db->insertID()]);
    }
}