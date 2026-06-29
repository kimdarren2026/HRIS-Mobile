# 05. Stitch UI Prompts — HRIS Mobile App

**Petunjuk pemakaian**: salin setiap blok prompt di bawah ini satu per satu ke Google Stitch (atau UI generator lain seperti Figma AI / v0). Semua prompt sudah mengikuti gaya desain yang sama: mobile-first, clean modern HR dashboard, warna biru/indigo/putih, profesional untuk aplikasi kantor.

> Status terkini: prompt payroll/payslip di dokumen ini adalah artefak rancangan historis. Setelah Phase 28 direvert, final payroll calculation/payment akan ditangani external payroll system. HRIS akan menyediakan employee data dan attendance sebagai source of truth, lalu menerima payroll/payslip results setelah kontrak integrasi tersedia.

**Gaya desain global** (sertakan di setiap prompt jika tool memerlukan konteks ulang):
> Design style: mobile-first, clean and modern HR dashboard, professional and corporate feel, color palette primary indigo/blue (#4F46E5 / #4338CA) with white background and soft gray neutrals, rounded cards with subtle shadow, clear status badges (green=approved, yellow=pending, red=rejected), bottom navigation bar with icons, generous spacing, sans-serif font (Inter or similar).

---

**1. Login**
```
Design a mobile login screen for an HR/HRIS app called "HRIS Mobile App". Include: app logo/name at top, email input field, password input field with show/hide toggle, "Forgot Password" link, primary indigo "Login" button (full width, rounded), subtle footer text "© Company Name". Clean white background, mobile-first layout (390px width), modern corporate style, indigo/blue accent color.
```

**2. Employee Dashboard**
```
Design a mobile dashboard home screen for an employee in an HRIS app. Top section: greeting "Hi, [Name]" with profile photo avatar and notification bell icon. Below: a card showing today's attendance status with a status badge (Approved/Pending/Not yet checked in) and quick check-in/check-out button. Next: a card showing remaining leave balance (e.g. "12 days left"). Next: a card showing latest leave request status with badge. Next: a card showing latest payslip summary with "View Payslip" link. Bottom navigation bar with icons: Home, Attendance, Leave, Payslip, Profile. Style: clean modern HR dashboard, indigo/blue/white color palette, rounded cards with soft shadow, mobile-first 390px width.
```

**3. Attendance Check-in/Check-out**
```
Design a mobile attendance check-in screen for an HRIS app. Show: current date and time at top, a map preview card showing user's current GPS location with a pin, a circular selfie camera capture button/preview below the map, status text showing "Within office radius" or "Outside office radius" with colored indicator (green/red), a conditional text area for "Reason" that appears only when outside radius, large primary indigo "Check In" button at the bottom (full width, rounded). Mobile-first, clean modern HR app style, indigo/blue/white palette.
```

**4. Attendance History**
```
Design a mobile attendance history screen for an HRIS app. Top: filter bar with date range picker and dropdown (This Month/Last Month). Below: a scrollable list of attendance entries, each item as a card showing date, check-in time, check-out time, and a status badge (Approved=green, Pending Review=yellow, Rejected=red). Empty state illustration if no data. Bottom navigation bar present. Style: clean modern HR dashboard, indigo/blue/white, mobile-first 390px width.
```

**5. Leave Request Form**
```
Design a mobile leave/permission request form screen for an HRIS app. Include: header "Request Leave" with back button, dropdown to select leave type (Annual Leave, Sick Leave, Personal Leave, Special Leave), date range picker for start date and end date, a text area for reason, an attachment upload button (optional, shows file name once uploaded), a summary text showing remaining leave balance, primary indigo "Submit Request" button full width at bottom. Mobile-first, clean modern HR app style, indigo/blue/white palette, rounded input fields.
```

**6. Leave History**
```
Design a mobile leave request history screen for an HRIS app. Top: tabs or filter chips (All, Pending, Approved, Rejected). Below: scrollable list of leave request cards, each showing leave type icon, date range, number of days, and a status badge (Pending HR=yellow, Approved=green, Rejected=red). Tapping a card would show detail (just design the list view). Bottom navigation present. Style: clean modern HR dashboard, indigo/blue/white, mobile-first.
```

**7. HR Approval Queue**
```
Design a mobile HR approval queue screen for an HRIS app (used by Admin HR role). Top: tabs to switch between "Attendance Approval" and "Leave Approval", with a count badge showing number of pending items. Below: scrollable list of pending request cards, each showing employee photo/avatar, name, department, request summary (e.g. "Check-in outside radius - 350m" or "Annual Leave - 3 days"), submitted time, and two action buttons "Approve" (green) and "Reject" (red, outline style). Style: clean modern HR dashboard, professional corporate feel, indigo/blue/white palette, mobile-first 390px width.
```

**8. Payroll List**
```
Design a mobile payroll periods list screen for an HRIS app (used by Finance role). Top: header "Payroll" with a "+ New Period" button. Below: list of payroll period cards, each showing period name (e.g. "Payroll June 2026"), date range, total employees count, and a status badge (Draft=gray, Calculated=blue, HR Review=yellow, Finance Approval=orange, Locked=purple, Paid=green). Tapping opens detail (design list view only). Style: clean modern HR dashboard, professional finance feel, indigo/blue/white, mobile-first.
```

**9. Payslip Detail**
```
Design a mobile payslip detail screen for an HRIS app. Header showing employee name, position, and payroll period (e.g. "June 2026"). Below: a clean breakdown table/list showing Basic Salary, Allowance, Bonus, Overtime, then a divider, then Deductions, Late Deduction, Attendance Deduction, then a highlighted total row "Net Salary" in bold indigo. Payment status badge (Paid=green / Unpaid=gray) near the top. A "Download PDF" button (outline style) at the bottom. Style: clean modern, professional payslip document feel within a mobile app, indigo/blue/white palette, mobile-first 390px width.
```

**10. Employee Profile**
```
Design a mobile employee profile screen for an HRIS app. Top: large circular profile photo, employee name, position and department subtitle. Below: a list of info rows with icons (Email, Phone, NIK, Join Date, Address, Bank Account masked e.g. "**** 4521"). An "Edit Profile" button. A "Logout" button at the very bottom (red text, no fill). Bottom navigation present. Style: clean modern HR dashboard, indigo/blue/white palette, mobile-first.
```

**11. Admin HR Dashboard**
```
Design a mobile dashboard home screen for an HR Admin role in an HRIS app. Top: greeting with HR admin name and notification bell. Below: a grid of summary cards (2 columns) showing: Total Employees, Pending Attendance Approvals, Pending Leave Requests, Active Payroll Period. Below that: a quick-access section with buttons "Review Attendance" and "Review Leave Requests" highlighted in indigo. Below: a simple bar chart card showing attendance trend for the week. Bottom navigation bar with icons: Home, Employees, Approvals, Reports, Profile. Style: clean modern HR dashboard, professional corporate feel, indigo/blue/white palette, mobile-first 390px width.
```

**12. Finance Payroll Dashboard**
```
Design a mobile dashboard home screen for a Finance role in an HRIS app. Top: greeting with Finance staff name and notification bell. Below: summary cards showing Current Payroll Period status, Total Employees in Payroll, Total Net Salary Amount, and Payment Status overview (Paid vs Unpaid count). Below: a quick action button "Process Current Payroll" in indigo, full width. Below: a list/card of recent payroll periods with status badges. Bottom navigation bar with icons: Home, Payroll, Reports, Profile. Style: clean modern HR/finance dashboard, professional corporate feel, indigo/blue/white palette, mobile-first 390px width.
```
