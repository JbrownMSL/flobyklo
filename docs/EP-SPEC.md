# EP — Ward Emergency Preparedness Tracker — Complete Specification

> Reverse-documentation of a live, fully-built system. Generated from the codebase at
> `/home/jason/ep` (binks dev tree) + runbook `~/.claude/runbook/ep-project.md`, cross-checked
> against the live database on vader. Status as of 2026-06-01: **Phases 0–8 SHIPPED — build complete.**

---

## 1. Overview & Purpose

EP is a web application for tracking the **emergency-preparedness readiness of every household in an
LDS ward**, so ward leadership (and especially block captains) can respond when "technology fails."
It digitizes the paper *Family Preparedness Survey* (South Jordan Stake) into a per-household record,
overlays households on neighborhood/apartment-floor maps, and produces print/PDF reports for offline
use during an actual emergency.

- **Live:** https://readyneighbor.org (canonical, cutover 2026-06-02) — `ep.jnbgroup.net` still serves the same instance.
- **Owner / sole admin:** Jason Brown (`jbrown@motorsportsland.com`); personal/church project, **NOT MSL business**.
- **First tenant:** South Jordan Stake → 8th Ward (the "Prospector Place" subdivision + Beckstead Apartments).
- **Designed multi-ward / multi-stake** from day one (see §7).

### Who uses it
| User | What they do |
|---|---|
| **System Admin** (Jason) | Hidden backdoor; full control of everything incl. role catalog. |
| **General Admin** | Visible site admin; full edit + assign across all wards. |
| **Bishopric + EP Coordinator** | Full ward-wide edit + can assign callings/captains in their ward. |
| **RS / EQ presidency, SS pres, ward mission leader** | Read-only ward-wide. |
| **Block Captain + Assistant** (pairs) | Edit only their assigned block's households. |
| **Stake President** | Read-only across all wards in the stake. |
| **Household member** | Edits only their own family's record. |

### LDS-ward domain model
```
stake  (e.g. "South Jordan")
 └─ ward  (e.g. "8th Ward")
     ├─ block / zone  (a block-captain pair covers a numbered zone; e.g. "1 · Copper King 1")
     │   └─ household  (a family at one address)
     │        ├─ residents       (kids/parents/etc. beyond head+spouse)
     │        └─ sub-residence    (basement renter / 2nd family unit at the SAME address —
     │                            itself a households row, with its own residents)
     └─ map_pages  (neighborhood plat, parkway frontage, 4 apartment floors)
```
A **sub-residence** (basement) is **not** a separate table — it is a `households` row with
`is_sub_residence=1` + `parent_household_id` set, inheriting the parent's ward/block/address.

---

## 2. Architecture & Stack

| Layer | Choice |
|---|---|
| Framework | CodeIgniter 4 (**4.7.3**) appstarter |
| Auth | CodeIgniter **Shield** (^1.3) — extended for email-or-phone OTP login |
| PDF | **mPDF** (^8.3.1) |
| PHP | 8.3 |
| DB | MySQL **8.4.9**, database `ep`, user `ep@localhost` (local to vader) |
| Repo | `git@github.com:JbrownMSL/ep` branch **main** (binks SSH key authenticates as JbrownMSL) |

Sister project: **vanwagenen** (thevanwagenens.com) — same CI4 4.7 + Shield + mPDF + vader-local-MySQL + rsync-deploy skeleton.

### CI4 structure (under `app/`)
- `Config/` — `Routes.php`, `Roles.php` (role catalog), `Survey.php` (form labels), `Auth.php`/`AuthGroups.php` (Shield), `Filters.php`.
- `Controllers/` — `Home`, `OverviewController`, `HouseholdController`, `MapController`, `MemberController`, `ReportController`; `Controllers/Auth/` (`LoginController`, `ChangePasswordController`); `Controllers/Admin/` (`RolesController`, `RoleDefsController`).
- `Models/` — Household, Resident, Block, Ward, Stake, MapPage, Member, RoleAssignment, RoleDef, User (extends Shield's).
- `Libraries/` — `ScopeService` + `Scope` (row-scope authz), `RoleService` (assign/clear), `UserProvisioner` (OTP accounts), `Notifier` (email/SMS).
- `Helpers/` — `phone_helper` (E.164), `ep_helper` (`ep_find_user`).
- `Filters/` — `CanAssignFilter`.
- `Commands/` — 10 `ep:*` spark commands (§6).
- `Database/Migrations/` — 14 migrations (the data model, §3).
- `Views/` — see §5.

### Request flow
- **Login is OTP-only — there are no chosen passwords until the member sets one.** A member is provisioned with an 8-digit one-time code as their initial password + `force_reset=1`; they sign in with it (login field accepts **email OR phone**), get bounced to `/change-password`, set a real password, then land on `/`.
- Almost every route is behind the `session` + `force-reset` filter group. Admin routes add `can-assign`. `change-password` is behind `session` only (so a force-reset member can reach it without a redirect loop).
- **The single chokepoint for "which rows" is `ScopeService`** — controllers never read Shield groups to decide visibility (see §4).

### Deployment
- **Dev tree (binks):** `/home/jason/ep`.
- **Prod (vader):** `/home/www/ep`, served from `/home/www/ep/public`. vhost `/etc/httpd/conf.d/ep.jnbgroup.net.conf` (HTTP→HTTPS → `192.168.253.5:443`).
- **Deploy loop:** edit on binks →
  `rsync -az --delete --exclude .git --exclude .env --exclude 'writable/*' /home/jason/ep/ vader:/home/www/ep/`
  → on vader run `php spark migrate` (if migrations changed) + `systemctl reload httpd php-fpm`.
- **DNS:** `ep.jnbgroup.net A 136.40.66.105` (vader public IP), GoDaddy, TTL 600.
- **Cert:** Let's Encrypt DNS-01 via `/root/request_cert.sh ep.jnbgroup.net` (GoDaddy hook).
- **Secrets:** `/home/www/ep/.env` (640, root:apache). Public registration disabled; tz `America/Denver`.

---

## 3. Data Model

Every migration lives in `app/Database/Migrations/` (all dated `2026-06-01-0000NN`). Below, each table
with its columns + purpose. **Shield's own tables** (`users`, `auth_identities`, `auth_groups_users`,
`auth_logins`, `auth_token_logins`, `auth_remember_tokens`, `settings`) are also in use.

