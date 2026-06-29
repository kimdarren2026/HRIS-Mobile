# 07. Development Roadmap — Step by Step

Dokumen ini berisi roadmap awal dan catatan status terkini. Roadmap awal di bawah dipecah menjadi **10 phase berurutan** dan tetap disimpan sebagai riwayat perencanaan. Status terbaru sudah melewati Phase 21 dan perlu dibaca bersama addendum berikut.

## Addendum Status Terkini

Production URL: https://hrismobile.my.id

Production saat ini sudah memiliki HTTPS, secure cookie configuration, role management UI, production master data, attendance out-of-radius pending review, dan in-app notifications.

| Phase | Scope | Status | Deployment note |
| --- | --- | --- | --- |
| 1-21 | Baseline HRIS mobile, attendance, leave, employee data, expenses, audit, notifications, and historical internal payroll/payslip work | PASS | Historical baseline |
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

Payroll plan terkini:

- HRIS menjadi source of truth untuk employee data dan attendance.
- Salary calculation dan payment processing akan ditangani oleh external payroll system.
- HRIS nantinya menerima payroll dan payslip results dari external payroll system.
- Phase 28 internal payroll payment workflow sudah direvert dan tidak boleh dianggap sebagai final internal HRIS payroll.

Post-Phase 21 timeline:

- Phase 22 prepared staging deployment work after the Phase 21 baseline.
- Phase 22B updated dependency integration for PHP 8.3 staging compatibility.
- Phase 23 refactored Blade UI details and reused flash messages.
- Phase 24 refactored bottom navigation.
- Phase 25 extracted reusable validation error presentation.
- Phase 26 integrated employee management with database-backed production data.
- Phase 27 completed security hardening and was merged and deployed.
- Phase 28 introduced an internal payroll payment workflow, then was reverted.
- Phase 29 delivered role management UI and production master data, then was merged and deployed.
- Phase 30 delivered attendance production hardening and UX work, then was merged.

Next planned phases:

| Phase | Planned scope | Status |
| --- | --- | --- |
| 31 | Documentation Status Update | Current documentation-only update |
| 32 | Mobile UI Consistency Audit | Planned |
| 33 | Backup/Production DevOps | Planned |
| 34 | Payroll External Integration Contract | Planned; waiting for external payroll project details |
| 35 | SIAKAT SSO Integration | Planned |

## Roadmap Awal Phase 1-10

Setiap phase punya: tujuan, file/tabel yang dibuat, fitur yang harus selesai, testing wajib, output akhir, dan **prompt lanjutan** yang bisa langsung Anda copy ke Claude/Codex untuk mengerjakan phase tersebut.

> **Aturan utama: JANGAN lanjut ke phase berikutnya sebelum phase saat ini berstatus PASS** (lihat kolom "Output Akhir Phase" sebagai kriteria PASS, dan silangkan dengan `06_ACCEPTANCE_CRITERIA.md` + `09_TESTING_CHECKLIST.md`).

---

## Phase 1 — Setup Laravel + Auth + Role

**Tujuan**: Menyiapkan fondasi project: Laravel terinstall, Breeze + Tailwind aktif, koneksi database jalan, sistem login & role berfungsi.

**File/Tabel yang dibuat**:
- Project Laravel baru + `laravel/breeze` + Tailwind
- Migration `users` (tambah kolom `role`, `is_active`, `last_login_at`)
- `app/Http/Middleware/RoleMiddleware.php`
- `app/Services/AuditLogService.php` (struktur dasar, dipakai mulai phase ini)
- Migration `audit_logs`

**Fitur yang harus selesai**:
- Register tidak dipakai publik (akun dibuat manual/seeder, sesuai sifat HRIS internal)
- Login & logout berfungsi
- Redirect otomatis ke dashboard sesuai role setelah login
- Middleware role memblokir akses ke route yang bukan haknya (403)
- Login tercatat di `audit_logs`

**Testing wajib**: lihat `09_TESTING_CHECKLIST.md` bagian *Testing Login* dan *Testing Role Access*.

