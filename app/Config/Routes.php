<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Stock Shield auth routes (login, register, magic-link, password reset).
// Registration is ON (customer self-signup) — see Config/Auth::$allowRegistration.
service('auth')->routes($routes);

// ---------------------------------------------------------------------------
// Public storefront (no login required)
// ---------------------------------------------------------------------------
$routes->get('/', 'Home::index');
$routes->get('catalog', 'Catalog::index');
$routes->get('catalog/(:segment)', 'Catalog::category/$1');
$routes->get('equipment/(:num)', 'Catalog::show/$1');
$routes->get('search', 'Catalog::search');
$routes->get('sitemap.xml', 'Sitemap::index');

// Legal / info
$routes->get('terms', 'Legal::terms');
$routes->get('privacy', 'Legal::privacy');
$routes->get('faq', 'Legal::faq');
$routes->get('contact', 'Legal::contact');
$routes->post('contact', 'Legal::contactSubmit');
$routes->get('delivery', 'Legal::delivery');          // request-a-delivery-quote form (emails Andrew)
$routes->post('delivery', 'Legal::deliverySubmit');

// Cart (session-based; no login needed to build a cart)
$routes->get('cart', 'Cart::index');
$routes->post('cart/add', 'Cart::add');
$routes->post('cart/remove', 'Cart::remove');
$routes->post('cart/trailer', 'Cart::toggleTrailer');   // remove/restore auto-added trailer
$routes->post('cart/waiver', 'Cart::toggleWaiver');     // opt in/out of the damage waiver
$routes->post('cart/availability', 'Cart::checkAvailability');  // AJAX date-range check

// ---------------------------------------------------------------------------
// Customer area (login required)
// ---------------------------------------------------------------------------
$routes->group('', ['filter' => 'session'], static function (RouteCollection $routes): void {
    // Checkout: contract e-sign → Square payment → reservation
    $routes->get('checkout', 'Checkout::index');
    $routes->post('checkout/contract', 'Checkout::acceptContract');
    $routes->post('checkout/pay', 'Checkout::pay');               // Square nonce → charge + hold
    $routes->get('checkout/confirm/(:num)', 'Checkout::confirm/$1');

    // My account
    $routes->get('account', 'Account::index');
    $routes->get('account/reservations', 'Account::reservations');
    $routes->get('account/reservation/(:num)', 'Account::reservation/$1');
    $routes->post('account/reservation/(:num)/cancel', 'Account::cancel/$1');
    $routes->get('account/profile', 'Account::profile');
    $routes->post('account/profile', 'Account::saveProfile');
});

// ---------------------------------------------------------------------------
// Admin area (admin group only — gated in BaseAdminController)
// ---------------------------------------------------------------------------
$routes->group('admin', ['filter' => 'session', 'namespace' => 'App\Controllers\Admin'], static function (RouteCollection $routes): void {
    $routes->get('', 'Dashboard::index');

    // Users / staff logins
    $routes->get('users', 'Users::index');
    $routes->get('users/new', 'Users::form');
    $routes->get('users/(:num)', 'Users::form/$1');
    $routes->post('users/save', 'Users::save');
    $routes->post('users/(:num)/active', 'Users::toggleActive/$1');
    $routes->post('users/(:num)/reset', 'Users::resetPassword/$1');
    $routes->post('users/(:num)/resend', 'Users::resendWelcome/$1');

    // Inventory / listings
    $routes->get('equipment', 'Equipment::index');
    $routes->get('equipment/new', 'Equipment::form');
    $routes->get('equipment/(:num)', 'Equipment::form/$1');
    $routes->post('equipment/save', 'Equipment::save');
    $routes->post('equipment/(:num)/media', 'Equipment::saveMedia/$1');
    $routes->post('equipment/(:num)/ownership', 'Equipment::saveOwnership/$1');
    $routes->post('equipment/import', 'Equipment::importCsv');     // one-time CSV inventory import

    // Reservations + returns
    $routes->get('reservations', 'Reservations::index');
    $routes->get('reservations/(:num)', 'Reservations::show/$1');
    $routes->post('reservations/(:num)/pickup', 'Reservations::markPickedUp/$1');
    $routes->post('reservations/(:num)/return', 'Reservations::markReturned/$1');   // release/capture deposit
    $routes->post('reservations/(:num)/damage', 'Reservations::logDamage/$1');
    $routes->post('reservations/(:num)/refund', 'Reservations::refund/$1');         // tiered cancellation refund

    // Maintenance (writes blackouts)
    $routes->get('maintenance', 'Maintenance::index');
    $routes->post('maintenance/log', 'Maintenance::log');
    $routes->post('maintenance/(:num)/close', 'Maintenance::close/$1');

    // Reports + financials (Phase 2/3)
    $routes->get('reports', 'Reports::index');
    $routes->get('reports/tax', 'Reports::tax');                  // tax-collected (remittance)
    $routes->get('reports/utilization', 'Reports::utilization');
    $routes->get('reports/pnl', 'Reports::pnl');                  // per-item P&L after owner splits
    $routes->get('reports/owner/(:num)', 'Reports::ownerStatement/$1');
});
