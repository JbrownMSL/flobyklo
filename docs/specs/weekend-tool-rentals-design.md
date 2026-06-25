# Weekend Tool Rentals — project spec & build plan

## Context

Andrew Brown (`abrown@motorsportsland.com`) owns **weekendtoolrentals.com** (registered 2026‑06‑08) and a Utah‑registered equipment‑rental business. On 2026‑06‑08 he recorded a voice‑Claude session defining the project and emailed it to Jason (06‑09) expressly to be fed into fleet Claude Code to build. He wants a **CodeIgniter 4** site on **vader** to list rental equipment, take date‑range reservations, collect deposits + payment via **Square**, generate signed rental contracts, and give him admin tools (inventory, financials, maintenance, profit‑split with equipment co‑owners).

This plan captures his requirements verbatim, flags what he **missed/underspecified**, recommends **what to add**, and lays out an architecture grounded in existing fleet patterns so we don't reinvent what the RV‑Storage module + the EP/Mojo CI4 skeleton already solve. **Goal of this turn:** a complete, reviewable spec + phased build plan — not a full build (the actual deploy is on vader and should be driven from vader's Claude per fleet rules).

---

## 1. Requirements Andrew specified (from his chat)

- **Stack/deploy:** CI4, "Shield off," strip unused libraries, host on **vader**, point DNS, install SSL.
- **Customer site:** homepage; browse + search; listing detail (image gallery, embedded how‑to **videos** + **PPE/safety** instructions on dangerous items); booking flow.
- **Booking:** select item + date range → **real‑time availability check** → reserve → block those dates. Checkout = accept rental contract + damage waiver, insurance selection, pay deposit.
- **Cancellation refund tiers:** >48h → 75% refunded; 24–48h → keep 50%; <24h → keep 75%. (Baked into contract.)
- **Accounts:** customer login; view/modify reservations + history.
- **Payments:** **Square** (he has a Square kiosk). Optional save‑card; **no card data stored on our servers** (Square token only).
- **Inventory:** **one‑time import** from his existing Google Sheet.
- **Trailers + Utah tax:** some items **auto‑add a transport trailer** (default‑on, removable if renter has their own adequate trailer). Trailers = motor vehicles under Utah law → **separate motor‑vehicle rental tax line** distinct from equipment rental tax; tag each item's tax class.
- **E‑signature:** display contract + "I agree" checkbox + timestamp + customer ID (Utah UETA); renter picks **typed name OR drawn signature** (SignaturePad).
- **Admin:** inventory mgmt, listing CRUD, rental‑request handling, customer accounts, payments, dashboard (upcoming rentals + maintenance‑due), damage reporting between rentals.
- **Ownership/profit‑split:** per‑item ownership (owned / partial % / fully leased from another entity); **auto‑allocate rental income** per ownership; track income/expense/maintenance per item; **reporting** (net profit per item after partner splits).
- **Add‑ons agreed:** email booking confirmations, FAQ + contact page. **Deferred:** per‑equipment customer reviews.

---

## 2. What Andrew missed / underspecified (gaps to resolve)

1. **Auth contradiction (biggest).** He said "Shield off," but also wants customer accounts, login, and reservation history — which *require* auth. "Shield off" almost certainly meant "not the heavy DMS identity/roles," not "hand‑roll auth." **Recommend keeping Shield** (the house standard in EP/Mojo/Van Wagenen) for customer registration/login/password‑reset/pwned‑password; disable only its group/permission complexity. Hand‑rolling auth on a public payment site is a security liability.
2. **Pricing model undefined.** No rate structure: daily vs half‑day vs weekly, multi‑day discounts, minimum rental period. Need a rate schema per item.
3. **Deposit semantics conflated.** Two different deposits are mixed: the **reservation deposit** (subject to the cancellation tiers) vs a **damage/security deposit** (refundable if returned in good condition). Also unclear: is the *full rental* paid upfront or only a deposit, and is the balance due at booking or pickup? Square supports **auth‑and‑capture** (place a hold, capture on damage) — ideal for the damage deposit.
4. **Inventory cardinality.** Does he have one unit per tool or multiple? Availability is boolean for single‑unit but **count‑based** for multi‑unit. Needs an explicit SKU‑vs‑serialized‑unit decision.
5. **Return / overdue workflow.** He covers booking but not the **return**: condition inspection that releases or captures the damage deposit, late/overdue handling + late fees, equipment‑breakdown‑before‑pickup → full refund.
6. **Maintenance ↔ availability link.** He wants maintenance tracking and a "maintenance‑due" dashboard, but an item in for repair must also be **blocked from booking** — maintenance windows must feed the availability engine.
7. **Insurance/damage‑waiver pricing.** "Insurance selection" + "damage waiver" mentioned but never priced or defined (flat fee? % of rental? what it covers).
8. **Tax remittance side.** The site computes two Utah taxes, but he didn't ask for the **tax‑collected report** he'll need to actually remit them.
9. **Renter eligibility / liability.** No age/ID verification, no pickup ID check, nothing on high‑value‑equipment risk. Worth at least an age attestation + email‑verified account.
10. **Notifications beyond confirmation.** Only booking confirmation mentioned; needs **pickup reminder, return reminder, overdue notice, deposit‑refund notice**. Requires SPF/DKIM/DMARC on weekendtoolrentals.com.
11. **Cancellation refund execution.** The tiered refunds require Square **partial‑refund** API calls — an operational flow, not just a policy.
12. **Cart with multiple items + trailer interaction.** He implied a cart; availability must be checked per line, and the auto‑trailer rule must work inside a multi‑item cart.
13. **Legal pages.** Terms of Service + Privacy Policy (collecting PII + processing payment) are mandatory and unmentioned.

