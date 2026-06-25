<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Master definition of every role in the Mojo Lacrosse club portal.
 *
 * This is the SINGLE source of truth for what a role can do. ScopeService reads
 * it to decide edit / assign / accounting / comms and at what tenancy level.
 * Authorization (which rows a user sees) is keyed on club_id / team_id / the
 * player_guardians graph via role assignments — NOT on Shield groups alone.
 *
 * Roles are NON-EXCLUSIVE: a person may hold several (e.g. parent + coach +
 * team_manager, across teams). ScopeService unions all of a user's assignments.
 *
 * scope:   global | team | family | self   (tenancy level the role operates at)
 * edit:    may edit roster/player records within scope
 * assign:  may assign roles + approve enrollments within scope
 * account: may view/manage the club accounting ledger (full) — treasurer/admin
 * comms:   may send team/club email + run event signups within scope
 * single:  only one holder per scope unit (unused for Mojo; kept for parity)
 * hidden:  never shown in user/role lists (system backdoor)
 * read:    read-only within scope (sees but cannot edit)
 */
class Roles extends BaseConfig
{
    /**
     * @var array<string, array{label:string, category:string, scope:string, edit:bool, assign:bool, account:bool, comms:bool, single:bool, hidden:bool, read:bool}>
     */
    public array $defs = [
        // ---- Global (Shield groups, not team-scoped) -------------------------
        'system_admin' => ['label' => 'System Admin', 'category' => 'admin', 'scope' => 'global', 'edit' => true, 'assign' => true, 'account' => true, 'comms' => true, 'single' => false, 'hidden' => true,  'read' => false],
        'club_admin'   => ['label' => 'Club Admin',   'category' => 'admin', 'scope' => 'global', 'edit' => true, 'assign' => true, 'account' => true, 'comms' => true, 'single' => false, 'hidden' => false, 'read' => false],

        // ---- Treasurer (club-wide accounting only) ---------------------------
        'treasurer' => ['label' => 'Treasurer', 'category' => 'admin', 'scope' => 'global', 'edit' => false, 'assign' => false, 'account' => true, 'comms' => false, 'single' => false, 'hidden' => false, 'read' => true],

        // ---- Team staff (team-scoped) ----------------------------------------
        'coach'        => ['label' => 'Coach',        'category' => 'team', 'scope' => 'team', 'edit' => true,  'assign' => true,  'account' => false, 'comms' => true, 'single' => false, 'hidden' => false, 'read' => false],
        'team_manager' => ['label' => 'Team Manager', 'category' => 'team', 'scope' => 'team', 'edit' => false, 'assign' => false, 'account' => false, 'comms' => true, 'single' => false, 'hidden' => false, 'read' => false],

        // ---- Family / player -------------------------------------------------
        'parent' => ['label' => 'Parent / Guardian', 'category' => 'family', 'scope' => 'family', 'edit' => true, 'assign' => false, 'account' => false, 'comms' => false, 'single' => false, 'hidden' => false, 'read' => false],
        'player' => ['label' => 'Player',            'category' => 'family', 'scope' => 'self',   'edit' => true, 'assign' => false, 'account' => false, 'comms' => false, 'single' => false, 'hidden' => false, 'read' => false],
    ];

    /** Role keys that are Shield groups (global bypass), not role_assignments rows. */
    public array $globalGroups = ['system_admin', 'club_admin'];

    /**
     * Load role definitions from the `role_defs` table when present (so admins
     * can add roles + edit permissions from the UI). The hardcoded $defs above
     * are the seed + the fallback if the table is missing or empty.
     */
    public function __construct()
    {
        parent::__construct();

        try {
            $db = \Config\Database::connect();
            if (! $db->tableExists('role_defs')) {
                return;
            }
            $rows = $db->table('role_defs')->orderBy('sort')->orderBy('id')->get()->getResultArray();
            if (! $rows) {
                return;
            }
            $defs = [];
            foreach ($rows as $r) {
                $defs[$r['role_key']] = [
                    'label'   => $r['label'], 'category' => $r['category'], 'scope' => $r['scope'],
                    'edit'    => (bool) $r['can_edit'], 'assign' => (bool) $r['can_assign'],
                    'account' => (bool) ($r['can_account'] ?? 0), 'comms' => (bool) ($r['can_comms'] ?? 0),
                    'single'  => (bool) $r['single_holder'], 'hidden' => (bool) $r['hidden'], 'read' => (bool) $r['read_only'],
                ];
            }
            $this->defs         = $defs;
            $this->globalGroups = array_values(array_intersect(
                array_keys(array_filter($defs, static fn ($d) => $d['scope'] === 'global')),
                ['system_admin', 'club_admin'],
            ));
        } catch (\Throwable $e) {
            // DB unavailable / pre-migration — keep the hardcoded defs.
        }
    }

    public function get(string $key): ?array
    {
        return $this->defs[$key] ?? null;
    }

    public function exists(string $key): bool
    {
        return isset($this->defs[$key]);
    }

    public function label(string $key): string
    {
        return $this->defs[$key]['label'] ?? $key;
    }

    public function isGlobal(string $key): bool
    {
        return in_array($key, $this->globalGroups, true);
    }

    /** Roles assignable through the role-admin UI (excludes hidden). */
    public function assignable(): array
    {
        $out = [];
        foreach ($this->defs as $key => $d) {
            if ($d['hidden']) {
                continue;
            }
            $out[$key] = $d;
        }

        return $out;
    }
}