### Tenancy hierarchy (migration `000003_CreateWardStructure`)

**`stakes`** — `id`, `name`, `created_at`, `updated_at`.
**`wards`** — `id`, `stake_id` (FK), `name`, `map_image` (added `000009`), `created_at`, `updated_at`.
**`blocks`** — `id`, `ward_id` (FK), `name`, `map_region` (TEXT, overlay bbox JSON — legacy, superseded by per-household polygons), `captain_name` (VARCHAR 255 — widened `000013`), `captain_phone` (added `000007`), `created_at`, `updated_at`.

### `role_assignments` (migration `000003`)
The single authoritative scope anchor for every **non-global** role. **One row per user** —
`UNIQUE(user_id)` — because roles are mutually exclusive.
| Column | Purpose |
|---|---|
| `id` | PK |
| `user_id` | UNIQUE — one role per user |
| `role_key` | matches a key in `Config\Roles` / `role_defs` |
| `stake_id` | populated for stake roles |
| `ward_id` | populated for ward + household roles |
| `block_id` | populated for block roles (ward derived via `blocks.ward_id`) |
| `assigned_by`, `created_at`, `updated_at` | audit |

Global roles (`system_admin`, `general_admin`) are **Shield groups**, NOT rows here.

### `households` (created `000004`, **reshaped to the survey** `000005`)
The core preparedness record. The `000004` spec-guess schema (food_storage_days, has_generator, etc.)
was dropped same-day and replaced by the **form-accurate ~72-column schema** in `000005`, generated
from `Config\Survey` so the form and DB can never drift.

**Scope anchors / structure:**
- `id`, `user_id` (head's login — UNIQUE, nullable), **`spouse_user_id`** (spouse's login — added 2026-06-02; two adults per household, both resolve to + edit this record), `ward_id` (req), `block_id` (nullable).
- `parent_household_id`, `is_sub_residence` (0/1), `sub_label` (e.g. "Basement") — added `000008`.
- `survey_returned` (Y/N/null — added `000006`), `map_page_id` (added `000012`).

**Survey header / contact:**
- `last_name`, `first_name`, `spouse_name`, `spouse_phone`, `spouse_email` (`000007`), `survey_date` (DATE), `address`.
- `phone_cell`, `phone_home`, `phone_work`, `other_residents` (TEXT), `email`.
- `husband_edu_occ`, `wife_edu_occ`.
- `lat`, `lng`; **map overlay** `map_x`, `map_y` (DECIMAL 5,2 percent — `000009`), `map_polygon` (TEXT JSON `[[x,y],…]` percent, centroid → map_x/map_y — `000011`).

**In-case-of-emergency contact:** `ec_name_relation`, `ec_phone`, `ec_email`.

**Skills (per Husband `_h` + Wife `_w`)** — 11 booleans each from `Config\Survey::$skills`:
`auto_mechanic, cert_hazmat, first_aid_cpr, ham_operator, utilities, heavy_equipment, medical, military, trades, police_fire, counseling` → `skill_{key}_h` / `skill_{key}_w` + `skills_other` (TEXT).

**Equipment** — 12 booleans from `Config\Survey::$equipment`:
`atv_4x4, commercial_equip, chainsaw, adv_first_aid, generator, ham_radio, heaters_fuel, propane, mass_cooking, self_rv, portable_sanitation, water_purifier` → `equip_{key}` + `equipment_other` (TEXT).

**Special needs + Y/N preparedness questions** — `special_needs` (bool), `special_needs_notes` (TEXT);
5 nullable Y/N questions from `Config\Survey::$questions` → `q_{key}`:
`q_mobile, q_oxygen, q_kit_72hr, q_meds_72hr, q_evac_place`. Plus `has_pets` (Y/N), `pets_desc`,
`water_on_hand`, `evac_destination`, `can_host` (`000006`), `notes`, `updated_by`, `created_at`, `updated_at`.

### `household_residents` (migration `000008`)
Extra people beyond head+spouse. `id`, `household_id` (FK), `name`, `relationship`
(child/parent/grandparent/renter/other), `age_group` (adult/child/senior), `phone`, `email`,
`special_needs` (bool), `notes`, `sort`, timestamps.

