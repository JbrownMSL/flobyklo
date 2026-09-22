<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * #2944: plaid_accounts (the per-account enable rule, keyed by mask on re-link) and
 * plaid_transactions.account_id. Both were first created by hand on vader 2026-09-21; this
 * records them so the schema is reproducible. Idempotent — a no-op where they already exist.
 */
class PlaidAccounts extends Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS plaid_accounts (
            id int NOT NULL AUTO_INCREMENT,
            plaid_connection_id int NOT NULL,
            account_id varchar(64) NOT NULL,
            name varchar(191) DEFAULT NULL,
            official_name varchar(191) DEFAULT NULL,
            mask varchar(16) DEFAULT NULL,
            type varchar(32) DEFAULT NULL,
            subtype varchar(32) DEFAULT NULL,
            enabled tinyint(1) NOT NULL DEFAULT 0,
            note varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT NULL,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_plaid_account (account_id),
            KEY idx_conn_enabled (plaid_connection_id, enabled)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        if (! $this->db->fieldExists('account_id', 'plaid_transactions')) {
            $this->db->query("ALTER TABLE plaid_transactions ADD COLUMN account_id varchar(64) NULL AFTER plaid_connection_id");
        }
    }

    public function down()
    {
    }
}
