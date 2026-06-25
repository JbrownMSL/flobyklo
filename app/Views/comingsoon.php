<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Weekend Tool Rentals — Launching Soon</title>
<meta name="robots" content="noindex">
<style>
 *{box-sizing:border-box} html,body{height:100%}
 body{margin:0;font-family:system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;
   background:#1a1a1a;color:#fff;display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center;padding:1.5rem;}
 .card{max-width:560px;}
 h1{font-size:2.2rem;margin:.2rem 0;} h1 b{color:#F0B810;}
 p{color:#cbd5e1;font-size:1.05rem;line-height:1.6;}
 .badge{display:inline-block;background:#F0B810;color:#1a1a1a;font-weight:800;padding:.35rem 1rem;border-radius:999px;letter-spacing:.04em;font-size:.8rem;margin-bottom:1rem;}
 .contact{margin-top:1.6rem;font-size:.95rem;color:#e5e7eb;}
 .contact a{color:#F0B810;text-decoration:none;}
</style>
</head>
<body>
<div class="card">
  <div class="badge">LAUNCHING SOON</div>
  <h1>🔧 Weekend<b>Tool</b>Rentals</h1>
  <p>Online booking for tractors, skid steers, trailers, and jobsite tools is almost ready. We're putting the finishing touches on secure checkout.</p>
  <div class="contact">
    Need a rental now? Call <a href="tel:<?= esc(preg_replace('/[^0-9]/', '', config('Wtr')->businessPhone)) ?>"><?= esc(config('Wtr')->businessPhone) ?></a>
    &nbsp;·&nbsp; <a href="mailto:<?= esc(config('Wtr')->businessEmail) ?>"><?= esc(config('Wtr')->businessEmail) ?></a><br>
    <span style="color:#94a3b8;font-size:.85rem;"><?= esc(config('Wtr')->pickupAddress) ?></span>
  </div>
</div>
</body></html>
