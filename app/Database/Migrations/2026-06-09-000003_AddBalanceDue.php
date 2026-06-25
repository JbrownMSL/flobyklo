<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * 50/50 payment (Andrew 2026-06-09): track the amount paid at booking vs the
 * balance collected at pickup.
 */
class AddBalanceDue extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('reservations', [
            'amount_paid' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0, 'after' => 'grand_total'],
            'balance_due' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0, 'after' => 'amount_paid'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('reservations', ['amount_paid', 'balance_due']);
    }
}
