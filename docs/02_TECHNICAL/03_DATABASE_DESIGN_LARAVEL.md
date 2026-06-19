# 03. Database Design — Laravel + MySQL

### C.1 Daftar Tabel

1. `users` — akun login & role
2. `employees` — data detail karyawan (1:1 dengan users)
3. `departments` — daftar departemen
4. `positions` — daftar jabatan
5. `attendance_records` — data presensi harian
6. `leave_types` — jenis cuti/izin (referensi, agar fleksibel dikonfigurasi)
7. `leave_requests` — pengajuan cuti/izin
8. `leave_balances` — saldo cuti per karyawan per tahun
9. `payroll_periods` — periode payroll
10. `payroll_records` — detail komponen gaji per karyawan per periode
11. `payslips` — snapshot payslip final
12. `notifications` — notifikasi in-app
13. `audit_logs` — log aktivitas sistem
14. `office_locations` — (pendukung) titik koordinat kantor & radius, agar tidak hardcode

### C.2 Field Penting Setiap Tabel

**users**
- id (PK)
- name
- email (unique)
- password (hashed)
- role (enum: employee, admin_hr, finance, super_admin)
- is_active (boolean)
- last_login_at
- timestamps

**employees**
- id (PK)
- user_id (FK → users.id, unique)
- nik (unique)
- department_id (FK → departments.id)
- position_id (FK → positions.id)
- join_date
- employment_status (enum: active, probation, resigned, terminated)
- phone_number
- address (text)
- photo_path
- bank_account_number
- bank_name
- timestamps

**departments**
- id (PK)
- name
- description
- timestamps

**positions**
- id (PK)
- name
- department_id (FK, nullable jika jabatan lintas departemen)
- timestamps

**office_locations**
- id (PK)
- name
- latitude
- longitude
- radius_meters (default 100)
- is_active
- timestamps

**attendance_records**
- id (PK)
- employee_id (FK → employees.id)
- attendance_date (date)
- check_in_time (datetime, nullable)
- check_in_lat, check_in_lng
- check_in_photo_path
- check_out_time (datetime, nullable)
- check_out_lat, check_out_lng
- check_out_photo_path
- status (enum: APPROVED, PENDING_REVIEW, REJECTED)
- out_of_radius_reason (text, nullable)
- approved_by (FK → users.id, nullable)
- approved_at (nullable)
- approval_note (text, nullable)
- timestamps

**leave_types**
- id (PK)
- name (mis: Cuti Tahunan, Sakit, Izin Pribadi, Cuti Khusus)
- deducts_balance (boolean)
- timestamps

**leave_requests**
- id (PK)
- employee_id (FK → employees.id)
- leave_type_id (FK → leave_types.id)
- start_date
- end_date
- total_days (computed/stored)
- reason (text)
- attachment_path (nullable)
- status (enum: PENDING_HR, APPROVED, REJECTED)
- approved_by (FK → users.id, nullable)
- approved_at (nullable)
- approval_note (text, nullable)
- timestamps

**leave_balances**
- id (PK)
- employee_id (FK → employees.id)
- leave_type_id (FK → leave_types.id)
- year (int)
- total_quota (decimal)
- used (decimal)
- remaining (decimal, bisa computed: total_quota - used)
- timestamps
- unique index: (employee_id, leave_type_id, year)

**payroll_periods**
- id (PK)
- name (mis: "Payroll Juni 2026")
- start_date
- end_date
- status (enum: DRAFT, CALCULATED, HR_REVIEW, FINANCE_APPROVAL, LOCKED, PAID)
- created_by (FK → users.id)
- timestamps

**payroll_records**
- id (PK)
- payroll_period_id (FK → payroll_periods.id)
- employee_id (FK → employees.id)
- basic_salary (decimal)
- allowance (decimal, default 0)
- bonus (decimal, default 0)
- overtime (decimal, default 0)
- deduction (decimal, default 0)
- late_deduction (decimal, default 0)
- attendance_deduction (decimal, default 0)
- tax_bpjs (decimal, default 0, nullable jika belum dipakai)
- net_salary (decimal, computed saat calculate)
- timestamps
- unique index: (payroll_period_id, employee_id)

**payslips**
- id (PK)
- payroll_record_id (FK → payroll_records.id)
- employee_id (FK → employees.id)
- payroll_period_id (FK → payroll_periods.id)
- snapshot_data (json — salinan komponen gaji final, agar tidak berubah meski payroll_records di-update di masa depan)
- payment_status (enum: UNPAID, PAID)
- paid_at (nullable)
- timestamps