**Output akhir phase (kriteria PASS)**:
- [ ] 4 role (employee, admin_hr, finance, super_admin) bisa login dan diarahkan ke dashboard kosong masing-masing
- [ ] User salah role yang akses URL role lain mendapat 403
- [ ] Entri login muncul di tabel `audit_logs`

**Prompt lanjutan untuk Claude/Codex**:
```
Kerjakan Phase 1 dari roadmap HRIS Mobile App: Setup Laravel + Auth + Role.

Yang harus dikerjakan:
1. Setup project Laravel baru dengan Laravel Breeze (Blade) + Tailwind CSS
2. Migration users: tambah kolom role (enum: employee, admin_hr, finance, super_admin), is_active (boolean), last_login_at (timestamp nullable)
3. Buat RoleMiddleware untuk membatasi akses route berdasarkan role
4. Buat migration audit_logs sesuai skema di 03_DATABASE_DESIGN_LARAVEL.md
5. Buat AuditLogService dengan method log($user, $action, $module, $description, $changes = null)
6. Setelah login berhasil, redirect ke dashboard sesuai role dan catat audit log "login"
7. Buat seeder untuk 4 akun contoh (satu per role) untuk keperluan testing

File yang boleh diubah: seluruh file baru di app/Http/Middleware, app/Services, database/migrations, database/seeders, routes/web.php, app/Http/Controllers/Auth (jika perlu override redirect Breeze)
File yang tidak boleh diubah: tidak ada file existing lain di luar scaffolding Breeze default

Output yang saya harapkan: kode lengkap untuk setiap file di atas, beserta instruksi command artisan yang perlu dijalankan.

Testing wajib sebelum lanjut: pastikan 4 akun role bisa login dan diarahkan ke dashboard sesuai role, akses lintas-role mendapat 403, dan baris login tercatat di audit_logs.

Jangan kerjakan phase lain di luar Phase 1 ini.
```

---

## Phase 2 — Employee Management

**Tujuan**: Membangun data master karyawan beserta departemen dan jabatan, plus CRUD untuk HR/Super Admin.

**File/Tabel yang dibuat**:
- Migration `departments`, `positions`, `employees`
- Model `Department`, `Position`, `Employee`
- `EmployeeManagementController` (HR/Super Admin)
- `EmployeeStoreRequest` (Form Request validation)
- `EmployeePolicy`

**Fitur yang harus selesai**:
- CRUD departemen & jabatan (Super Admin)
- CRUD data karyawan dengan validasi email unik, NIK unik
- Soft delete untuk karyawan (bukan hard delete)
- List karyawan bisa difilter/dicari (nama, departemen, status kerja)
- Update data karyawan tercatat di audit log (before/after)

**Testing wajib**: lihat `09_TESTING_CHECKLIST.md` bagian *Testing Employee Management*.

**Output akhir phase (kriteria PASS)**:
- [ ] HR/Super Admin dapat tambah, edit, nonaktifkan karyawan tanpa error validasi yang tidak jelas
- [ ] Email dan NIK duplikat ditolak dengan pesan error yang jelas
- [ ] Karyawan nonaktif tidak bisa login lagi tapi data historisnya tetap ada

**Prompt lanjutan untuk Claude/Codex**:
```
Kerjakan Phase 2 dari roadmap HRIS Mobile App: Employee Management.
Prasyarat: Phase 1 (Setup Laravel + Auth + Role) sudah PASS.

Yang harus dikerjakan:
1. Migration & model: departments, positions, employees sesuai skema di 03_DATABASE_DESIGN_LARAVEL.md
2. EmployeeManagementController dengan resource CRUD, hanya bisa diakses role admin_hr dan super_admin
3. EmployeeStoreRequest: validasi email unik, NIK unik, field wajib sesuai 01_PRD_HRIS_Mobile_App.md bagian Validasi Penting
4. EmployeePolicy: pastikan employee biasa tidak bisa CRUD data karyawan lain
5. Implementasikan soft delete (SoftDeletes trait) pada model Employee
6. Setiap create/update/delete karyawan panggil AuditLogService dengan detail before/after
7. View Blade: list karyawan dengan search & filter (nama, departemen, status kerja), form tambah/edit

File yang boleh diubah: app/Models, app/Http/Controllers/HR, app/Http/Requests, app/Policies, database/migrations, resources/views/hr
File yang tidak boleh diubah: file-file dari Phase 1 (auth, role middleware) kecuali untuk registrasi route baru

Output yang saya harapkan: kode lengkap tiap file, beserta route yang ditambahkan ke routes/web.php (prefix hr, middleware role:admin_hr,super_admin).

Testing wajib sebelum lanjut: CRUD karyawan berjalan, validasi unik email/NIK berfungsi, soft delete tidak menghapus data historis, audit log tercatat saat update data karyawan.

Jangan kerjakan phase lain di luar Phase 2 ini.
```