### `map_pages` (migration `000012`)
A ward can have several map images. `id`, `ward_id`, `sort` (tab order), `label`, `image` (filename
in `writable/uploads/maps/`), timestamps. Each household plotted on **one** page via `households.map_page_id`.

### `members` — CONFIDENTIAL directory (migration `000010`)
Backs member-picker typeahead so names/phones come from the official roster, not free text.
`id`, `ward_id`, `last_name`, `first_name`, `full_name`, `gender` (CHAR 1), `birth_date` (VARCHAR — "19 Jul"),
`age`, `phone` (E.164), `callings` (TEXT, reference only), timestamps. **No bulk/list endpoint exists** (see §4/§8).

### `role_defs` (migration `000014`) — DB-backed role catalog
Moves role definitions into a table so admins can add roles + edit permissions from the UI.
Seeded from `Config\Roles` (identical); `Config\Roles` loads from this table at construct time and
falls back to the hardcoded array if the table is missing/empty.
`id`, `role_key` (UNIQUE), `label`, `category`, `scope`, `can_edit`, `can_assign`, `single_holder`,
`hidden`, `read_only`, `sort`, `is_builtin`, timestamps.

### `ep_otp_send_log` (migration `000002`)
Backs the per-recipient OTP send cap. `id`, `channel` (`sms`|`email`), `recipient` (E.164 or email),
`user_id`, `success`, `created_at`. Indexed on `(recipient, created_at)`.

### Shield `users` extension (migration `000001`)
Adds `users.phone_number` (VARCHAR 20, UNIQUE over non-NULL). Phone-only members log in by phone:
the E.164 phone is stored as the **`secret` of their `email_password` identity** (so all of Shield's
password/force_reset/throttle machinery works unchanged) **and** denormalized on `users.phone_number`
for display/overlay. Email members have `phone_number` NULL.

---

## 4. Authorization Model

> **Hard rule (from the spec): authorization is ROW-SCOPED, not Shield-groups-alone.** Shield groups
> decide *role* (only the 2 global bypass roles); the **scope layer decides which rows**. A block
> captain must never URL-tamper into another block.

### Two role stores
1. **Global bypass = Shield groups** (`app/Config/AuthGroups.php`): only `system_admin` (HIDDEN — never
   in rosters) + `general_admin`. `defaultGroup = general_admin`.
2. **Every ward/stake/block/household role = a `role_assignments` row** (one per user, mutually exclusive).

### Role catalog — `Config\Roles` (now DB-backed via `role_defs`)
`app/Config/Roles.php::$defs` is the single source of truth; per role: `label, category, scope, edit,
assign, single, hidden, read`. `scope ∈ {global, city, stake, ward, block, household}` (city added 2026-06-02 — parent of stake; `cities` table + `stakes.city_id` + `role_assignments.city_id`; a `city`-scoped role sees all wards in all its stakes and is **names+address only** via `Scope::$namesOnly` + `HouseholdModel::projectNamesOnly()`). At construct time it
loads from `role_defs` (admin-editable) and recomputes `globalGroups` = all scope-global roles. The full set:

| role_key | label | scope | edit | assign | single | read-only |
|---|---|---|---|---|---|---|
| `system_admin` | System Admin | global | ✓ | ✓ | – | – (**hidden**) |
| `general_admin` | General Admin | global | ✓ | ✓ | – | – |
| `city_viewer` | City Viewer | **city** | – | – | – | ✓ (**names+address only**) |
| `stake_president` | Stake President | stake | – | – | ✓ | ✓ |
| `bishop` | Bishop | ward | ✓ | ✓ | ✓ | – |
| `bishopric_first_counselor` | 1st Counselor | ward | ✓ | ✓ | ✓ | – |
| `bishopric_second_counselor` | 2nd Counselor | ward | ✓ | ✓ | ✓ | – |
| `executive_secretary` | Executive Secretary | ward | ✓ | ✓ | ✓ | – |
| `ward_clerk` | Ward Clerk | ward | ✓ | ✓ | ✓ | – |
| `assistant_executive_secretary` | Asst Exec Secretary | ward | ✓ | ✓ | ✓ | – |
| `ep_coordinator` | Emergency Prep Coordinator | ward | ✓ | ✓ | ✓ | – |
| `rs_president` … `rs_secretary` (4) | Relief Society presidency | ward | – | – | ✓ | ✓ |
| `eq_president` … `eq_secretary` (4) | Elders Quorum presidency | ward | – | – | ✓ | ✓ |
| `sunday_school_president` | Sunday School President | ward | – | – | ✓ | ✓ |
| `ward_mission_leader` | Ward Mission Leader | ward | – | – | ✓ | ✓ |
| `block_captain` | Block Captain | block | ✓ | – | – (pair) | – |
| `assistant_block_captain` | Assistant Block Captain | block | ✓ | – | – (pair) | – |
| `household` | Household | household | ✓ | – | – | – |

### Scope resolution — `ScopeService` → `Scope`
`service('scope')->for($user)` resolves a user to an immutable `Scope` (per-request cached):
1. **Global Shield group is checked FIRST** — if the user is in `system_admin` or `general_admin`,
   scope is `global` (`wardIds='*'`, edit+assign), **regardless of any role_assignment they also hold**
   (assignment becomes display-only).
