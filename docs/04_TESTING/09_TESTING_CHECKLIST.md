# 09. Testing Checklist — HRIS Mobile App

Checklist manual testing ini dipakai sebelum demo/rilis MVP. Jalankan dengan minimal 2 akun berbeda per role (untuk menguji isolasi data antar user, misal Karyawan A tidak bisa lihat data Karyawan B).

> Status terkini: testing payroll internal dari roadmap awal tidak lagi menjadi acceptance path final. Phase 28 Payroll Payment Workflow dibuat lalu direvert. HRIS menjadi source of truth untuk employee data dan attendance; salary calculation/payment akan ditangani external payroll system; HRIS nantinya menerima payroll/payslip results.

## Testing Login

- [ ] Login dengan email & password benar → berhasil masuk
- [ ] Login dengan email benar, password salah → gagal dengan pesan error jelas
- [ ] Login dengan email tidak terdaftar → gagal dengan pesan error jelas (tidak membocorkan apakah email terdaftar atau tidak, demi keamanan)
- [ ] Percobaan login gagal berulang kali → terkena rate limit, muncul pesan "terlalu banyak percobaan"
- [ ] Setelah login, `last_login_at` ter-update
- [ ] Logout berhasil, mencoba akses halaman privat setelah logout (termasuk via tombol back browser) tidak menampilkan data

## Testing Role Access

- [ ] Employee login → diarahkan ke Employee Dashboard, tidak bisa akses URL HR/Finance/Super Admin (403)
- [ ] Admin HR login → diarahkan ke HR Dashboard, tidak bisa akses fitur khusus Finance (proses payroll) atau Super Admin (kelola user)
- [ ] Finance login → diarahkan ke Finance Dashboard, tidak bisa akses fitur khusus HR (approval cuti/presensi) kecuali memang didesain overlap
- [ ] Super Admin login → bisa akses seluruh modul

## Testing Employee Management

- [ ] Tambah karyawan baru dengan data lengkap → berhasil, muncul di list
- [ ] Tambah karyawan dengan email yang sudah dipakai → ditolak dengan pesan error
- [ ] Tambah karyawan dengan NIK yang sudah dipakai → ditolak dengan pesan error
- [ ] Edit data karyawan → perubahan tersimpan dan tercatat di audit log
- [ ] Nonaktifkan karyawan → karyawan tidak bisa login lagi, tapi data presensi/cuti historisnya tetap muncul di laporan
- [ ] Search/filter karyawan berdasarkan nama, departemen, status kerja → hasil sesuai

## Testing Presensi Dalam Radius

- [ ] Check-in dari lokasi dalam radius 100 meter kantor → status otomatis APPROVED, tidak perlu approval manual
- [ ] Foto selfie tersimpan dan bisa dilihat kembali di riwayat presensi (oleh karyawan yang bersangkutan)
- [ ] Check-out di hari yang sama setelah check-in → berhasil
- [ ] Mencoba check-in kedua kali di hari yang sama → ditolak sistem

## Testing Presensi Luar Radius

- [ ] Check-in dari lokasi di luar radius 100 meter → sistem meminta alasan wajib diisi
- [ ] Submit tanpa mengisi alasan (atau alasan < 10 karakter) → ditolak validasi
- [ ] Submit dengan alasan valid → status menjadi PENDING_REVIEW
- [ ] Admin HR melihat presensi ini di antrian approval
- [ ] Admin HR approve → status menjadi APPROVED, karyawan menerima notifikasi
- [ ] Admin HR reject dengan catatan → status menjadi REJECTED, karyawan menerima notifikasi berisi alasan reject

## Testing Kamera/GPS Ditolak

- [ ] Buka halaman presensi, tolak izin akses lokasi (GPS) di browser → tombol check-in tidak bisa digunakan / muncul pesan jelas GPS wajib aktif
- [ ] Buka halaman presensi, tolak izin akses kamera → tidak bisa mengambil selfie, submit presensi diblokir
- [ ] Izinkan GPS tapi tolak kamera (atau sebaliknya) → tetap diblokir, karena dua-duanya wajib aktif

## Testing Cuti

