# 08. Security and Validation Rules — HRIS Mobile App

> Status terkini: aturan payroll internal di dokumen ini adalah referensi historis. Setelah Phase 28 direvert, final payroll calculation/payment ditangani external payroll system. Validasi, otorisasi, dan audit untuk payroll eksternal akan ditentukan ulang saat Phase 34 Payroll External Integration Contract tersedia.

Dokumen ini adalah checklist keamanan dan validasi **khusus untuk konteks HRIS** — bukan generic security checklist. Gunakan sebagai acuan wajib di setiap phase development (Phase 1-10), bukan ditambahkan belakangan saat aplikasi sudah jadi. Standar teknis dasar (SOLID, fail-fast, least privilege, secure defaults, secrets via env, logging tanpa data sensitif) mengikuti `CLAUDE.md` yang menjadi acuan engineering project ini.

## 1. Autentikasi dan Session

- Password disimpan dengan hashing bawaan Laravel (bcrypt/argon2), **jangan pernah** menyimpan plaintext atau membuat hashing custom.
- Session timeout otomatis setelah periode tidak aktif tertentu (sarankan 30-60 menit untuk aplikasi HR yang menyimpan data sensitif).
- Tidak ada halaman register publik — akun dibuat oleh Super Admin/HR (sifat HRIS adalah internal tool, bukan aplikasi publik).
- Rate limit percobaan login (sarankan: maksimal 5 percobaan gagal per 15 menit per IP/email) untuk mencegah brute-force.
- `last_login_at` di-update setiap login berhasil, untuk membantu deteksi akun yang tidak wajar.

## 2. Role Middleware dan Policy

- **Defense in depth**: validasi role dilakukan di **middleware** (level route) **dan** di **policy** (level objek/aksi), tidak cukup hanya menyembunyikan tombol di Blade view.
- Setiap Controller method yang melakukan aksi sensitif (approve, edit data karyawan, ubah status payroll) wajib memanggil `authorize()` terhadap Policy terkait, bukan hanya mengandalkan middleware route.
- Policy harus eksplisit menolak (bukan default-allow) — prinsip *least privilege*: akses ditolak kecuali secara eksplisit diizinkan.
- Karyawan (role employee) **tidak boleh** memiliki endpoint apapun yang bisa mengubah data karyawan lain, presensi lain, atau cuti orang lain — uji ini dengan mencoba mengubah ID di request (lihat bagian 12 di bawah).

## 3. CSRF

- Seluruh form Blade wajib menyertakan `@csrf`.
- Endpoint AJAX (jika ada, misal submit presensi via fetch) wajib menyertakan token CSRF di header (`X-CSRF-TOKEN`).
- Jangan menonaktifkan middleware `VerifyCsrfToken` untuk route apapun di aplikasi ini kecuali ada alasan teknis kuat yang didokumentasikan.

## 4. File Upload — Selfie dan Lampiran Cuti

- Validasi **di sisi server** (Form Request), bukan hanya di frontend: tipe file (`image` untuk selfie; `pdf,jpg,png` untuk lampiran cuti), ukuran maksimal (sarankan 5MB).
- Simpan file di **storage privat** (`storage/app/private/...`), **bukan** di `storage/app/public` — agar tidak bisa diakses langsung lewat URL publik tanpa otorisasi.
- Akses file dilakukan lewat route terproteksi yang memvalidasi: (a) user sudah login, (b) user berhak melihat file tersebut (miliknya sendiri, atau HR/Super Admin untuk keperluan approval).
- Sanitasi nama file saat disimpan (gunakan nama hasil generate sistem seperti UUID, bukan nama asli dari user) untuk mencegah path traversal atau collision.
- Pertimbangkan kompresi gambar selfie sebelum disimpan untuk efisiensi storage, tanpa menurunkan kualitas yang dibutuhkan untuk verifikasi visual.

## 5. Validasi GPS

- Validasi `latitude` dan `longitude` wajib berupa angka dalam rentang valid (lat: -90 s.d. 90, lng: -180 s.d. 180) — tolak nilai di luar rentang ini.
- Hitung jarak ke kantor di **sisi server** (`AttendanceService::isWithinRadius()`), jangan percaya status "dalam radius/luar radius" yang dikirim dari frontend — frontend hanya boleh mengirim koordinat mentah, keputusan status dihitung ulang oleh backend.
- Catat juga **timestamp server** saat presensi disubmit (bukan hanya timestamp dari device user) sebagai referensi waktu yang lebih bisa dipercaya.

## 6. Anti Manipulasi Presensi

Risiko nyata yang perlu diantisipasi (tidak dibahas detail di dokumen awal, perlu ditambahkan sejak Phase 3):

- **Mock location / GPS spoofing**: browser modern memberi sinyal `coords.accuracy` — jika akurasi terlalu rendah/tidak wajar, pertimbangkan menandai presensi untuk review tambahan (bukan otomatis ditolak, karena bisa false positive di lokasi indoor).
- **Selfie diambil dari galeri, bukan kamera langsung**: pada implementasi web, gunakan atribut `capture="user"` di input file untuk mendorong penggunaan kamera langsung di perangkat mobile. Ini bukan jaminan mutlak, tapi mengurangi kemudahan upload foto lama.
- **Submit presensi berulang dalam waktu singkat**: terapkan rate limit di endpoint check-in/check-out (lihat bagian 7).
- **Approval HR tetap menjadi lapisan verifikasi manusia** untuk kasus PENDING_REVIEW — sistem tidak mengklaim mendeteksi kecurangan 100% otomatis, melainkan menyaring kasus mencurigakan untuk direview manusia.

