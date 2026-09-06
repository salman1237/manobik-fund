# Manobik Fund — Project Specification & Development Roadmap

> A humanitarian crowdfunding and emergency-resource platform for Bangladesh, built with Laravel, MySQL, and Filament (admin/internal panels).

This document is the working spec for Claude Code. It defines the tech stack, roles, modules, database structure, and a phase-by-phase build order. Work through the phases sequentially — each phase should be functional and testable before moving to the next.

---

## 0. Client Decisions Log (2026-09-07)

Feedback received directly from the client, and decisions made in response. Any future spec change driven by client feedback should be appended here with a date, not silently edited into the sections below.

- **No fake/static campaign data.** All campaign progress, donation totals, and counters must reflect real records — no hardcoded/demo numbers left in shipped views.
- **Minimize manual work for volunteers.** Favor workflows/automation that reduce repetitive manual steps for the Volunteer role (e.g. batch actions, sane defaults, notifications instead of polling) wherever the spec allows it without compromising the verification pipeline's integrity.
- **Live/real-time counters.** Campaign progress bars and raised-amount counters must be visibly "live" — reflect the true current DB state on page load/refresh at minimum; consider polling or websockets in a later phase if the client wants sub-refresh live updates.
- **Daily patient updates.** Campaign update feed (`campaign_updates`, §4.1/§5) should support and visually surface a per-day cadence of patient status updates, not just an ad hoc timeline.
- **Theme: light, not dark.** The public site must render in a **white/light theme by default** — no dark mode as default anywhere in the product. Per the 2026-09-07 decision, this also applies to the Filament admin/volunteer/verification panels (light theme everywhere, not just the public site).
- **Repo visibility:** private GitHub repository under the developer's account for now.
- **Payment gateways (Stripe/ShurjoPay):** scaffold `.env` config and service class structure early, but defer real key wiring/testing to Phase 4 when test credentials are available.

---

## 1. Project Overview

**App name:** Manobik Fund
**Purpose:** Connects verified medical patients (and disaster-affected communities) with donors through transparent, milestone-tracked fundraising campaigns. Also hosts a blood donation network and an ambulance directory as public-good utilities.

**Core principle driving the architecture:** trust and transparency. Every campaign passes through a two-stage human verification pipeline before it can accept donations, and every published campaign shows live progress data (medical milestones, fund utilization) so donors can see the real impact of their contribution.

---

## 2. Tech Stack

| Layer | Choice | Notes |
|---|---|---|
| Backend framework | **Laravel 11/12** | Latest stable |
| Database | **MySQL 8** | InnoDB, utf8mb4 |
| Admin / internal panels | **Filament v3** | Powers Volunteer, Verification, and Admin panels |
| Roles & permissions | **spatie/laravel-permission** | Backing store for roles |
| Filament ↔ permission bridge | **bezhansalleh/filament-shield** | Auto-generates permissions per Filament resource, gates navigation/actions by role |
| Public/donor-facing frontend | **Laravel Livewire + Blade** (or Vue/Inertia if preferred later) | Guest browsing, donation flow, user dashboard |
| File & document storage | **spatie/laravel-medialibrary** | Medical docs, deposit slips, campaign images |
| Charts (admin + public) | **Filament native charts (ApexCharts)** for admin; **Chart.js** or ApexCharts CDN for public campaign pages | Line/bar/pie for medical + fund tracking |
| Activity/audit log | **spatie/laravel-activitylog** | Tracks every verification/approval/disbursement action |
| International payments | **Stripe** (via `stripe/stripe-php` or `laravel/cashier` for one-off charges) | USD/EUR/GBP |
| Local (BD) payments | **ShurjoPay** (custom HTTP integration — no official Laravel package, build a thin service class) | BDT, bKash, Nagad, cards |
| Notifications | **Laravel Notifications** (mail + database + optional SMS gateway later) | Campaign status changes, donation receipts |
| Queues | **Laravel Queue (database or Redis driver)** | Payment webhooks, notification dispatch, report generation |
| Geolocation | **Lat/Long columns + a mapping JS library (Leaflet or Google Maps JS API)** on the frontend | Used by campaigns, blood donors, ambulances |

---

## 3. User Roles & Permissions

Roles are implemented with `spatie/laravel-permission` and enforced across both the public app and Filament panels via `filament-shield` policies. Below is what each role **is** and **can do** — build authorization (Policies + Gates) around this exactly.

### Guest (Unauthenticated)
Not a stored role — simply the absence of a session. Can browse public campaigns, search by category/location, view campaign progress charts, and make a **one-off anonymous donation** (no account required, but still requires a valid email for the receipt).