---

## Phase 3 — Attendance (GPS + Selfie)

**Tujuan**: Fitur presensi inti — check-in/out dengan validasi GPS dan selfie, logika radius kantor, dan approval HR untuk presensi di luar radius.

**File/Tabel yang dibuat**:
- Migration `office_locations`, `attendance_records`
- Model `OfficeLocation`, `AttendanceRecord`
- `AttendanceController`, `AttendanceApprovalController`
- `AttendanceService` (logika haversine/radius)
- `AttendanceCheckInRequest`

**Fitur yang harus selesai**:
- Check-in/out wajib GPS aktif + foto selfie, ditolak jika salah satu kosong
- Logika radius 100 meter: dalam radius → APPROVED otomatis, luar radius → wajib alasan → PENDING_REVIEW
- Satu check-in & satu check-out per hari (unique constraint)
- Halaman approval HR untuk presensi pending
- File selfie disimpan di storage privat, diakses lewat route terproteksi (lihat `08_SECURITY_AND_VALIDATION_RULES.md`)

**Testing wajib**: lihat `09_TESTING_CHECKLIST.md` bagian *Testing Presensi Dalam Radius*, *Testing Presensi Luar Radius*, *Testing Kamera/GPS Ditolak*.

**Output akhir phase (kriteria PASS)**:
- [ ] Check-in dalam radius langsung APPROVED tanpa approval manual
- [ ] Check-in luar radius wajib alasan dan masuk status PENDING_REVIEW
- [ ] HR bisa approve/reject presensi pending dengan catatan
- [ ] Tidak bisa check-in dua kali atau check-out tanpa check-in di hari yang sama
- [ ] Foto selfie tidak bisa diakses langsung lewat URL publik

**Prompt lanjutan untuk Claude/Codex**:
```
Kerjakan Phase 3 dari roadmap HRIS Mobile App: Attendance (GPS + Selfie).
Prasyarat: Phase 1 dan Phase 2 sudah PASS.

Yang harus dikerjakan:
1. Migration & model: office_locations, attendance_records sesuai 03_DATABASE_DESIGN_LARAVEL.md
2. AttendanceService dengan method isWithinRadius() menggunakan haversine formula (lihat contoh di 04_LARAVEL_DEVELOPMENT_PLAN.md)
3. AttendanceCheckInRequest: validasi latitude/longitude required numeric, photo required image max 5MB, reason required_if luar radius (min 10 karakter)
4. AttendanceController: method checkIn() dan checkOut(), terapkan unique constraint employee_id + attendance_date
5. AttendanceApprovalController: list presensi PENDING_REVIEW, approve/reject dengan catatan wajib saat reject
6. Simpan file selfie ke storage privat (storage/app/private/attendance), buat route terproteksi untuk menampilkan foto dengan pengecekan policy
7. Setiap submit, approve, reject presensi panggil AuditLogService
8. Terapkan seluruh aturan dari 08_SECURITY_AND_VALIDATION_RULES.md bagian "Validasi GPS" dan "Anti Manipulasi Presensi"

File yang boleh diubah: app/Models, app/Services, app/Http/Controllers (Employee & HR), app/Http/Requests, database/migrations, resources/views (attendance & approval)
File yang tidak boleh diubah: file Phase 1 dan Phase 2 kecuali penambahan route baru

Output yang saya harapkan: kode lengkap tiap file, termasuk contoh JavaScript minimal untuk mengambil koordinat GPS (navigator.geolocation) dan capture kamera di Blade view.

Testing wajib sebelum lanjut: seluruh skenario di 09_TESTING_CHECKLIST.md bagian Presensi (dalam radius, luar radius, GPS/kamera ditolak) harus lulus manual test.

Jangan kerjakan phase lain di luar Phase 3 ini.
```

