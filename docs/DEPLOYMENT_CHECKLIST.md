# Deployment Checklist

Use this before deploying HRIS Mobile App to any shared demo, staging, or production environment.

## Verified Baseline

- Production URL: https://hrismobile.my.id
- Phase 27 Security Hardening: PASS, merged, deployed
- Phase 28 Payroll Payment Workflow: created, then reverted
- Phase 29 Role Management UI + Production Master Data: PASS, merged, deployed
- Phase 30 Attendance Production Hardening & UX: PASS, merged
- Last documented automated test baseline before later Phase 27-30 updates: 415 tests, 818 assertions
- GitHub audit result: SAFE
- Repository audit: no ABSENSI MOBILE contamination, no secrets or runtime files tracked
- Production currently has HTTPS, secure cookie configuration, role management UI, production master data, attendance out-of-radius pending review, and in-app notifications.
- Phase 30 is recorded as merged; this checklist does not claim Phase 30 has been deployed.

## Pre-Deployment

- Confirm branch and commit to deploy.
- Run the test suite:

```bash
cd laravel-app
php artisan test
```

- Back up the database before any migration.
- Back up private uploaded files from `storage/app/private`.
- Confirm no real secrets are committed.
- Confirm `.env` exists on the server and is not tracked by Git.
- Confirm runtime files remain untracked: logs, caches, SQLite demo databases, private uploads, `vendor/`, `node_modules/`, and public build artifacts.
- Confirm notification delivery expectations are clear: notifications are in-app only.

## Environment

Set production-safe values:

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...
APP_URL=https://your-domain.example
```

Database:

```env
DB_CONNECTION=mysql
DB_HOST=...
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
```

Session/cache/queue:

```env
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

Local/demo SQLite is acceptable only for local portfolio review. Use a real database for shared deployments.

## Install and Build

```bash
cd laravel-app
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
```

Generate `APP_KEY` only for a new environment:

```bash
php artisan key:generate
```

Do not regenerate `APP_KEY` on an existing production app with encrypted data or active sessions unless planned.

## Migrations and Seeders

Run migrations safely:

```bash
php artisan migrate --force
```

For a demo environment only, seed demo data:

```bash
php artisan db:seed --force
```

Do not run:

```bash
php artisan migrate:fresh
```

`migrate:fresh` drops all tables and destroys existing data.

## Payroll and Notification Boundaries

- HRIS is the source of truth for employee data and attendance.
- Salary calculation and payment processing will be handled by a separate external payroll project.
- HRIS is planned to receive payroll and payslip results from the external payroll system later.
- Phase 28 internal payroll payment workflow was created and then reverted. Do not deploy or document it as the final HRIS payroll workflow.
- Historical internal payroll screens or data structures should be treated as legacy/demo context unless a future external integration contract reuses them explicitly.
- In-app notifications cover implemented workflows.
- No email, SMS, WhatsApp, Firebase, or browser push notification integration exists.
- Do not expose payroll amounts, bank accounts, private attachments, attendance selfies, passwords, tokens, or private notification details in logs, screenshots, tickets, or public portfolio material.

## Storage

- Ensure write permissions for:
  - `storage/`
  - `bootstrap/cache/`
- Private attendance selfies and leave attachments are stored under Laravel local/private storage.
- Do not publicly expose `storage/app/private`.
- Run public storage link only if the environment serves public assets from `storage/app/public`:

```bash
php artisan storage:link
```

The current private HRIS upload flows do not require public access to private files.

## HTTPS, Camera, and GPS

- Use HTTPS for shared demo/staging/production.
- Browser camera and geolocation APIs generally require a secure context.
- `http://localhost` is acceptable for local development only.
- Confirm mobile browser permissions for camera and location.

## Cache and Optimization

After environment variables are final:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

After each deploy:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Final Verification

- `/login` loads.
- Demo role routing works if demo data is seeded.
- Employee attendance page prompts for camera/location.
- Finance users with linked employee records can access personal attendance.
- Finance users remain forbidden from HR attendance approval.
- Leave attachment access is protected.
- Office location and leave type settings work for Admin HR/Super Admin.
- Role management UI is available where deployed.
- Production master data is present where deployed.
- Payroll calculation/payment is not verified as final internal HRIS functionality.
- Future payroll/payslip behavior should be verified against the external payroll integration contract once that contract exists.
- Company expense submit/approve/reject/paid workflow follows finance maker-checker controls.
- Audit log UI is accessible to Super Admin only.
- `/notifications` loads for Employee, Admin HR, Finance, and Super Admin.
- Users cannot view or mark another user's notification.
- Notification unread count, mark one as read, and mark all as read work.
- Admin/Finance/Employee dashboards load.
- Error pages render for forbidden/not found/server error cases.

## Rollback Notes

- Keep a database backup before migration.
- Keep the previous deploy artifact or Git commit available.
- If migration fails, stop traffic, restore DB backup if needed, and redeploy the previous known-good commit.
