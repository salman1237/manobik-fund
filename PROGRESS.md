# Manobik Fund — Progress Tracker

Source of truth for what's actually done vs. planned. Updated at the end of every phase (or whenever a phase's scope changes). See `manobik-fund-spec.md` for the full spec and phase definitions.

Legend: ⬜ Not started · 🟨 In progress · ✅ Done & tested · 🚫 Blocked

## Repo & Environment

| Item | Status | Notes |
|---|---|---|
| GitHub repo created (private) | ⬜ | |
| Local git init + first commit | ⬜ | |
| Laravel app scaffolded | ⬜ | |
| MySQL DB connected | ⬜ | Using XAMPP MySQL locally |

## Phases

| Phase | Description | Status | Commit | Notes |
|---|---|---|---|---|
| 0 | Project Foundation (Laravel install, permissions, shield, medialibrary, activitylog, roles seeder, settings table) | ⬜ | | |
| 1 | Auth & Panel Scaffolding | ⬜ | | |
| 2 | Campaign Core (Seeker side) | ⬜ | | |
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

- (none yet)

## Open Decisions / Follow-ups

- Stripe & ShurjoPay real credentials — deferred to Phase 4, client to provide test keys.
- Filament panel structure: starting with **one shared panel** at `/control`, gated by filament-shield (per spec §7). Revisit only if UX demands split panels.
- Theme: light/white everywhere (public site + all Filament panels), per client feedback 2026-09-07.
