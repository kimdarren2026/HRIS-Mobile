# HRIS Mobile App Manual QA Guide

Current production URL:

```text
https://hrismobile.my.id
```

Use this guide for portfolio/demo verification after running:

```bash
cd laravel-app
php artisan migrate --seed
php artisan serve
```

Base URL:

```text
http://127.0.0.1:8000
```

Demo password for all accounts:

```text
password
```

## Demo Accounts

| Role | Email | Expected Redirect |
| --- | --- | --- |
| Employee | employee@hris.local | /employee/dashboard |
| Admin HR | admin.hr@hris.local | /admin/dashboard |
| Finance | finance@hris.local | /finance/dashboard |
| Super Admin | super.admin@hris.local | /admin/dashboard |

## 1. Login Role Routing

1. Open `/login`.
2. Login with `employee@hris.local`.
3. Confirm redirect to `/employee/dashboard`.
4. Logout from `/profile`.
5. Repeat for Admin HR, Finance, and Super Admin.
6. Try opening a page outside the role, for example employee to `/admin/dashboard`.
7. Expected: forbidden response or redirect, not data exposure.

## 2. Employee Attendance

1. Login as Employee.
2. Open `/attendance/checkin`.
3. Allow browser location and camera.
4. Submit check-in within radius if available.
5. Expected: redirect to `/attendance/history` with approved status.
6. Test outside-radius path by using coordinates outside office radius if the browser/devtools allows location override.
7. Enter a reason and submit.
8. Expected: attendance status becomes `PENDING_REVIEW`.
9. Login as Admin HR and open `/hr/approval-queue`.
10. Approve or reject the pending attendance.
11. Expected: status changes and private selfie access remains protected.

## 3. Leave Request and HR Approval

1. Login as Employee.
2. Open `/leave/request`.
3. Select a leave type, date range, and reason.
4. Optionally attach a PDF/JPG/PNG file under 5MB.
5. Submit request.
6. Expected: redirect to `/leave/history`.
7. Login as Admin HR.
8. Open `/hr/approval-queue`.
9. Approve a pending leave request.
10. Repeat with another request and reject with a clear note.
11. Expected: status changes correctly and attachment URL is protected by auth/policy.

## 4. Payroll Boundary

1. Confirm demo and handoff material does not describe HRIS as the final internal payroll calculator or payment processor.
2. Confirm the documented payroll direction is: HRIS is the source of truth for employee data and attendance; an external payroll system will calculate salary; HRIS will later receive payroll and payslip results from that external system.
3. Confirm Phase 28 is described as created and then reverted.
4. If historical payroll/payslip screens exist in the codebase, treat them as legacy/demo context only and do not present them as the current production payroll architecture.
5. Confirm no demo step claims HRIS initiates bank transfer or owns final salary calculation.

## 5. Employee Master Data CRUD

1. Login as Admin HR.
2. Open `/employees`.
3. Search for `Demo Employee`.
4. Open employee detail.
5. Edit non-sensitive demo fields such as phone, address, status, or department.
6. Save.
7. Expected: updated data appears in detail/list view.
8. Create a new demo employee with non-real NIK and bank data.
9. Expected: employee and linked user are created.

## 6. Dashboard Summaries

1. Login as Employee and open `/employee/dashboard`.
2. Confirm today attendance and leave summaries do not expose other employees.
3. Login as Admin HR and open `/admin/dashboard`.
4. Confirm employee count, pending attendance, and pending leave are shown.
5. Login as Finance and open `/finance/dashboard`.
6. Confirm finance dashboard data is limited to currently supported workflows and does not present reverted payroll payment workflow as final.

## 7. In-App Notifications

1. Login as Employee and submit outside-radius attendance.
2. Login as Admin HR and open `/notifications`.
3. Expected: HR sees an attendance review notification.
4. Approve or reject the attendance from `/hr/approval-queue`.
5. Login as the original Employee and open `/notifications`.
6. Expected: Employee sees the attendance decision notification.
7. Submit a leave request as Employee.
8. Expected: Admin HR/Super Admin receive an in-app leave review notification.
9. Mark one notification as read, then use Mark all as read.
10. Expected: unread count updates and only the signed-in user's notifications are visible.
11. Login as another role and try a direct URL to a different user's notification.
12. Expected: not found response, not data exposure.
13. Note: notifications are in-app only. Email, SMS, WhatsApp, Firebase, and browser push notifications are not implemented.

## 8. Finance Self-Service and Approval Boundary

1. Login as Finance with a linked employee record.
2. Open `/attendance/checkin` and `/attendance/history`.
3. Expected: personal attendance self-service loads.
4. Open `/hr/approval-queue`.
5. Expected: forbidden response.

## 9. Mobile Responsiveness QA

1. Open Chrome DevTools device toolbar.
2. Test widths `375px`, `390px`, and `430px` on login, dashboards, attendance, leave, employee directory, profile, notifications, and error pages.
3. Confirm fixed headers and bottom navigation stay centered within the mobile frame on desktop preview.
4. Confirm cards, status badges, action buttons, and empty states do not overflow horizontally.
5. Confirm any legacy payroll screens, if reviewed, are not used as evidence of final internal payroll ownership.
6. Open employee directory with filters that return no result; confirm the empty state and actions fit inside the mobile width.

## 10. Regression Checks

Run:

```bash
cd laravel-app
php artisan test
git diff --check
```

Expected:

```text
PASS. Last documented automated baseline before later Phase 27-30 updates: 415 tests and 818 assertions.
```

## Notes

- Do not use real NIK, bank account, or personal data in demo testing.
- Seeded files and uploads use Laravel local/private storage where implemented.
- `stitch/` is a design source folder and should not be edited during Laravel QA.
- Do not expose payroll, bank account, attachment, selfie, password, token, or private notification data in portfolio screenshots or handoff notes.
