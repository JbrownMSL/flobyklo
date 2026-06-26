<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Entities\User;

/**
 * Creates the two staff logins:
 *   • Kloe   → 'admin'        (visible Manager — full back-office access)
 *   • Jason  → 'system_admin' (HIDDEN owner/backdoor — never listed anywhere)
 *
 * Emails default to the fleet identities; override at deploy via env
 * (fbk.admin.kloe / fbk.admin.jason). Each account is created ACTIVE with a
 * random temporary password printed ONCE to the console — change it after
 * first login. Idempotent: existing users are left in place and just ensured
 * into the right group.
 *
 * Run: php spark db:seed FbkAdminSeeder
 */
class FbkAdminSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['username' => 'kloe',  'email' => env('fbk.admin.kloe',  'kloe@florabyklo.com'),       'group' => 'admin'],
            ['username' => 'jason', 'email' => env('fbk.admin.jason', 'jbrown@motorsportsland.com'), 'group' => 'system_admin'],
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
            $pw   = bin2hex(random_bytes(6)) . 'A!';   // temp; meets Shield rules
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
