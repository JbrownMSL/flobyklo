# Weekend Tool Rentals

Equipment-rental booking site for **weekendtoolrentals.com** — CodeIgniter 4 + Shield, deployed on **vader**. Built 2026-06-09 (autonomous build from Andrew Brown's project brief). Full spec: [`docs/specs/weekend-tool-rentals-design.md`](docs/specs/weekend-tool-rentals-design.md).

## What it does
- **Storefront:** catalog + search, equipment detail pages (photos, how-to video, PPE/safety), session cart.
- **Booking:** date-range availability engine (count-based, maintenance-aware), auto-added transport trailer (removable), two-class Utah tax (equipment vs motor-vehicle), optional damage waiver.
- **Checkout:** e-signed rental agreement (typed name **or** drawn signature via SignaturePad) → Square Web Payments → reservation; rental charged + refundable damage deposit held; confirmation email.
- **Accounts:** Shield customer login/registration; view/modify/cancel reservations (tiered refunds).
- **Admin:** dashboard, inventory CRUD + one-time CSV import, reservations (pickup/return/damage/refund), maintenance (blocks availability), reports (tax remittance, utilization, per-item P&L, owner statements).
- **Profit-split:** per-item ownership → automatic income allocation + partner statements.

## Stack
CI4 ^4.7, Shield ^1.3 (customer + admin groups), mpdf (contract PDFs). Local MySQL on the deploy host. Square via REST (no SDK) — **graceful**: with no keys it records bookings in sandbox/record-only mode so the whole flow is testable.

## Local dev
```bash
composer install
cp env .env            # set CI_ENVIRONMENT, app.baseURL, DB, encryption.key, wtr.square.*
php spark key:generate
php spark migrate --all # IMPORTANT: --all (Shield + app migrations)
php spark db:seed WtrSeeder   # tax classes, v1 contract, starter categories
php spark serve
```
Make yourself admin: `php spark shield:group add admin you@example.com` (or insert into `auth_groups_users`).

## Deploy (binks → vader)
`bash deploy.sh` — rsync + perms + SELinux relabel + `php spark migrate --all` + reload php-fpm. DNS (GoDaddy A → 136.40.66.105) + Let's Encrypt cert + Apache vhost are one-time vader steps (see the design doc §9).

## Configuration / judgment-call defaults
All business defaults live in `app/Config/Wtr.php` (env-overridable as `wtr.*`): pricing, deposit %, cancellation tiers, damage-waiver %, late fee, tax classes, renter age, Square keys. These were chosen during the autonomous build and are itemized in the decisions email sent to Andrew — confirm/adjust before launch.

## Status
All phases scaffolded and lint-clean. **Not yet run against a live DB or Square** — needs `composer install`, a DB, and Square keys on the host. Tax rates in the seeder are placeholders pending Andrew/accountant confirmation.
