# 04. Laravel Development Plan

### D.1 Struktur Folder yang Disarankan

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/                  (dari Breeze)
│   │   ├── Employee/
│   │   │   ├── DashboardController.php
│   │   │   ├── AttendanceController.php
│   │   │   ├── LeaveRequestController.php
│   │   │   └── PayslipController.php
│   │   ├── HR/
│   │   │   ├── DashboardController.php
│   │   │   ├── EmployeeManagementController.php
│   │   │   ├── AttendanceApprovalController.php
│   │   │   ├── LeaveApprovalController.php
│   │   │   └── ReportController.php
│   │   ├── Finance/
│   │   │   ├── DashboardController.php
│   │   │   └── PayrollController.php
│   │   ├── SuperAdmin/
│   │   │   ├── UserManagementController.php
│   │   │   ├── DepartmentController.php
│   │   │   ├── PositionController.php
│   │   │   ├── OfficeLocationController.php
│   │   │   └── AuditLogController.php
│   │   └── NotificationController.php
│   ├── Middleware/
│   │   ├── RoleMiddleware.php
│   │   └── CheckActiveUser.php
│   └── Requests/
│       ├── AttendanceCheckInRequest.php
│       ├── LeaveRequestStoreRequest.php
│       ├── EmployeeStoreRequest.php
│       └── PayrollRecordRequest.php
├── Models/
│   ├── User.php
│   ├── Employee.php
│   ├── Department.php
│   ├── Position.php
│   ├── OfficeLocation.php
│   ├── AttendanceRecord.php
│   ├── LeaveType.php
│   ├── LeaveRequest.php
│   ├── LeaveBalance.php
│   ├── PayrollPeriod.php
│   ├── PayrollRecord.php
│   ├── Payslip.php
│   ├── Notification.php
│   └── AuditLog.php
├── Services/
│   ├── AttendanceService.php       (hitung radius, validasi presensi)
│   ├── LeaveService.php            (validasi saldo, alur approval)
│   ├── PayrollService.php          (perhitungan gaji bersih)
│   ├── NotificationService.php
│   └── AuditLogService.php
├── Policies/
│   ├── AttendanceRecordPolicy.php
│   ├── LeaveRequestPolicy.php
│   ├── PayrollRecordPolicy.php
│   └── EmployeePolicy.php
resources/
├── views/
│   ├── layouts/
│   │   ├── app.blade.php
│   │   └── navigation-bottom.blade.php
│   ├── employee/
│   ├── hr/
│   ├── finance/
│   ├── super-admin/
│   └── components/
routes/
├── web.php
├── employee.php
├── hr.php
├── finance.php
└── super-admin.php
```

### D.2 Model yang Dibutuhkan

`User`, `Employee`, `Department`, `Position`, `OfficeLocation`, `AttendanceRecord`, `LeaveType`, `LeaveRequest`, `LeaveBalance`, `PayrollPeriod`, `PayrollRecord`, `Payslip`, `Notification`, `AuditLog`.

Relasi penting di model (contoh):

```php
// Employee.php
public function user() { return $this->belongsTo(User::class); }
public function department() { return $this->belongsTo(Department::class); }
public function position() { return $this->belongsTo(Position::class); }
public function attendanceRecords() { return $this->hasMany(AttendanceRecord::class); }
public function leaveRequests() { return $this->hasMany(LeaveRequest::class); }
public function leaveBalances() { return $this->hasMany(LeaveBalance::class); }
```

### D.3 Controller yang Dibutuhkan

- **AttendanceController** — form check-in/out, submit, riwayat presensi karyawan.
- **AttendanceApprovalController** (HR) — list pending, approve/reject.
- **LeaveRequestController** — form pengajuan, riwayat cuti karyawan.
- **LeaveApprovalController** (HR) — list pending, approve/reject, update saldo.
- **EmployeeManagementController** (HR/Super Admin) — CRUD data karyawan.
- **PayrollController** (Finance) — CRUD periode, input komponen, proses hitung, ubah status.
- **PayslipController** — tampil payslip karyawan.
- **ReportController** (HR) — rekap presensi/cuti/payroll dengan filter.
- **NotificationController** — list notifikasi, mark as read.
- **AuditLogController** (Super Admin/HR) — list & filter audit log.
- **DashboardController** per role (atau satu controller dengan method berbeda per role).

### D.4 Middleware Role Access

```php
// app/Http/Middleware/RoleMiddleware.php
public function handle($request, Closure $next, ...$roles)
{
    if (!$request->user() || !in_array($request->user()->role, $roles)) {
        abort(403, 'Akses ditolak.');
    }
    return $next($request);
}
```

Daftarkan alias di `bootstrap/app.php` (Laravel 11+) atau `Kernel.php` (Laravel ≤10):

```php
'role' => \App\Http\Middleware\RoleMiddleware::class,
```

Pemakaian di route:

```php
Route::middleware(['auth', 'role:admin_hr,super_admin'])->group(function () {
    // route khusus HR & Super Admin
});
```

### D.5 Route Group Berdasarkan Role

```php
// routes/web.php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/notifications', [NotificationController::class, 'index']);

    // Employee — semua role yang punya data employee bisa akses
    Route::middleware('role:employee,admin_hr,finance,super_admin')->group(function () {
        Route::get('/attendance', [AttendanceController::class, 'index']);
        Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn']);
        Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut']);
        Route::resource('/leave-requests', LeaveRequestController::class)->only(['index','create','store','show']);
        Route::get('/payslips', [PayslipController::class, 'index']);
    });

    // HR
    Route::middleware('role:admin_hr,super_admin')->prefix('hr')->group(function () {
        Route::resource('/employees', EmployeeManagementController::class);
        Route::get('/attendance-approval', [AttendanceApprovalController::class, 'index']);
        Route::post('/attendance-approval/{attendance}', [AttendanceApprovalController::class, 'process']);
        Route::get('/leave-approval', [LeaveApprovalController::class, 'index']);
        Route::post('/leave-approval/{leave}', [LeaveApprovalController::class, 'process']);
        Route::get('/reports', [ReportController::class, 'index']);
    });

    // Finance
    Route::middleware('role:finance,super_admin')->prefix('finance')->group(function () {
        Route::resource('/payroll-periods', PayrollController::class);
        Route::post('/payroll-periods/{period}/calculate', [PayrollController::class, 'calculate']);
        Route::post('/payroll-periods/{period}/status', [PayrollController::class, 'updateStatus']);
    });

    // Super Admin
    Route::middleware('role:super_admin')->prefix('admin')->group(function () {
        Route::resource('/users', UserManagementController::class);
        Route::resource('/departments', DepartmentController::class);
        Route::resource('/positions', PositionController::class);
        Route::resource('/office-locations', OfficeLocationController::class);
        Route::get('/audit-logs', [AuditLogController::class, 'index']);
    });
});
```

### D.6 Service Class yang Diperlukan

- **AttendanceService**: hitung jarak (haversine formula) antara koordinat user dan `office_locations`, tentukan status APPROVED/PENDING_REVIEW, simpan record.
- **LeaveService**: cek tumpang tindih tanggal, cek saldo cuti, proses approve (kurangi saldo) & reject.
- **PayrollService**: hitung `net_salary` dari seluruh komponen, ambil data potongan otomatis dari `attendance_records` (keterlambatan/absen), generate `payslips` saat status LOCKED.
- **NotificationService**: helper `notify($user, $title, $message, $type, $reference)` dipanggil dari service lain.
- **AuditLogService**: helper `log($user, $action, $module, $description, $changes = null)` dipanggil di titik-titik penting (login, submit, approve, update, dsb).

Contoh sederhana perhitungan radius:

```php
// app/Services/AttendanceService.php
public function isWithinRadius(float $lat, float $lng, OfficeLocation $office): bool
{
    $earthRadius = 6371000; // meter
    $dLat = deg2rad($lat - $office->latitude);
    $dLng = deg2rad($lng - $office->longitude);
    $a = sin($dLat/2) ** 2 + cos(deg2rad($office->latitude)) * cos(deg2rad($lat)) * sin($dLng/2) ** 2;
    $distance = $earthRadius * 2 * atan2(sqrt($a), sqrt(1-$a));
    return $distance <= $office->radius_meters;
}
```

### D.7 Validasi Form (Form Request)

- `AttendanceCheckInRequest`: `latitude` required numeric, `longitude` required numeric, `photo` required image max:5120, `reason` required_if luar radius.
- `LeaveRequestStoreRequest`: `leave_type_id` required exists, `start_date` required date, `end_date` required date after_or_equal:start_date, `reason` required min:10, `attachment` nullable file mimes:pdf,jpg,png max:5120.
- `EmployeeStoreRequest`: `name` required, `email` required email unique, `nik` required unique, `department_id` required exists, `position_id` required exists, `join_date` required date, `phone_number` required regex sesuai format.
- `PayrollRecordRequest`: semua komponen gaji `numeric min:0`.

### D.8 File Upload untuk Selfie dan Lampiran

- Simpan di disk privat: `storage/app/private/attendance/` dan `storage/app/private/leave-attachments/`.
- Gunakan `Storage::disk('local')->putFile(...)` agar tidak bisa diakses publik langsung.
- Buat route terproteksi untuk menampilkan file: `GET /files/attendance/{id}` yang mengecek policy sebelum `Storage::response()`.
- Validasi MIME type dan ukuran file di Form Request (server-side), jangan andalkan validasi browser saja.
- Pertimbangkan kompresi gambar selfie (mis. dengan `Intervention/Image`) sebelum disimpan agar storage lebih efisien.

### D.9 Rekomendasi Package Laravel

| Kebutuhan | Package |
|---|---|
| Auth scaffolding | `laravel/breeze` |
| Styling | `tailwindcss` (via Breeze) |
| Role/permission lanjutan (opsional) | `spatie/laravel-permission` |
| Image processing (resize/compress selfie) | `intervention/image` |
| PDF generation (payslip — future) | `barryvdh/laravel-dompdf` |
| Export Excel/CSV (laporan — future) | `maatwebsite/excel` |
| Aktivitas/audit log (alternatif manual) | `spatie/laravel-activitylog` |
| Date handling | `nesbot/carbon` (sudah built-in Laravel) |
| API token untuk pengembangan mobile native ke depan | `laravel/sanctum` |
| PWA support | `laravel-pwa` atau setup manual manifest + service worker |

**Catatan implementasi**: untuk MVP, audit log dan role access bisa dibuat manual (sesuai Service class & Middleware di atas) agar tim lebih memahami alurnya, baru opsional diganti package seperti `spatie/laravel-permission` saat kebutuhan role makin kompleks (multi-permission granular).
