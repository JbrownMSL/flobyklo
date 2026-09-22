<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** #2947 follow-up: how income was received, so CASH booked on the Income page counts toward Form 8300. */
class AddIncomeMethod extends Migration
{
    public function up()
    {
        $this->forge->addColumn('income', [
            'method' => ['type' => 'ENUM', 'constraint' => ['cash', 'check', 'card', 'transfer', 'other'],
                         'default' => 'other', 'after' => 'amount'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('income', 'method');
    }
}
