# Portfolio Overview

HRIS Mobile App is a mobile-first Laravel HRIS portfolio project built from final Google Stitch screens and completed through Phase 15.

## Completed Phases

| Phase | Scope | Status |
| --- | --- | --- |
| 1 | Static Blade Preview | PASS |
| 2 | Static Navigation | PASS |
| 3 | Database + Models | PASS |
| 4 | Auth + Role Routing | PASS |
| 5 | Attendance GPS + Live Camera Selfie | PASS |
| 6 | Leave Request + HR Approval + Protected Attachment | PASS |
| 7 | Payroll Period + Calculation Foundation | PASS |
| 8 | Employee Payroll View + Payslip Access | PASS |
| 9 | Payroll Approval, Lock, and Payment Workflow | PASS |
| 10 | Payslip Print/Download + Payroll Report Export | PASS |
| 11 | Employee Master Data Management | PASS |
| 12 | Admin/Finance/Employee Dashboard Summary | PASS |
| 13 | Demo Data Seeder + Project Documentation & Manual QA Guide | PASS |
| 14 | Security Hardening + Error Pages + Final Role Access Audit | PASS |
| 15 | Deployment Readiness + Environment Setup Guide | PASS |

## Main Features

- Mobile-first Laravel Blade UI based on final Stitch exports.
- Session authentication and role-based routing.
- Employee attendance check-in with server-side GPS radius validation.
- Live camera selfie capture stored in private Laravel storage.
- HR attendance approval and rejection.
- Employee leave request with protected private attachment.
- HR leave approval and rejection.
- Payroll period creation, calculation, HR review, finance approval, lock, and paid status.
- Employee payroll and payslip self-service.
- Payslip print view.
- Finance CSV export.
- Employee master data CRUD.
- Dashboard summaries for Admin HR, Finance, and Employee.
- Security hardening, role access tests, and custom error pages.

## Role Matrix

| Capability | Employee | Admin HR | Finance | Super Admin |
| --- | --- | --- | --- | --- |
| Employee dashboard | Yes | No | No | No |
| Attendance check-in/history | Yes | No | No | No |
| Leave request/history | Yes | No | No | No |
| View own payroll/payslip | Yes | No | No | No |
| Attendance approval queue | No | Yes | No | Yes |
| Leave approval | No | Yes | No | Yes |
| Employee master data | No | Yes | No | Yes |
| Payroll period view | No | Yes | Yes | Yes |
| Payroll calculate/approve/lock/pay/export | No | Limited HR review | Yes | Yes |
| Reports page | No | Yes | Yes | Yes |
| Dashboard summary | Own dashboard | Admin dashboard | Finance dashboard | Admin dashboard |

## Demo Accounts

All demo accounts use:

```text
password
```

| Role | Email |
| --- | --- |
| Employee | employee@hris.local |
| Admin HR | admin.hr@hris.local |
| Finance | finance@hris.local |
| Super Admin | super.admin@hris.local |

## Screens and Pages to Demonstrate

- `/login`
- `/employee/dashboard`
- `/attendance/checkin`
- `/attendance/history`
- `/leave/request`
- `/leave/history`
- `/my/payroll`
- `/admin/dashboard`
- `/hr/approval-queue`
- `/employees`
- `/finance/dashboard`
- `/payroll/periods`
- `/reports`
- `/profile`
- `/preview`

## Recommended Demo Flow

1. Login as Employee and show dashboard, attendance, leave request, payroll, payslip print.
2. Login as Admin HR and show approval queue, leave/attendance approval, employee master data.
3. Login as Finance and show payroll periods, calculation workflow, CSV export, finance dashboard.
4. Login as Super Admin and show cross-admin access.
5. Show protected access: employee cannot access HR/Finance pages.
6. Show error pages for forbidden and not-found cases.

## Test Status

Run:

```bash
cd laravel-app
php artisan test
```

Expected: PASS.

Last Phase 15 verification: full suite PASS before commit.

## Deployment Readiness

See:

```text
docs/DEPLOYMENT_CHECKLIST.md
```

Key reminders:

- Use `APP_ENV=production` and `APP_DEBUG=false` outside local development.
- Set a real `APP_KEY`.
- Back up database and private uploads before migrations.
- Use `php artisan migrate --force` for production migrations.
- Do not run `migrate:fresh` on existing data.