**notifications**
- id (PK)
- user_id (FK → users.id)
- title
- message
- type (enum: attendance, leave, payroll, general)
- reference_id (nullable, id record terkait)
- reference_type (nullable, nama model terkait)
- is_read (boolean, default false)
- timestamps

**audit_logs**
- id (PK)
- user_id (FK → users.id, nullable jika sistem)
- action (string, mis: "login", "submit_attendance", "approve_leave")
- module (string, mis: "attendance", "leave", "payroll", "employee")
- description (text)
- changes (json, nullable — berisi before/after)
- ip_address (nullable)
- created_at (tanpa updated_at, karena append-only)

### C.3 Relasi Antar Tabel

- `users` 1—1 `employees` (satu user punya satu data employee, kecuali role HR/Finance/Super Admin yang mungkin tidak punya data employee jika mereka bukan karyawan operasional — opsional)
- `departments` 1—N `employees`
- `positions` 1—N `employees`
- `departments` 1—N `positions` (opsional, tergantung kebutuhan)
- `employees` 1—N `attendance_records`
- `employees` 1—N `leave_requests`
- `leave_types` 1—N `leave_requests`
- `employees` 1—N `leave_balances`
- `leave_types` 1—N `leave_balances`
- `payroll_periods` 1—N `payroll_records`
- `employees` 1—N `payroll_records`
- `payroll_records` 1—1 `payslips`
- `users` 1—N `notifications`
- `users` 1—N `audit_logs`
- `office_locations` digunakan sebagai referensi saat menghitung radius presensi (tidak perlu FK langsung ke attendance_records, cukup dipakai saat proses hitung jarak)

### C.4 Status Enum yang Digunakan

| Tabel | Field | Nilai Enum |
|---|---|---|
| users | role | employee, admin_hr, finance, super_admin |
| employees | employment_status | active, probation, resigned, terminated |
| attendance_records | status | APPROVED, PENDING_REVIEW, REJECTED |
| leave_requests | status | PENDING_HR, APPROVED, REJECTED |
| payroll_periods | status | DRAFT, CALCULATED, HR_REVIEW, FINANCE_APPROVAL, LOCKED, PAID |
| payslips | payment_status | UNPAID, PAID |
| notifications | type | attendance, leave, payroll, general |

### C.5 Saran Migration Laravel

Urutan migration disarankan mengikuti dependensi FK (tabel referensi dulu, baru tabel yang punya FK ke sana):

```
1. create_users_table (default Laravel, tambah kolom role, is_active, last_login_at)
2. create_departments_table
3. create_positions_table
4. create_office_locations_table
5. create_employees_table (FK ke users, departments, positions)
6. create_leave_types_table
7. create_attendance_records_table (FK ke employees, users untuk approved_by)
8. create_leave_requests_table (FK ke employees, leave_types, users)
9. create_leave_balances_table (FK ke employees, leave_types)
10. create_payroll_periods_table (FK ke users untuk created_by)
11. create_payroll_records_table (FK ke payroll_periods, employees)
12. create_payslips_table (FK ke payroll_records, employees, payroll_periods)
13. create_notifications_table (FK ke users)
14. create_audit_logs_table (FK ke users, nullable)
```

Contoh potongan migration untuk `attendance_records`:

```php
Schema::create('attendance_records', function (Blueprint $table) {
    $table->id();
    $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
    $table->date('attendance_date');
    $table->dateTime('check_in_time')->nullable();
    $table->decimal('check_in_lat', 10, 7)->nullable();
    $table->decimal('check_in_lng', 10, 7)->nullable();
    $table->string('check_in_photo_path')->nullable();
    $table->dateTime('check_out_time')->nullable();
    $table->decimal('check_out_lat', 10, 7)->nullable();
    $table->decimal('check_out_lng', 10, 7)->nullable();
    $table->string('check_out_photo_path')->nullable();
    $table->enum('status', ['APPROVED', 'PENDING_REVIEW', 'REJECTED'])->default('APPROVED');
    $table->text('out_of_radius_reason')->nullable();
    $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
    $table->dateTime('approved_at')->nullable();
    $table->text('approval_note')->nullable();
    $table->timestamps();

    $table->unique(['employee_id', 'attendance_date']);
});
```

Catatan: gunakan `foreignId()->constrained()` untuk relasi standar, tambahkan index pada kolom yang sering difilter (`status`, `attendance_date`, `payroll_period_id`) agar query laporan tetap cepat saat data sudah besar.
