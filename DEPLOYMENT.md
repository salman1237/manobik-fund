# Manobik Fund — Production Deployment Checklist

Companion to `manobik-fund-spec.md` §6 Phase 12 ("Production deployment checklist: queue worker, scheduler, storage/CDN for documents & images, backups"). Written for whoever runs the first production deploy — check items off as you go, don't assume any are already handled by hosting defaults.

## 1. Environment

- [ ] `APP_ENV=production`, `APP_DEBUG=false` (never expose stack traces publicly)
- [ ] `APP_URL` set to the real production domain, HTTPS
- [ ] `APP_KEY` generated fresh for production (`php artisan key:generate`) and backed up somewhere outside the app server — **losing it makes every encrypted `bank_account_details` row unrecoverable**. Never regenerate it on an existing database; that silently breaks decryption of all existing encrypted data.
- [ ] Real database credentials (MySQL 8, InnoDB, utf8mb4 per spec §2) — not the local XAMPP root/no-password setup used in development
- [ ] `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION` set to `database` or `redis` (not `array`/`sync`) — sync queue means notifications block the HTTP response (see §3)
- [ ] Real mail driver (Postmark/SES/Resend/SMTP) — currently `log` in dev, meaning **no email has ever actually been sent**, only logged
- [ ] Real Stripe keys (`STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`) and ShurjoPay merchant credentials — see `PROGRESS.md`'s Open Decisions for the ShurjoPay field-verification follow-up needed once these exist
- [ ] SMS provider credentials, if the client wants the optional SMS alerts (Phase 11) actually delivered — otherwise `LogSmsGateway` silently no-ops (logs only) in production too

## 2. Queue worker

Several things (queued notifications as of Phase 12, and anything added later) depend on a worker actually running — without one, jobs sit in the `jobs` table forever and users never get emails/SMS.

- **On a VPS/dedicated server with root access**: run `php artisan queue:work --tries=3 --backoff=30` under a process supervisor (systemd unit or Supervisor), not `screen`/`nohup`; restart it on every deploy (`php artisan queue:restart`).
- **On shared cPanel hosting (the actual deployment target as of 2026-09-07 — `fund.callofhumanity.com`)**: there's no root access for a persistent supervisor. `routes/console.php` instead schedules `queue:work --stop-when-empty --tries=3 --max-time=50` to run every minute via Laravel's scheduler (`withoutOverlapping()` guards against a slow run bleeding into the next tick) — this only needs the single cron entry in §3 below, nothing queue-specific to configure separately.
- [ ] Monitor the `failed_jobs` table / configure `queue:failed` alerting

## 3. Scheduler

- [ ] Add a single cPanel cron entry (Cron Jobs in cPanel, or `crontab -e`): `* * * * * cd /home/skoder/public_html/fund.callofhumanity.com && /opt/cpanel/ea-php82/root/usr/bin/php artisan schedule:run >> /dev/null 2>&1`
- This single entry drives both the queue worker (§2) and anything else added to `routes/console.php`'s schedule later (e.g. future refund-window expiry, digest emails) - no separate queue cron needed.

## 4. File storage

- `campaign-documents` (medical reports, ID proofs) live on the **`local`** disk (`storage/app/private`) — deliberately not public. In production this should be a private S3 bucket (or equivalent) with signed/temporary URLs for staff access, not a world-readable path.
- `deposit-slips` live on the **`public`** disk by design (spec: "public proof") — an S3 bucket with public-read is appropriate, fronted by a CDN.
- Campaign cover/gallery images (medialibrary) should also move to S3 + CDN for production — serving them from local disk doesn't scale past a single app server.
- [ ] Configure `AWS_*` env vars and set `FILESYSTEM_DISK`/relevant disk configs to `s3` in production
- [ ] Run `php artisan storage:link` is **not** needed once on S3 (only relevant for the local `public` disk in dev)

## 5. Backups

- [ ] Automated daily MySQL dumps (encrypted at rest, since `bank_account_details` is application-level encrypted but the dump itself should still be protected) with a retention policy and a tested restore procedure — an untested backup is not a backup
- [ ] S3 bucket versioning or a separate backup of uploaded documents/deposit slips

## 6. Caching & performance

- [ ] `php artisan config:cache`, `route:cache`, `view:cache` as part of the deploy pipeline (never run `config:cache` in dev — it freezes `.env` values)
- [ ] `php artisan filament:optimize` after each deploy (component discovery cache — see the Phase 12 testing log for a case where a stale cache would have hidden newly-added widgets)
- **No page-level caching was added for public campaign/donation pages** — this was a deliberate choice, not an oversight: the client explicitly required live/real-time raised-amount counters and progress bars (spec's Client Decisions log, 2026-09-07), and caching those pages would show stale totals. If traffic ever makes this a real bottleneck, cache the campaign *listing* query briefly (a few seconds) rather than full pages, and never cache an individual campaign's `raised_amount`/progress.

## 7. Security review (spec §12)

- [x] `bank_account_details` uses Laravel's `encrypted:array` cast (AES-256-CBC via `APP_KEY`) — verified in tests against the raw DB column, not just the model accessor (see Phase 2 testing log)
- [x] Stripe webhook signature verified via `Stripe\Webhook::constructEvent` before any donation is touched; rejects invalid signatures with 400 (Phase 4)
- [x] Donation and blood-request completion logic is idempotent (safe to run twice) - checked directly with duplicate-delivery tests (Phase 4)
- [x] Donation submission and blood-request posting are both rate-limited per IP (Phase 12) - 5/min and 3/5min respectively, since both are guest-accessible and abuse-prone (card testing, spamming donors with fake critical SMS alerts)
- [x] Stripe webhook route additionally throttled (120/min) as defense in depth beyond signature verification
- [ ] **ShurjoPay has no separate webhook** by design - payment confirmation is verified server-side via a direct API call to ShurjoPay's `/verification` endpoint from the donor's own return-page load, rather than trusting a webhook payload. This is intentionally *stronger* than typical webhook-signature checking (nothing client-supplied is ever trusted), but means ShurjoPay's `get_token`/`secret-pay`/`verification` field names still need to be confirmed against a live sandbox once credentials exist (see PROGRESS.md)
- [ ] Add HTTPS-only cookies / `SESSION_SECURE_COOKIE=true` in production env
- [ ] Review Filament panel session timeout settings for the internal `/control` panel (financial/medical data - shorter idle timeout than the public site is reasonable)

## 8. Post-deploy smoke test

Before announcing the deploy is done:

- [ ] Register a test account, verify email delivery actually arrives (not just logged)
- [ ] Walk one campaign through the full pipeline: create -> assign volunteer -> field report -> forward -> publish -> donate (small real amount) -> disburse
- [ ] Confirm the queue worker picked up and sent the resulting notifications (check `failed_jobs` is empty)
- [ ] Confirm `/control` panel renders light-only (no dark mode) and the Super Admin analytics widgets load