### Authenticated User
The base role every registered account gets. Can donate (tracked), view a personal transaction history, accumulate **reward points** ("Humanity Badges") on donations, request a refund on an eligible donation, and manage their own profile. This role is a prerequisite for the "Donation Seeker" upgrade.

### Donation Seeker
Not a separate account type — it's a **capability unlocked** on an Authenticated User once their email is verified. A Seeker can:
- Create a new Treatment Fund or Emergency campaign (draft state)
- Upload clinical/medical documents and banking details
- Post progress updates and medical parameter data once approved
- View their own campaign's donation and fund-utilization breakdown

A Seeker never sees other users' campaigns for editing — enforce via Policy (`update`, `view` scoped to `campaign.seeker_id === auth()->id()`).

### Volunteer
A field-operations role, invited/approved by a Verification Admin. Has access to the **Volunteer Portal** (a Filament panel) where they:
- Receive assigned field-visit tasks for campaigns pending verification
- Submit field visit reports (notes, photos, GPS confirmation)
- Manage/update blood donation drive events
- Update emergency medical camp status (active/closed, supplies needed)

Volunteers **cannot** approve or publish campaigns — they only report findings upward.

### Verification Admin
First line of internal review. Operates the **Verification Panel** (Filament). Responsibilities:
- Reviews newly submitted campaigns and their documents
- Assigns a Volunteer for a physical field visit
- Reviews the Volunteer's field report
- Either rejects the campaign (with reason, sent back to Seeker) or **passes it forward** to Executive Admin

### Executive / Sub Admin
Second and final line of review, and the only role that can move money. Operates the **Admin Panel** (Filament). Responsibilities:
- Re-verifies everything the Verification Admin passed along
- Publishes the campaign live (makes it publicly donatable)
- Monitors funding progress
- Once a campaign is funded (or a manual disbursement milestone is reached), transfers funds to the beneficiary and **uploads the deposit slip** as public proof
- Manages refunds/redirects for cancelled or over-funded campaigns

### Super Admin
Full system authority, also in the **Admin Panel** but with a wider permission set:
- All Executive Admin capabilities
- Role & permission management (creating/removing admin accounts, reassigning roles)
- Global settings control (platform name, logo, theme, contact info, payment gateway keys)
- Full financial analytics (platform-wide donation totals, gateway breakdowns, disbursement history)

### Role summary table

| Role | Panel | Can Publish Campaigns? | Can Disburse Funds? | Can Manage Roles/Settings? |
|---|---|---|---|---|
| Guest | Public site | No | No | No |
| Authenticated User | User Dashboard | No | No | No |
| Donation Seeker | User Dashboard | No (submits only) | No | No |
| Volunteer | Volunteer Portal | No | No | No |
| Verification Admin | Verification Panel | No (forwards only) | No | No |
| Executive Admin | Admin Panel | Yes | Yes | No |
| Super Admin | Admin Panel | Yes | Yes | Yes |

---

## 4. Core Modules — Detailed Explanation

### 4.1 Treatment Fund Raising (core module)
The heart of the platform. A Donation Seeker fills out a structured application: patient details, hospital name, GPS location, treatment category, banking details for eventual disbursement, and supporting clinical documents. Once published, the campaign page shows:
- A donation progress bar (raised vs. target)
- Live medical parameter charts (see 4.2)
- A fund utilization pie chart
- A timeline of admin-verified updates

### 4.2 Real-Time Tracking Parameters
To keep the "live chart" data flexible across very different illness types (cancer, kidney failure, burns, etc.) without needing a new database table for every condition, use a **generic key-value parameter model** (see `treatment_parameters` table in section 5). This lets any campaign track any combination of:
- **Treatment milestones** — e.g. "Chemo Cycle 2 of 6" → progress bar / pie chart
- **Vital metrics** — WBC/platelet count, creatinine, bilirubin, % unhealed tissue, etc. → line chart over time
- **Pain/mobility scale** — 1–10, logged weekly → line chart
- **Hospital days vs. target release** → timeline/countdown chart
- **Fund utilization** — category + amount + proof document → pie chart

Only the Seeker or an admin can add parameter entries; each entry can optionally require admin sign-off before it appears publicly, to prevent fabricated progress data.

### 4.3 Emergency Response & Medical Camps
Same underlying campaign engine as Treatment Fund, but for disaster relief (flood, cyclone) or free medical camps — goal-based rather than patient-based, and typically created/managed directly by admins or approved organizations rather than individual Seekers.

### 4.4 Blood Donation Network
A searchable directory of willing blood donors, indexed by blood group and location (lat/long), so a requester can find nearby matches during an emergency. Volunteers help manage donation drive events; donors can toggle their own availability status.

