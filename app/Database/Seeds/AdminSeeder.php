<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Entities\User;

/**
 * Creates the two staff logins:
 *   • Andrew  → 'admin'        (visible Manager portal)
 *   • Jason   → 'system_admin' (HIDDEN owner/backdoor — never listed anywhere)
 *
 * Emails default to the fleet identities; override at deploy via env
 * (wtr.admin.andrew / wtr.admin.jason). Each account is created ACTIVE with a
 * random temporary password printed ONCE to the console — change it after first
 * login (Account → password, or the reset flow). Idempotent: existing users are
 * left in place and just ensured into the right group.
 *
 * Run: php spark db:seed AdminSeeder
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['username' => 'andrew', 'email' => env('wtr.admin.andrew', 'abrown@motorsportsland.com'), 'group' => 'admin'],
            ['username' => 'jason',  'email' => env('wtr.admin.jason',  'jbrown@motorsportsland.com'), 'group' => 'system_admin'],
        ];

        $provider = auth()->getProvider();

        foreach ($accounts as $a) {
            $existing = $provider->findByCredentials(['email' => $a['email']]);
            if ($existing) {
                if (! $existing->inGroup($a['group'])) {
                    $existing->addGroup($a['group']);
                }
                echo "exists: {$a['email']} ({$a['group']})\n";
                continue;
            }
            $pw = bin2hex(random_bytes(6)) . 'A!';   // temp; meets Shield rules
            $user = new User([
                'username' => $a['username'],
                'email'    => $a['email'],
                'password' => $pw,
                'active'   => 1,
            ]);
            $provider->save($user);
            $user = $provider->findById($provider->getInsertID());
            $user->addGroup($a['group']);

            echo "created: {$a['email']}  group={$a['group']}  TEMP PASSWORD: {$pw}\n";
        }
        echo "NOTE: change these temporary passwords after first login.\n";
    }
}
