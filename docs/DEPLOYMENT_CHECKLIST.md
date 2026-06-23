# Deployment Checklist

Use this before deploying HRIS Mobile App to any shared demo, staging, or production environment.

## Verified Baseline

- Latest main commit: `72775ef Implement functional in-app notifications`
- Current verified test result: 415 tests, 818 assertions
- Phase 1-21: PASS
- Finance self-service attendance fix: PASS
- Functional in-app notifications: PASS
- GitHub audit result: SAFE
- Repository audit: no ABSENSI MOBILE contamination, no secrets or runtime files tracked

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

## Payment and Notification Boundaries

- Mark Paid records application state and payment reference only.
- No real bank transfer integration exists.
- In-app notifications cover attendance, leave, payroll, and expense workflows.
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
- Payroll CSV export works for Finance/Super Admin only.
- Payroll Mark Paid requires/records a payment reference and does not initiate bank transfer.
- Employee payslip access does not expose other employees.
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