### 4.5 Ambulance Directory
A directory (and optionally live-location) of available ambulances by district, with direct contact numbers. Can start as a static directory (Phase 1) and evolve into live GPS tracking (later phase) if ambulance operators adopt a companion app/device.

### 4.6 Education & Training
Campaigns that fund medical awareness programs, volunteer training, or nursing education — structurally identical to Treatment Fund campaigns but a distinct category, without the medical-parameter tracking.

---

## 5. Database Schema — Key Tables

This is a starting structure; refine field types/constraints during implementation.

```
settings
  id, key, value, type, group, timestamps

users
  id, name, email, email_verified_at, password, phone, avatar, timestamps
  (roles/permissions via spatie pivot tables: model_has_roles, model_has_permissions, roles, permissions)

campaigns
  id, seeker_id (FK users), category (treatment|emergency|camp|education),
  title, slug, description, hospital_name, latitude, longitude,
  bank_account_details (encrypted json), target_amount, raised_amount,
  status (draft|pending_verification|field_visit|executive_review|published|funded|completed|rejected|cancelled),
  deadline, published_at, timestamps

campaign_documents
  id, campaign_id, type (medical_report|id_proof|hospital_bill|other),
  file_path, uploaded_by, verified_at, verified_by, timestamps

campaign_updates
  id, campaign_id, posted_by, content, images (json), timestamps

treatment_parameters
  id, campaign_id, parameter_type (e.g. wbc_count|platelet|creatinine|pain_scale|milestone|hospital_days),
  label, value, unit, recorded_at, is_verified, verified_by, timestamps

fund_utilization
  id, campaign_id, category (surgery|medication|icu|post_op|other),
  amount, description, proof_file, timestamps

donations
  id, campaign_id (nullable for general fund), user_id (nullable = guest),
  donor_name, donor_email, amount, currency, gateway (stripe|shurjopay),
  transaction_id, status (pending|completed|refunded|failed), is_anonymous, timestamps

reward_points
  id, user_id, donation_id, points, timestamps

refund_requests
  id, donation_id, user_id, reason, status (pending|approved|rejected), processed_by, timestamps

disbursements
  id, campaign_id, amount, deposit_slip_file, disbursed_by (FK users), disbursed_at, timestamps

blood_donors
  id, user_id, blood_group, latitude, longitude, last_donation_date, is_available, timestamps

blood_requests
  id, requester_name, requester_phone, blood_group, hospital_name,
  latitude, longitude, urgency (normal|urgent|critical), status, timestamps

ambulances
  id, name, driver_contact, vehicle_type, district, latitude, longitude,
  is_available, added_by, timestamps

field_visit_reports
  id, campaign_id, volunteer_id, notes, images (json), recommendation (approve|reject), timestamps

activity_log  (provided by spatie/laravel-activitylog)
```

---

## 6. Development Phases

Build in this order. Each phase should end in a working, demoable slice.

### Phase 0 — Project Foundation
- Fresh Laravel install, MySQL connection, `.env` structure for multi-gateway keys
- Install and configure: `spatie/laravel-permission`, `filament-shield`, `spatie/laravel-medialibrary`, `spatie/laravel-activitylog`
- Set up base roles (`super_admin`, `executive_admin`, `verification_admin`, `volunteer`, `user`) via a seeder
- Global `settings` table + a `Settings` helper/facade so branding is pulled dynamically everywhere (site name, logo, theme colors, contact info)

### Phase 1 — Auth & Panel Scaffolding
- Standard Laravel auth (Breeze/Fortify) for the public-facing side, with email verification required before "Donation Seeker" capability unlocks
- Set up Filament panels: decide between **one shared internal panel** with Shield-gated navigation (recommended starting point — simpler to maintain) vs. three fully separate panel instances (`/volunteer`, `/verification`, `/admin`). Start with one panel, split later only if UX demands it.
- Role-based dashboard redirects after login

### Phase 2 — Campaign Core (Seeker side)
- Campaign creation form (multi-step: basic info → medical/hospital info → documents → banking)
- Document upload via medialibrary
- Draft/save-and-continue support
- Seeker's own campaign management dashboard (status, edit while in draft, post updates once published)

### Phase 3 — Verification Workflow
- Verification Admin queue: list of pending campaigns
- Volunteer assignment action (notifies Volunteer)
- Volunteer Portal: view assigned visits, submit field visit report
- Verification Admin: approve (forward to Executive) or reject (with reason back to Seeker)
- Executive Admin: final review + Publish action (flips campaign to `published`, sets `published_at`)
- Full activity log on every transition

