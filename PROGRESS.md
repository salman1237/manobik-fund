# Manobik Fund — Progress Tracker

Source of truth for what's actually done vs. planned. Updated at the end of every phase (or whenever a phase's scope changes). See `manobik-fund-spec.md` for the full spec and phase definitions.

Legend: ⬜ Not started · 🟨 In progress · ✅ Done & tested · 🚫 Blocked

## Repo & Environment

| Item | Status | Notes |
|---|---|---|
| GitHub repo created (private) | ✅ | https://github.com/salman1237/manobik-fund |
| Local git init + first commit | ✅ | |
| Laravel app scaffolded | ✅ | Laravel 12.69.1 (upgraded from 11.x — see Testing Log) |
| MySQL DB connected | ✅ | XAMPP MariaDB 10.4, db `manobik_fund` |

## Phases

| Phase | Description | Status | Commit | Notes |
|---|---|---|---|---|
| 0 | Project Foundation (Laravel install, permissions, shield, medialibrary, activitylog, roles seeder, settings table) | ✅ | (next commit) | See Testing Log below |
| 1 | Auth & Panel Scaffolding | ✅ | (next commit) | See Testing Log below |
| 2 | Campaign Core (Seeker side) | ✅ | (next commit) | See Testing Log below |
| 3 | Verification Workflow | ⬜ | | |
| 4 | Public Campaign Pages & Donations | ⬜ | | |
| 5 | Real-Time Medical Parameter Tracking | ⬜ | | |
| 6 | Disbursement & Transparency | ⬜ | | |
| 7 | Reward Points & Refunds | ⬜ | | |
| 8 | Blood Donation Network | ⬜ | | |
| 9 | Ambulance Directory | ⬜ | | |
| 10 | Emergency Response, Medical Camps & Education Modules | ⬜ | | |
| 11 | Notifications & Communication | ⬜ | | |
| 12 | Analytics, Polish & Deployment | ⬜ | | |

## Testing Log

Each phase gets a short entry here when it's marked ✅: what was tested (feature tests / manual), and result.

### Phase 0 — Project Foundation (2026-09-07)

- Installed Laravel 11.x via `composer create-project`, then immediately upgraded to **Laravel 12.69.1**: `composer audit` flagged a high-severity CRLF-injection advisory (GHSA-5vg9-5847-vvmq) unpatched on the 11.x branch (fix only landed in 12.60.0 / 13.10.0). Post-upgrade `composer audit` reports zero advisories.
- Installed: `filament/filament` v3.3.55, `spatie/laravel-permission` v6.25, `spatie/laravel-medialibrary` v11.23, `spatie/laravel-activitylog` v4.12, `bezhansalleh/filament-shield` v3.9.
- Filament admin panel registered at `/control` (per spec §7, single shared panel gated by Shield), `Color::Emerald` primary, brand name "Manobik Fund".
- `users` table extended with `phone`, `avatar` per spec §5 schema.
- `User` model: `HasRoles` (spatie/permission), `MustVerifyEmail`, and `FilamentUser::canAccessPanel()` restricting `/control` to `volunteer`/`verification_admin`/`executive_admin`/`super_admin` — plain `user`/Donation Seeker accounts are refused at the panel gate.
- `settings` table + `App\Support\Settings` service (bound as singleton) + `Settings` facade + global `setting()` helper — matches spec §6 Phase 0 "Settings helper/facade" requirement.
- `RoleSeeder` creates the 5 base roles (`super_admin`, `executive_admin`, `verification_admin`, `volunteer`, `user`); `DatabaseSeeder` also creates a local super-admin (`admin@manobikfund.test`).
- **Bug caught by manual smoke test, then regression-tested**: `->darkMode(false, isForced: true)` in `AdminPanelProvider` does NOT mean "force light" — Filament's `isForced` flag on `darkMode()` forces **dark** mode regardless of the first argument (see `vendor/filament/filament/resources/views/components/layout/base.blade.php:11`). Confirmed via `curl` on `/control/login` that `<html class="fi min-h-screen dark">` was being rendered. Fixed to `->darkMode(false)` (no `isForced`), re-verified via curl that the `dark` class is gone and `localStorage.setItem('theme', 'light')` is emitted instead. Added `test_admin_panel_dark_mode_is_disabled` to lock this in.
- Automated: `tests/Feature/Phase0FoundationTest.php` — 6 tests / 12 assertions, all passing (role seeding, panel access gating for staff vs. plain users vs. guests, dark-mode-disabled regression, settings helper read/write). Full suite (`php artisan test`): 7 passed.
- Manual: booted `php artisan serve`, confirmed `/control/login` returns 200 and renders light-only.
- DB reset to a clean `migrate:fresh --seed` state before commit.

### Phase 1 — Auth & Panel Scaffolding (2026-09-07)

