# HRIS Mobile App

Mobile-first HRIS demo built with Laravel. It covers attendance, leave, payroll, payslip, employee master data, dashboard summaries, role-based access, protected file access, and final deployment-readiness documentation.

## Current Status

Phase 1-15: PASS

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

## Main Features

- Laravel session login with role routing.
- Role access for Employee, Admin HR, Finance, and Super Admin.
- Employee attendance check-in with server-side radius validation and private selfie storage.
- Attendance history and HR approval queue.
- Leave request with validated protected attachment storage.
- HR leave approval and rejection.
- Payroll period create, calculate, HR review, finance approval, lock, mark paid.
- Employee payroll and payslip self-service.
- Payslip print view and finance CSV export.
- Employee master data list, create, view, and update.
- Dashboard summaries for employee, HR/admin, and finance roles.
- Protected access for attendance photos and leave attachments.

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
- Browser camera/GPS APIs require HTTPS in real deployments, except localhost development.

## Testing

```bash
cd laravel-app
php artisan test
git diff --check
```

## Documentation

- Manual QA: `docs/QA_MANUAL_TEST_GUIDE.md`
- Deployment checklist: `docs/DEPLOYMENT_CHECKLIST.md`
- Portfolio overview: `docs/PORTFOLIO_OVERVIEW.md`

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
