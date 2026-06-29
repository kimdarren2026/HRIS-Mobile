# 06. Acceptance Criteria — HRIS Mobile App

Checklist ini menandakan suatu fitur **selesai dan siap demo** apabila seluruh poin tercentang. Gunakan bersama `09_TESTING_CHECKLIST.md` saat verifikasi.

> Status terkini: acceptance criteria payroll internal dari PRD awal sudah tidak menjadi arah final. Setelah Phase 28 direvert, HRIS menjadi source of truth untuk employee data dan attendance; external payroll system akan menghitung salary; HRIS nantinya menerima payroll/payslip results dari external payroll system.

### F.1 Authentication & Role Access
- [ ] User dapat login dengan email & password yang valid, dan ditolak jika salah dengan pesan error yang jelas.
- [ ] Setelah login, user diarahkan otomatis ke dashboard sesuai role-nya.
- [ ] User yang mencoba mengakses URL di luar hak role-nya mendapat response 403, bukan ditampilkan halamannya.
- [ ] User dapat logout dan session langsung tidak valid setelahnya (mencoba back browser tidak menampilkan halaman privat).
- [ ] Aktivitas login tercatat di audit log.

### F.2 Dashboard
- [ ] Employee dashboard menampilkan status presensi hari ini, sisa cuti, status pengajuan terbaru, dan ringkasan payslip terakhir, semuanya sesuai data real karyawan yang login.
- [ ] Admin HR dashboard menampilkan angka yang akurat: total karyawan aktif, jumlah presensi pending, jumlah cuti pending.
- [ ] Finance dashboard menampilkan periode payroll aktif dan status pembayarannya dengan benar.
- [ ] Semua data di dashboard ter-update real-time (tidak perlu cache manual) setiap halaman dimuat ulang.

### F.3 Employee Management
- [ ] Admin HR/Super Admin dapat menambah karyawan baru dengan semua field wajib tervalidasi (email unik, NIK unik).
- [ ] Data karyawan dapat diedit dan perubahan tersimpan dengan benar, tercatat di audit log (before/after).
- [ ] Karyawan yang dihapus/nonaktifkan tidak bisa login lagi, tetapi data historis (presensi, cuti, payroll) tetap tersimpan (soft delete, bukan hard delete).
- [ ] List karyawan dapat difilter/dicari berdasarkan nama, departemen, atau status kerja.

### F.4 Attendance / Presensi
- [ ] Karyawan tidak dapat submit presensi tanpa mengizinkan GPS dan kamera.
- [ ] Jika lokasi dalam radius 100 meter, status otomatis APPROVED tanpa perlu approval manual.
- [ ] Jika lokasi di luar radius, sistem mewajibkan pengisian alasan dan status menjadi PENDING_REVIEW.
- [ ] Admin HR dapat melihat daftar presensi pending dan melakukan approve/reject dengan catatan.
- [ ] Karyawan tidak bisa check-in dua kali di hari yang sama, dan tidak bisa check-out sebelum check-in.
- [ ] Riwayat presensi karyawan menampilkan data yang sesuai dengan apa yang sudah disubmit, termasuk foto selfie yang dapat dilihat (oleh karyawan sendiri dan HR).
- [ ] Setiap submit dan approval presensi tercatat di audit log.

### F.5 Leave & Permission
- [ ] Karyawan dapat mengajukan cuti dengan memilih jenis, tanggal, alasan, dan lampiran opsional.
- [ ] Sistem menolak pengajuan jika saldo cuti tidak mencukupi (untuk jenis cuti yang memotong saldo).
- [ ] Sistem menolak pengajuan jika tanggal tumpang tindih dengan pengajuan lain yang masih aktif (pending/approved).
- [ ] Saldo cuti berkurang hanya setelah HR approve, tidak berkurang saat reject.
- [ ] Admin HR dapat melihat antrian cuti pending dan approve/reject dengan catatan.
- [ ] Karyawan dapat melihat riwayat seluruh pengajuan cuti beserta statusnya.
- [ ] Setiap submit dan approval cuti tercatat di audit log.

### F.6 Payroll
- [ ] Dokumentasi dan demo tidak mengklaim HRIS sebagai final internal payroll calculator atau payment processor.
- [ ] HRIS menjaga employee data dan attendance sebagai source of truth untuk kebutuhan payroll eksternal.
- [ ] Kontrak integrasi payroll eksternal belum diklaim selesai sampai Phase 34 menerima detail external payroll project.
- [ ] Phase 28 internal payroll payment workflow tercatat sebagai created lalu reverted.

### F.7 Payslip
- [ ] Payslip HRIS diperlakukan sebagai hasil yang nantinya diterima dari external payroll system.
- [ ] Detail struktur, akses, dan download payslip menunggu kontrak integrasi Phase 34.
- [ ] Tidak ada klaim bahwa payslip final saat ini berasal dari payroll calculation internal HRIS.

### F.8 Report / Laporan
- [ ] HR dapat melihat rekap presensi dan cuti dalam bentuk tabel.
- [ ] Laporan dapat difilter berdasarkan rentang tanggal, departemen, dan/atau nama karyawan.
- [ ] Data laporan sesuai dengan data aktual di database (tidak ada selisih perhitungan).
- [ ] Tombol export CSV/Excel tampil di UI sebagai rencana fitur (boleh non-fungsional untuk MVP).

### F.9 Notification
- [ ] Karyawan menerima notifikasi saat pengajuan presensi/cuti berhasil disubmit.
- [ ] Karyawan menerima notifikasi saat pengajuan di-approve atau di-reject.
- [ ] Notifikasi payroll/payslip baru menunggu kontrak integrasi external payroll.
- [ ] Notifikasi dapat ditandai sebagai sudah dibaca, dan jumlah notifikasi belum dibaca tampil sebagai badge.

### F.10 Audit Log
- [ ] Semua aksi penting yang ditentukan (login, submit presensi, approve/reject presensi, submit cuti, approve/reject cuti, update data karyawan, dan aksi payroll eksternal setelah kontraknya tersedia) tercatat otomatis tanpa perlu input manual dari user.
- [ ] Setiap entri audit log menyimpan user, action, module, timestamp, dan detail perubahan jika relevan.
- [ ] Audit log hanya bisa dilihat (read-only) oleh Admin HR dan Super Admin, tidak bisa diedit/dihapus oleh siapapun melalui aplikasi.
- [ ] Audit log dapat difilter berdasarkan user, modul, atau rentang tanggal.

---