- Installed Laravel Breeze (`livewire` stack, which now scaffolds via Livewire Volt) for the public-facing side, matching spec §2's "Laravel Livewire + Blade" frontend choice.
- **Mistake caught before commit**: first ran `breeze:install livewire --dark`, which installs dark-mode *support* — the opposite of the client's light-only requirement. Re-ran without `--dark`; also set `darkMode: 'selector'` in `tailwind.config.js` so `dark:` utility classes never activate from the visitor's OS `prefers-color-scheme` (the site never adds a `dark` class, so it can't go dark even from leftover `dark:` classes in default Breeze/Laravel welcome markup).
- `AssignDefaultRole` listener (auto-discovered on `Illuminate\Auth\Events\Registered`) assigns the base `user` role to every new registration, without overriding a role a staff account may already have (spec §3: "Authenticated User" is the base role every registered account gets).
- `User::isDonationSeeker()` — the spec is explicit that Donation Seeker is *not* a role, only a capability unlocked once email is verified (`hasVerifiedEmail()`). No new role/column added for it.
- `DashboardController` (Phase 1 "role-based dashboard redirects after login"): staff roles (`volunteer` and above) hitting `/dashboard` are redirected to `/control`; everyone else sees the public dashboard view. Removed the `verified` middleware from `/dashboard` since viewing the dashboard itself doesn't require a verified email, only creating a campaign will (Phase 2).
- **Test-suite-wide gap found while testing**: Breeze's own generated `RegistrationTest` failed with `RoleDoesNotExist` because it doesn't seed roles, and `AssignDefaultRole` now runs on every registration. Fixed at the root by auto-seeding `RoleSeeder` for every `RefreshDatabase` test via `protected bool $seed = true` / `protected string $seeder = RoleSeeder::class` on the shared `tests/TestCase.php`, since roles are now foundational reference data most user-related tests implicitly depend on - not something each test file should have to remember.
- Automated: `tests/Feature/Phase1AuthTest.php` — 6 tests covering default-role assignment, role preservation, staff vs. plain-user dashboard redirect, guest redirect, and the email-verification-gated Seeker capability. Full suite: **38 passed / 101 assertions** (includes Breeze's own auth/profile tests, now passing with the auto-seed fix).
- Manual: booted `php artisan serve`, confirmed `/`, `/register`, `/login` all return 200 with no `dark` class on `<html>`; verified via tinker that a fresh registration gets the `user` role.
- DB reset to a clean `migrate:fresh --seed` state before commit.

### Phase 2 — Campaign Core, Seeker side (2026-09-07)

- New tables/models: `campaigns` (+ `rejection_reason`, added ahead of Phase 3 since it's cheap to include now and avoids a later ALTER), `campaign_documents`, `campaign_updates` - matches spec §5, `Campaign` implements `HasMedia` (medialibrary) with `cover` (single file) and `gallery` collections for campaign images; `campaign_documents` stores medical/ID/bill uploads as plain file paths on the **`local`** disk (`storage/app/private`, confirmed non-web-accessible) rather than medialibrary, since spec §5 models them as their own reviewable/verifiable table.
- `CampaignPolicy`: `create` requires `isDonationSeeker()`; `update`/`delete` require both ownership and `draft` status (a Seeker never sees another Seeker's campaign for editing, per spec §3); `postUpdate` requires ownership **and** the campaign already being public - a draft/pending campaign can't receive patient updates yet.
- `CampaignWizard` Livewire component: 4-step form (basic info -> hospital/medical info -> documents -> banking) at `/seeker/campaigns/create` and `/seeker/campaigns/{campaign}/edit`. Each step persists immediately on "Save & Continue" (draft/save-and-continue, spec Phase 2), so a Seeker can leave mid-form and resume later without losing data. Final step flips `status` from `draft` to `pending_verification` - queuing it for the Phase 3 verification pipeline.
- Money stored as integer poisha (BDT smallest unit) per spec §8; the wizard converts the human-entered Taka amount on save and back on load.
- `bank_account_details` uses the `encrypted:array` cast per spec §8 - verified directly against the raw DB column in a test (`test_bank_account_details_are_encrypted_at_rest`), not just through the model accessor, since a passing model-level assertion wouldn't catch a cast that decrypts correctly but was never actually encrypting.
- `Campaign::progressPercentage()` computes live from `raised_amount`/`target_amount` on every call, never cached/stored - matches the client's "no fake/static campaign data, must be live" feedback logged in the spec's Client Decisions section.
- `SeekerDashboard` and seeker `show` page scope every query to `seeker_id = auth()->id()`; verified with a test that one seeker cannot see another's campaign title on the dashboard.
- Automated: `tests/Feature/Phase2CampaignTest.php` - 8 tests / 27 assertions: unverified-seeker rejection, HTTP-level page rendering (not just component-level, to catch Blade/layout wiring bugs), full 4-step wizard walkthrough (asserts DB state after every step, not just the final one), encryption-at-rest, cross-seeker edit denial, post-draft edit lockout, publish-gated update posting, dashboard scoping. Full suite: **45 passed / 123 assertions**.
- DB reset to a clean `migrate:fresh --seed` state before commit.

## Open Decisions / Follow-ups

- Stripe & ShurjoPay real credentials — deferred to Phase 4, client to provide test keys.
- Filament panel structure: starting with **one shared panel** at `/control`, gated by filament-shield (per spec §7). Revisit only if UX demands split panels.
- Theme: light/white everywhere (public site + all Filament panels), per client feedback 2026-09-07.
