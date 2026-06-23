# Portfolio Overview

HRIS Mobile App is a mobile-first Laravel HRIS portfolio project built from final Google Stitch screens and completed through Phase 21, plus verified post-phase fixes for finance self-service attendance and functional in-app notifications.

## Verified Status

- Phase 1-21: PASS
- Finance self-service attendance fix: PASS
- Functional in-app notifications: PASS
- Current verified test result: 415 tests, 818 assertions
- Latest main commit: `72775ef Implement functional in-app notifications`
- GitHub audit result: SAFE
- Repository audit: no ABSENSI MOBILE contamination, no secrets or runtime files tracked

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
| 16 | Final UI Polish + Mobile Responsiveness QA | PASS |
| 17 | Repository Cleanup + Ignored Metadata Files | PASS |
| 18 | Production Navigation + Profile Routing Fixes | PASS |
| 19 | Finance/Admin HR Self-Service + Office Location Settings + Leave Type Settings | PASS |
| 20 | Attendance Role Audit + Payroll Payment Reference + Company Expense Workflow | PASS |
| 21 | Audit Trail + Finance Maker-Checker + Payroll Payment Hardening + Super Admin Audit UI | PASS |

## Post-Phase Improvements

- Finance can access personal attendance when linked to an employee record.
- Finance remains forbidden from HR attendance approval.
- Admin HR and Finance users with linked employee records can access supported self-service routes.
- In-app notification center is available for employee, finance, admin_hr, and super_admin users.
- Each user only sees and updates their own notifications.
- Notifications cover attendance, leave, payroll, and expense workflows.
- Notifications are in-app only. Email, SMS, WhatsApp, Firebase, and browser push notifications are not implemented.

## Main Features

- Mobile-first Laravel Blade UI based on final Stitch exports.
- Session authentication and role-based routing.
- Employee attendance check-in with server-side GPS radius validation.
- Live camera selfie capture stored in private Laravel storage.
- HR attendance approval and rejection.
- Employee leave request with protected private attachment.
- HR leave approval and rejection.
- Payroll period creation, calculation, HR review, finance approval, lock, and paid status.
- Mark Paid records application state and payment reference only. No real bank transfer integration exists.
- Employee payroll and payslip self-service.
- Payslip print view.
- Finance CSV export.
- Employee master data CRUD.
- Functional office location and leave type settings.
- Company expense/disbursement workflow with finance maker-checker controls.
- Append-only audit trail with Super Admin-only audit log UI.
- In-app notification list/detail, unread bell count, mark one as read, and mark all as read.
- Dashboard summaries for Admin HR, Finance, and Employee.
- Security hardening, role access tests, and custom error pages.
- Final mobile UI polish for core demo pages and payslip print responsiveness.

## Role Matrix

| Capability | Employee | Admin HR | Finance | Super Admin |
| --- | --- | --- | --- | --- |
| Employee dashboard | Yes | No | No | No |
| Attendance check-in/history | Yes | Yes, if linked employee | Yes, if linked employee | Yes, if linked employee |
| Leave request/history | Yes | Yes, if linked employee | Yes, if linked employee | Yes, if linked employee |
| View own payroll/payslip | Yes | Yes, if linked employee | Yes, if linked employee | Yes, if linked employee |
| Attendance approval queue | No | Yes | No | Yes |
| Leave approval | No | Yes | No | Yes |
| Employee master data | No | Yes | No | Yes |
| Payroll period view | No | Yes | Yes | Yes |
| Payroll calculate/approve/lock/pay/export | No | Limited HR review | Yes | Yes |
| Company expense workflow | No | Create/view own permitted HR submissions | Yes | Yes |
| Notification center | Yes | Yes | Yes | Yes |
| Audit log UI | No | No | No | Yes |
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
- `/finance/expenses`
- `/payroll/periods`
- `/notifications`
- `/audit-logs`
- `/reports`
- `/profile`
- `/preview`

## Recommended Demo Flow

1. Login as Employee and show dashboard, attendance, leave request, payroll, payslip print.
2. Login as Admin HR and show approval queue, leave/attendance approval, employee master data.
3. Login as Finance and show payroll periods, payment reference, company expenses, CSV export, finance dashboard, and personal attendance when linked to an employee record.
4. Login as Super Admin and show cross-admin access plus audit logs.
5. Show protected access: employee cannot access HR/Finance pages, finance cannot access HR attendance approval, and users cannot view other users' notifications.
6. Show in-app notifications for attendance, leave, payroll, and expense workflows.
7. Show error pages for forbidden and not-found cases.

## Test Status

Run:

```bash
cd laravel-app
php artisan test
```

Expected: PASS, currently verified at 415 tests and 818 assertions.

Latest verified main commit: `72775ef Implement functional in-app notifications`.

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
