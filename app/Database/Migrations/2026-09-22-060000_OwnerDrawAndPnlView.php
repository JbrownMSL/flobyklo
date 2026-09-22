<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * #2950 — re-asserts the database half of owner distributions in CODE (it was applied by hand on
 * 2026-09-21 and survived, while the code half was lost to a concurrent rsync deploy). Idempotent:
 * MODIFY with the full enum, CREATE OR REPLACE the view.
 */
class OwnerDrawAndPnlView extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE expenses MODIFY category ENUM('flowers','supplies','fuel','rent','labor','marketing','other','owner_draw') NOT NULL DEFAULT 'other'");
        $this->db->query("CREATE OR REPLACE VIEW expenses_pnl AS SELECT * FROM expenses WHERE category <> 'owner_draw'");
    }

    public function down()
    {
        $this->db->query('DROP VIEW IF EXISTS expenses_pnl');
    }
}
