<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Weekend Tool Rentals — core schema (all phases).
 * Shield tables (users, auth_identities, …) are created by Shield's own
 * migrations; run `php spark migrate --all`. user_id columns reference
 * users.id (int unsigned) via index, not a hard cross-namespace FK.
 */
class CreateWtrCore extends Migration
{
    public function up(): void
    {
        $f = $this->forge;

        // --- customer_profiles ------------------------------------------------
        $f->addField([
            'id'                => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'           => ['type' => 'INT', 'unsigned' => true],
            'full_name'         => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'phone'             => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'address'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'city'              => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'state'             => ['type' => 'VARCHAR', 'constraint' => 2, 'null' => true],
            'zip'               => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'age_attested'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'square_customer_id'=> ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $f->addKey('id', true);
        $f->addUniqueKey('user_id');
        $f->createTable('customer_profiles');

        // --- categories -------------------------------------------------------
        $f->addField([
            'id'        => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'      => ['type' => 'VARCHAR', 'constraint' => 100],
            'slug'      => ['type' => 'VARCHAR', 'constraint' => 120],
            'sort'      => ['type' => 'INT', 'default' => 0],
            'active'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $f->addKey('id', true);
        $f->addUniqueKey('slug');
        $f->createTable('categories');

        // --- equipment --------------------------------------------------------
        $f->addField([
            'id'                  => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'category_id'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'sku'                 => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'name'                => ['type' => 'VARCHAR', 'constraint' => 150],
            'slug'                => ['type' => 'VARCHAR', 'constraint' => 180],
            'description'         => ['type' => 'TEXT', 'null' => true],
            'daily_rate'          => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'weekly_rate'         => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],   // null => computed (6× daily)
            'min_days'            => ['type' => 'INT', 'default' => 1],
            'quantity'            => ['type' => 'INT', 'default' => 1],                                 // count-based availability
            'damage_deposit'      => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],     // null => % of rental (Config\Wtr)
            'tax_class'           => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'equipment_rental'],
            'requires_trailer_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],             // auto-add this trailer item
            'is_trailer'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_dangerous'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'active'              => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $f->addKey('id', true);
        $f->addUniqueKey('slug');
        $f->addKey('category_id');
        $f->createTable('equipment');

        // --- equipment_media --------------------------------------------------
        $f->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'equipment_id' => ['type' => 'INT', 'unsigned' => true],
            'kind'         => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'image'], // image|video|safety_doc
            'url'          => ['type' => 'VARCHAR', 'constraint' => 255],
            'caption'      => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'sort'         => ['type' => 'INT', 'default' => 0],
        ]);
        $f->addKey('id', true);
        $f->addKey('equipment_id');
        $f->createTable('equipment_media');

        // --- equipment_safety -------------------------------------------------
        $f->addField([
            'id'                  => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'equipment_id'        => ['type' => 'INT', 'unsigned' => true],
            'ppe_required'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'safety_instructions' => ['type' => 'TEXT', 'null' => true],
            'safety_video_url'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $f->addKey('id', true);
        $f->addUniqueKey('equipment_id');
        $f->createTable('equipment_safety');

        // --- reservations -----------------------------------------------------
        $f->addField([
            'id'                     => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'                => ['type' => 'INT', 'unsigned' => true],
            'status'                 => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'], // pending|confirmed|picked_up|returned|cancelled
            'start_date'             => ['type' => 'DATE'],
            'end_date'               => ['type' => 'DATE'],
            'rental_subtotal'        => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'waiver_amount'          => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'tax_total'              => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'grand_total'            => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'damage_deposit'         => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'damage_waiver'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'contract_acceptance_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'             => ['type' => 'DATETIME', 'null' => true],
            'updated_at'             => ['type' => 'DATETIME', 'null' => true],
        ]);
        $f->addKey('id', true);
        $f->addKey('user_id');
        $f->addKey('status');
        $f->createTable('reservations');

        // --- reservation_items ------------------------------------------------
        $f->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'reservation_id' => ['type' => 'INT', 'unsigned' => true],
            'equipment_id'   => ['type' => 'INT', 'unsigned' => true],
            'qty'            => ['type' => 'INT', 'default' => 1],
            'days'           => ['type' => 'INT', 'default' => 1],
            'rate_snapshot'  => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0], // per-unit total for the period
            'line_subtotal'  => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'tax_class'      => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'equipment_rental'],
            'tax_amount'     => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'is_trailer'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'auto_added'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
        ]);
        $f->addKey('id', true);
        $f->addKey('reservation_id');
        $f->addKey('equipment_id');
        $f->createTable('reservation_items');

        // --- unit_blackouts (availability source of truth) --------------------
        $f->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'equipment_id'   => ['type' => 'INT', 'unsigned' => true],
            'qty'            => ['type' => 'INT', 'default' => 1],
            'start_date'     => ['type' => 'DATE'],
            'end_date'       => ['type' => 'DATE'],
            'reason'         => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'booked'], // booked|maintenance
            'reservation_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $f->addKey('id', true);
        $f->addKey(['equipment_id', 'start_date', 'end_date']);
        $f->createTable('unit_blackouts');

        // --- payments ---------------------------------------------------------
        $f->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'reservation_id'   => ['type' => 'INT', 'unsigned' => true],
            'square_payment_id'=> ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'type'             => ['type' => 'VARCHAR', 'constraint' => 24], // rental|reservation_deposit|damage_hold|damage_capture|refund
            'amount'           => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'status'           => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'],
            'note'             => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $f->addKey('id', true);
        $f->addKey('reservation_id');
        $f->createTable('payments');

        // --- deposits ---------------------------------------------------------
        $f->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'reservation_id'  => ['type' => 'INT', 'unsigned' => true],
            'kind'            => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'damage'], // reservation|damage
            'amount'          => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'square_ref'      => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'captured_amount' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'refunded_amount' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'status'          => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'held'], // held|released|captured|refunded
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $f->addKey('id', true);
        $f->addKey('reservation_id');
        $f->createTable('deposits');

        // --- contracts (versioned) -------------------------------------------
        $f->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'version'      => ['type' => 'VARCHAR', 'constraint' => 20],
            'title'        => ['type' => 'VARCHAR', 'constraint' => 150],
            'body_html'    => ['type' => 'MEDIUMTEXT'],
            'effective_at' => ['type' => 'DATETIME', 'null' => true],
            'active'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $f->addKey('id', true);
        $f->createTable('contracts');

        // --- contract_acceptances --------------------------------------------
        $f->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'          => ['type' => 'INT', 'unsigned' => true],
            'contract_id'      => ['type' => 'INT', 'unsigned' => true],
            'contract_version' => ['type' => 'VARCHAR', 'constraint' => 20],
            'method'           => ['type' => 'VARCHAR', 'constraint' => 10], // typed|drawn
            'signature_name'   => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'signature_image'  => ['type' => 'MEDIUMTEXT', 'null' => true], // base64 PNG (drawn)
            'accepted_at'      => ['type' => 'DATETIME'],
            'ip'               => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $f->addKey('id', true);
        $f->addKey('user_id');
        $f->createTable('contract_acceptances');

        // --- tax_rates --------------------------------------------------------
        $f->addField([
            'id'        => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tax_class' => ['type' => 'VARCHAR', 'constraint' => 30],
            'label'     => ['type' => 'VARCHAR', 'constraint' => 100],
            'rate'      => ['type' => 'DECIMAL', 'constraint' => '6,4', 'default' => 0], // 0.0745 = 7.45%
            'active'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $f->addKey('id', true);
        $f->addUniqueKey('tax_class');
        $f->createTable('tax_rates');

        // --- equipment_ownership ---------------------------------------------
        $f->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'equipment_id' => ['type' => 'INT', 'unsigned' => true],
            'owner_entity' => ['type' => 'VARCHAR', 'constraint' => 150], // 'WTR' for self-owned
            'owned_pct'    => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 100], // % of income to this owner
            'notes'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $f->addKey('id', true);
        $f->addKey('equipment_id');
        $f->createTable('equipment_ownership');

        // --- income_allocations ----------------------------------------------
        $f->addField([
            'id'                  => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'reservation_item_id' => ['type' => 'INT', 'unsigned' => true],
            'equipment_id'        => ['type' => 'INT', 'unsigned' => true],
            'owner_entity'        => ['type' => 'VARCHAR', 'constraint' => 150],
            'amount'              => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $f->addKey('id', true);
        $f->addKey('equipment_id');
        $f->addKey('owner_entity');
        $f->createTable('income_allocations');

        // --- equipment_costs --------------------------------------------------
        $f->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'equipment_id' => ['type' => 'INT', 'unsigned' => true],
            'kind'         => ['type' => 'VARCHAR', 'constraint' => 20], // purchase|expense|maintenance
            'amount'       => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'cost_date'    => ['type' => 'DATE'],
            'notes'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $f->addKey('id', true);
        $f->addKey('equipment_id');
        $f->createTable('equipment_costs');

        // --- maintenance_log --------------------------------------------------
        $f->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'equipment_id' => ['type' => 'INT', 'unsigned' => true],
            'kind'         => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'start_date'   => ['type' => 'DATE'],
            'end_date'     => ['type' => 'DATE', 'null' => true],
            'cost'         => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'blackout_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'notes'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $f->addKey('id', true);
        $f->addKey('equipment_id');
        $f->createTable('maintenance_log');

        // --- damage_reports ---------------------------------------------------
        $f->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'reservation_id'  => ['type' => 'INT', 'unsigned' => true],
            'equipment_id'    => ['type' => 'INT', 'unsigned' => true],
            'description'     => ['type' => 'TEXT', 'null' => true],
            'photos'          => ['type' => 'TEXT', 'null' => true], // JSON array of paths
            'assessed_cost'   => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'deposit_captured'=> ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $f->addKey('id', true);
        $f->addKey('reservation_id');
        $f->createTable('damage_reports');

        // --- email_log --------------------------------------------------------
        $f->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'recipient'      => ['type' => 'VARCHAR', 'constraint' => 150],
            'type'           => ['type' => 'VARCHAR', 'constraint' => 40],
            'reservation_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'subject'        => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'sent'           => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'sent_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $f->addKey('id', true);
        $f->createTable('email_log');

        // --- contact_messages -------------------------------------------------
        $f->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 120],
            'email'      => ['type' => 'VARCHAR', 'constraint' => 150],
            'message'    => ['type' => 'TEXT'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $f->addKey('id', true);
        $f->createTable('contact_messages');
    }

    public function down(): void
    {
        foreach ([
            'contact_messages', 'email_log', 'damage_reports', 'maintenance_log',
            'equipment_costs', 'income_allocations', 'equipment_ownership', 'tax_rates',
            'contract_acceptances', 'contracts', 'deposits', 'payments', 'unit_blackouts',
            'reservation_items', 'reservations', 'equipment_safety', 'equipment_media',
            'equipment', 'categories', 'customer_profiles',
        ] as $t) {
            $this->forge->dropTable($t, true);
        }
    }
}
