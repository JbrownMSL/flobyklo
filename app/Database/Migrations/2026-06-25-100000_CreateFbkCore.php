<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Flora by Klo — core schema (all modules).
 * Shield tables are created by Shield's own migrations; run `php spark migrate --all`.
 * user_id columns reference users.id (int unsigned) by convention, not a hard FK.
 */
class CreateFbkCore extends Migration
{
    public function up(): void
    {
        $f = $this->forge;

        // --- clients (CRM / lead pipeline) -----------------------------------
        $f->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'email'      => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'phone'      => ['type' => 'VARCHAR', 'constraint' => 30,  'null' => true],
            'address'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'source'     => ['type' => 'ENUM', 'constraint' => ['inquiry', 'referral', 'instagram', 'facebook', 'google', 'other'], 'default' => 'inquiry'],
            'status'     => ['type' => 'ENUM', 'constraint' => ['lead', 'consult', 'booked', 'completed', 'lost'], 'default' => 'lead'],
            'notes'      => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $f->addKey('id', true);
        $f->addKey('status');
        $f->createTable('clients');

        // --- events (bookings / per-weekend capacity) -------------------------
        $f->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'client_id'        => ['type' => 'INT', 'unsigned' => true],
            'type'             => ['type' => 'ENUM', 'constraint' => ['wedding', 'event', 'popup', 'other'], 'default' => 'wedding'],
            'event_date'       => ['type' => 'DATE', 'null' => true],
            'venue'            => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'guest_count'      => ['type' => 'INT', 'null' => true],
            'status'           => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'tentative'],
            'capacity_weekend' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'notes'            => ['type' => 'TEXT', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $f->addKey('id', true);
        $f->addKey('client_id');
        $f->addKey('event_date');
        $f->createTable('events');

        // --- recipes (floral arrangements / labor) ----------------------------
        $f->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'          => ['type' => 'VARCHAR', 'constraint' => 150],
            'type'          => ['type' => 'ENUM', 'constraint' => ['bouquet', 'centerpiece', 'install', 'boutonniere', 'arch', 'other'], 'default' => 'bouquet'],
            'labor_minutes' => ['type' => 'INT', 'default' => 0],
            'notes'         => ['type' => 'TEXT', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $f->addKey('id', true);
        $f->createTable('recipes');

        // --- recipe_stems (stem-level cost engine) ----------------------------
        $f->addField([
            'id'        => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'recipe_id' => ['type' => 'INT', 'unsigned' => true],
            'stem_name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'qty'       => ['type' => 'DECIMAL', 'constraint' => '8,2', 'default' => 1],
            'unit_cost' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
        ]);
        $f->addKey('id', true);
        $f->addKey('recipe_id');
        $f->createTable('recipe_stems');

        // --- quotes (proposals) -----------------------------------------------
        $f->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'client_id'   => ['type' => 'INT', 'unsigned' => true],
            'event_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'status'      => ['type' => 'ENUM', 'constraint' => ['draft', 'sent', 'accepted', 'declined'], 'default' => 'draft'],
            'subtotal'    => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'tax'         => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'total'       => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'deposit_pct' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 50],
            'valid_until' => ['type' => 'DATE', 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $f->addKey('id', true);
        $f->addKey('client_id');
        $f->addKey('status');
        $f->createTable('quotes');

        // --- quote_items -------------------------------------------------------
        $f->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'quote_id'    => ['type' => 'INT', 'unsigned' => true],
            'recipe_id'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'description' => ['type' => 'VARCHAR', 'constraint' => 255],
            'qty'         => ['type' => 'DECIMAL', 'constraint' => '8,2', 'default' => 1],
            'unit_price'  => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'line_total'  => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'cost'        => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
        ]);
        $f->addKey('id', true);
        $f->addKey('quote_id');
        $f->createTable('quote_items');

