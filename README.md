# HRIS Mobile App

Mobile-first HRIS demo built with Laravel. The project covers employee attendance, leave, payroll, payslip, employee master data, dashboard summaries, role-based access, and protected private file access.

## Current Status

Phase 1-13: PASS

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

## Feature List

- Session-based Laravel login with role routing.
- Employee attendance check-in with server-side radius decision and private selfie storage.
- Attendance history and HR approval queue.
- Leave request with validated private attachment storage.
- HR leave approval/rejection.
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

## Setup

```bash
cd laravel-app
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

Open:

```text
http://127.0.0.1:8000/login
```

## Safe Database Commands

Use this for normal setup or demo refresh:

```bash
php artisan migrate --seed
```

Use this to refresh demo data without dropping tables:

```bash
php artisan db:seed
```

The demo seeder is idempotent and uses `updateOrCreate()` for seeded records. Do not use `migrate:fresh` on shared or reviewed data.

## Testing

```bash
cd laravel-app
php artisan test
```

Manual QA guide:

```text
docs/QA_MANUAL_TEST_GUIDE.md
```

## Manual Testing Summary

1. Login role routing: sign in with each demo account and confirm the expected dashboard.
2. Employee attendance: login as employee, open `/attendance/checkin`, allow location/camera, submit check-in, confirm `/attendance/history`.
3. Leave request: login as employee, submit `/leave/request`, then login as Admin HR and approve/reject from `/hr/approval-queue`.
4. Payroll workflow: login as Finance to create/calculate payroll, Admin HR to submit HR review, Finance to approve, lock, mark paid, and export CSV.
5. Payslip view: login as employee, open `/my/payroll`, view and print a payslip.
6. Employee master data: login as Admin HR, open `/employees`, create/edit demo employee data.
7. Dashboard summaries: check `/employee/dashboard`, `/admin/dashboard`, and `/finance/dashboard`.

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
