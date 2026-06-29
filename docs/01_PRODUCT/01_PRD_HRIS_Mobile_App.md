# 01. PRD — HRIS Mobile App

> Status terkini: PRD awal ini pernah memuat payroll internal sebagai bagian MVP. Setelah Phase 28 Payroll Payment Workflow dibuat lalu direvert, arah payroll berubah. HRIS menjadi source of truth untuk employee data dan attendance; external payroll system akan menghitung salary; HRIS nantinya menerima payroll/payslip results dari external payroll system.

## A. PRD (Product Requirements Document)

### A.1 Ringkasan Produk

**HRIS Mobile App** adalah aplikasi web mobile-first berbasis Laravel yang digunakan untuk mengelola proses HR harian: presensi berbasis GPS dan selfie, pengajuan izin/cuti, approval HR, manajemen data karyawan, laporan, audit log, dan data sumber untuk integrasi payroll eksternal. Aplikasi dirancang agar bisa diakses langsung dari browser HP karyawan (mobile-first responsive), dengan opsi dikembangkan menjadi PWA agar terasa seperti aplikasi native (bisa di-install, bekerja offline-ready untuk beberapa fitur).

Aplikasi ini menyatukan tiga kebutuhan yang biasanya terpisah di banyak perusahaan kecil-menengah:
- **Karyawan**: presensi, cuti, payslip, profil.
- **HR**: approval presensi & cuti, manajemen data karyawan, laporan, audit log.
- **Finance/Payroll eksternal**: konsumsi data karyawan dan presensi dari HRIS untuk proses payroll di sistem terpisah.

### A.2 Latar Belakang Masalah

Banyak perusahaan skala kecil-menengah masih mengelola presensi dengan kertas/Excel, approval cuti lewat chat/WhatsApp, dan payroll dihitung manual. Ini menimbulkan masalah:

1. **Presensi tidak akurat** — karyawan bisa titip absen, tidak ada bukti lokasi/wajah.
2. **Proses approval tidak terlacak** — tidak ada riwayat siapa approve, kapan, dan alasan apa.
3. **Payroll rawan human error** — perhitungan manual rentan salah hitung tunjangan, potongan, lembur; pada arah terbaru, perhitungan ini ditangani oleh external payroll system.
4. **Tidak ada audit trail** — sulit menelusuri siapa mengubah data apa dan kapan, terutama untuk kebutuhan kepatuhan internal.
5. **Data karyawan tersebar** — di Excel terpisah-pisah, tidak terpusat dan tidak real-time.

HRIS Mobile App hadir untuk menyatukan proses ini dalam satu sistem berbasis role, dengan validasi otomatis (radius GPS, status approval berjenjang) sehingga mengurangi kecurangan dan mempercepat proses administrasi HR.

### A.3 Tujuan Aplikasi

1. Menyediakan sistem presensi yang sulit dimanipulasi (GPS + selfie + radius kantor).
2. Mempercepat proses pengajuan dan approval cuti/izin secara digital.
3. Menyediakan employee data dan attendance yang akurat sebagai sumber data payroll eksternal.
4. Memberi karyawan akses mandiri (self-service) untuk melihat status presensi, cuti, dan nantinya payslip hasil integrasi payroll eksternal.
5. Memberi HR dan Finance dashboard yang jelas untuk mengambil keputusan approval dan pembayaran.
6. Mencatat seluruh aktivitas penting ke dalam audit log untuk transparansi dan akuntabilitas.
7. Dibangun dengan arsitektur yang scalable (Laravel + MySQL) agar mudah dikembangkan ke fitur lanjutan (export, PDF, PWA, notifikasi push).

### A.4 Target Pengguna

| Target | Deskripsi |
|---|---|
| Karyawan (Employee) | Seluruh staf perusahaan yang melakukan presensi harian dan mengajukan cuti/izin |
| Admin HR | Tim HR yang mengelola data karyawan, approval presensi/cuti, dan laporan |
| Finance | Tim keuangan yang memerlukan data HRIS dan hasil payroll dari sistem payroll eksternal |
| Super Admin | Pemilik sistem/IT internal yang mengelola seluruh data, role, dan konfigurasi sistem |

Perusahaan target: skala kecil-menengah (10–500 karyawan), satu atau beberapa kantor cabang, belum memiliki sistem HR digital terintegrasi.

