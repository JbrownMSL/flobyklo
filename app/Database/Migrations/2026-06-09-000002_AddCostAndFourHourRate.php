<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Andrew's sheet carries per-item Cost (drives the $300/$500 deposit rule) and
 * a 4-Hour Rate alongside daily/weekly. Add both to equipment. Additive only.
 */
class AddCostAndFourHourRate extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('equipment', [
            'cost'            => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true, 'after' => 'description'],
            'four_hour_rate'  => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true, 'after' => 'daily_rate'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('equipment', ['cost', 'four_hour_rate']);
    }
}
