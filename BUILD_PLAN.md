# Flora by Klo — Back-office (admin.florabyklo.com) — BUILD PLAN

Purpose-built CI4 + Shield florist back-office. **Option B** from `msl-ops/claude/runbook/florabyklo.md` Phase-3 spec (do NOT clone the DMS). Scaffolded from the Weekend Tool Rentals skeleton 2026-06-25.

## Stack / hosting
- CI4 ^4.7 + Shield (from WTR skeleton). Dev tree: **binks `/home/jason/florabyklo-admin`** (source of truth). Prod: **vader `/home/www/florabyklo-admin`** (docroot `…/public`).
- URL: **https://admin.florabyklo.com** → vader public IP **136.40.66.105** (internal 192.168.253.5). DNS A via GoDaddy API; LE cert via certbot DNS-01 (apex-aware hooks, `--account 713352c89412f67accbb1f0eaeac1b2d`).
- DB: MySQL on vader, schema **`florabyklo_admin`**, user `florabyklo` (creds in vader `.env`).
- Deploy: `deploy.sh` (rsync binks→vader incl. vendor; vendor built on binks — vader has no composer). `php spark migrate --all` after.
- Repo: `JbrownMSL/florabyklo-admin` (to create).

## Auth (Shield) — reuse WTR's hidden-system_admin pattern
- Groups: `customer` (unused), `admin` (Kloe — Manager/full), **`system_admin`** (HIDDEN — Jason, never listed).
- Seed: **Kloe** `kloe@florabyklo.com` → admin; **Jason** `jbrown@motorsportsland.com` → system_admin.
- `fbk_is_admin()` helper = admin OR system_admin; `/admin` behind `BaseAdmin::guard()`. NO DMS identity system.

## Database schema (`florabyklo_admin`) — migration CreateFbkCore
- **clients**: id, name, email, phone, address, source(inquiry/referral/instagram/…), status(lead/consult/booked/completed/lost), notes, created_at. (leads land here from the WP inquiry/Flamingo.)
- **events**: id, client_id, type(wedding/event/popup), event_date, venue, guest_count, status, capacity_weekend flag, notes. (per-weekend capacity cap logic.)
- **recipes**: id, name, type(bouquet/centerpiece/install/…), labor_minutes, notes. **recipe_stems**: id, recipe_id, stem_name, qty, unit_cost. → recipe cost = Σ(qty×unit_cost) + labor.
- **quotes**: id, client_id, event_id, status(draft/sent/accepted/declined), subtotal, tax, total, deposit_pct(default 50), valid_until, created_at. **quote_items**: id, quote_id, recipe_id(nullable), description, qty, unit_price, line_total, cost(from recipe→margin).
- **contracts**: id, quote_id, body, signed_name, signed_at, signature(typed/drawn), ip. (reuse WTR e-sign pattern.)
- **invoices**: id, quote_id, client_id, number, status(draft/sent/deposit_paid/paid/void), subtotal, tax, total, amount_paid, balance_due, due_date. **payments**: id, invoice_id, amount, method(square/cash/check/other), kind(deposit/balance/other), square_ref, status, paid_at.
- **expenses**: id, date, vendor, category(flowers/supplies/fuel/rent/…), amount, event_id(nullable→COGS allocation), plaid_txn_id(nullable), notes.
- **plaid_connections** / **plaid_transactions**: mirror DMS `gl.plaid_connections`/`gl.plaid_transactions` shape (item_id, access_token, account_id, name, mask; txn id, date, amount, name, category, pending). Used by the Plaid module.
- **pnl** = a VIEW/report, not a table: income (payments) − expenses, by month + per-event margin (quote total − Σ event COGS). Single-entry. NO double-entry GL.

## Modules (each = controller + model(s) + views + routes; admin-gated)
1. **Dashboard** (`/admin`) — KPIs: open leads, upcoming events, unpaid invoices $, MTD income/expense, this-weekend capacity.
2. **Clients/CRM** (`/admin/clients`) — list/add/edit, status pipeline, link events. Lead intake endpoint for the WP form.
3. **Events/Bookings** (`/admin/events`) — calendar + capacity cap per weekend.
4. **Recipes** (`/admin/recipes`) — stem-cost engine (recipe_stems CRUD → live cost+margin).
5. **Quotes/Proposals** (`/admin/quotes`) — itemized, pull recipes (auto cost/margin), deposit %, send (SendEmail), accept→invoice. PDF (mpdf, in WTR).
6. **Contracts** (`/admin/contracts`) — e-sign (reuse WTR SignaturePad/typed) gated on quote accept + deposit.
7. **Invoices/Payments** (`/admin/invoices`) — deposit/balance, Square (SIMULATED via WTR `Services/Square.php` — graceful when keys absent), record manual payments.
8. **Expenses** (`/admin/expenses`) — entry + category + per-event COGS; can be created from a Plaid txn.
9. **Plaid Bank** (`/admin/bank`) ⭐ REAL — Plaid Link (model on DMS `Plaid_ctl` + `App\Libraries\Plaid`): connect bank → sync transactions → categorize → create expense / mark income. **Use Jason's existing Plaid key** (DMS `App\Libraries\Plaid` reads it — pull the client_id/secret/env from there; likely a `gl`-side config or the DMS `.env`; confirm and put in florabyklo-admin vader `.env` as `plaid.clientId/secret/env`). Port `Plaid` library + Link view from the DMS. **View-only/no-auto-post** by default (mirror DMS double-gate ethos).
10. **P&L/Reports** (`/admin/reports`) — monthly income/expense + per-event margin.

## Reuse map
- `Services/Square.php` (WTR) — payments, simulated mode. `wtr_helper`→`fbk_helper` (rename). e-sign from WTR checkout. mpdf for quote/invoice PDFs. `SendEmail`-style mail (WTR uses `wtr_send_email`; here send via the same SMTP or the fleet pattern). Layout/admin views from WTR (restyle to florabyklo sage/blush palette later).
- Plaid: copy `/var/www/msl/app/Libraries/Plaid.php` + `app/Views/plaid/*` from the DMS as the starting point; swap `gl.plaid_*` tables → `florabyklo_admin.plaid_*`.

## Build order (workers)
P1: migration CreateFbkCore + helper/auth/seeder + Clients + Quotes + Invoices (+Square sim).
P2: Recipes (stem-cost) + Expenses + P&L report + Dashboard.
P3: Plaid module (real) + Contracts e-sign + Events/bookings calendar.
Integrations live in graceful/simulated mode until Kloe's Square keys + Plaid connect.

## STATUS
- [x] Scaffold from WTR (2026-06-25)
- [ ] Foundation: config/.env, DB, CreateFbkCore migration, auth seeder, fbk_helper
- [ ] Deploy base to vader (DNS+cert+vhost) — login works
- [ ] Modules P1 / P2 / P3
- [ ] Plaid key wired