### A.5 Role dan Permission

| Role | Hak Akses Utama |
|---|---|
| **Employee** | Check-in/out presensi, ajukan cuti/izin, lihat riwayat presensi & cuti, lihat payslip, edit profil terbatas |
| **Admin HR** | Semua hak Employee + kelola data karyawan (CRUD), approve/reject presensi pending, approve/reject cuti, lihat & buat laporan, lihat audit log, kelola periode payroll (tahap awal) |
| **Finance** | Melihat data HRIS yang relevan untuk payroll, memantau hasil payroll/payslip setelah integrasi eksternal tersedia |
| **Super Admin** | Semua hak di atas + kelola user & role, kelola departemen/jabatan, kelola konfigurasi sistem (radius kantor, jenis cuti, dll), akses penuh audit log |

**Prinsip permission**: setiap route dan setiap aksi (approve, edit, delete) divalidasi di middleware dan policy berdasarkan role yang login. Tidak ada akses langsung via URL tanpa validasi role.

### A.6 Daftar Fitur Utama

1. Authentication & Role-based Access Control
2. Dashboard per Role
3. Employee Management (CRUD data karyawan)
4. Attendance / Presensi (GPS + Selfie + Radius)
5. Leave & Permission (Cuti & Izin)
6. Payroll external integration (planned; contract menunggu detail external payroll project)
7. Payslip hasil payroll eksternal (planned)
8. Report / Laporan (presensi, cuti, dan data HR terkait payroll)
9. Notification (in-app)
10. Audit Log

### A.7 User Stories

**Employee**
- Sebagai karyawan, saya ingin check-in dengan GPS dan selfie agar presensi saya tervalidasi otomatis.
- Sebagai karyawan, saya ingin mengisi alasan jika saya check-in di luar radius kantor, agar tetap tercatat meski perlu review HR.
- Sebagai karyawan, saya ingin mengajukan cuti dengan memilih jenis cuti dan melampirkan dokumen pendukung jika perlu.
- Sebagai karyawan, saya ingin melihat sisa saldo cuti saya sebelum mengajukan.
- Sebagai karyawan, saya ingin melihat status pengajuan cuti saya (pending/approved/rejected).
- Sebagai karyawan, saya ingin melihat dan (nantinya) mengunduh payslip saya per periode setelah hasil payroll diterima dari external payroll system.
- Sebagai karyawan, saya ingin mendapat notifikasi saat pengajuan saya disetujui/ditolak.

**Admin HR**
- Sebagai admin HR, saya ingin melihat daftar presensi yang berstatus pending review agar bisa segera memprosesnya.
- Sebagai admin HR, saya ingin approve/reject presensi dan cuti beserta catatan alasan.
- Sebagai admin HR, saya ingin mengelola data karyawan (tambah, edit, nonaktifkan) dengan mudah.
- Sebagai admin HR, saya ingin melihat laporan rekap presensi dan cuti per departemen/periode.
- Sebagai admin HR, saya ingin melihat audit log untuk menelusuri perubahan data penting.

**Finance**
- Sebagai finance, saya ingin HRIS menyediakan employee data dan attendance yang akurat sebagai input ke external payroll system.
- Sebagai finance, saya ingin HRIS menerima hasil payroll/payslip dari external payroll system setelah integrasi tersedia.
- Sebagai finance, saya ingin melihat status hasil payroll/payslip yang diterima tanpa menjadikan HRIS sebagai kalkulator salary final.

**Super Admin**
- Sebagai super admin, saya ingin mengelola seluruh user dan role agar akses sistem terkontrol.
- Sebagai super admin, saya ingin mengatur radius kantor dan jenis cuti yang tersedia di sistem.
- Sebagai super admin, saya ingin memiliki akses penuh ke seluruh modul untuk keperluan audit/maintenance.

### A.8 Business Rules

1. **Presensi**
   - Check-in/out wajib mengaktifkan GPS dan kamera (selfie). Jika salah satu ditolak, presensi tidak bisa disubmit.
   - Radius kantor default **100 meter** dari titik koordinat kantor (dapat dikonfigurasi Super Admin).
   - Jika lokasi dalam radius → status presensi otomatis **APPROVED**.
   - Jika lokasi di luar radius → karyawan **wajib mengisi alasan**, status menjadi **PENDING_REVIEW**, dan masuk ke antrian approval HR.
   - Satu karyawan hanya bisa memiliki satu check-in dan satu check-out aktif per hari.
   - Check-out hanya bisa dilakukan setelah check-in pada hari yang sama.