### Phase 4 — Public Campaign Pages & Donations
- Public campaign listing + search/filter (category, location, urgency)
- Campaign detail page: progress bar, description, documents (public-safe subset), updates feed
- Guest and Authenticated donation flow (amount entry, gateway choice)
- Stripe integration (international)
- ShurjoPay integration (local/BDT + bKash/Nagad)
- Donation confirmation + email receipt

### Phase 5 — Real-Time Medical Parameter Tracking
- `treatment_parameters` CRUD (Seeker submits, requires admin verification toggle before public display)
- Public chart rendering per campaign: line charts (vitals, pain scale), progress/pie (milestones), timeline (hospital days)
- Fund utilization pie chart, tied to `fund_utilization` entries the Executive Admin logs on disbursement

### Phase 6 — Disbursement & Transparency
- Executive Admin disbursement action: mark funded/completed, upload deposit slip
- Public-facing "Transparency" section on campaign page showing deposit slip + fund utilization breakdown

### Phase 7 — Reward Points & Refunds
- Award points automatically on completed donation (configurable % in Settings)
- "Humanity Badges" display on user profile + optional homepage leaderboard/boost mechanic
- Refund request flow: user requests → Executive Admin approves/rejects → gateway refund or credit redirect to another campaign

### Phase 8 — Blood Donation Network
- Donor registration/profile (blood group, location, availability toggle)
- Public search by blood group + proximity (lat/long distance query)
- Blood request posting (public or authenticated) with urgency level
- Volunteer-managed donation drive events

### Phase 9 — Ambulance Directory
- Admin/Volunteer CRUD for ambulance listings (district, contact, vehicle type)
- Public directory with district filter and map view
- (Future extension: live GPS tracking once operators have a companion device/app)

### Phase 10 — Emergency Response, Medical Camps & Education Modules
- Reuse the campaign engine with `category` = `emergency`/`camp`/`education`
- Simplify the form for these types (no medical-parameter tracking needed for education campaigns; camps may skip banking details if run directly by the platform)

### Phase 11 — Notifications & Communication
- Email + in-app notifications: campaign status changes, new donation received, field visit assigned, disbursement completed
- Optional SMS gateway integration for critical alerts (blood requests, urgent camp needs)

### Phase 12 — Analytics, Polish & Deployment
- Super Admin financial analytics dashboard (Filament charts: total raised, per-gateway breakdown, per-category breakdown, disbursement history)
- Performance pass (query optimization, caching for public listing pages)
- Security review (encrypted banking fields, rate-limiting donation endpoints, webhook signature verification for Stripe/ShurjoPay)
- Production deployment checklist (queue worker, scheduler, storage/CDN for documents & images, backups)

---

## 7. Filament Admin Panel Structure

Recommended starting approach — **one Filament panel**, permissions-gated via `filament-shield`:

- Register a single `AdminPanelProvider` (path: `/control`)
- Generate a Filament Resource per model that needs internal management: `CampaignResource`, `CampaignDocumentResource`, `TreatmentParameterResource`, `DonationResource`, `DisbursementResource`, `BloodDonorResource`, `BloodRequestResource`, `AmbulanceResource`, `FieldVisitReportResource`, `SettingsPage` (a custom Filament page, not a resource, for the global settings singleton), `UserResource`, `RoleResource`
- Use `filament-shield`'s generated permissions (`view_campaign`, `publish_campaign`, `disburse_campaign`, etc.) to control which navigation items and actions each role sees — e.g., only `executive_admin` and `super_admin` get the "Publish" and "Disburse" actions on `CampaignResource`
- Volunteers get a heavily restricted view: only their assigned `FieldVisitReportResource` entries and blood-drive/camp status widgets

If the roles' workflows diverge significantly later (e.g., Volunteers need a mobile-first UI), split into a second panel (`VolunteerPanelProvider` at `/volunteer`) — Filament supports multiple panels natively without restructuring the underlying app.

---

## 8. Notes & Conventions for Claude Code

- Use **Form Requests** for all validation, not inline controller validation.
- Use **Policies** for every model that has role-scoped access (`CampaignPolicy`, `DonationPolicy`, etc.) rather than scattering `if` checks in controllers.
- Encrypt banking detail fields at the model level (`'bank_account_details' => 'encrypted:array'` cast).
- All money fields stored as integers (smallest currency unit, e.g. cents/poisha) to avoid float rounding issues; format for display only.
- Webhook endpoints (Stripe, ShurjoPay) must verify signatures and be idempotent — donation status updates should be safe to receive twice.
- Keep the `treatment_parameters` table generic (don't create a new table per illness type) — the chart-rendering layer should key off `parameter_type` + `unit` to decide chart style.
- Write feature tests for the verification pipeline state machine (campaign status transitions) before building the UI around it — this is the most failure-prone part of the system.
