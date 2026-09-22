<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Shield auth routes (login, password reset, magic-link). Registration is OFF
// for this admin-only app — accounts are seeded via FbkAdminSeeder.
service('auth')->routes($routes);

// ---------------------------------------------------------------------------
// Public
// ---------------------------------------------------------------------------
$routes->get('/', 'Home::index');

// Lead-intake endpoint — called by the WP website inquiry form (no auth)
$routes->post('intake/lead', 'Intake::lead', ['as' => 'intake.lead']);

// ---------------------------------------------------------------------------
// Admin area — session-gated; group check in BaseAdmin::guard()
// ---------------------------------------------------------------------------
$routes->group('admin', ['filter' => 'session', 'namespace' => 'App\Controllers\Admin'], static function (RouteCollection $routes): void {

    // Dashboard
    $routes->get('',          'Dashboard::index', ['as' => 'admin.dashboard']);

    // Clients / CRM
    $routes->get('clients',              'Clients::index',       ['as' => 'admin.clients']);
    $routes->get('clients/new',          'Clients::form',        ['as' => 'admin.clients.new']);
    $routes->get('clients/(:num)',       'Clients::form/$1',     ['as' => 'admin.clients.edit']);
    $routes->post('clients/save',        'Clients::save',        ['as' => 'admin.clients.save']);
    $routes->post('clients/(:num)/status', 'Clients::setStatus/$1');
    $routes->get('clients/(:num)/form8300', 'Clients::form8300/$1');

    // Events / bookings
    $routes->get('events',               'Events::index',        ['as' => 'admin.events']);
    $routes->get('events/new',           'Events::form',         ['as' => 'admin.events.new']);
    $routes->get('events/(:num)',        'Events::form/$1',      ['as' => 'admin.events.edit']);
    $routes->post('events/save',         'Events::save',         ['as' => 'admin.events.save']);

    // Recipes (stem-cost engine)
    $routes->get('recipes',              'Recipes::index',       ['as' => 'admin.recipes']);
    $routes->get('recipes/new',          'Recipes::form',        ['as' => 'admin.recipes.new']);
    $routes->get('recipes/(:num)',       'Recipes::form/$1',     ['as' => 'admin.recipes.edit']);
    $routes->post('recipes/save',        'Recipes::save',        ['as' => 'admin.recipes.save']);
    $routes->post('recipes/(:num)/stem', 'Recipes::saveStem/$1');
    $routes->post('recipes/stem/(:num)/delete', 'Recipes::deleteStem/$1');

    // Quotes / proposals
    $routes->get('quotes',               'Quotes::index',        ['as' => 'admin.quotes']);
    $routes->get('quotes/new',           'Quotes::form',         ['as' => 'admin.quotes.new']);
    $routes->get('quotes/(:num)',        'Quotes::form/$1',      ['as' => 'admin.quotes.edit']);
    $routes->post('quotes/save',         'Quotes::save',         ['as' => 'admin.quotes.save']);
    $routes->get('quotes/(:num)/pdf',    'Quotes::pdf/$1',       ['as' => 'admin.quotes.pdf']);
    $routes->post('quotes/(:num)/send',  'Quotes::send/$1');
    $routes->post('quotes/(:num)/invoice', 'Quotes::createInvoice/$1');

    // Contracts / e-sign
    $routes->get('contracts',            'Contracts::index',     ['as' => 'admin.contracts']);
    $routes->get('contracts/(:num)',     'Contracts::show/$1',   ['as' => 'admin.contracts.show']);
    $routes->post('contracts/(:num)/sign', 'Contracts::sign/$1');

    // Invoices + payments
    $routes->get('invoices',             'Invoices::index',      ['as' => 'admin.invoices']);
    $routes->get('invoices/(:num)',      'Invoices::show/$1',    ['as' => 'admin.invoices.show']);
    $routes->post('invoices/(:num)/payment', 'Invoices::recordPayment/$1');
    $routes->get('invoices/(:num)/pdf',  'Invoices::pdf/$1');

    // Expenses
    $routes->get('expenses',             'Expenses::index',      ['as' => 'admin.expenses']);
    $routes->get('expenses/new',         'Expenses::form',       ['as' => 'admin.expenses.new']);
    $routes->get('expenses/(:num)',      'Expenses::form/$1',    ['as' => 'admin.expenses.edit']);
    $routes->post('expenses/save',       'Expenses::save',       ['as' => 'admin.expenses.save']);

    // #2948 receipt photos (stored outside the webroot; served only through Receipts)
    $routes->post('expenses/(:num)/receipts',      'Receipts::upload/$1');
    $routes->post('expenses/(:num)/receipt-waive', 'Receipts::waive/$1');
    $routes->get('receipts/(:num)',                'Receipts::show/$1');
    $routes->get('receipts/(:num)/thumb',          'Receipts::thumb/$1');
    $routes->post('receipts/(:num)/delete',        'Receipts::delete/$1');

    // Plaid bank
    $routes->get('bank',                 'Bank::index',          ['as' => 'admin.bank']);
    $routes->post('bank/link',           'Bank::linkToken');
    $routes->post('bank/exchange',       'Bank::exchange');
    $routes->post('bank/sync',           'Bank::sync');
    $routes->post('bank/txn/(:num)/expense', 'Bank::toExpense/$1');
    $routes->post('bank/txn/(:num)/income',  'Bank::toIncome/$1');
    $routes->post('bank/txn/(:num)/expense-photo', 'Bank::toExpenseWithPhoto/$1');

    // Income (#2946) — money in that is not an invoice payment
    $routes->get('income',               'Income::index',        ['as' => 'admin.income']);
    $routes->get('income/new',           'Income::form',         ['as' => 'admin.income.new']);
    $routes->get('income/(:num)',        'Income::form/$1',      ['as' => 'admin.income.edit']);
    $routes->post('income/save',         'Income::save',         ['as' => 'admin.income.save']);

    // Reports / P&L
    $routes->get('reports',              'Reports::index',       ['as' => 'admin.reports']);
    $routes->get('reports/pnl',          'Reports::pnl',         ['as' => 'admin.reports.pnl']);
    $routes->get('reports/mtd',          'Reports::mtd',         ['as' => 'admin.reports.mtd']);

    // Users
    $routes->get('users',                'Users::index',         ['as' => 'admin.users']);
    $routes->get('users/new',            'Users::form',          ['as' => 'admin.users.new']);
    $routes->get('users/(:num)',         'Users::form/$1',       ['as' => 'admin.users.edit']);
    $routes->post('users/save',          'Users::save');
    $routes->post('users/(:num)/active', 'Users::toggleActive/$1');
    $routes->post('users/(:num)/reset',  'Users::resetPassword/$1');
    $routes->post('users/(:num)/resend', 'Users::resendWelcome/$1');
});