---

## Phase 4 — Leave / Cuti

**Tujuan**: Fitur pengajuan dan approval cuti/izin, termasuk manajemen saldo cuti.

**File/Tabel yang dibuat**:
- Migration `leave_types`, `leave_requests`, `leave_balances`
- Model `LeaveType`, `LeaveRequest`, `LeaveBalance`
- `LeaveRequestController`, `LeaveApprovalController`
- `LeaveService` (validasi saldo & tumpang tindih tanggal)
- `LeaveRequestStoreRequest`

**Fitur yang harus selesai**:
- Form pengajuan cuti dengan jenis, tanggal, alasan, lampiran opsional
- Validasi saldo cuti cukup & tidak tumpang tindih tanggal
- Saldo cuti berkurang hanya setelah approved
- Halaman approval HR untuk cuti pending
- Riwayat pengajuan untuk karyawan

**Testing wajib**: lihat `09_TESTING_CHECKLIST.md` bagian *Testing Cuti* dan *Testing Approval HR*.

**Output akhir phase (kriteria PASS)**:
- [ ] Pengajuan dengan saldo tidak cukup ditolak sistem
- [ ] Pengajuan tanggal tumpang tindih dengan pengajuan aktif lain ditolak
- [ ] Saldo cuti hanya berkurang setelah HR approve, tidak berkurang saat reject
- [ ] Riwayat cuti karyawan menampilkan status yang akurat

**Prompt lanjutan untuk Claude/Codex**:
```
Kerjakan Phase 4 dari roadmap HRIS Mobile App: Leave / Cuti.
Prasyarat: Phase 1, 2, 3 sudah PASS.

Yang harus dikerjakan:
1. Migration & model: leave_types, leave_requests, leave_balances sesuai 03_DATABASE_DESIGN_LARAVEL.md
2. Seeder leave_types (Cuti Tahunan, Sakit, Izin Pribadi, Cuti Khusus) dengan flag deducts_balance
3. LeaveService: method cekTumpangTindih(), cekSaldoCukup(), processApprove() (kurangi saldo), processReject() (tidak kurangi saldo)
4. LeaveRequestStoreRequest: validasi sesuai 01_PRD_HRIS_Mobile_App.md bagian Validasi Penting
5. LeaveRequestController: form pengajuan + riwayat untuk karyawan
6. LeaveApprovalController: antrian pending untuk HR, approve/reject dengan catatan
7. Setiap submit, approve, reject cuti panggil AuditLogService
8. Buat halaman riwayat cuti dengan filter status (Pending/Approved/Rejected)

File yang boleh diubah: app/Models, app/Services, app/Http/Controllers, app/Http/Requests, database/migrations, database/seeders, resources/views (leave)
File yang tidak boleh diubah: file dari Phase 1-3 kecuali penambahan route baru

Output yang saya harapkan: kode lengkap tiap file.

Testing wajib sebelum lanjut: seluruh skenario di 09_TESTING_CHECKLIST.md bagian Cuti dan Approval HR harus lulus.

Jangan kerjakan phase lain di luar Phase 4 ini.
```

---

## Phase 5 — Payroll

> Status note: bagian ini adalah roadmap historis dari arah payroll internal awal. Setelah Phase 28 direvert, final payroll calculation/payment tidak lagi direncanakan sebagai workflow internal HRIS. Lihat Addendum Status Terkini untuk arah external payroll integration.

**Tujuan**: Membangun fitur perhitungan payroll per periode dengan alur status berjenjang.

**File/Tabel yang dibuat**:
- Migration `payroll_periods`, `payroll_records`
- Model `PayrollPeriod`, `PayrollRecord`
- `PayrollController`
- `PayrollService` (perhitungan net_salary, ambil potongan otomatis dari attendance)
- `PayrollRecordRequest`

**Fitur yang harus selesai**:
- Finance/HR buat periode payroll baru
- Input komponen gaji per karyawan
- Perhitungan otomatis net_salary
- Alur status DRAFT → CALCULATED → HR_REVIEW → FINANCE_APPROVAL → LOCKED → PAID tanpa lompat tahap
- Data tidak bisa diedit setelah LOCKED

