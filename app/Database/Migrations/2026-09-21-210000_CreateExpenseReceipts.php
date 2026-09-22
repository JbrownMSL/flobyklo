<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * #2948 — receipt photo on every expense (required except fuel).
 *
 * Files live OUTSIDE the webroot (writable/receipts/YYYY/MM/) and are served only
 * through Admin\Receipts::show, so `stored_name` is the whole path input the app
 * ever trusts — the original filename is kept for display only and is never used
 * to build a path.
 *
 * `previewable` is 0 for a format GD cannot render (HEIC): the receipt is still
 * STORED and still satisfies the rule, we just cannot draw a thumbnail for it.
 * Rejecting it would lose the receipt, which is the opposite of the point.
 */
class CreateExpenseReceipts extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'expense_id'    => ['type' => 'INT', 'unsigned' => true],
            'stored_name'   => ['type' => 'VARCHAR', 'constraint' => 120],
            'original_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'mime'          => ['type' => 'VARCHAR', 'constraint' => 60],
            'bytes'         => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'sha256'        => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'previewable'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('expense_id');
        $this->forge->addUniqueKey('stored_name');
        $this->forge->createTable('expense_receipts', true);

        // A non-fuel expense that genuinely has no receipt (rent ACH, a cash tip) must be
        // dismissable, or the "missing receipts" worklist fills with rows that can never be
        // cleared and she stops trusting it.
        if (! $this->db->fieldExists('receipt_waived', 'expenses')) {
            $this->forge->addColumn('expenses', [
                'receipt_waived'        => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0, 'after' => 'notes'],
                'receipt_waived_reason' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'after' => 'receipt_waived'],
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropTable('expense_receipts', true);
        if ($this->db->fieldExists('receipt_waived', 'expenses')) {
            $this->forge->dropColumn('expenses', ['receipt_waived', 'receipt_waived_reason']);
        }
    }
}
