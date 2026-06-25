<?php

namespace App\Controllers\Admin;

use CodeIgniter\Shield\Entities\User;

/**
 * Admin → Manage Users. Lets a Manager (admin group) add staff/customer
 * logins, grant/revoke the Manager role, activate/deactivate, reset a
 * password, and (re)send the welcome emails — through the app instead of CLI.
 *
 * Hard rules:
 *   - The hidden system_admin tier (owner/backdoor) is NEVER listed, never
 *     editable, and never offered as a selectable role (wtr_hidden_group()).
 *   - Only 'customer' and 'admin' (Manager) are assignable here.
 *   - You can't demote or deactivate your own account (lock-out guard).
 *   - New users / resets get a random temp password; on create (and resend) the
 *     user is emailed a welcome + a SEPARATE password message. Delivery honors
 *     wtr_send_email's dev-lockout; if blocked, the temp password is surfaced
 *     to the admin in the flash so they can hand it off.
 */
class Users extends BaseAdmin
{
    private const ROLES = ['customer' => 'Customer', 'admin' => 'Manager'];

    private function provider()
    {
        return auth()->getProvider();
    }

    private function isHidden(?User $u): bool
    {
        return $u !== null && $u->inGroup(wtr_hidden_group());
    }

    private function genTempPassword(): string
    {
        return bin2hex(random_bytes(6)) . 'A!';   // 14 chars, letter+symbol — meets Shield rules
    }

    /** Build + send the two onboarding emails. Returns [welcomeOk, passwordOk]. */
    private function sendWelcomeEmails(string $email, string $name, string $pw, string $role): array
    {
        helper('wtr');
        $loginUrl = site_url('login');
        $welcome  = "<p>Hi {$name},</p>"
                  . '<p>An account has been created for you on <strong>Weekend Tool Rentals</strong>'
                  . ($role === 'admin' ? ' with <strong>Manager</strong> access' : '') . '.</p>'
                  . "<p><strong>Your login (username / email):</strong> {$email}<br>"
                  . "Sign in at <a href=\"{$loginUrl}\">{$loginUrl}</a></p>"
                  . '<p>Your temporary password is in a separate email — please change it after your first sign-in.</p>';
        $pwMail   = "<p>Hi {$name},</p>"
                  . '<p>Your temporary password for <strong>Weekend Tool Rentals</strong> is:</p>'
                  . "<p style=\"font-size:18px;font-weight:bold;letter-spacing:1px;\">{$pw}</p>"
                  . "<p>Sign in at <a href=\"{$loginUrl}\">{$loginUrl}</a> and change it (Account &rarr; password, or &ldquo;Forgot password&rdquo;).</p>";
        return [
            wtr_send_email($email, 'Welcome to Weekend Tool Rentals — your account', $welcome, 'user_welcome'),
            wtr_send_email($email, 'Your Weekend Tool Rentals temporary password', $pwMail, 'user_password'),
        ];
    }

    public function index()
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $db     = db_connect();
        $hidden = wtr_hidden_group();
        $rows = $db->query(
            "SELECT u.id, u.username, u.active, u.last_active,
                    (SELECT ai.secret FROM auth_identities ai
                       WHERE ai.user_id = u.id AND ai.type = 'email_password' LIMIT 1) AS email,
                    (SELECT GROUP_CONCAT(g.`group` ORDER BY g.`group`)
                       FROM auth_groups_users g WHERE g.user_id = u.id) AS `groups`
             FROM users u
             WHERE u.id NOT IN (SELECT user_id FROM auth_groups_users WHERE `group` = ?)
             ORDER BY u.id",
            [$hidden]
        )->getResultArray();

