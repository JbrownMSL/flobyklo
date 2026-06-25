<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>
<div class="row" style="align-items:center;">
  <h1 style="flex:1;">Manage Users</h1>
</div>

<div class="card">
  <h2>Add a user</h2>
  <p class="muted">Creates a login and shows a one-time temporary password to hand off. They set their own via <em>Forgot password</em> at sign-in. <strong>Manager</strong> = full admin portal access.</p>
  <form action="<?= site_url('admin/users/save') ?>" method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="0">
    <div class="row">
      <div style="flex:2;"><label>Email (login)</label><input type="email" name="email" required placeholder="name@example.com"></div>
      <div style="flex:2;"><label>Name</label><input type="text" name="username" placeholder="(defaults to email name)"></div>
      <div><label>Role</label><select name="role">
        <?php foreach ($roles as $k => $v): ?><option value="<?= esc($k) ?>"<?= $k === 'customer' ? ' selected' : '' ?>><?= esc($v) ?></option><?php endforeach ?>
      </select></div>
    </div>
    <button class="btn" type="submit" style="margin-top:.5rem;">Create user</button>
  </form>
</div>

<div class="card">
  <table><thead><tr><th>Email</th><th>Name</th><th>Role</th><th>Active</th><th>Last seen</th><th></th></tr></thead><tbody>
  <?php foreach ($users as $u): $isMe = (int) $u['id'] === $meId;
        $isMgr = strpos((string) $u['groups'], 'admin') !== false; ?>
    <tr>
      <td><?= esc($u['email'] ?? '—') ?><?= $isMe ? ' <span class="pill">you</span>' : '' ?></td>
      <td><?= esc($u['username'] ?? '') ?></td>
      <td><?= $isMgr ? '<span class="pill">Manager</span>' : '<span class="muted">Customer</span>' ?></td>
      <td><?= $u['active'] ? '✓' : '<span class="muted">no</span>' ?></td>
      <td class="muted"><?= esc($u['last_active'] ?: '—') ?></td>
      <td style="white-space:nowrap;">
        <a href="<?= site_url('admin/users/' . (int) $u['id']) ?>">Edit</a>
        <?php if (! $isMe): ?>
          &nbsp;|&nbsp;
          <form method="post" action="<?= site_url('admin/users/' . (int) $u['id'] . '/active') ?>" style="display:inline;">
            <?= csrf_field() ?><button class="linkbtn" type="submit"><?= $u['active'] ? 'Deactivate' : 'Activate' ?></button>
          </form>
        <?php endif ?>
        &nbsp;|&nbsp;
        <form method="post" action="<?= site_url('admin/users/' . (int) $u['id'] . '/reset') ?>" style="display:inline;"
              onsubmit="return confirm('Reset this user\'s password to a new temporary one?');">
          <?= csrf_field() ?><button class="linkbtn" type="submit">Reset password</button>
        </form>
        &nbsp;|&nbsp;
        <form method="post" action="<?= site_url('admin/users/' . (int) $u['id'] . '/resend') ?>" style="display:inline;"
              onsubmit="return confirm('Reset password and email a fresh welcome + password to this user?');">
          <?= csrf_field() ?><button class="linkbtn" type="submit">Resend welcome</button>
        </form>
      </td>
    </tr>
  <?php endforeach ?>
  <?php if (! $users): ?><tr><td colspan="6" class="muted">No users yet.</td></tr><?php endif ?>
  </tbody></table>
</div>
<style>.linkbtn{background:none;border:0;color:#1565c0;cursor:pointer;padding:0;font:inherit;text-decoration:underline;}</style>
<?= $this->endSection() ?>
