# HRIS Mobile App

Mobile-first HRIS project built with Laravel. It covers attendance, leave, employee master data, company expenses, audit trails, in-app notifications, dashboard summaries, role-based access, protected file access, production handoff documentation, and future payroll integration boundaries.

Payroll direction has changed: HRIS is the source of truth for employee data and attendance. Salary calculation and payment processing will be handled by a separate external payroll project. HRIS is planned to receive payroll and payslip results from that external system later.

## Current Status

Current production URL: https://hrismobile.my.id

Production currently has HTTPS, secure cookie configuration, role management UI, production master data, attendance out-of-radius pending review, and in-app notifications.

| Phase | Module | Status | Deployment note |
| --- | --- | --- | --- |
| 1 | Static Blade preview from final Stitch screens | PASS | Historical baseline |
| 2 | Static navigation and Blade cleanup | PASS | Historical baseline |
| 3 | Database migrations and base models | PASS | Historical baseline |
| 4 | Authentication and role routing | PASS | Historical baseline |
| 5 | Attendance GPS validation and live camera selfie | PASS | Historical baseline |
| 6 | Leave request, HR approval, protected attachment | PASS | Historical baseline |
| 7 | Payroll period and calculation foundation | PASS | Historical internal payroll work; not the final payroll direction |
| 8 | Employee payroll view and payslip access | PASS | Historical internal payroll work; not the final payroll direction |
| 9 | Payroll approval, lock, and payment workflow | PASS | Historical internal payroll work; not the final payroll direction |
| 10 | Payslip print/download and payroll CSV export | PASS | Historical internal payroll work; not the final payroll direction |
| 11 | Employee master data management | PASS | Historical baseline |
| 12 | Admin, finance, and employee dashboard summaries | PASS | Historical baseline |
| 13 | Demo seed data, documentation, manual QA guide | PASS | Historical baseline |
| 14 | Security hardening, error pages, role access audit | PASS | Historical baseline |
| 15 | Deployment readiness and environment setup guide | PASS | Historical baseline |
| 16 | Final UI polish and mobile responsiveness QA | PASS | Historical baseline |
| 17 | Repository cleanup and ignored metadata files | PASS | Historical baseline |
| 18 | Production navigation and profile routing fixes | PASS | Historical baseline |
| 19 | Finance/Admin HR self-service access, office location settings, leave type settings | PASS | Historical baseline |
| 20 | Attendance role audit, payroll payment reference, company expense workflow | PASS | Historical payroll payment reference only; no bank transfer integration |
| 21 | Audit trail, finance maker-checker controls, payroll payment hardening, Super Admin audit UI | PASS | Historical internal payroll hardening; not the final payroll direction |
| 22 | Staging deployment preparation | PASS, merged | Not separately documented as deployed |
| 22B | PHP 8.3 dependency integration | PASS, merged | Not separately documented as deployed |
| 23 | Blade UI refactor and flash message reuse | PASS, merged | Not separately documented as deployed |
| 24 | Bottom navigation refactor | PASS, merged | Not separately documented as deployed |
| 25 | Validation error component refactor | PASS, merged | Not separately documented as deployed |
| 26 | Employee management database integration | PASS, merged | Not separately documented as deployed |
| 27 | Security hardening | PASS, merged | Deployed |
| 28 | Payroll payment workflow | Reverted | Created, then reverted because payroll will be handled by a separate external payroll project |
| 29 | Role management UI and production master data | PASS, merged | Deployed |
| 30 | Attendance production hardening and UX | PASS, merged | Merged; deployment not claimed here |

## Post-Phase 21 Timeline

- Phase 22 prepared staging deployment work after the Phase 21 baseline.
- Phase 22B updated dependency integration for PHP 8.3 staging compatibility.
- Phase 23 refactored Blade UI details and reused flash messages.
- Phase 24 refactored bottom navigation.
- Phase 25 extracted reusable validation error presentation.
- Phase 26 integrated employee management with database-backed production data.
- Phase 27 completed security hardening and was merged and deployed.
- Phase 28 introduced an internal payroll payment workflow, then was reverted. It is not the current payroll plan.
- Phase 29 delivered role management UI and production master data, then was merged and deployed.
- Phase 30 delivered attendance production hardening and UX work, then was merged.

## Next Planned Phases

| Phase | Planned scope | Status |
| --- | --- | --- |
| 31 | Documentation Status Update | Current documentation-only update |
| 32 | Mobile UI Consistency Audit | Planned |
| 33 | Backup/Production DevOps | Planned |
| 34 | Payroll External Integration Contract | Planned; waiting for external payroll project details |
| 35 | SIAKAT SSO Integration | Planned |

## Post-Phase Improvements

- Finance and Admin HR users with linked employee records can use personal self-service routes.
- Finance users can access their own attendance check-in/history when linked to an employee record.
- Finance users remain forbidden from HR attendance approval.
- In-app notification center is available to employee, finance, admin_hr, and super_admin users.
- Each user only sees and updates their own notifications.
- Notifications cover implemented in-app workflows.
- Notification action links are internal in-app links only.

## Main Features

- Laravel session login with role routing.
- Role access for Employee, Admin HR, Finance, and Super Admin.
- Employee attendance check-in with server-side radius validation and private selfie storage.
- Attendance history and HR approval queue.
- Leave request with validated protected attachment storage.
- HR leave approval and rejection.
- HRIS source data for external payroll: employee master data and attendance.
- Payroll calculation/payment workflow is not treated as final internal HRIS functionality.
- Future payroll scope: receive payroll and payslip results from the external payroll system.
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

Last documented automated baseline before the later Phase 27-30 updates:

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