        return view('admin/users', [
            'title' => 'Manage Users',
            'users' => $rows,
            'roles' => self::ROLES,
            'meId'  => (int) (auth()->id() ?? 0),
        ]);
    }

    /** Add (id null) or edit form. */
    public function form($id = null)
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $user = null;
        if ($id !== null) {
            $user = $this->provider()->findById((int) $id);
            if (! $user || $this->isHidden($user)) {
                return redirect()->to('/admin/users')->with('error', 'User not found.');
            }
        }
        return view('admin/user_form', [
            'title' => $user ? 'Edit User' : 'New User',
            'user'  => $user,
            'email' => $user ? $user->email : '',
            'role'  => ($user && $user->inGroup('admin')) ? 'admin' : 'customer',
            'roles' => self::ROLES,
        ]);
    }

    /** Create a new user, or update an existing one's name + role. */
    public function save()
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $id   = (int) $this->request->getPost('id');
        $name = trim((string) $this->request->getPost('username'));
        $role = (string) $this->request->getPost('role');

        if (! array_key_exists($role, self::ROLES)) {
            return redirect()->back()->withInput()->with('error', 'Pick a valid role.');
        }

        // ── edit existing ───────────────────────────────────────────────
        if ($id > 0) {
            $user = $this->provider()->findById($id);
            if (! $user || $this->isHidden($user)) {
                return redirect()->to('/admin/users')->with('error', 'User not found.');
            }
            if ($id === (int) auth()->id() && $role !== 'admin') {
                return redirect()->back()->with('error', "You can't remove your own Manager access.");
            }
            if ($name !== '' && $name !== $user->username) {
                $user->username = $name;
                $this->provider()->save($user);
            }
            $other = $role === 'admin' ? 'customer' : 'admin';
            if (! $user->inGroup($role)) { $user->addGroup($role); }
            if ($user->inGroup($other))  { $user->removeGroup($other); }
            return redirect()->to('/admin/users')->with('msg', "Updated {$user->email}.");
        }

        // ── create new ──────────────────────────────────────────────────
        $email = trim((string) $this->request->getPost('email'));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->withInput()->with('error', 'A valid email is required.');
        }
        if ($this->provider()->findByCredentials(['email' => $email])) {
            return redirect()->back()->withInput()->with('error', 'A user with that email already exists — edit them instead.');
        }
        if ($name === '') {
            $name = strstr($email, '@', true) ?: $email;
        }
        $pw   = $this->genTempPassword();
        $user = new User(['username' => $name, 'email' => $email, 'password' => $pw, 'active' => 1]);
        $this->provider()->save($user);
        $user = $this->provider()->findById($this->provider()->getInsertID());
        $user->addGroup($role);

        [$okA, $okB] = $this->sendWelcomeEmails($email, $user->username, $pw, $role);
        $note = ($okA && $okB)
            ? "Welcome + password emails sent to {$email}."
            : "⚠ Email blocked/failed (check dev-lockout/mailer) — temp password to hand off: {$pw}";
        return redirect()->to('/admin/users')->with('msg',
            'Created ' . esc($email) . ' as ' . self::ROLES[$role] . ". {$note}");
    }

    /** Reset password + re-send the welcome and password emails. */
    public function resendWelcome($id)
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $user = $this->provider()->findById((int) $id);
        if (! $user || $this->isHidden($user)) {
            return redirect()->to('/admin/users')->with('error', 'User not found.');
        }
        $pw = $this->genTempPassword();
        $user->setPassword($pw);
        $this->provider()->save($user);
        $role = $user->inGroup('admin') ? 'admin' : 'customer';

        [$okA, $okB] = $this->sendWelcomeEmails($user->email, $user->username, $pw, $role);
        $note = ($okA && $okB)
            ? "Welcome + new password emailed to {$user->email}."
            : "⚠ Email blocked/failed (check dev-lockout/mailer) — new temp password to hand off: {$pw}";
        return redirect()->to('/admin/users')->with('msg', $note);
    }

    /** Activate / deactivate (can't deactivate yourself). */
    public function toggleActive($id)
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $user = $this->provider()->findById((int) $id);
        if (! $user || $this->isHidden($user)) {
            return redirect()->to('/admin/users')->with('error', 'User not found.');
        }
        if ((int) $id === (int) auth()->id()) {
            return redirect()->to('/admin/users')->with('error', "You can't deactivate your own account.");
        }
        if ($user->active) {
            $user->active = 0;
            $this->provider()->save($user);
            $msg = "Deactivated {$user->email}.";
        } else {
            $user->activate();
            $msg = "Activated {$user->email}.";
        }
        return redirect()->to('/admin/users')->with('msg', $msg);
    }

    /** Reset a user's password to a fresh temp (shown once, no email). */
    public function resetPassword($id)
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $user = $this->provider()->findById((int) $id);
        if (! $user || $this->isHidden($user)) {
            return redirect()->to('/admin/users')->with('error', 'User not found.');
        }
        $pw = $this->genTempPassword();
        $user->setPassword($pw);
        $this->provider()->save($user);

        return redirect()->to('/admin/users')->with('msg',
            "New temporary password for {$user->email} (shown once): {$pw}  —  have them change it via “Forgot password”.");
    }
}
