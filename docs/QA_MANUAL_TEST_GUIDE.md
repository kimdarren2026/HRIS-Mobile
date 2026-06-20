# HRIS Mobile App Manual QA Guide

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

## 4. Payroll Calculate and Approval Workflow

1. Login as Finance.
2. Open `/payroll/periods`.
3. Use seeded `Demo Payroll July 2026 Draft` or create a new payroll period.
4. Open the period detail.
5. Click `Run Calculation`.
6. Expected: period status becomes `CALCULATED` and payroll records are created without duplicates.
7. Logout and login as Admin HR.
8. Open `/payroll/periods`, then the calculated period.
9. Click `Submit for HR Review`.
10. Expected: status becomes `HR_REVIEW`.
11. Logout and login as Finance.
12. Open the same period.
13. Click `Finance Approve`.
14. Expected: status becomes `FINANCE_APPROVAL`.
15. Click `Lock Payroll`.
16. Expected: status becomes `LOCKED`.
17. Click `Mark as Paid`.
18. Expected: status becomes `PAID`.

## 5. Employee Payslip View and Print

1. Login as Employee.
2. Open `/my/payroll`.
3. Open seeded `Demo Payroll June 2026`.
4. Confirm employee can only see own payroll record.
5. Open print view from the payslip page.
6. Expected: print-friendly payslip loads without exposing other employees.

## 6. Finance CSV Export

1. Login as Finance.
2. Open `/payroll/periods`.
3. Open `Demo Payroll June 2026`.
4. Click `Export CSV`.
5. Expected: CSV downloads with payroll period, employee, attendance days, leave days, salary columns, and status.
6. Login as Employee and try the export URL.
7. Expected: access denied.

## 7. Employee Master Data CRUD

1. Login as Admin HR.
2. Open `/employees`.
3. Search for `Demo Employee`.
4. Open employee detail.
5. Edit non-sensitive demo fields such as phone, address, status, or department.
6. Save.
7. Expected: updated data appears in detail/list view.
8. Create a new demo employee with non-real NIK and bank data.
9. Expected: employee and linked user are created.

## 8. Dashboard Summaries

1. Login as Employee and open `/employee/dashboard`.
2. Confirm today attendance, leave, and payroll summaries do not expose other employees.
3. Login as Admin HR and open `/admin/dashboard`.
4. Confirm employee count, pending attendance, pending leave, and latest payroll are shown.
5. Login as Finance and open `/finance/dashboard`.
6. Confirm payroll status counts and latest periods are shown.

## 9. Regression Checks

Run:

```bash
cd laravel-app
php artisan test
git diff --check
```

Expected:

```text
PASS
```

## Notes

- Do not use real NIK, bank account, or personal data in demo testing.
- Seeded files and uploads use Laravel local/private storage where implemented.
- `stitch/` is a design source folder and should not be edited during Laravel QA.