2. **Cuti/Izin**
   - Status awal pengajuan selalu **PENDING_HR**.
   - Jenis cuti: Cuti Tahunan, Sakit, Izin Pribadi, Cuti Khusus (masing-masing bisa punya aturan saldo berbeda; Sakit & Cuti Khusus bisa tidak memotong saldo tergantung kebijakan perusahaan — dikonfigurasi Super Admin).
   - Tanggal selesai tidak boleh lebih awal dari tanggal mulai.
   - Saldo cuti tahunan berkurang otomatis **hanya setelah disetujui HR**, bukan saat pengajuan dibuat (saldo "dicadangkan" sebagai pending, dikurangi permanen saat approved).
   - Karyawan tidak bisa mengajukan cuti baru jika ada pengajuan lain yang tanggalnya tumpang tindih dan masih pending/approved.
   - Jika ditolak, saldo cuti tidak berkurang.

3. **Payroll External Integration**
   - HRIS menyimpan employee data dan attendance sebagai source of truth.
   - External payroll system bertanggung jawab atas salary calculation dan payment processing.
   - HRIS nantinya menerima payroll/payslip results dari external payroll system.
   - Kontrak data, endpoint/API, format file, status, dan aturan sinkronisasi akan ditentukan di Phase 34 setelah detail external payroll project tersedia.
   - Phase 28 internal payroll payment workflow sudah direvert dan tidak menjadi final internal HRIS payroll.

4. **Approval umum**
   - Setiap aksi approve/reject wajib dicatat siapa approver-nya dan waktu approve.
   - Reject wajib disertai alasan/catatan.

5. **Role & Akses**
   - Semua endpoint backend divalidasi ulang di server (tidak hanya hide di UI), menggunakan middleware role + policy.

### A.9 Validasi Penting

| Area | Validasi |
|---|---|
| Login | Email valid, password minimal 8 karakter, rate limit percobaan login (anti brute-force) |
| Presensi | GPS aktif (lat/long terisi), foto selfie wajib (file image, max 5MB), alasan wajib jika di luar radius (min 10 karakter) |
| Cuti | Tanggal mulai ≤ tanggal selesai, jenis cuti wajib dipilih, alasan wajib diisi, lampiran opsional (pdf/jpg/png, max 5MB), saldo cuti cukup (untuk jenis yang memotong saldo) |
| Data Karyawan | Email unik, NIK unik, nomor HP format valid, field wajib (nama, email, NIK, jabatan, departemen, tanggal masuk) tidak boleh kosong |
| Payroll external integration | Validasi menunggu kontrak integrasi Phase 34; HRIS harus menjaga akurasi employee data dan attendance sebagai input payroll eksternal |
| Upload File | Validasi tipe file dan ukuran maksimal di sisi server, bukan hanya di frontend |

### A.10 Data yang Disimpan

- Data identitas & akun: nama, email, password (hashed), role.
- Data karyawan: NIK, jabatan, departemen, tanggal masuk, status kerja, no. HP, alamat, foto profil, nomor rekening bank.
- Data presensi: timestamp check-in/out, koordinat GPS, foto selfie, status, alasan (jika di luar radius), approver.
- Data cuti: jenis, tanggal mulai/selesai, alasan, lampiran, status, approver, catatan approval.
- Data saldo cuti per karyawan per tahun.
- Data payroll/payslip: planned sebagai hasil yang diterima dari external payroll system, detail struktur menunggu kontrak integrasi Phase 34.
- Data notifikasi: judul, pesan, status dibaca/belum, relasi ke modul terkait.
- Data audit log: user, aksi, modul, waktu, detail perubahan (before/after jika relevan).

### A.11 Non-Functional Requirements