**Testing wajib**: lihat `09_TESTING_CHECKLIST.md` bagian *Testing Payroll*.

**Output akhir phase (kriteria PASS)**:
- [ ] Perhitungan net_salary akurat sesuai rumus di PRD
- [ ] Status payroll tidak bisa dilompati (tidak bisa dari DRAFT langsung ke LOCKED)
- [ ] Setelah LOCKED, form edit komponen gaji nonaktif/diblokir di backend (bukan hanya disembunyikan di frontend)

**Prompt lanjutan untuk Claude/Codex**:
```
Kerjakan Phase 5 dari roadmap HRIS Mobile App: Payroll.
Prasyarat: Phase 1-4 sudah PASS.

Yang harus dikerjakan:
1. Migration & model: payroll_periods, payroll_records sesuai 03_DATABASE_DESIGN_LARAVEL.md
2. PayrollService: method calculate() yang menghitung net_salary = basic_salary + allowance + bonus + overtime - deduction - late_deduction - attendance_deduction - tax_bpjs, dan ambil late_deduction/attendance_deduction otomatis dari data attendance_records periode terkait
3. PayrollController: CRUD periode, input komponen per karyawan, transisi status (DRAFT→CALCULATED→HR_REVIEW→FINANCE_APPROVAL→LOCKED→PAID) dengan validasi server-side bahwa transisi tidak boleh melompat tahap
4. PayrollRecordRequest: validasi seluruh komponen numeric min:0
5. Backend HARUS menolak request update payroll_records jika payroll_periods.status sudah LOCKED atau PAID (validasi di Controller/Policy, bukan hanya disembunyikan di UI)
6. Setiap perubahan status payroll panggil AuditLogService

File yang boleh diubah: app/Models, app/Services, app/Http/Controllers/Finance, app/Http/Requests, app/Policies, database/migrations, resources/views/finance
File yang tidak boleh diubah: file dari Phase 1-4 kecuali penambahan route baru

Output yang saya harapkan: kode lengkap tiap file.

Testing wajib sebelum lanjut: seluruh skenario di 09_TESTING_CHECKLIST.md bagian Payroll harus lulus, termasuk percobaan edit payroll yang sudah LOCKED (harus ditolak backend).

Jangan kerjakan phase lain di luar Phase 5 ini.
```

---

## Phase 6 — Payslip

**Tujuan**: Generate dan tampilkan payslip digital untuk karyawan berdasarkan payroll yang sudah LOCKED/PAID.

**File/Tabel yang dibuat**:
- Migration `payslips`
- Model `Payslip`
- `PayslipController`

**Fitur yang harus selesai**:
- Payslip otomatis ter-generate (snapshot) saat payroll berstatus LOCKED
- Karyawan hanya bisa melihat payslip miliknya sendiri
- Detail payslip menampilkan komponen gaji lengkap
- Tombol download PDF (placeholder untuk MVP, fungsionalitas nyata masuk future enhancement)

**Testing wajib**: lihat `09_TESTING_CHECKLIST.md` bagian *Testing Payslip*.

**Output akhir phase (kriteria PASS)**:
- [ ] Payslip hanya muncul untuk periode LOCKED/PAID
- [ ] Karyawan A tidak bisa mengakses payslip karyawan B meskipun mengubah ID di URL
- [ ] Data payslip tetap konsisten meski payroll_records di-edit setelahnya (karena pakai snapshot)

