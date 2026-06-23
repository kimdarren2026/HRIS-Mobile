# HRIS Mobile App

Mobile-first HRIS demo built with Laravel. It covers attendance, leave, payroll, payslip, employee master data, company expenses, audit trails, in-app notifications, dashboard summaries, role-based access, protected file access, and deployment handoff documentation.

## Current Status

Phase 1-21: PASS

- Finance self-service attendance fix: PASS
- Functional in-app notifications: PASS
- Current verified test result: 415 tests, 818 assertions
- Latest main commit: `72775ef Implement functional in-app notifications`
- GitHub audit result: SAFE
- Repository audit: no ABSENSI MOBILE contamination, no secrets or runtime files tracked

| Phase | Module | Status |
| --- | --- | --- |
| 1 | Static Blade preview from final Stitch screens | PASS |
| 2 | Static navigation and Blade cleanup | PASS |
| 3 | Database migrations and base models | PASS |
| 4 | Authentication and role routing | PASS |
| 5 | Attendance GPS validation and live camera selfie | PASS |
| 6 | Leave request, HR approval, protected attachment | PASS |
| 7 | Payroll period and calculation foundation | PASS |
| 8 | Employee payroll view and payslip access | PASS |
| 9 | Payroll approval, lock, and payment workflow | PASS |
| 10 | Payslip print/download and payroll CSV export | PASS |
| 11 | Employee master data management | PASS |
| 12 | Admin, finance, and employee dashboard summaries | PASS |
| 13 | Demo seed data, documentation, manual QA guide | PASS |
| 14 | Security hardening, error pages, role access audit | PASS |
| 15 | Deployment readiness and environment setup guide | PASS |
| 16 | Final UI polish and mobile responsiveness QA | PASS |
| 17 | Repository cleanup and ignored metadata files | PASS |
| 18 | Production navigation and profile routing fixes | PASS |
| 19 | Finance/Admin HR self-service access, office location settings, leave type settings | PASS |
| 20 | Attendance role audit, payroll payment reference, company expense workflow | PASS |
| 21 | Audit trail, finance maker-checker controls, payroll payment hardening, Super Admin audit UI | PASS |

## Post-Phase Improvements

- Finance and Admin HR users with linked employee records can use personal self-service routes.
- Finance users can access their own attendance check-in/history when linked to an employee record.
- Finance users remain forbidden from HR attendance approval.
- In-app notification center is available to employee, finance, admin_hr, and super_admin users.
- Each user only sees and updates their own notifications.
- Notifications cover attendance, leave, payroll, and expense workflows.
- Notification action links are internal in-app links only.

## Main Features

- Laravel session login with role routing.
- Role access for Employee, Admin HR, Finance, and Super Admin.
- Employee attendance check-in with server-side radius validation and private selfie storage.
- Attendance history and HR approval queue.
- Leave request with validated protected attachment storage.
- HR leave approval and rejection.
- Payroll period create, calculate, HR review, finance approval, lock, mark paid.
- Mark Paid records application state and payment reference only. No real bank transfer integration exists.
- Employee payroll and payslip self-service.
- Payslip print view and finance CSV export.
- Employee master data list, create, view, and update.
- Functional office location and leave type settings for Admin HR/Super Admin.
- Company expense/disbursement workflow with finance maker-checker controls.
- Super Admin-only audit log UI and append-only audit trail for key workflow changes.
- In-app notification center with unread count, mark-one-read, and mark-all-read behavior.
- Dashboard summaries for employee, HR/admin, and finance roles.
- Protected access for attendance photos and leave attachments.
- Notifications are in-app only. Email, SMS, WhatsApp, Firebase, and browser push notifications are not implemented.

## Demo Accounts

All demo passwords are for local testing only:

```text
password
```

| Role | Email | Landing Page |
| --- | --- | --- |
| Employee | employee@hris.local | /employee/dashboard |
| Admin HR | admin.hr@hris.local | /admin/dashboard |
| Finance | finance@hris.local | /finance/dashboard |
| Super Admin | super.admin@hris.local | /admin/dashboard |

## Local Setup

```bash
cd laravel-app
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
```

For local SQLite demo, keep:

```env
DB_CONNECTION=sqlite
```

Then run:

```bash
php artisan migrate
php artisan db:seed
php artisan test
php artisan serve
```

Open:

```text
http://127.0.0.1:8000/login
```

## Safe Database Commands

Use this for normal setup or deployment:

```bash
php artisan migrate
php artisan db:seed
```

Production migration command:

```bash
php artisan migrate --force
```

Do not run `php artisan migrate:fresh` on existing data. It drops tables and destroys records. Use it only in disposable local test databases.

## Environment Notes

- `.env.example` contains local demo-safe defaults and no real secrets.
- `APP_KEY` must be generated per environment.
- `APP_DEBUG=false` for production.
- `APP_ENV=production` for production.
- Private attendance and leave files use Laravel local/private storage; do not expose private upload folders through public symlinks.
- Do not expose payroll, bank account, attachment, selfie, password, token, or private notification data in public logs or documentation.
- Browser camera/GPS APIs require HTTPS in real deployments, except localhost development.

## Testing

Current verified baseline:

```text
415 tests, 818 assertions
```

```bash
cd laravel-app
php artisan test
git diff --check
```

## Documentation

- Manual QA: `docs/QA_MANUAL_TEST_GUIDE.md`
- Deployment checklist: `docs/DEPLOYMENT_CHECKLIST.md`
- Portfolio overview: `docs/PORTFOLIO_OVERVIEW.md`

## Repository Notes

- `laravel-app/` is the Laravel application used for development, testing, and demo deployment.
- `docs/` contains project documentation, QA flows, portfolio notes, and deployment guidance.
- `stitch/` folders contain UI design/export references only and are not edited during Laravel development.
- Metadata and runtime files are ignored; do not track `.env`, logs, caches, private uploads, `vendor/`, `node_modules/`, or local IDE/agent folders.

## Project Structure

```text
HRIS MOBILE/
|-- README.md
|-- CLAUDE.md
|-- docs/
|-- laravel-app/
|-- stitch/
|-- prompts/
```

`stitch/` contains source design exports and final screen assets. It is not required for running the Laravel app.