        // --- contracts (e-sign — reuse WTR SignaturePad pattern) --------------
        $f->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'quote_id'    => ['type' => 'INT', 'unsigned' => true],
            'body'        => ['type' => 'MEDIUMTEXT', 'null' => true],
            'signed_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'signed_at'   => ['type' => 'DATETIME', 'null' => true],
            'signature'   => ['type' => 'VARCHAR', 'constraint' => 16, 'null' => true],  // 'typed' | 'drawn'
            'ip'          => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $f->addKey('id', true);
        $f->addKey('quote_id');
        $f->createTable('contracts');

        // --- invoices ---------------------------------------------------------
        $f->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'quote_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'client_id'   => ['type' => 'INT', 'unsigned' => true],
            'number'      => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'status'      => ['type' => 'ENUM', 'constraint' => ['draft', 'sent', 'deposit_paid', 'paid', 'void'], 'default' => 'draft'],
            'subtotal'    => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'tax'         => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'total'       => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'amount_paid' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'balance_due' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'due_date'    => ['type' => 'DATE', 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $f->addKey('id', true);
        $f->addKey('client_id');
        $f->addKey('status');
        $f->createTable('invoices');

        // --- payments ---------------------------------------------------------
        $f->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'invoice_id' => ['type' => 'INT', 'unsigned' => true],
            'amount'     => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'method'     => ['type' => 'ENUM', 'constraint' => ['square', 'cash', 'check', 'other'], 'default' => 'square'],
            'kind'       => ['type' => 'ENUM', 'constraint' => ['deposit', 'balance', 'other'], 'default' => 'deposit'],
            'square_ref' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'status'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'],
            'paid_at'    => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $f->addKey('id', true);
        $f->addKey('invoice_id');
        $f->createTable('payments');

        // --- expenses (COGS + overhead) ----------------------------------------
        $f->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'date'         => ['type' => 'DATE'],
            'vendor'       => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'category'     => ['type' => 'ENUM', 'constraint' => ['flowers', 'supplies', 'fuel', 'rent', 'labor', 'marketing', 'other'], 'default' => 'other'],
            'amount'       => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'event_id'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'plaid_txn_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'notes'        => ['type' => 'TEXT', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $f->addKey('id', true);
        $f->addKey('date');
        $f->addKey('event_id');
        $f->createTable('expenses');

        // --- plaid_connections ------------------------------------------------
        $f->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'item_id'      => ['type' => 'VARCHAR', 'constraint' => 100],
            'access_token' => ['type' => 'VARCHAR', 'constraint' => 200],
            'account_id'   => ['type' => 'VARCHAR', 'constraint' => 100],
            'name'         => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'mask'         => ['type' => 'VARCHAR', 'constraint' => 10,  'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $f->addKey('id', true);
        $f->addUniqueKey('item_id');
        $f->createTable('plaid_connections');

        // --- plaid_transactions -----------------------------------------------
        $f->addField([
            'id'                  => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'plaid_connection_id' => ['type' => 'INT', 'unsigned' => true],
            'txn_id'              => ['type' => 'VARCHAR', 'constraint' => 100],
            'date'                => ['type' => 'DATE'],
            'amount'              => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'name'                => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'category'            => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'pending'             => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $f->addKey('id', true);
        $f->addUniqueKey('txn_id');
        $f->addKey('plaid_connection_id');
        $f->createTable('plaid_transactions');

        // --- email_log (used by fbk_send_email) --------------------------------
        $f->addField([
            'id'        => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'recipient' => ['type' => 'VARCHAR', 'constraint' => 150],
            'type'      => ['type' => 'VARCHAR', 'constraint' => 40],
            'subject'   => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'sent'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'sent_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $f->addKey('id', true);
        $f->createTable('email_log');
    }

    public function down(): void
    {
        foreach ([
            'email_log', 'plaid_transactions', 'plaid_connections',
            'expenses', 'payments', 'invoices', 'contracts',
            'quote_items', 'quotes', 'recipe_stems', 'recipes',
            'events', 'clients',
        ] as $t) {
            $this->forge->dropTable($t, true);
        }
    }
}
