<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds a phone_number column to Shield's users table.
 *
 * Phone-only members log in by phone: the phone (E.164) is stored as the
 * `secret` of their email_password identity (so all of Shield's password /
 * force_reset / throttle machinery works unchanged), and ALSO denormalized
 * here on users.phone_number for display, contact, and the ward-map overlay.
 * Email members have phone_number NULL.
 */
class AddPhoneToUsers extends Migration
{
    public function up(): void
    {
        $users = $this->db->prefixTable('users');

        $this->forge->addColumn('users', [
            'phone_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'after'      => 'username',
            ],
        ]);

        // Unique only enforced for non-NULL values (MySQL allows multiple NULLs
        // in a UNIQUE index), so email members (NULL phone) don't collide.
        $this->db->query("ALTER TABLE {$users} ADD UNIQUE INDEX users_phone_number (phone_number)");
    }

    public function down(): void
    {
        $users = $this->db->prefixTable('users');
        $this->db->query("ALTER TABLE {$users} DROP INDEX users_phone_number");
        $this->forge->dropColumn('users', 'phone_number');
    }
}
