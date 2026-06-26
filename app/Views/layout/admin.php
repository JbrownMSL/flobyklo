<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf" content="<?= csrf_hash() ?>">
<title><?= esc($title ?? 'FBK Admin') ?> — Flora by Klo</title>
<style>
 *{box-sizing:border-box} body{margin:0;font-family:system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;color:#1d2433;background:#0f172a;}
 a{color:#7c9a6f;text-decoration:none} a:hover{text-decoration:underline}
 .topbar{background:#1a1a2e;color:#fff;padding:.7rem 1rem;display:flex;gap:1.2rem;align-items:center;flex-wrap:wrap;}
 .topbar .brand{font-weight:800;color:#d4a5a5;font-size:1rem;letter-spacing:.03em;}
 .topbar nav{display:flex;gap:1rem;margin-left:auto;font-size:.88rem;flex-wrap:wrap;}
 .topbar nav a{color:#c9d1d9;}
 .wrap{max-width:1200px;margin:0 auto;padding:1.2rem 1rem 3rem;}
 .card{background:#fff;border:1px solid #1e293b;border-radius:10px;padding:1rem 1.1rem;margin-bottom:1rem;}
 h1{font-size:1.4rem;color:#fff;margin:.2rem 0 1rem;} h2{font-size:1.05rem;margin:.1rem 0 .7rem;}
 label{font-size:.78rem;color:#556;display:block;margin:.5rem 0 .15rem;font-weight:600;}
 input,select,textarea{font:inherit;padding:.5rem .6rem;border:1px solid #d7dbe3;border-radius:7px;width:100%;}
 .btn{display:inline-block;background:#7c9a6f;color:#fff;padding:.5rem 1rem;border-radius:7px;border:0;font-weight:600;cursor:pointer;text-decoration:none;}
 .btn:hover{background:#6b8960;text-decoration:none;} .btn.alt{background:#c47a7a;} .btn.ghost{background:#fff;color:#334;border:1px solid #d7dbe3;}
 table{width:100%;border-collapse:collapse;font-size:.9rem;} th,td{text-align:left;padding:.45rem .6rem;border-bottom:1px solid #eef;}
 .kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:.8rem;margin-bottom:1.2rem;}
 .kpi{background:#fff;border-radius:10px;padding:.9rem 1rem;} .kpi .n{font-size:1.6rem;font-weight:800;} .kpi .l{font-size:.78rem;color:#667;}
 .muted{color:#667;} .row{display:flex;gap:.8rem;flex-wrap:wrap;} .row>*{flex:1 1 180px;}
 .flash{border:1px solid #bfe3c8;background:#f0faf2;border-radius:8px;padding:.6rem 1rem;margin-bottom:1rem;}
 .flash.err{border-color:#f3c6c6;background:#fdf0f0;color:#a12;}
 .pill{display:inline-block;font-size:.72rem;padding:.12rem .5rem;border-radius:999px;background:#f0ede8;color:#7a6050;border:1px solid #e0d8d0;}
</style>
</head>
<body>
<div class="topbar">
  <span class="brand">🌸 Flora by Klo</span>
  <nav>
    <a href="<?= site_url('admin') ?>">Dashboard</a>
    <a href="<?= site_url('admin/clients') ?>">Clients</a>
    <a href="<?= site_url('admin/events') ?>">Events</a>
    <a href="<?= site_url('admin/quotes') ?>">Quotes</a>
    <a href="<?= site_url('admin/invoices') ?>">Invoices</a>
    <a href="<?= site_url('admin/expenses') ?>">Expenses</a>
    <a href="<?= site_url('admin/bank') ?>">Bank</a>
    <a href="<?= site_url('admin/reports') ?>">Reports</a>
    <a href="<?= site_url('admin/users') ?>">Users</a>
    <a href="<?= site_url('logout') ?>">Sign out</a>
  </nav>
</div>
<div class="wrap">
  <?php if (session()->getFlashdata('msg')): ?><div class="flash"><?= esc(session()->getFlashdata('msg')) ?></div><?php endif ?>
  <?php if (session()->getFlashdata('error')): ?><div class="flash err"><?= esc(session()->getFlashdata('error')) ?></div><?php endif ?>
  <?= $this->renderSection('content') ?>
</div>
</body></html>
