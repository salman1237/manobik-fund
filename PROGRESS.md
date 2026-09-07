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
| 3 | Verification Workflow | ✅ | (next commit) | See Testing Log below |
| 4 | Public Campaign Pages & Donations | ✅ | (next commit) | See Testing Log below |
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

### Phase 3 — Verification Workflow (2026-09-07)

- Followed spec §8's explicit instruction to the letter: **wrote and ran the state-machine tests before writing any Filament UI**. `App\Services\CampaignVerificationService` is the single place the `draft -> pending_verification -> field_visit -> executive_review -> published` (with `rejected` reachable from any of the three review stages) transitions happen; every method guards its own preconditions and throws `App\Exceptions\InvalidCampaignTransition` on an invalid call rather than silently no-oping.
- **Bug caught by the state-machine tests themselves, before any UI existed**: `Campaign::publish()` set `published_at` via mass-update, but `published_at` was missing from the model's `$fillable` array, so it was silently dropped (Laravel doesn't throw on this by default) - `test_publishing_from_executive_review_marks_the_campaign_live` failed on `assertNotNull($result->published_at)`. This is exactly the class of bug spec §8 was warning about, and it was caught at the service layer, before it could reach a Filament button.
- New: `field_visit_reports` table/model, `campaigns.assigned_volunteer_id` + `volunteer_assigned_at` columns, `notifications` table (Laravel's standard database-notifications table, not previously present in the skeleton).
- `CampaignPolicy` extended with `assignVolunteer` (verification_admin+), `submitFieldReport` (only the campaign's actually-assigned volunteer), `forwardToExecutive` (verification_admin+), `reject` (verification_admin+), `publish` (executive_admin/super_admin only - matches the spec §3 role table exactly). New `FieldVisitReportPolicy` for the read-only report audit trail.
- Three notifications (`VolunteerAssignedNotification`, `CampaignRejectedNotification`, `CampaignPublishedNotification`) fire from inside the service on the relevant transitions - mail channel uses the `log` driver in dev (nothing external sent), satisfying spec Phase 3's "notifies Volunteer" requirement without waiting for the full Phase 11 notification system.
- Every transition is logged via `spatie/laravel-activitylog`'s `activity()` helper with `causedBy()`/`performedOn()` - gives the "full activity log on every transition" spec Phase 3 asks for, queryable later for an audit trail UI.
- `CampaignResource` (Filament, `/control/campaigns`): **not** a generic CRUD resource - `canCreate()` is hard-disabled (campaigns are only ever created by Seekers via the Phase 2 public wizard) and the generic edit form was removed entirely, because a raw form field bound to `bank_account_details` (an `encrypted:array` cast) would have serialized it as a plain string and silently corrupted the encrypted-array cast on save. Instead: a read-only `infolist()` (Filament\Infolists) for viewing, and dedicated per-transition table actions (Assign Volunteer, Submit Field Report, Forward to Executive, Reject, Publish) that call straight into `CampaignVerificationService`, each gated by both `->visible()` (policy + current status) and the policy check inside the service-backed action itself.
- `CampaignResource::getEloquentQuery()` scopes Volunteers to `assigned_volunteer_id = auth()->id()` only; `FieldVisitReportResource::getEloquentQuery()` scopes Volunteers to their own submitted reports - matches spec §7's "Volunteers get a heavily restricted view."
- Authorization is implemented as direct role checks inside hand-written Policies rather than filament-shield's auto-generated per-resource permissions - simpler to read and test directly, at the cost of not using Shield's fine-grained permission-per-role UI. Noted as an open decision below in case granular permission management is wanted later; Shield itself is still installed and still gates panel navigation/access at a coarse level.
- Automated: `tests/Feature/Phase3VerificationTest.php` (17 tests / 39 assertions) exercises the service directly with no Filament involved - every valid and invalid transition, the full happy path, and policy-level authorization checks. `tests/Feature/Phase3FilamentUiTest.php` (9 tests / 36 assertions) exercises the actual Filament panel over HTTP and via Livewire table-action testing: panel access denial for plain users, volunteer row-scoping, action visibility by role and status, and both infolist view pages rendering without error. Full suite: **72 passed / 203 assertions**.
- DB reset to a clean `migrate:fresh --seed` state before commit.

### Phase 4 — Public Campaign Pages & Donations (2026-09-07)

- **No real Stripe/ShurjoPay credentials yet** (per the 2026-09-07 client decisions log) - built the entire donation flow against a `PaymentGateway` interface so it's fully testable without live credentials, and real keys can be dropped into `.env` later with zero code changes. `stripe/stripe-php` installed; `StripeGatewayService` (Checkout Sessions) and `ShurjoPayGatewayService` (a hand-rolled HTTP client per spec §2 - no official ShurjoPay Laravel package exists) both implement it.
- **Flagged explicitly, not guessed silently**: `ShurjoPayGatewayService`'s request/response field names follow ShurjoPay's publicly documented v2 flow (`get_token` -> `secret-pay` -> `verification`) but are **not** verified against a live sandbox response, since no merchant credentials exist yet. Left an inline code comment and a PROGRESS.md follow-up to confirm exact field names once real sandbox credentials arrive - flagging this now is safer than presenting unverified guesses as certain.
- New public site: replaced Laravel's default `welcome` view (deleted, no longer referenced anywhere) with a real homepage - `/` and `/campaigns` list published/funded/completed campaigns only, with category filter and title/hospital/description search; `/campaigns/{slug}` is the detail page (progress bar, description, patient-update feed, donation form). All 404 for non-public campaigns (draft/pending/rejected are not guessable/visible).
- **Bug caught before it shipped**: the existing `<x-app-layout>` nav (`livewire/layout/navigation.blade.php`) calls `auth()->user()->name` unconditionally, which throws for guests - realized this before wiring the public pages to it, so built a separate guest-safe `<x-public-layout>` + `livewire.layout.public-navigation` (shows Login/Sign Up for guests, Dashboard/Log Out for authenticated users) instead of reusing the authenticated app shell for pages guests must be able to browse.
- `donations` table/model per spec §5 (plus a `gateway_meta` json column for raw gateway payloads, useful for debugging/audit). Money stored as integer smallest-unit per spec §8 convention already used for campaigns.
- `DonationForm` (Livewire, embedded on the campaign show page): gateway choice (Stripe international vs. ShurjoPay/bKash/Nagad/card BDT), amount/currency, donor name+email (prefilled for authenticated donors, editable), anonymous checkbox. Creates a `pending` Donation, calls the resolved gateway's `createCheckout()`, and redirects to the hosted checkout URL - works for guests (no account required, per spec §3) and authenticated users alike.
- **Idempotent, dual-path completion** (spec §8: "donation status updates should be safe to receive twice"): `DonationCompletionService::complete()` is a no-op if the donation is already completed, and is called from *two* independent paths that can't interfere with each other - the Stripe webhook (`checkout.session.completed`, signature-verified via `Stripe\Webhook::constructEvent`) and the donor's own return-page load (which re-verifies directly against the gateway rather than trusting query-string state), so a donation still completes correctly even in a local/dev environment with no public webhook URL for Stripe to reach.
- Stripe webhook route excluded from CSRF verification in `bootstrap/app.php` (server-to-server delivery, authenticated by its own HMAC signature instead).
- `DonationReceiptNotification` (mail-only, sent via `Notification::route('mail', $donation->donor_email)` so it works for guest donors with no User record) fulfills spec Phase 4's "donation confirmation + email receipt."
- Automated: `tests/Feature/Phase4PublicCampaignsTest.php` (5 tests) - listing scoped to public statuses only, category filter, search, 404 on non-public campaigns, progress/updates rendering. `tests/Feature/Phase4DonationsTest.php` (9 tests / 23 assertions) - guest and authenticated donation creation for both gateways (with fake `PaymentGateway` bindings, no real API calls), validation, a **real** Stripe webhook signature generated via `Stripe\WebhookSignature::generateSignatureHeader()` (genuine HMAC verification, not mocked), webhook idempotency across duplicate delivery, invalid-signature rejection, and the ShurjoPay return-page flow via `Http::fake()` for both successful and failed verification outcomes. Full suite: **86 passed / 241 assertions**.
- Manual: booted `php artisan serve`, confirmed `/`, `/campaigns`, and `/campaigns/{slug}` all return 200, render light-only (no `dark` class), and the donation form is present and functional on the detail page.
- DB reset to a clean `migrate:fresh --seed` state before commit.

## Open Decisions / Follow-ups

- **Stripe & ShurjoPay real credentials — still needed.** Phase 4's donation flow is fully built and tested against a `PaymentGateway` interface, but nothing has been verified against a live sandbox. Once test keys are provided: (1) confirm `ShurjoPayGatewayService`'s field names against a real `get_token`/`secret-pay`/`verification` response (see Phase 4 testing log), (2) set a real Stripe webhook endpoint + `STRIPE_WEBHOOK_SECRET` and do one live end-to-end test donation on each gateway.
- Filament panel structure: starting with **one shared panel** at `/control`, gated by filament-shield (per spec §7). Revisit only if UX demands split panels.
- Theme: light/white everywhere (public site + all Filament panels), per client feedback 2026-09-07.
- Authorization for Campaign/FieldVisitReport resources uses hand-written role-based Policies rather than filament-shield's generated per-resource permissions (see Phase 3 testing log). Fine for now; revisit if the client wants finer-grained, DB-editable permission assignment per role from the Super Admin panel rather than role checks baked into code.
- Rejected campaigns are currently a terminal display state (reason shown to the Seeker on their dashboard) — no resubmission flow back to draft yet. Not in spec's explicit Phase 3 scope; flag if the client expects seekers to be able to fix and resubmit a rejected campaign.