1. **Performance**: halaman utama (dashboard, presensi) harus dapat dimuat < 2 detik pada koneksi 4G normal.
2. **Responsiveness**: seluruh halaman wajib mobile-first, dapat digunakan nyaman di layar 360px–414px lebar.
3. **Availability**: target uptime backend minimal 99% (untuk skala internal perusahaan).
4. **Scalability**: struktur database dan kode harus mendukung penambahan jumlah karyawan tanpa perombakan besar.
5. **Maintainability**: kode mengikuti struktur Laravel standar (MVC + Service layer) agar mudah dikembangkan tim lain.
6. **Usability**: alur presensi maksimal 3 langkah (buka halaman → izinkan GPS/kamera → submit).
7. **Compatibility**: berjalan baik di browser mobile utama (Chrome Android, Safari iOS).
8. **PWA-ready**: struktur frontend disiapkan agar mudah ditambahkan manifest.json & service worker di tahap lanjutan.

### A.12 Security Requirements

1. Password disimpan dengan hashing (bcrypt/argon2, default Laravel).
2. Autentikasi menggunakan session/Laravel Breeze, dengan opsi token (Sanctum) jika diperlukan akses API mobile native ke depan.
3. CSRF protection aktif di semua form.
4. Validasi role dilakukan di middleware **dan** policy (defense in depth), bukan hanya di Blade view.
5. File upload (selfie, lampiran cuti) divalidasi tipe & ukuran, disimpan di storage privat (tidak public langsung), diakses lewat route terproteksi.
6. Rate limiting pada endpoint login dan endpoint submit presensi/cuti untuk mencegah abuse.
7. Audit log tidak dapat dihapus/diedit oleh role mana pun (append-only).
8. Data sensitif (nomor rekening, NIK) ditampilkan dengan masking parsial di tampilan list, full hanya di halaman detail dengan akses terbatas.
9. HTTPS wajib digunakan di environment production (untuk keamanan GPS & foto yang dikirim).
10. Logout otomatis (session expired) setelah periode tidak aktif tertentu (dikonfigurasi).

### A.13 MVP Scope

**Termasuk MVP (wajib ada di rilis pertama):**
- Login, logout, role-based dashboard & proteksi halaman.
- Employee management dasar (CRUD).
- Presensi GPS + selfie + radius + approval pending.
- Pengajuan & approval cuti/izin + saldo cuti.
- Data HRIS untuk payroll eksternal: employee data dan attendance siap digunakan sebagai sumber data.
- Payslip hasil payroll eksternal belum termasuk sampai kontrak integrasi tersedia.
- Laporan dasar (rekap presensi, cuti, dan data HR terkait payroll dalam tabel, filter tanggal/departemen).
- Notifikasi in-app sederhana (list/toast).
- Audit log dasar untuk aksi-aksi penting yang disebutkan.

**Tidak termasuk MVP (future enhancement):**
- Export CSV/Excel.
- Download payslip dalam format PDF (real generation).
- Push notification (web push/mobile native).
- Payroll external integration contract dan penerimaan hasil payroll/payslip dari sistem payroll eksternal.
- Integrasi pajak otomatis (PPh21) dan BPJS resmi jika menjadi bagian dari external payroll system.
- Multi-kantor dengan radius berbeda per cabang.
- Offline mode penuh (PWA caching presensi saat offline).
- Aplikasi native (Android/iOS) terpisah.

### A.14 Future Enhancement

1. Export laporan ke CSV/Excel dan generate payslip PDF otomatis (mis. menggunakan package `barryvdh/laravel-dompdf` atau `maatwebsite/excel`).
2. Push notification via web push (PWA) atau integrasi Firebase Cloud Messaging.
3. Integrasi perhitungan pajak PPh21 dan BPJS Kesehatan/Ketenagakerjaan otomatis.
4. Multi-cabang/multi-lokasi dengan radius kantor berbeda per cabang.
5. Approval berjenjang (multi-level approval) untuk cuti dengan durasi panjang.
6. Integrasi absensi fingerprint/RFID sebagai alternatif GPS+selfie.
7. Mode offline untuk presensi (data tersimpan lokal, sync saat online kembali — fitur khas PWA).
8. Dashboard analitik lanjutan (grafik tren kehadiran, turnover, dsb).
9. Integrasi kalender (Google Calendar) untuk jadwal cuti tim.
10. Self-service slip gaji + riwayat pajak tahunan (semacam e-Bukti Potong) setelah hasil tersedia dari external payroll system.
