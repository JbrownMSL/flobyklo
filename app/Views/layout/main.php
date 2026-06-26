<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf" content="<?= csrf_hash() ?>">
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" type="image/svg+xml" href="/icon.svg">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<title><?= esc($title ?? 'Flora by Klo') ?></title>
<style>
 *{box-sizing:border-box} body{margin:0;font-family:system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;color:#1d2433;background:#f4f6fa;}
 a{color:#7c9a6f;text-decoration:none} a:hover{text-decoration:underline}
 .topbar{background:#1a1a2e;color:#fff;padding:.55rem 1rem;display:flex;align-items:center;gap:1.2rem;flex-wrap:wrap;}
 .topbar .brand{font-weight:800;font-size:1.1rem;color:#d4a5a5;}
 .topbar nav{display:flex;gap:1rem;flex-wrap:wrap;margin-left:auto;font-size:.9rem;}
 .topbar nav a{color:#e5e7eb;}
 .wrap{max-width:900px;margin:0 auto;padding:1.2rem 1rem 3rem;}
 .card{background:#fff;border:1px solid #e6e9ef;border-radius:12px;padding:1rem 1.1rem;margin-bottom:1rem;}
 h1{font-size:1.6rem;margin:.2rem 0 1rem;} h2{font-size:1.15rem;margin:.2rem 0 .8rem;}
 .btn{display:inline-block;background:#7c9a6f;color:#fff;text-decoration:none;padding:.6rem 1.2rem;border-radius:8px;border:0;font-size:.95rem;font-weight:700;cursor:pointer;}
 .btn:hover{background:#6b8960;text-decoration:none;}
 .flash{border:1px solid #bfe3c8;background:#f0faf2;border-radius:10px;padding:.7rem 1rem;margin-bottom:1rem;}
 .flash.err{border-color:#f3c6c6;background:#fdf0f0;color:#a12;}
</style>
</head>
<body>
<div class="topbar">
  <span class="brand">🌸 Flora by Klo</span>
  <nav>
    <?php if (function_exists('auth') && auth()->loggedIn()): ?>
      <?php if (fbk_is_admin()): ?><a href="<?= site_url('admin') ?>">Admin</a><?php endif ?>
      <a href="<?= site_url('logout') ?>">Sign out</a>
    <?php else: ?>
      <a href="<?= site_url('login') ?>">Sign in</a>
    <?php endif ?>
  </nav>
</div>
<div class="wrap">
  <?php if (session()->getFlashdata('msg')): ?><div class="flash"><?= esc(session()->getFlashdata('msg')) ?></div><?php endif ?>
  <?php if (session()->getFlashdata('error')): ?><div class="flash err"><?= esc(session()->getFlashdata('error')) ?></div><?php endif ?>
  <?= $this->renderSection('content') ?>
</div>
</body></html>
