<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Income (board #2946, Jason 2026-09-21): money IN that is not an invoice payment — a bank deposit
 * turned into income from the Bank page, or cash/Venmo entered by hand. payments.invoice_id is a
 * required FK, so a deposit with no invoice had nowhere to land. Reports count payments + income.
 */
class CreateIncome extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'date'         => ['type' => 'DATE'],
            'payer'        => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true],
            'category'     => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'sales'],
            'amount'       => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'client_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'event_id'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'plaid_txn_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'notes'        => ['type' => 'TEXT', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('date');
        $this->forge->addKey('plaid_txn_id');
        $this->forge->createTable('income', true);
    }

    public function down()
    {
        $this->forge->dropTable('income', true);
    }
}