2. Otherwise read the single `role_assignments` row; resolve `wardIds`/`blockIds` per the role's `scope`:
   - `stake` → all wards in `stake_id`; `ward` → `[ward_id]`; `block` → ward derived from `blocks.ward_id`, restricted to `[block_id]`; `household` → ward(+block) for context.
3. No assignment + no group → `Scope::none()` (`level='none'`, no access).

`Scope` exposes: `level`, `stakeId/wardId/blockId`, `wardIds ('*'|list)`, `blockIds (null|list)`,
`canEdit`, `canAssign`, `userId`, plus the guards every controller/model uses:
`canSeeWard()`, `canSeeBlock()`, `canEditWard()`, `canEditBlock()`, `isGlobal()`, `isReadOnly()`, `hasAccess()`.
`HouseholdModel::applyScope()` translates a `Scope` into a query `whereIn`/`where` clause — **the single
SQL chokepoint** through which every household read/write (incl. map, overview, reports) passes.

### RoleService (assign/clear) + the lockout guard
`RoleService::assign()` enforces mutual exclusivity (deletes prior row), single-person rules
(`UNIQUE(ward_id, role_key)` semantics via `holdersInWard()`), and syncs the Shield group for global roles.
**Roles are mutually exclusive**, so `assign()`/`clear()` call `clearGlobalGroups()`, which strips
`system_admin`/`general_admin` when a ward role is set/removed.
**Lockout guard:** `clearGlobalGroups()` **never strips `system_admin` when it is the LAST one**
(`systemAdminCount() <= 1`). This is the permanent backdoor. *(2026-06-01 incident: a ward role
direct-SQL-assigned to the sole admin, then "remove" ran `clear()` → stripped his `system_admin` →
scope=none → 404 on every gated page. The guard prevents recurrence.)*

### Per-surface permission matrix (verified 2026-06-01 cross-role audit)
| Surface | system/general admin | bishopric / EP coord | RS/EQ pres (read-only) | block captain | household |
|---|---|---|---|---|---|
| `/` dashboard tiles | all | ward tiles | ward tiles (no Roles/Perms) | block tiles | only "My Record" |
| `/overview` | all wards | ward | ward | own block | → `/household` |
| `/map`, `/map/edit` | ✓ | ✓ / edit ✓ | view ✓ / **edit 404** | own block | **404** |
| `/households`, autosave | all | ward edit | view; **autosave 403** | own block only (other block → 404) | → `/household`; own only |
| `/reports` | any block / ward | any block / ward | view | only their block | **404** |
| `/members/search` | ✓ | ✓ | **403** (not canEdit) | ✓ | **403** |
| `/admin/roles` | ✓ | ✓ (own ward) | **404** (`can-assign`) | **404** | **404** |
| `/admin/roledefs` | ✓ (global only) | **404** | **404** | **404** | **404** |

Filters: `CanAssignFilter` 404s anyone whose `scope->canAssign` is false. `RoleDefsController::gate()`
404s anyone who isn't `isGlobal()`. **`system_admin` is PROTECTED in `/admin/roledefs`** — never listed,
never editable/deletable (`index()` filters it; `save()`/`delete()` reject it).

---

## 5. Feature / Surface Inventory

All app routes are in `app/Config/Routes.php`. Shield's routes are mounted except `login`/`register`
(login overridden; registration disabled).

### Auth
- `GET/POST /login` → `Auth\LoginController` — **email-or-phone** single field. Non-email input is
  normalized to E.164 and matched against the identity `secret`; email is lowercased. Stock Shield
  attempt/throttle/remember/force-reset beyond that.
- `GET/POST /change-password` → `Auth\ChangePasswordController` (behind `session` only). `strong_password`
  rule; on save clears force_reset and redirects to `/`.
- `GET/POST /logout`, Shield auth-action routes, etc. (mounted by Shield).

### Dashboard
- `GET /` → `Home::index` → `home.php`. **Scope-aware tile grid:** Overview / Map / Households / Reports,
  plus **Roles** (if `canAssign`) and **Permissions** (if `isGlobal`); a household-level user sees only
  **My Record**. No-access users see a "ask an admin to set your role" card. `partials/nav` + `partials/footer`.

