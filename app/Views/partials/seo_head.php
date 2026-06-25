<?php
/**
 * SEO + social + analytics <head> block. Included by layout/main.php.
 *
 * Optional view vars (set by controllers): $title, $description, $canonical,
 * $ogImage, $metaRobots, $jsonLd (array — page-specific structured data, e.g.
 * Product on an item page). All have sensible defaults so every page is covered.
 *
 * Analytics tags (GA4 / Google Ads / Meta Pixel) and the GSC verification meta
 * emit ONLY when their id is set in .env (Config\Wtr) — safe to ship inert.
 */
$wtr   = config('Wtr');
$brand = 'Weekend Tool Rentals';
$t     = trim((string) ($title ?? ''));
$pageTitle = ($t === '' || $t === $brand) ? $brand : ($t . ' | ' . $brand);
$desc  = trim((string) ($description
    ?? 'Weekend Tool Rentals — quality tools, trailers, and equipment for rent by the day or week in Bluffdale, Utah. Check live availability and reserve online; pickup or delivery.'));
$url    = $canonical ?? current_url();
$img    = $ogImage ?? base_url('logo.png');
$robots = $metaRobots ?? 'index,follow';
?>
<title><?= esc($pageTitle) ?></title>
<meta name="description" content="<?= esc($desc) ?>">
<meta name="robots" content="<?= esc($robots) ?>">
<link rel="canonical" href="<?= esc($url) ?>">
<?php if ($wtr->gscVerification !== ''): ?>
<meta name="google-site-verification" content="<?= esc($wtr->gscVerification) ?>">
<?php endif ?>
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= esc($brand) ?>">
<meta property="og:title" content="<?= esc($pageTitle) ?>">
<meta property="og:description" content="<?= esc($desc) ?>">
<meta property="og:url" content="<?= esc($url) ?>">
<meta property="og:image" content="<?= esc($img) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= esc($pageTitle) ?>">
<meta name="twitter:description" content="<?= esc($desc) ?>">
<meta name="twitter:image" content="<?= esc($img) ?>">
<?php
// LocalBusiness structured data (NAP from Config\Wtr).
$ld = array_filter([
    '@context'   => 'https://schema.org',
    '@type'      => 'LocalBusiness',
    'name'       => $wtr->businessName,
    'url'        => base_url('/'),
    'telephone'  => $wtr->businessPhone,
    'email'      => $wtr->businessEmail,
    'image'      => base_url('logo.png'),
    'priceRange' => '$$',
]);
if ($addr = $wtr->addressParts()) {
    $ld['address'] = array_merge(['@type' => 'PostalAddress', 'addressCountry' => 'US'], $addr);
}
echo '<script type="application/ld+json">' . json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) . "</script>\n";
if (! empty($jsonLd)) {
    echo '<script type="application/ld+json">' . json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) . "</script>\n";
}
?>
<?php if ($wtr->ga4Id !== '' || $wtr->googleAdsId !== ''): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= esc($wtr->ga4Id ?: $wtr->googleAdsId) ?>"></script>
<script>
window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());
<?php if ($wtr->ga4Id !== ''): ?>gtag('config','<?= esc($wtr->ga4Id) ?>');<?php endif ?>
<?php if ($wtr->googleAdsId !== ''): ?>gtag('config','<?= esc($wtr->googleAdsId) ?>');<?php endif ?>
</script>
<?php endif ?>
<?php if ($wtr->metaPixelId !== ''): ?>
<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','<?= esc($wtr->metaPixelId) ?>');fbq('track','PageView');</script>
<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=<?= esc($wtr->metaPixelId) ?>&ev=PageView&noscript=1"/></noscript>
<?php endif ?>