---

## 3. What we should add (recommendations)

- **Keep Shield** for customer auth (registration/login/reset), groups disabled → satisfies "no heavy roles" while staying secure. One admin group for Andrew.
- **Square Web Payments SDK** (card‑not‑present, tokenized) for web checkout + **delayed capture** for the damage deposit hold. Do **NOT** copy the RV‑Storage AES‑card‑in‑DB/PayTrace pattern — Square tokenization keeps all card data off our servers (exactly Andrew's stated requirement and far less PCI exposure).
- **First‑class availability engine:** `reservations` + `unit_blackouts` (booked + maintenance) with an overlap query; model **SKU + units** so multi‑unit tools and maintenance downtime both work.
- **Two‑deposit model:** reservation deposit (cancellation tiers, Square refunds) + damage deposit (Square auth‑hold, capture only on a logged damage report).
- **Tax engine:** per‑item `tax_class` (`equipment_rental` | `motor_vehicle_rental`) → separate tax line items + a **tax‑collected report** for remittance. (Pattern echoes DMS `msl.defaults` location‑rate lookup, but standalone here.)
- **Versioned contracts + e‑sign log:** store which contract version was signed, method (typed/drawn), signature image/name, timestamp, IP, user‑agent.
- **Admin dashboard:** upcoming rentals, returns due, overdue, maintenance due, open damage reports, per‑item P&L after owner splits.
- **Ownership/profit‑split ledger:** per‑item ownership %, automatic income allocation per reservation, partner statements.
- **One‑time inventory import via CSV** (Andrew exports his Google Sheet → upload → validated batch insert). Avoids building Google OAuth for a one‑shot load (model on `cronJobs/meyer_price_sheet_import.php`). Offer Sheets‑API only if he later wants recurring sync.
- **Media + safety fields** per listing (images, video URL, PPE list, safety instructions) as he asked.
- **Operational reports:** per‑item utilization + revenue, plus the tax‑remittance report.
- **Phased delivery** so an MVP can launch before the financial/profit‑split depth is finished.

---

## 4. Recommended architecture

- **Standalone CI4 app on vader**, separate from the DMS — its own GitHub repo + its own local MySQL DB on vader (same model as Mojo/EP/Van Wagenen). NOT a DMS module, NOT on yoda.
- **Skeleton:** clone the house CI4 skeleton (Mojo/EP) → strip club‑specific code → keep Shield (customer‑only), migrations pattern, `Config/*`, `deploy.sh`, `composer.json`. Keep `autoRoutesImproved=false` + `translateUriToCamelCase=false` (`runbook/ci4.md`).
- **Repo:** `git@github.com:JbrownMSL/weekendtoolrentals.git`; dev clone `/home/jason/weekendtoolrentals` on binks; vader pulls read‑only; deploy via `deploy.sh` (rsync + perms + SELinux relabel + `php spark migrate --all` + reload). **`migrate --all`, never bare `migrate`.**
- **Hosting:** docroot `/home/www/weekendtoolrentals/public`; Apache vhost `/etc/httpd/conf.d/weekendtoolrentals.com.conf` (template from `readyneighbor.org.conf`); `CI_ENVIRONMENT=production`.
- **DNS:** GoDaddy API A‑record `weekendtoolrentals.com → 136.40.66.105` (+ www CNAME); TTL floor 600s (`runbook/godaddy.md`).
- **SSL:** certbot DNS‑01 via the apex‑aware `/root/godaddy_acme_auth.sh` + `_clean.sh` hooks (the polling hook has the 2‑label‑apex bug — avoid).
- **Reuse patterns (not code):** RV‑Storage module (`Storage_ctl.php`, `app/Views/storage/CLAUDE.md`) as the conceptual template for *separate customer file + line‑item invoice + payment audit*; Payroc/PayTrace helpers as structural references for the Square integration; `meyer_price_sheet_import.php` for the CSV importer.
- **Email:** vader sendmail + OpenDKIM (as `mslrv.biz`) or Workspace relay; add SPF/DKIM/DMARC for the new domain.

---

## 5. Data model (vader‑local `weekendtoolrentals` DB; sketch)

- **Shield tables** (`users`, `auth_identities`, …) — customer accounts + one admin.
- `customer_profiles` — user_id, name, phone, address, age‑attestation, square_customer_id.
- `categories` — equipment categories/nav.
- `equipment` — name, category_id, description, daily_rate, weekly_rate, min_days, deposit_amount, damage_deposit_amount, **tax_class**, requires_trailer (FK→trailer equipment), active, status.
- `equipment_media` — equipment_id, type (image|video|safety_doc), path/url, sort.
- `equipment_safety` — equipment_id, ppe_required (list), safety_instructions (html), is_dangerous.
- `units` — equipment_id, serial/asset tag, status (available|maintenance|retired) — for count‑based availability (collapse to 1 row if single‑unit).
- `reservations` — customer_id, status (pending|confirmed|picked_up|returned|cancelled), start_date, end_date, contract_acceptance_id, totals, created_at.
- `reservation_items` — reservation_id, equipment_id/unit_id, qty, rate_snapshot, line_subtotal, tax_class, tax_amount, is_trailer, auto_added.
- `unit_blackouts` — unit_id, start_date, end_date, reason (booked|maintenance), reservation_id (nullable) — **the availability source of truth** (overlap query).
- `payments` — reservation_id, square_payment_id, type (rental|reservation_deposit|damage_hold|damage_capture|refund), amount, status, created_at.
- `deposits` — reservation_id, kind (reservation|damage), amount, square_ref, captured_amount, refunded_amount, status.
- `contracts` — version, title, body_html, effective_at.
- `contract_acceptances` — reservation_id, customer_id, contract_version, method (typed|drawn), signature_name, signature_image_path, accepted_at, ip, user_agent.
- `tax_rates` — tax_class, rate, label, active.
- `equipment_ownership` — equipment_id, owner_entity, owned_pct, split_terms.
- `income_allocations` — reservation_item_id, owner_entity, amount.
- `equipment_costs` — equipment_id, type (purchase|expense|maintenance), amount, date, notes.
- `maintenance_log` — equipment_id/unit_id, type, start, end, cost, notes (writes a `unit_blackouts` row).
- `damage_reports` — reservation_id, equipment_id, description, photos, assessed_cost, deposit_captured.
- `email_log` — recipient, type, reservation_id, sent_at.

---

## 6. Key flows

- **Availability:** for each cart item + date range, `SELECT 1 FROM unit_blackouts WHERE equipment_id=? AND NOT (end_date < :start OR start_date > :end)` against unit count; reserve = insert `unit_blackouts(reason=booked)`.
- **Trailer auto‑add:** if `equipment.requires_trailer`, inject the trailer line (`auto_added=1`, `tax_class=motor_vehicle_rental`); renter may remove with an explicit "I have my own adequate trailer" confirmation.
- **Tax:** each line taxed by its `tax_class` rate → two subtotaled tax lines on the invoice; feeds the remittance report.
- **Payment (Square):** rental + reservation‑deposit **captured** at booking; damage deposit placed as an **auth hold**; on clean return → void/release hold; on damage → capture up to assessed cost. Cancellations → Square **partial refund** per the tier.
- **E‑sign:** show current `contracts` version; capture checkbox + typed name and/or SignaturePad image → write `contract_acceptances` (with IP/UA/timestamp) before payment finalizes.
- **Profit‑split:** on confirmed reservation, allocate each item's rental income per `equipment_ownership` into `income_allocations`; admin P&L nets income − costs − partner shares.

---

## 7. Phasing

- **Phase 0 — Infra:** repo + skeleton; vader DB/user; DNS + SSL + vhost; SPF/DKIM/DMARC; `.env`; first `deploy.sh` green; homepage 200.
- **Phase 1 — MVP (customer‑facing):** catalog + search + listing pages (media/safety); Shield accounts; date‑range availability + booking; cart incl. auto‑trailer; tax engine (2 classes); contract e‑sign; Square checkout (rental + reservation deposit) + damage‑deposit hold; booking confirmation email; **one‑time CSV inventory import**; ToS/Privacy/FAQ/Contact.
- **Phase 2 — Operations/admin:** admin dashboard (upcoming/returns/overdue/maintenance/damage); return + damage‑report flow (deposit capture/release); cancellation refunds (tiered, Square); maintenance log ↔ blackouts; reminder/overdue/refund emails; tax‑collected report.
- **Phase 3 — Financials & extras:** ownership/profit‑split ledger + partner statements; per‑item P&L + utilization reports; optional save‑card; (deferred) reviews; optional Google Sheets recurring sync.

---

## 8. Open decisions for Andrew (surface before/early in build)

1. **Auth:** ✅ **DECIDED (Jason, 2026‑06‑09) — Shield ON.** Customer accounts via Shield (registration/login/password‑reset/pwned‑password), groups disabled except one admin group for Andrew. Keep `codeigniter4/shield` in composer; wire a custom branded login view.
2. **Pricing:** daily/weekly rates, minimum rental days, multi‑day discounts.
3. **Payment timing:** full rental upfront vs deposit‑now/balance‑at‑pickup; damage‑deposit hold amount per item.
4. **Inventory cardinality:** single unit per tool, or quantities (multi‑unit)?
5. **Insurance/damage‑waiver:** options + pricing + coverage.
6. **Pickup/return:** location(s), hours, delivery offered?, late‑fee policy.
7. **Renter eligibility:** age minimum, ID check at pickup?
8. **Square account/keys:** which Square merchant + API credentials (Web Payments SDK app id + access token).
9. **Domain email:** confirm weekendtoolrentals.com mail (Workspace secondary vs vader sendmail+DKIM).

---

## 9. Build/deploy recipe (condensed; full detail in `runbook/vader.md` + `runbook/godaddy.md`)

1. Create `JbrownMSL/weekendtoolrentals`; clone Mojo skeleton on binks → strip → keep Shield/migrations/deploy.sh.
2. GoDaddy A‑record + www CNAME → `136.40.66.105` (TTL 600).
3. vader: `certbot certonly --manual --preferred-challenges=dns --manual-auth-hook /root/godaddy_acme_auth.sh --manual-cleanup-hook /root/godaddy_acme_clean.sh -d weekendtoolrentals.com -d www.weekendtoolrentals.com`.
4. vader: vhost `/etc/httpd/conf.d/weekendtoolrentals.com.conf`; create DB + user; app dir `/home/www/weekendtoolrentals` with `writable/` 2770 root:apache; `.env` (640 root:apache, `CI_ENVIRONMENT=production`, baseURL, encryption key, DB, Square keys).
5. binks: `bash deploy.sh` (rsync + perms + SELinux relabel + `php spark migrate --all` + reload php‑fpm).

---

## 10. Verification

- **Infra:** `dig +short A weekendtoolrentals.com` = 136.40.66.105; `curl -I https://weekendtoolrentals.com/` → 200; cert valid; error log clean.
- **MVP:** create account (Shield) → log in; book an item for a date range → second overlapping booking is refused; auto‑trailer appears with a separate motor‑vehicle tax line; equipment + motor‑vehicle tax both shown; sign contract (typed + drawn) → `contract_acceptances` row written; Square sandbox checkout captures rental+reservation deposit and places damage hold; confirmation email received; CSV import populates `equipment` and a spot‑check matches the sheet.
- **Phase 2/3:** cancel within each tier → correct partial refund via Square; mark maintenance → item unbookable for that window; return clean → damage hold released; log damage → hold captured to assessed cost; admin P&L nets owner splits; tax report totals reconcile to `payments`.
- Deliver this document into the new repo (e.g. `docs/specs/weekend-tool-rentals-design.md`) as the canonical spec once approved.

---

## Note on ownership of execution
weekendtoolrentals.com runs on **vader**; per fleet rules vader work is driven from vader's local Claude. This binks session produced the spec/plan; **Phase 0 scaffolding + deploy should be executed from a vader session** (or explicitly coordinated). Confirm where you want the build to run.
