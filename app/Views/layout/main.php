<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf" content="<?= csrf_hash() ?>">
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" type="image/svg+xml" href="/icon.svg">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#0E0E0E">
<?= $this->include('partials/seo_head') ?>
<style>
 *{box-sizing:border-box} body{margin:0;font-family:system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;color:#1d2433;background:#f4f6fa;}
 a{color:#9A7400;text-decoration:none} a:hover{text-decoration:underline}
 .topbar{background:#0E0E0E;color:#fff;padding:.55rem 1rem;display:flex;align-items:center;gap:1.2rem;flex-wrap:wrap;}
 .topbar .brand{font-weight:800;font-size:1.1rem;letter-spacing:.02em;color:#fff;}
 .topbar .brand img{height:48px;width:auto;display:block;}
 .topbar .brand b{color:#F0B810;}
 .topbar nav{display:flex;gap:1rem;flex-wrap:wrap;margin-left:auto;font-size:.9rem;}
 .topbar nav a{color:#e5e7eb;}
 .wrap{max-width:1040px;margin:0 auto;padding:1.2rem 1rem 3rem;}
 .card{background:#fff;border:1px solid #e6e9ef;border-radius:12px;padding:1rem 1.1rem;margin-bottom:1rem;}
 h1{font-size:1.6rem;margin:.2rem 0 1rem;} h2{font-size:1.15rem;margin:.2rem 0 .8rem;}
 label{font-size:.8rem;color:#556;display:block;margin:.6rem 0 .2rem;font-weight:600;}
 input,select,textarea{font:inherit;padding:.55rem .65rem;border:1px solid #d7dbe3;border-radius:8px;width:100%;}
 .btn{display:inline-block;background:#F0B810;color:#111;text-decoration:none;padding:.6rem 1.2rem;border-radius:8px;border:0;font-size:.95rem;font-weight:700;cursor:pointer;}
 .btn:hover{background:#D9A40C;text-decoration:none;} .btn.alt{background:#1a1a1a;color:#fff;} .btn.ghost{background:#fff;color:#374151;border:1px solid #d7dbe3;}
 .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:1rem;}
 .tile{background:#fff;border:1px solid #e6e9ef;border-radius:12px;overflow:hidden;display:flex;flex-direction:column;}
 .tile img{width:100%;height:150px;object-fit:cover;background:#eee;}
 .tile .body{padding:.8rem;flex:1;display:flex;flex-direction:column;}
 .tile .price{font-weight:800;color:#1a1a1a;margin-top:.3rem;}
 .muted{color:#667;font-size:.9rem;} .row{display:flex;gap:.8rem;flex-wrap:wrap;} .row>*{flex:1 1 180px;}
 table{width:100%;border-collapse:collapse;font-size:.92rem;} th,td{text-align:left;padding:.5rem .6rem;border-bottom:1px solid #eef;}
 .pill{display:inline-block;font-size:.72rem;padding:.12rem .5rem;border-radius:999px;background:#FFF8E6;color:#7A5B00;border:1px solid #F3D98A;}
 .flash{border:1px solid #bfe3c8;background:#f0faf2;border-radius:10px;padding:.7rem 1rem;margin-bottom:1rem;}
 .flash.err{border-color:#f3c6c6;background:#fdf0f0;color:#a12;}
 .totals td{border:0;padding:.25rem .5rem;} .totals tr.grand td{font-weight:800;border-top:2px solid #1a1a1a;}
</style>
</head>
<body>
<div class="topbar">
  <a class="brand" href="<?= site_url('/') ?>" style="display:inline-flex;align-items:center;gap:.5rem;">
    <img src="/logo.png" alt="Weekend Tool Rentals">
  </a>
  <nav>
    <a href="<?= site_url('catalog') ?>">Catalog</a>
    <a href="<?= site_url('cart') ?>">Cart</a>
    <?php if (function_exists('auth') && auth()->loggedIn()): ?>
      <a href="<?= site_url('account') ?>">My Account</a>
      <?php if (wtr_is_admin()): ?><a href="<?= site_url('admin') ?>">Admin</a><?php endif ?>
      <a href="<?= site_url('logout') ?>">Sign out</a>
    <?php else: ?>
      <a href="<?= site_url('login') ?>">Sign in</a>
      <a href="<?= site_url('register') ?>">Register</a>
    <?php endif ?>
  </nav>
</div>
<div class="wrap">
  <?php if (! config('Wtr')->squareConfigured()): ?>
    <div style="background:#FFF8E6;border:1px solid #F3D98A;color:#7A5B00;border-radius:10px;padding:.7rem 1rem;margin-bottom:1rem;font-size:.9rem;">
      🚧 <strong>Soft launch:</strong> browse the full fleet, check availability, and reserve online. Card payment is being finalized — for now we confirm your reservation and arrange the deposit by phone/text.
    </div>
  <?php endif ?>
  <?php if (session()->getFlashdata('msg')): ?><div class="flash"><?= esc(session()->getFlashdata('msg')) ?></div><?php endif ?>
  <?php if (session()->getFlashdata('error')): ?><div class="flash err"><?= esc(session()->getFlashdata('error')) ?></div><?php endif ?>
  <?= $this->renderSection('content') ?>
</div>
<footer style="text-align:center;font-size:.78rem;color:#99a;padding:1.5rem;">
  Weekend Tool Rentals &middot;
  <a href="<?= site_url('terms') ?>">Terms</a> &middot;
  <a href="<?= site_url('privacy') ?>">Privacy</a> &middot;
  <a href="<?= site_url('faq') ?>">FAQ</a> &middot;
  <a href="<?= site_url('delivery') ?>">Request Delivery</a> &middot;
  <a href="<?= site_url('contact') ?>">Contact</a>
</footer>
</body></html>