**Prompt lanjutan untuk Claude/Codex**:
```
Kerjakan Phase 6 dari roadmap HRIS Mobile App: Payslip.
Prasyarat: Phase 1-5 sudah PASS.

Yang harus dikerjakan:
1. Migration & model: payslips sesuai 03_DATABASE_DESIGN_LARAVEL.md, dengan kolom snapshot_data (json)
2. Saat PayrollService mengubah status payroll_periods menjadi LOCKED, generate payslip otomatis untuk setiap payroll_records di periode tersebut, isi snapshot_data dengan salinan komponen gaji saat itu
3. PayslipController: index (list periode payslip milik karyawan login), show (detail payslip)
4. Buat Policy/otorisasi memastikan karyawan hanya bisa lihat payslip miliknya sendiri (cek employee_id == auth user punya employee_id)
5. Tampilkan tombol "Download PDF" sebagai placeholder (disabled/coming soon), tidak perlu generate PDF asli di phase ini
6. Tampilkan payment_status (UNPAID/PAID) di halaman payslip

File yang boleh diubah: app/Models, app/Http/Controllers/Employee, app/Policies, database/migrations, resources/views (payslip), app/Services/PayrollService.php (tambahkan logic generate payslip)
File yang tidak boleh diubah: file Phase 1-5 selain integrasi generate payslip di PayrollService

Output yang saya harapkan: kode lengkap tiap file.

Testing wajib sebelum lanjut: payslip hanya tampil untuk periode LOCKED/PAID, karyawan tidak bisa akses payslip orang lain (uji manual ganti ID di URL).

Jangan kerjakan phase lain di luar Phase 6 ini.
```

---

## Phase 7 — Report

**Tujuan**: Menyediakan laporan rekap presensi, cuti, dan payroll untuk HR dengan filter.

**File/Tabel yang dibuat**:
- `ReportController`
- View laporan dengan filter tanggal, departemen, karyawan

**Fitur yang harus selesai**:
- Rekap presensi, cuti, payroll dalam bentuk tabel
- Filter berdasarkan rentang tanggal, departemen, dan/atau nama karyawan
- Tombol export CSV/Excel (placeholder, future enhancement)

**Testing wajib**: lihat `09_TESTING_CHECKLIST.md` bagian *Testing Report*.

**Output akhir phase (kriteria PASS)**:
- [ ] Data laporan sesuai dengan data aktual di database (tidak ada selisih)
- [ ] Filter berfungsi dan query tidak lambat untuk data dalam jumlah wajar (ratusan-ribuan baris)

**Prompt lanjutan untuk Claude/Codex**:
```
Kerjakan Phase 7 dari roadmap HRIS Mobile App: Report.
Prasyarat: Phase 1-6 sudah PASS.

Yang harus dikerjakan:
1. ReportController dengan method untuk 3 jenis laporan: rekap presensi, rekap cuti, rekap payroll
2. Setiap laporan mendukung filter: rentang tanggal (start_date, end_date), department_id, employee_id
3. Gunakan eager loading (with()) untuk menghindari N+1 query saat menampilkan relasi employee->department, employee->position
4. Tambahkan index database jika perlu pada kolom yang sering difilter (lihat catatan index di 03_DATABASE_DESIGN_LARAVEL.md)
5. Tampilkan tombol "Export CSV/Excel" sebagai placeholder (disabled/coming soon)
6. Pastikan hanya role admin_hr dan super_admin yang bisa akses laporan

File yang boleh diubah: app/Http/Controllers/HR, resources/views/hr/reports, routes/hr.php (atau bagian terkait di web.php)
File yang tidak boleh diubah: file Phase 1-6 kecuali penambahan route baru

Output yang saya harapkan: kode lengkap tiap file, termasuk contoh query Eloquent untuk masing-masing laporan.

Testing wajib sebelum lanjut: data laporan dicocokkan manual dengan data presensi/cuti/payroll yang ada, filter diuji dengan berbagai kombinasi.

Jangan kerjakan phase lain di luar Phase 7 ini.
```

---

## Phase 8 — Audit Log (Penyempurnaan)

**Tujuan**: Memastikan seluruh aksi penting dari Phase 1-7 benar-benar tercatat lengkap, dan membangun halaman viewer audit log untuk HR/Super Admin.

**File/Tabel yang dibuat**:
- `AuditLogController`
- View list & filter audit log

**Fitur yang harus selesai**:
- Audit log mencakup: login, submit presensi, approve/reject presensi, submit cuti, approve/reject cuti, update data karyawan, proses payroll
- Halaman viewer dengan filter user, modul, rentang tanggal
- Audit log read-only — tidak ada endpoint untuk edit/hapus

**Testing wajib**: lihat `09_TESTING_CHECKLIST.md` bagian *Testing Audit Log*.