### Households
- `GET /household` → `HouseholdController::mine` — the member's own record; **auto-created blank** on
  first visit (seeded from their role assignment's ward/block + email identity). Non-household roles with
  no record fall through to the list.
- `GET /households` → `index` — scope-filtered list (sorted A–Z by last name via `listFor`). Household
  role redirects to their own record. Provides the block dropdown for adding a vacant home.
- `POST /households/add` → `create` — **vacant homes**: takes `last_name` + `address` + `block_id`
  (editors only) so an empty home gets a block captain; plot/fill later.
- `GET /household/{id}` → `edit` — `households/edit.php` (336 lines): Main / Spouse / Household-address
  boxes + Residents + Sub-residences sections. **Member-picker** typeahead on the head + resident fields.
  404 if out of scope; inputs disabled in read-only mode.
- `POST /household/{id}/save` → `autosave` — **single-field PATCH** `{field, value}`. Allowlist =
  `HouseholdModel::editableFields()` (generated from `Config\Survey`); coerced per type (bool/yn/date/phone/string/text).
  403 if scope can't edit this household; 422 if field not editable (e.g. scope-anchor columns).
- `POST /household/{id}/block` → `setBlock` (move to a block in the same ward you manage).
- `POST /household/{id}/delete` → `delete` (editors; cascades residents + sub-residences).
- `POST /household/{id}/resident` → `residentAdd`; `POST /resident/{id}/save` → `residentSave`
  (field autosave, allowlist `name/relationship/age_group/phone/email/special_needs/notes`);
  `POST /resident/{id}/delete` → `residentDelete`.
- `POST /household/{id}/sub` → `subAdd` — create a **sub-residence** (basement / 2nd family) under a
  household; inherits ward/block/address, gets its own editor + residents.

**Autosave UX:** every field saves on change/blur via fetch POST with an ephemeral "Saved" toast, no
save button. `Security::$regenerate=false` keeps the CSRF token stable across rapid saves (sent via
`X-CSRF-TOKEN` from a meta tag).

### Overview
- `GET /overview` → `OverviewController::index` — scope-filtered **stats dashboard + drill-down tree**
  (block → captain → households → sub-residences + residents). Household role bounced to own record.
  Stats roll up: households count, survey returned / not-returned, special-needs (incl. `q_oxygen`),
  generator, ham, chainsaw, medical (any `skill_medical_*`/`skill_first_aid_cpr_*`). Blocks naturally
  sorted (zone-number leading). Households with no block surface as "orphans."

### Map (multi-page, tabbed)
- `GET /map` → `MapController::view` — tabbed overlay; one page at a time. Default tab = page where the
  signed-in user has the most plotted households (admins→Neighborhood, floor captain→their floor).
  Each household renders **name + 3-row label** (last name / HoH first + cell / spouse first + cell;
  cell formatter drops 801 area code) **inside its polygon** (navy text + white halo). Households with a
  sub-residence get a **purple numbered basement badge** at the lot's bottom-left + a **clickable
  basement key** in the print area below the map. Special-needs flagged red; click → detail. **Household
  role → 404.**
- `GET /map/edit` → `edit` (editors only; household→404) — **click-to-edit vertices**: click a lot to
  edit; drag a corner; double-click a vertex to delete; click empty to add a vertex. **Snap-to-corners**
  toggle (snaps a vertex onto a neighbor lot's shared corner); **hold Ctrl/Alt to place freely** (bypass
  snap). Shows the **address** by the name. **Remove from map** clears a placement (record stays).
  Unplaced in-scope households listed for placement on the current page.
- `POST /map/place/{id}` → `place` — saves a placement: a `polygon` (JSON `[[x,y],…]` percent, ≥3 pts,
  clamped 0–100; centroid → map_x/map_y) **or** a single `x`/`y` point; `page` POST assigns the page.
  Empty x/y clears. 403 out of scope, 422 unknown page.
- `POST /map/upload` → `upload` (canAssign) — replace a page image (png/jpg → `writable/uploads/maps/page_{id}.ext`).
- `GET /map/pimage/{pageId}` → `pageImage` — **gated** page-image stream (404 if out of scope or missing file).

**Live ward-5 pages:** 1 Parkway (10400 S), 2 Neighborhood (the **blank** Prospector plat outline — names
come from pins, not a baked-in image), 3–6 Beckstead Floor 1–4 (apartment unit grids, auto-plotted from
`tools/render_floors.py` parsing `Legacy Map Builder.xlsm`).

### Members directory (CONFIDENTIAL)
- `GET /members/search` → `MemberController::search` — **on-demand typeahead only**; min 2 chars, ≤15
  results, ward-scoped. **403 for household role, read-only roles (not `canEdit`), and anon.** No bulk/list
  endpoint exists. Returns `{id,name,first,last,phone,pretty}`. Wired into household head + resident-add
  fields and the `/admin/roles` assign form (picking a member with no login provisions one on demand).

### Reports (print + PDF)
- `GET /reports` → `ReportController::index` — menu of scoped blocks. Household→404.
- `GET /reports/run` → `run` — params `type` (`ward`|`block`|`roster`), `format` (`html`|`pdf`),
  `block`, `sections[]` (`skills`/`equipment`/`essentials`).
  - **Ward report** (`report.php`): every scoped block as a section with **captain name + phone headed**,
    household table (spouse/address/phone), **sub-residences printed as their own household rows with a
    purple BASEMENT tag** (`blockData()` attaches `_residents` to subs; `renderRow($h,$subTag)`),
    residents nested, **special-needs always flagged ⚠ (incl. oxygen)**.
  - **Single-block report** (filtered to one in-scope block).
  - **Block-captain roster** (`roster.php`): block / captain / phone / household count.
  - **Section toggles** turn skills / equipment / essentials (water + 72-hr kit/meds) on/off.
  - Output = HTML print-friendly (`window.print()`) or **PDF via mPDF** (`Output('','S')` →
    `$this->response`, tempDir `writable/mpdf-tmp`). Block captains only ever get their own block.

### Role admin
- `GET /admin/roles` → `Admin\RolesController::index` (filters `session`+`force-reset`+`can-assign`).
  Per-ward roster (council-ordered by `Config\Roles` declaration order) + assign/remove forms. Global
  admins pick any ward; ward assigners locked to their ward. `system_admin` users never appear (no
  role_assignments row).
- `POST /admin/roles/assign` → `assign` — assign a ward/block/household role to a member (by id/email/phone,
  or pick a directory member → provision a login on demand). Re-checks `canSeeWard`; rejects global/stake/hidden roles.
- `POST /admin/roles/remove` → `remove` — clears a member's role (scope-checked).

### User-login admin (added 2026-06-02)
- `GET /admin/users` → `Admin\UsersController::index`. **Gate = `scope->canAssign`** (ward edit+assign tier:
  bishopric + exec sec + clerk + asst exec sec + EP coordinator) **OR global admin**; read-only callings are
  404'd. **Ward-scoped:** ward leaders see only their ward (filtered by `role_assignments.ward_id` OR
  `households.ward_id`, global admins excluded from their list); global admins see all wards. Lists each login
  with name / email-or-phone / admin badge / calling / status (active · pending first sign-in).
