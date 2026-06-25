<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>
<p class="muted"><a href="<?= site_url('admin/users') ?>">← Manage Users</a></p>
<h1><?= esc($title) ?></h1>

<div class="card">
  <form action="<?= site_url('admin/users/save') ?>" method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $user ? (int) $user->id : 0 ?>">
    <div class="row">
      <div style="flex:2;"><label>Email (login)</label>
        <input type="email" value="<?= esc($email) ?>" disabled>
        <p class="muted" style="font-size:.85em;">Email is the login — change it via the database if it must move.</p>
      </div>
      <div style="flex:2;"><label>Name</label><input type="text" name="username" value="<?= esc($user->username ?? '') ?>"></div>
      <div><label>Role</label><select name="role">
        <?php foreach ($roles as $k => $v): ?><option value="<?= esc($k) ?>"<?= $k === $role ? ' selected' : '' ?>><?= esc($v) ?></option><?php endforeach ?>
      </select></div>
    </div>
    <button class="btn" type="submit" style="margin-top:.5rem;">Save</button>
  </form>
</div>
<?= $this->endSection() ?>