**Output akhir phase (kriteria PASS)**:
- [ ] Setiap kategori aksi di atas benar-benar muncul di tabel audit_logs saat diuji manual satu per satu
- [ ] Tidak ada route/method untuk update atau delete audit_logs di seluruh codebase
- [ ] Filter di halaman viewer berfungsi dengan benar

**Prompt lanjutan untuk Claude/Codex**:
```
Kerjakan Phase 8 dari roadmap HRIS Mobile App: Audit Log (Penyempurnaan & Verifikasi).
Prasyarat: Phase 1-7 sudah PASS.

Yang harus dikerjakan:
1. Audit ulang seluruh controller dari Phase 1-7, pastikan AuditLogService::log() benar-benar dipanggil di titik: login, submit presensi, approve/reject presensi, submit cuti, approve/reject cuti, update data karyawan, proses payroll (setiap perubahan status)
2. Jika ada yang terlewat, tambahkan pemanggilan AuditLogService di titik yang sesuai
3. Buat AuditLogController dengan halaman list audit log, filter berdasarkan user, module, dan rentang tanggal
4. Pastikan tidak ada route PUT/PATCH/DELETE untuk model AuditLog di seluruh aplikasi (sesuai aturan append-only di 08_SECURITY_AND_VALIDATION_RULES.md)
5. Tampilkan kolom changes (json) dalam format yang mudah dibaca manusia di halaman detail audit log (bukan raw json mentah)

File yang boleh diubah: app/Http/Controllers/SuperAdmin/AuditLogController.php, resources/views/admin/audit-logs, dan penambahan baris AuditLogService::log() di file controller/service Phase 1-7 yang relevan
File yang tidak boleh diubah: struktur tabel audit_logs (migration), logic bisnis utama di luar penambahan logging

Output yang saya harapkan: daftar titik mana saja yang ditambahkan logging (checklist), kode AuditLogController dan view-nya.

Testing wajib sebelum lanjut: lakukan satu kali masing-masing aksi (login, presensi, cuti, approval, update karyawan, payroll) lalu verifikasi muncul di audit log.

Jangan kerjakan phase lain di luar Phase 8 ini.
```

---

## Phase 9 — UI Polishing + Testing

**Tujuan**: Menyempurnakan tampilan mobile, konsistensi UI, notifikasi in-app, dan melakukan testing menyeluruh sebelum demo.

**File/Tabel yang dibuat**:
- Migration `notifications`
- Model `Notification`, `NotificationService`
- `NotificationController`
- `resources/views/layouts/navigation-bottom.blade.php` (komponen bottom nav konsisten)

**Fitur yang harus selesai**:
- Notifikasi in-app untuk submit, approve/reject, payslip tersedia
- Bottom navigation konsisten di semua halaman employee
- Status badge konsisten (warna sama di semua halaman: hijau=approved, kuning=pending, merah=rejected)
- Loading state & empty state di semua list/table
- Seluruh checklist di `09_TESTING_CHECKLIST.md` dijalankan penuh

**Testing wajib**: seluruh isi `09_TESTING_CHECKLIST.md`, termasuk *Testing Responsive Mobile*.

**Output akhir phase (kriteria PASS)**:
- [ ] Seluruh checklist di `09_TESTING_CHECKLIST.md` tercentang
- [ ] Notifikasi muncul untuk seluruh event yang ditentukan di PRD
- [ ] Tidak ada elemen UI yang terpotong/overflow di lebar layar 360px-414px