## 7. Rate Limiting

| Endpoint | Saran Limit |
|---|---|
| Login | 5 percobaan gagal / 15 menit / kombinasi IP+email |
| Submit presensi (check-in/check-out) | 1 request setiap beberapa detik (mencegah double-submit akibat klik ganda), dan maksimal sesuai logika bisnis (1x check-in, 1x check-out per hari sudah dijamin unique constraint) |
| Submit pengajuan cuti | Rate limit longgar (mis. 10/menit) cukup untuk mencegah abuse/spam, bukan untuk membatasi penggunaan wajar |

Implementasikan dengan middleware `throttle:` bawaan Laravel pada route group terkait.

## 8. Storage Private

- Semua file sensitif (selfie presensi, lampiran cuti, foto profil jika dianggap sensitif) disimpan di disk `local` (privat), **tidak** di disk `public`.
- Jangan membuat symbolic link (`storage:link`) untuk folder yang berisi data presensi/cuti — `storage:link` hanya untuk aset benar-benar publik (misal logo aplikasi).
- Setiap request mengambil file privat wajib melalui Controller yang memeriksa otorisasi sebelum melakukan `Storage::response()` atau `Storage::download()`.

## 9. Audit Log Append-Only

- Model `AuditLog` **tidak boleh** memiliki route/Controller method untuk `update` atau `destroy` di seluruh aplikasi.
- Pertimbangkan tambahan proteksi di level database: revoke privilege `UPDATE`/`DELETE` pada tabel `audit_logs` untuk user database aplikasi jika environment production mendukung (defense in depth tambahan, opsional tapi disarankan).
- Audit log tidak boleh menyimpan data sensitif mentah (misal jangan simpan password atau nomor rekening penuh di kolom `changes`) — masking diterapkan juga di log, bukan hanya di tampilan UI (selaras dengan standar "logging tanpa data sensitif" di `CLAUDE.md`).

## 10. Data Masking — NIK dan Rekening

| Field | Tampilan List/Ringkasan | Tampilan Detail (akses terbatas) |
|---|---|---|
| NIK | Masking sebagian, contoh: `32**********1234` | Full, hanya untuk HR/Super Admin yang membuka halaman detail karyawan |
| Nomor rekening | Masking sebagian, contoh: `**** **** 4521` | Full, hanya untuk Finance/HR/Super Admin saat memproses payroll |
| Karyawan melihat datanya sendiri | Boleh full (ini data miliknya sendiri) | — |

- Masking diterapkan di level **resource/transformer/view**, bukan mengubah data asli di database.
- Untuk role Employee yang melihat profilnya sendiri, data ditampilkan full karena itu data miliknya sendiri — masking hanya berlaku saat data tersebut dilihat oleh pihak lain yang bukan pemilik/role yang tidak berkepentingan.

## 11. Validasi Payroll — Tidak Bisa Diedit Setelah LOCKED

- Validasi ini **wajib dilakukan di backend** (Controller/Policy/Form Request), bukan hanya menyembunyikan tombol edit di frontend.
- Contoh pengecekan minimal sebelum proses update:
```php
if ($payrollPeriod->status === 'LOCKED' || $payrollPeriod->status === 'PAID') {
    abort(403, 'Payroll sudah dikunci dan tidak dapat diubah.');
}
```
- Jika Super Admin butuh override dalam kondisi darurat, buat mekanisme terpisah yang **eksplisit dicatat di audit log** dengan flag khusus (misal `action: "payroll_override_edit"`), bukan jalur edit biasa yang sama dengan role lain.

## 12. Permission — Karyawan Tidak Bisa Melihat Data Karyawan Lain

- Setiap query yang mengambil data presensi, cuti, atau payslip **wajib difilter berdasarkan `employee_id` milik user yang login**, untuk role employee.
- Jangan mengandalkan hanya menyembunyikan link/menu di UI — uji dengan skenario: karyawan login, lalu mencoba mengakses URL detail presensi/cuti/payslip milik karyawan lain dengan mengubah ID secara manual di address bar. Request ini **harus** ditolak (403/404), bukan menampilkan data.
- Gunakan Laravel Policy (`viewAny`, `view`) secara konsisten di setiap Controller yang relevan, jangan hanya mengandalkan middleware role global.

## Ringkasan Checklist Wajib per Phase

| Phase | Aturan Security yang Wajib Diterapkan |
|---|---|
| Phase 1 | Hashing password, rate limit login, session timeout, audit log login |
| Phase 2 | Policy karyawan tidak bisa edit data karyawan lain, masking NIK/rekening di list |
| Phase 3 | Validasi GPS server-side, storage privat untuk selfie, rate limit submit presensi, route file terproteksi |
| Phase 4 | Validasi server-side saldo & tumpang tindih, storage privat untuk lampiran |
| Phase 5 | Validasi backend payroll LOCKED tidak bisa diedit |
| Phase 6 | Policy payslip hanya bisa diakses pemilik |
| Phase 7 | Policy laporan hanya untuk HR/Super Admin |
| Phase 8 | Audit log append-only terverifikasi tidak ada endpoint edit/delete |
| Phase 9 | Review menyeluruh seluruh poin di atas sebelum demo |
| Phase 10 | Audit secrets tidak hardcode, APP_DEBUG=false di production, HTTPS aktif |