- [ ] Ajukan cuti dengan jenis, tanggal, alasan lengkap → berhasil, status PENDING_HR
- [ ] Ajukan cuti dengan saldo tidak mencukupi (untuk jenis yang memotong saldo) → ditolak sistem dengan pesan jelas
- [ ] Ajukan cuti dengan tanggal mulai > tanggal selesai → ditolak validasi
- [ ] Ajukan cuti dengan tanggal tumpang tindih dengan pengajuan lain yang masih pending/approved → ditolak sistem
- [ ] Upload lampiran (pdf/jpg/png) di bawah 5MB → berhasil
- [ ] Upload lampiran melebihi 5MB atau tipe file tidak didukung → ditolak validasi
- [ ] Riwayat cuti karyawan menampilkan status terbaru dengan akurat

## Testing Approval HR

- [ ] HR melihat antrian presensi pending dan cuti pending dengan data lengkap (nama karyawan, detail pengajuan)
- [ ] HR approve cuti → saldo cuti karyawan berkurang sesuai jumlah hari yang diajukan
- [ ] HR reject cuti → saldo cuti karyawan **tidak** berkurang
- [ ] Reject tanpa mengisi catatan alasan → ditolak validasi (catatan wajib saat reject)
- [ ] Setelah approve/reject, item hilang dari antrian pending dan karyawan menerima notifikasi

## Testing Payroll

- [ ] Dokumentasi dan demo menyebut Phase 28 internal payroll payment workflow sebagai created lalu reverted.
- [ ] HRIS tidak diklaim sebagai final internal payroll calculator atau payment processor.
- [ ] Employee data dan attendance dijelaskan sebagai source data untuk external payroll system.
- [ ] Detail kontrak integrasi, format data, status, dan endpoint/API belum diklaim selesai sebelum Phase 34.
- [ ] Tidak ada klaim bahwa HRIS melakukan bank transfer atau final salary calculation.

## Testing Payslip

- [ ] Payslip HRIS dijelaskan sebagai hasil yang nantinya diterima dari external payroll system.
- [ ] Detail akses, tampilan, download, dan sinkronisasi payslip menunggu kontrak integrasi Phase 34.
- [ ] Tidak ada klaim bahwa payslip final saat ini berasal dari payroll calculation internal HRIS.

## Testing Report

- [ ] Laporan rekap presensi menampilkan data sesuai jumlah aktual presensi di database untuk filter tanggal yang dipilih
- [ ] Laporan rekap cuti menampilkan data sesuai jumlah aktual cuti
- [ ] Laporan terkait payroll eksternal belum diklaim selesai sebelum kontrak integrasi Phase 34
- [ ] Filter departemen menyaring data dengan benar
- [ ] Filter karyawan (nama spesifik) menyaring data dengan benar
- [ ] Akses halaman laporan oleh role Employee → ditolak (403)

## Testing Audit Log

- [ ] Login tercatat di audit log dengan user, action="login", timestamp yang sesuai
- [ ] Submit presensi tercatat di audit log
- [ ] Approve/reject presensi tercatat di audit log dengan approver yang benar
- [ ] Submit cuti tercatat di audit log
- [ ] Approve/reject cuti tercatat di audit log
- [ ] Update data karyawan tercatat di audit log dengan detail before/after
- [ ] Audit log untuk payroll eksternal menunggu kontrak integrasi Phase 34
- [ ] Tidak ditemukan endpoint/tombol untuk edit atau hapus entri audit log di seluruh aplikasi
- [ ] Filter audit log (user, modul, rentang tanggal) berfungsi dengan benar
- [ ] Role Employee tidak bisa mengakses halaman audit log (403)

## Testing Responsive Mobile

- [ ] Seluruh halaman utama (dashboard, presensi, cuti, employee directory, notifications, profile) diuji di lebar layar 360px dan 414px — tidak ada elemen terpotong/overflow horizontal
- [ ] Bottom navigation tetap terlihat dan berfungsi di semua halaman employee
- [ ] Form (presensi, cuti) mudah diisi dengan satu tangan di layar HP (tombol cukup besar, tidak terlalu rapat)
- [ ] Status badge terbaca jelas (kontras warna cukup) di mode terang
- [ ] Loading state muncul saat data sedang dimuat (tidak ada halaman kosong tanpa indikator saat loading)
- [ ] Empty state muncul dengan pesan yang jelas saat tidak ada data (misal: belum ada riwayat presensi)
- [ ] Diuji minimal di 2 browser mobile berbeda: Chrome Android dan Safari iOS