**Prompt lanjutan untuk Claude/Codex**:
```
Kerjakan Phase 9 dari roadmap HRIS Mobile App: UI Polishing + Testing.
Prasyarat: Phase 1-8 sudah PASS.

Yang harus dikerjakan:
1. Migration & model: notifications sesuai 03_DATABASE_DESIGN_LARAVEL.md
2. NotificationService dengan method notify($user, $title, $message, $type, $reference), panggil dari AttendanceService, LeaveService, PayrollService di titik approve/reject/payslip tersedia
3. NotificationController: list notifikasi, mark as read, badge jumlah belum dibaca
4. Buat komponen Blade navigation-bottom.blade.php yang dipakai konsisten di semua halaman employee (Home, Attendance, Leave, Payslip, Profile)
5. Audit ulang semua halaman: pastikan status badge pakai warna konsisten (hijau/kuning/merah) sesuai status yang sama
6. Tambahkan loading state dan empty state di semua halaman list/table yang sebelumnya belum ada
7. Review responsive di lebar 360px-414px, perbaiki elemen yang overflow

File yang boleh diubah: app/Models, app/Services/NotificationService.php, app/Http/Controllers/NotificationController.php, database/migrations, resources/views (seluruh, untuk polishing), file Phase sebelumnya untuk menambahkan pemanggilan NotificationService
File yang tidak boleh diubah: struktur database inti (selain tabel notifications baru), business logic perhitungan payroll/cuti/presensi

Output yang saya harapkan: kode lengkap untuk notifikasi, daftar perubahan UI yang dilakukan untuk konsistensi.

Testing wajib sebelum lanjut: jalankan seluruh checklist di 09_TESTING_CHECKLIST.md dari awal sampai akhir.

Jangan kerjakan phase lain di luar Phase 9 ini.
```

---

## Phase 10 — Deployment Preparation

**Tujuan**: Menyiapkan aplikasi untuk deployment ke server production/staging.

**File/Tabel yang dibuat**:
- `.env.example` final
- Dokumentasi deployment (`DEPLOYMENT.md` opsional)
- Konfigurasi storage, queue (jika dipakai), cache

**Fitur yang harus selesai**:
- Environment variable production terdaftar lengkap di `.env.example` (tanpa value asli/secret)
- HTTPS dipastikan aktif (dicatat di dokumentasi, bukan kode)
- Migration & seeder siap dijalankan di server baru (`php artisan migrate --seed` untuk data awal seperti departments/positions/leave_types/akun super admin pertama)
- Storage link untuk file publik (jika ada) dikonfigurasi, storage privat tetap privat
- Optimasi dasar: `config:cache`, `route:cache`, `view:cache` terdokumentasi sebagai langkah deploy

**Testing wajib**: smoke test penuh di environment staging — ulangi ringkas seluruh `09_TESTING_CHECKLIST.md` di server staging, bukan hanya lokal.

**Output akhir phase (kriteria PASS)**:
- [ ] Aplikasi bisa di-deploy ke server staging dari nol mengikuti dokumentasi deployment
- [ ] Tidak ada secret/credential ter-hardcode di kode (sesuai standar di `CLAUDE.md`: secrets via env vars)
- [ ] Smoke test di staging berhasil untuk seluruh role

**Prompt lanjutan untuk Claude/Codex**:
```
Kerjakan Phase 10 dari roadmap HRIS Mobile App: Deployment Preparation.
Prasyarat: Phase 1-9 sudah PASS.

Yang harus dikerjakan:
1. Susun .env.example lengkap dengan seluruh variabel yang dibutuhkan aplikasi (DB, mail jika ada, app url, dsb) TANPA value asli/secret
2. Audit codebase: pastikan tidak ada API key, password, atau credential lain yang ter-hardcode di kode (harus lewat env())
3. Buat dokumentasi DEPLOYMENT.md berisi: requirement server (PHP version, ekstensi, MySQL), langkah instalasi (composer install, migrate --seed, storage:link, config/route/view cache), dan catatan HTTPS wajib di production
4. Pastikan seeder untuk data awal (departments, positions, leave_types, 1 akun super_admin pertama) bisa dijalankan aman di server baru tanpa duplikasi jika dijalankan ulang (idempotent seeder)
5. Review middleware/.env untuk memastikan APP_DEBUG=false dan APP_ENV=production di environment production

File yang boleh diubah: .env.example, DEPLOYMENT.md (baru), database/seeders (untuk idempotency)
File yang tidak boleh diubah: business logic aplikasi, struktur database

Output yang saya harapkan: isi .env.example, isi DEPLOYMENT.md, daftar temuan jika ada credential ter-hardcode yang perlu diperbaiki.

Testing wajib sebelum dianggap selesai: deploy ke server staging mengikuti DEPLOYMENT.md dari nol, lalu jalankan smoke test seluruh role.

Ini adalah phase terakhir — setelah PASS, aplikasi siap untuk demo/rilis MVP.
```
