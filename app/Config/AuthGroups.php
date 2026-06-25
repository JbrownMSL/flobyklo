<?php

declare(strict_types=1);

/**
 * This file is part of CodeIgniter Shield.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Config;

use CodeIgniter\Shield\Config\AuthGroups as ShieldAuthGroups;

class AuthGroups extends ShieldAuthGroups
{
    /**
     * --------------------------------------------------------------------
     * Default Group
     * --------------------------------------------------------------------
     * The group that a newly registered user is added to.
     */
    // New self-registered users are CUSTOMERS. Admin is granted explicitly
    // (php spark shield:group add admin <email>).
    public string $defaultGroup = 'customer';

    /**
     * --------------------------------------------------------------------
     * Groups
     * --------------------------------------------------------------------
     * An associative array of the available groups in the system, where the keys
     * are the group names and the values are arrays of the group info.
     *
     * Whatever value you assign as the key will be used to refer to the group
     * when using functions such as:
     *      $user->addGroup('superadmin');
     *
     * @var array<string, array<string, string>>
     *
     * @see https://codeigniter4.github.io/shield/quick_start_guide/using_authorization/#change-available-groups for more info
     */
    public array $groups = [
        'customer' => [
            'title'       => 'Customer',
            'description' => 'A renter. Can book equipment and manage their own reservations.',
        ],
        'admin' => [
            'title'       => 'Manager',
            'description' => 'Weekend Tool Rentals staff/manager. Full access to inventory, reservations, maintenance, and reports.',
        ],
        // Hidden owner/backdoor tier (Jason). Full access; intentionally NOT
        // surfaced in any user/manager list — see wtr_admin_groups() +
        // WTR_HIDDEN_GROUP and the user-list exclusion in helper wtr.
        'system_admin' => [
            'title'       => 'System Admin',
            'description' => 'Hidden owner/backdoor. Complete control; never shown in member, manager, or admin lists.',
        ],
    ];

    /**
     * --------------------------------------------------------------------
     * Permissions
     * --------------------------------------------------------------------
     * The available permissions in the system.
     *
     * If a permission is not listed here it cannot be used.
     */
    public array $permissions = [
        'admin.access'        => 'Can access the sites admin area',
        'admin.settings'      => 'Can access the main site settings',
        'users.manage-admins' => 'Can manage other admins',
        'users.create'        => 'Can create new non-admin users',
        'users.edit'          => 'Can edit existing non-admin users',
        'users.delete'        => 'Can delete existing non-admin users',
        'beta.access'         => 'Can access beta-level features',
    ];

    /**
     * --------------------------------------------------------------------
     * Permissions Matrix
     * --------------------------------------------------------------------
     * Maps permissions to groups.
     *
     * This defines group-level permissions.
     */
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