- `POST /admin/users/create` → provision a login by email or phone via `UserProvisioner::provision()`; the
  8-digit OTP is surfaced in the flash for manual relay. Admin-access option is **global-admin only** (forced
  to none for ward leaders).
- `POST /admin/users/reissue` → fresh OTP + force_reset (backs "Resend code" / "Reset password"). Ward leaders
  limited to their ward via `canManage()`.
- `POST /admin/users/setAccess` → grant/revoke admin group — **GLOBAL admins only**; **never strips the last
  `system_admin`**.
- `POST /admin/users/toggleActive` → activate/deactivate; `canManage()`-gated, refuses self or last-system_admin.
  Ward callings remain on `/admin/roles`; this screen is the login itself. (Stake-level access = future dev.)

### Role catalog / Permissions admin
- `GET /admin/roledefs` → `Admin\RoleDefsController::index` (**global admins only**; `system_admin` row
  hidden + locked). Lists role defs + per-role usage counts.
- `POST /admin/roledefs/save` → add a role (slugged unique key) or edit an existing one's permissions
  (label/category/scope/edit/assign/single/hidden/read). `system_admin` rejected.
- `POST /admin/roledefs/delete` → delete a role. **Built-in roles can't be deleted; in-use roles can't be
  deleted; `system_admin` locked.**

