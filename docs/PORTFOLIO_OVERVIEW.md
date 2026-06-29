# Portfolio Overview

HRIS Mobile App is a mobile-first Laravel HRIS project built from final Google Stitch screens and advanced through Phase 30. Current production is available at https://hrismobile.my.id.

Payroll direction has changed. HRIS is the source of truth for employee data and attendance; salary calculation and payment processing will be handled by a separate external payroll project. HRIS is planned to receive payroll and payslip results from that external system later.

## Verified Status

- Phase 27 Security Hardening: PASS, merged, deployed
- Phase 28 Payroll Payment Workflow: created, then reverted
- Phase 29 Role Management UI + Production Master Data: PASS, merged, deployed
- Phase 30 Attendance Production Hardening & UX: PASS, merged
- Last documented automated test baseline before later Phase 27-30 updates: 415 tests, 818 assertions
- GitHub audit result: SAFE
- Repository audit: no ABSENSI MOBILE contamination, no secrets or runtime files tracked
- Production currently has HTTPS, secure cookie configuration, role management UI, production master data, attendance out-of-radius pending review, and in-app notifications.

## Completed Phases

| Phase | Scope | Status | Deployment note |
| --- | --- | --- | --- |
| 1 | Static Blade Preview | PASS | Historical baseline |
| 2 | Static Navigation | PASS | Historical baseline |
| 3 | Database + Models | PASS | Historical baseline |
| 4 | Auth + Role Routing | PASS | Historical baseline |
| 5 | Attendance GPS + Live Camera Selfie | PASS | Historical baseline |
| 6 | Leave Request + HR Approval + Protected Attachment | PASS | Historical baseline |
| 7 | Payroll Period + Calculation Foundation | PASS | Historical internal payroll work; not the final payroll direction |
| 8 | Employee Payroll View + Payslip Access | PASS | Historical internal payroll work; not the final payroll direction |
| 9 | Payroll Approval, Lock, and Payment Workflow | PASS | Historical internal payroll work; not the final payroll direction |
| 10 | Payslip Print/Download + Payroll Report Export | PASS | Historical internal payroll work; not the final payroll direction |
| 11 | Employee Master Data Management | PASS | Historical baseline |
| 12 | Admin/Finance/Employee Dashboard Summary | PASS | Historical baseline |
| 13 | Demo Data Seeder + Project Documentation & Manual QA Guide | PASS | Historical baseline |
| 14 | Security Hardening + Error Pages + Final Role Access Audit | PASS | Historical baseline |
| 15 | Deployment Readiness + Environment Setup Guide | PASS | Historical baseline |
| 16 | Final UI Polish + Mobile Responsiveness QA | PASS | Historical baseline |
| 17 | Repository Cleanup + Ignored Metadata Files | PASS | Historical baseline |
| 18 | Production Navigation + Profile Routing Fixes | PASS | Historical baseline |
| 19 | Finance/Admin HR Self-Service + Office Location Settings + Leave Type Settings | PASS | Historical baseline |
| 20 | Attendance Role Audit + Payroll Payment Reference + Company Expense Workflow | PASS | Historical payroll payment reference only; no bank transfer integration |
| 21 | Audit Trail + Finance Maker-Checker + Payroll Payment Hardening + Super Admin Audit UI | PASS | Historical internal payroll hardening; not the final payroll direction |
| 22 | Staging Deployment Preparation | PASS, merged | Not separately documented as deployed |
| 22B | PHP 8.3 Dependency Integration | PASS, merged | Not separately documented as deployed |
| 23 | Blade UI Refactor + Flash Message Reuse | PASS, merged | Not separately documented as deployed |
| 24 | Bottom Navigation Refactor | PASS, merged | Not separately documented as deployed |
| 25 | Validation Error Component Refactor | PASS, merged | Not separately documented as deployed |
| 26 | Employee Management Database Integration | PASS, merged | Not separately documented as deployed |
| 27 | Security Hardening | PASS, merged | Deployed |
| 28 | Payroll Payment Workflow | Reverted | Created, then reverted because payroll will be handled by a separate external payroll project |
| 29 | Role Management UI + Production Master Data | PASS, merged | Deployed |
| 30 | Attendance Production Hardening & UX | PASS, merged | Merged; deployment not claimed here |

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

- Finance can access personal attendance when linked to an employee record.
- Finance remains forbidden from HR attendance approval.
- Admin HR and Finance users with linked employee records can access supported self-service routes.
- In-app notification center is available for employee, finance, admin_hr, and super_admin users.
- Each user only sees and updates their own notifications.
- Notifications cover implemented in-app workflows.
- Notifications are in-app only. Email, SMS, WhatsApp, Firebase, and browser push notifications are not implemented.

## Main Features

- Mobile-first Laravel Blade UI based on final Stitch exports.
- Session authentication and role-based routing.
- Employee attendance check-in with server-side GPS radius validation.
- Live camera selfie capture stored in private Laravel storage.
- HR attendance approval and rejection.
- Employee leave request with protected private attachment.
- HR leave approval and rejection.
- HRIS source data for external payroll: employee master data and attendance.
- Payroll calculation/payment workflow is not treated as final internal HRIS functionality.
- Future payroll scope: receive payroll and payslip results from the external payroll system.
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
| Payroll calculation/payment | No | No final internal workflow | No final internal workflow | No final internal workflow |
| Future payroll results/payslip intake | Planned via external integration | Planned via external integration | Planned via external integration | Planned via external integration |
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
- `/admin/dashboard`
- `/hr/approval-queue`
- `/employees`
- `/finance/dashboard`
- `/finance/expenses`
- `/notifications`
- `/audit-logs`
- `/reports`
- `/profile`
- `/preview`

Historical payroll/payslip routes from earlier internal payroll work may exist in the codebase, but they should not be presented as the final payroll architecture.

## Recommended Demo Flow

1. Login as Employee and show dashboard, attendance, leave request, and profile.
2. Login as Admin HR and show approval queue, leave/attendance approval, employee master data.
3. Login as Finance and show company expenses, finance dashboard, and personal attendance when linked to an employee record.
4. Login as Super Admin and show cross-admin access plus audit logs.
5. Show protected access: employee cannot access HR/Finance pages, finance cannot access HR attendance approval, and users cannot view other users' notifications.
6. Show in-app notifications for implemented workflows.
7. Show error pages for forbidden and not-found cases.
8. Explain payroll boundary honestly: HRIS provides employee and attendance source data; external payroll will calculate salary and return payroll/payslip results later.

## Test Status

Run:

```bash
cd laravel-app
php artisan test
```

Expected historical baseline: PASS, last documented at 415 tests and 818 assertions before later Phase 27-30 updates.

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