### Email templates, newsletters & opt-out (added 2026-06-02)
Tables `ep_email_templates` / `ep_email_optout` / `ep_newsletter_sends` (migration `2026-06-02-000001`).
Helper `ep_email` (`ep_render_template` `{{var}}` substitution; `ep_unsub_token/url/verify` stateless HMAC;
`ep_is_opted_out`). Seeded templates: **welcome** (transactional, `{{login}}`+`{{code}}`), **update_info** +
**new_neighbor** (newsletters, `{{unsubscribe_url}}`).
- `GET /admin/templates` → `Admin\TemplatesController` (**global admins**): list/add/edit/delete +
  `POST .../sendTest` (send a rendered preview to yourself). `welcome` is locked (deactivate, don't delete).
- `GET /admin/mail` → `Admin\MailController` (**can-assign, ward-scoped**): pick a newsletter + audience
  (households / logins / both) → recipient-count preview → `POST /admin/mail/send` sends via the relay,
  skips opt-outs, logs to `ep_newsletter_sends`. Synchronous loop, MAX_SEND 1000.
- `GET /unsubscribe?e=&t=` → `UnsubscribeController` (**PUBLIC**, no auth/CSRF): HMAC-verify → opt out by
  email. Only newsletters honor opt-out; transactional mail always sends.
- **Welcome wiring:** `Notifier::sendOtp(..., $context)` — provisioning passes `'welcome'` (renders the
  welcome template with login+code when active); reissue passes `'code'` (plain `emails/otp.php`).
- **DEV MAIL LOCKOUT (safe default = locked):** `App\Libraries\GuardedEmail` overrides the `email` service;
  while `ep.devLockout` is on (absent = on), every send is filtered to `ep_mail_allowlist()` at `send()`
  level — no path can reach a real recipient. SMS guarded via `ep_sms_allowed`. Set `ep.devLockout = false`
  in `/home/www/ep/.env` (+ reload php-fpm) to go live. `/admin/users` also **never lists `system_admin`
  holders** (the hidden backdoor), for everyone.

### Views inventory (`app/Views/`)
`home.php`; `auth/{login,change_password}.php`; `emails/otp.php`; `households/{index,edit}.php`;
`map/{view,edit}.php`; `overview/index.php`; `reports/{index,report,roster}.php`;
`admin/roles/index.php`; `admin/roledefs/index.php`; `partials/{nav,footer}.php`.
**Footer disclaimer (every page):** *"This is not an official website of The Church of Jesus Christ of
Latter-day Saints. Confidential — for emergency-preparedness use by ward leadership only."*

---

## 6. Spark Commands (`app/Commands/`, group `EP`)

| Command | Purpose |
|---|---|
| `ep:ward --stake "X" --ward "Y" [--blocks "A,B,C"] [--stake-id N]` | Create a stake / ward / blocks (`WardSetup`). |
| `ep:user [--email x] [--phone x] [--name x] [--group x]` | Provision a member; sets 8-digit OTP + force_reset; **prints the OTP + delivery result** for manual relay (`ProvisionUser`). No group by default (role-less). `--group` = global Shield groups only. |
| `ep:assign --user <id\|email\|phone> --role <key> [--ward\|--block\|--stake N] \| --clear` | Assign/clear a role (`AssignRole` → `RoleService`). |
| `ep:scope --user <x>` | Print a user's resolved `Scope` (debug `ScopeService`). |
| `ep:households --user <x> [--target <id>]` | Show households a user can see + view/edit on a target (debug scope queries). |
| `ep:build-logins --ward N [--no-spouses] [--dry-run]` | Bulk-create logins for every household head (email) + spouse (spouse_email): assigns the `household` role, links head→`user_id` and spouse→`spouse_user_id`, **sends NO emails** (`notify=false`), idempotent. Also exposed as the `/admin/users` "Build logins" button (`HouseholdLoginBuilder`). |
| `ep:import --file <grid.csv> --ward N [--dry-run]` | Import the captain-grouped survey grid CSV into households (`ImportSurvey`). Idempotent on `(ward_id, address)`; captain `#N` rows open a block; the row after a household = spouse; skills K–U → `_h` columns. |
| `ep:enrich --file <csv> --ward N [--dry-run]` | Backfill households from the captain sheet, **matching by normalized address** (`EnrichFromCaptains`). Fills only-empty fields, never clobbers names; adds missing households; nests `(Basement)` rows as sub-residences, extras as residents. |
| `ep:members --file <csv> --ward N` | Import the confidential member directory (`ImportMembers`); idempotent on `(ward_id, full_name)`; phones → E.164. |
| `ep:callings --ward N [--dry-run]` | Seed ward-council role assignments from the directory's callings (`AssignCallings`); finds the member by name, provisions a login by phone, assigns the role. The role→holder MAP is curated in the command source. |
| `ep:review-names --ward N` | Flag households needing name/address cleanup (`ReviewNames`): missing first/last name, name-in-address mashups, "Basement" rows. |

**Importer discipline:** the messy xlsx→raw-grid-CSV conversion is one-time Python (zip/XML, sharedStrings);
the PHP importers consume the grid CSV (reusable for the same sheet format). Source CSVs kept at
`/home/www/ep/writable/uploads/` (PII, not web-served, re-runnable). `tools/render_floors.py` regenerates
the apartment-floor PNGs from the xlsm.

---

## 7. Multi-Tenancy

Everything is keyed on `stake_id` / `ward_id` / `block_id`; nothing is hardcoded to the 8th Ward:
- **Stake → ward → block → household** is a real hierarchy with FKs.
- `ScopeService` resolves any user to the wards/blocks they may see — a stake president sees every ward
  in their stake; a bishop one ward; a block captain one block.
- `members.ward_id` scopes the directory; `map_pages.ward_id` scopes maps; reports/overview/map all
  filter on the visible ward set.
- **To onboard a new ward:** `ep:ward` (create stake/ward/blocks) → `ep:members` (load directory) →
  `ep:import`/`ep:enrich` (load survey) → `ep:callings` (seed council) → assign block captains
  (`ep:assign`) → place households on map pages. No code changes.
- Current live state: **1 stake (South Jordan), 1 ward (8th Ward)** — the only tenant so far.

---

## 8. Conventions, Gotchas & Invariants

- **OTP-only login — no passwords until set.** Phone-only members have **NULL email**; the E.164 phone
  lives as the `email_password` identity `secret`. Login field accepts email OR phone (normalized).
  No `findByCredentials` override (stock Shield email-branch matches the secret).
- **Row-scope derives ONLY from `ScopeService`** — never read Shield groups directly to decide visibility.
  The global Shield group is checked FIRST, so a global admin who also holds a role_assignment still
  resolves to global (assignment = display-only).
- **`system_admin` is the permanent backdoor:** hidden from all lists, locked in `/admin/roledefs`, and
  **never strippable when it's the last one** (lockout guard in `RoleService::clearGlobalGroups`). Don't
  assign ward roles to the sole admin without that guard in place.
- **Roles are mutually exclusive** — `role_assignments` has `UNIQUE(user_id)`. Single-person roles also
  enforced per ward.
- **Basement = sub-residence pattern:** a sub-residence is a `households` row with `is_sub_residence=1` +
  `parent_household_id`, NOT a separate table. **CRITICAL past bug:** `HouseholdModel::$allowedFields`
  once omitted `parent_household_id`/`is_sub_residence`/`sub_label`, so every sub-residence insert silently
  became a top-level household at the parent's address (duplicate addresses + lost basements). Those fields
  are now in `allowedFields` — never remove them.
- **`Config\Survey` is the single source of truth for form labels;** `HouseholdModel::editableFields()` +
  the edit/print views are generated from it, so the form and the DB columns can't drift. Adding a survey
  field = edit `Survey.php` + one migration column.
- **Autosave needs a stable CSRF token** — `Security::$regenerate=false`; token via `X-CSRF-TOKEN` header.
- **Phone normalization is mandatory** (`ep_phone_e164`) — login-by-phone matching, the map overlay label,
  and uniqueness all depend on a single canonical `+1XXXXXXXXXX` format. `ep_find_user` treats all-digit
  ≤7 chars as a user id, 10+ as a phone (don't route a 10-digit phone to `findById`).
- **Zone-number block naming:** blocks are named `"N · <name>"`; `BlockModel::forWardsOrdered()` sorts by
  the leading zone number, putting Beckstead apartment floors (no number) last.
- **Blank-plat map:** the Neighborhood page is the *blank* Prospector outline — names render from pins, not
  a baked-in 2023 image. Don't replace it with a names-baked image.
- **The member directory is confidential** — sourced from Drive via the binks-gmail-reader SA
  (impersonating `jasbrown@jnbgroup.net`); raw rosters were parsed then shredded, never committed
  (`git ls-files` shows no .csv/.tsv). Only an on-demand, scope+canEdit-gated typeahead is exposed.
- **`role_defs` is DB-backed but `Config\Roles` is the seed + fallback** — if the table is empty/missing,
  the hardcoded defs apply. Built-in roles (`is_builtin=1`) can be edited but not deleted.
- **Email/SMS deliverability is the live gap — and `ep.jnbgroup.net` is DEV-only, not the final domain.**
  A permanent domain is still to be chosen; don't set up SPF/DKIM for jnbgroup.net. Once the real domain
  lands, stand up SPF/DKIM (or route via a Google Workspace) for THAT domain on vader and repoint
  `Email.from`; Twilio SMS needs creds in `.env`. Until then `sendmail` from `noreply@jnbgroup.net` may be
  spam-filed/rejected (same dormant-email gap as thevanwagenens.com), so `ep:user` prints the OTP for
  manual relay. Per-recipient OTP send cap = 5 / 24h (`ep_otp_send_log`) to bound Twilio cost/abuse.
- **Deploy = rsync binks→vader + `systemctl reload php-fpm`** (run `php spark migrate` if migrations changed).
  Default branch is `main`; push to `origin main`.

---

## 9. Current Data State (live `ep` DB on vader, 2026-06-01)

| Entity | Count |
|---|---|
| Households (top-level) | **218** |
| Sub-residences (basements) | **13** |
| Residents (`household_residents`) | 10 |
| Blocks (12 zone-numbered + 4 Beckstead floors) | **16** |
| Map pages | **6** (Parkway, Neighborhood, Beckstead Floor 1–4) |
| Members (confidential directory) | **173** |
| Wards | 1 (8th Ward, id 5) |
| Stakes | 1 (South Jordan, id 4) |
| Role assignments | 16 |
| Role defs | 23 |
| Users (logins) | 16 |

*(Runbook notes the reconciled survey figure as ~232 households / ~14 basements / ~50 with kids during
cleanup; the live table above is the current authoritative count.)*

Placement counts at last verify: Parkway 3 · Neighborhood ~116 · Floor1 24 · Floor2 22 · Floor3 23 · Floor4 28
(97/98 apartments auto-plotted on their exact unit).

---

## 10. Open Items / Not-Yet-Done

- **Domain cutover DONE 2026-06-02** — `readyneighbor.org` is live (DNS A→136.40.66.105, vhost apex+www,
  LE cert exp 2026-08-31 auto-renew, prod baseURL flipped, `Email.from`→noreply@readyneighbor.org,
  opendkim signing + SPF/DKIM/DMARC published). `ep.jnbgroup.net` still serves the same instance.
- ✅ **Email OTP delivery RESOLVED (2026-06-02)** — readyneighbor.org added as a secondary domain in the
  **jnbgroup** Google Workspace + vader's egress IP `136.41.66.122` registered in that Workspace's SMTP relay
  service. EP's existing `protocol=sendmail` / `noreply@readyneighbor.org` config sends unchanged; verified
  to jbrown@ with `dkim=pass (s=vader1)` + `spf=pass` + `dmarc=pass`, inbox not spam. (Direct-send from vader
  was ruled out — Google Fiber consumer IP, permanently blocked.) Only the SMS-OTP (Twilio) channel remains.
- **SMS OTP not live** — Twilio Programmable SMS creds + a ported number. Accounts still create; OTP relayed
  manually meanwhile. *(The `/home/jason/.env-twilio` account is an EMPTY "Motor Sportsland" account — not usable creds.)*
- **Hard OTP TTL not enforced** — Shield ignores `identity.expires` on password login, so the temp
  password doesn't auto-expire (force_reset makes it single-use-ish; `reissue()` rotates it). Deferred.
- **Name-order ambiguity** — some survey families entered "Last First"; auto-detection is unreliable.
  Correct in-app on the edit page; `ep:review-names` flags candidates.
- **A few family-contact name gaps** — households still blank everywhere needing a contact (e.g.
  Hellekson, Nguyen, Ziska, Baker, Karen).
- **Specific runbook follow-ups:** Rowley unit, Wolstenholme basement, parkway-frontage homes,
  block-captain freshness (captain sheet zone names verified 1:1; keep current).
- **Cosmetic dedup** — a person can occasionally appear as both a basement sub AND a resident
  (xlsx made resident, enrich made sub).
- **Temp Phase-0 superadmin `jason`** still present; replace with a real provisioned admin later (low priority).
- **Possible future:** multiple-adults-per-household logins (today a household links to ONE `user_id`);
  `activity_log` (spec'd, not yet built); soft-delete on households/users; full-ward CSV export.
