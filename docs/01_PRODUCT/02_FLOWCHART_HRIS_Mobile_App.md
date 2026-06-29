# 02. Flowchart — HRIS Mobile App

Seluruh flowchart menggunakan format **Mermaid**. Render di GitHub, VS Code (extension Markdown Preview Mermaid Support), atau https://mermaid.live

### B.1 Alur Login dan Role Redirect

```mermaid
flowchart TD
    A[User membuka halaman Login] --> B[Input email & password]
    B --> C{Validasi kredensial?}
    C -- Gagal --> D[Tampilkan pesan error]
    D --> B
    C -- Berhasil --> E{Cek Role User}
    E -- Employee --> F[Redirect ke Employee Dashboard]
    E -- Admin HR --> G[Redirect ke HR Dashboard]
    E -- Finance --> H[Redirect ke Finance Dashboard]
    E -- Super Admin --> I[Redirect ke Super Admin Dashboard]
    F --> J[Catat Audit Log: Login]
    G --> J
    H --> J
    I --> J
```

### B.2 Alur Presensi GPS + Selfie

```mermaid
flowchart TD
    A[Karyawan buka halaman Presensi] --> B{Izin GPS & Kamera diberikan?}
    B -- Tidak --> C[Tampilkan pesan: GPS/Kamera wajib aktif]
    C --> A
    B -- Ya --> D[Ambil koordinat GPS saat ini]
    D --> E[Ambil foto selfie]
    E --> F[Hitung jarak ke titik kantor]
    F --> G{Dalam radius 100 meter?}
    G -- Ya --> H[Status = APPROVED otomatis]
    G -- Tidak --> I[Lanjut ke alur Presensi Luar Radius - lihat B.3]
    H --> J[Simpan data presensi]
    J --> K[Catat Audit Log: Submit Presensi]
    K --> L[Tampilkan konfirmasi ke karyawan]
```

### B.3 Alur Presensi di Luar Radius

```mermaid
flowchart TD
    A[Lokasi terdeteksi di luar radius kantor] --> B[Tampilkan form alasan wajib diisi]
    B --> C{Alasan diisi minimal 10 karakter?}
    C -- Tidak --> D[Tampilkan error: alasan wajib]
    D --> B
    C -- Ya --> E[Simpan presensi dengan status PENDING_REVIEW]
    E --> F[Catat Audit Log: Submit Presensi Luar Radius]
    F --> G[Kirim notifikasi ke Admin HR]
    G --> H[Admin HR membuka antrian presensi pending]
    H --> I{Admin HR memutuskan}
    I -- Approve --> J[Status menjadi APPROVED]
    I -- Reject --> K[Status menjadi REJECTED + catatan alasan]
    J --> L[Catat Audit Log: Approve Presensi]
    K --> M[Catat Audit Log: Reject Presensi]
    L --> N[Notifikasi ke karyawan: presensi disetujui]
    M --> O[Notifikasi ke karyawan: presensi ditolak]
```

### B.4 Alur Pengajuan Cuti/Izin

```mermaid
flowchart TD
    A[Karyawan buka form pengajuan cuti] --> B[Pilih jenis cuti]
    B --> C[Isi tanggal mulai & selesai, alasan, lampiran opsional]
    C --> D{Validasi: tanggal valid & tidak tumpang tindih?}
    D -- Tidak --> E[Tampilkan error]
    E --> C
    D -- Ya --> F{Saldo cuti mencukupi? jika jenis memotong saldo}
    F -- Tidak --> G[Tampilkan error: saldo tidak cukup]
    G --> C
    F -- Ya --> H[Simpan pengajuan dengan status PENDING_HR]
    H --> I[Catat Audit Log: Submit Cuti]
    I --> J[Kirim notifikasi ke Admin HR]
    J --> K[Lanjut ke alur Approval HR - lihat B.5]
```

### B.5 Alur Approval HR (Presensi & Cuti)

```mermaid
flowchart TD
    A[Admin HR membuka daftar pengajuan pending] --> B{Pilih item: Presensi atau Cuti}
    B --> C[Lihat detail pengajuan]
    C --> D{Keputusan HR}
    D -- Approve --> E[Status menjadi APPROVED]
    D -- Reject --> F[Wajib isi catatan alasan]
    F --> G[Status menjadi REJECTED]
    E --> H{Jenis pengajuan = Cuti?}
    H -- Ya --> I[Kurangi saldo cuti karyawan]
    H -- Tidak --> J[Lanjut tanpa perubahan saldo]
    I --> K[Catat Audit Log: Approve]
    J --> K
    G --> L[Catat Audit Log: Reject]
    K --> M[Kirim notifikasi ke karyawan]
    L --> M
```

### B.6 Alur Payroll

> Status note: alur ini adalah rancangan historis payroll internal. Setelah Phase 28 direvert, final payroll calculation/payment akan ditangani external payroll system. HRIS berperan sebagai source of truth employee data dan attendance, lalu menerima payroll/payslip results dari sistem eksternal.

```mermaid
flowchart TD
    A[Finance/Admin HR buat Periode Payroll baru] --> B[Status: DRAFT]
    B --> C[Input komponen gaji per karyawan: pokok, tunjangan, bonus, lembur, potongan]
    C --> D[Sistem ambil data potongan otomatis dari presensi & keterlambatan]
    D --> E[Klik Hitung/Calculate]
    E --> F[Sistem hitung total gaji bersih per karyawan]
    F --> G[Status: CALCULATED]
    G --> H[Admin HR review hasil perhitungan]
    H --> I{HR setuju?}
    I -- Tidak --> C
    I -- Ya --> J[Status: HR_REVIEW selesai, lanjut ke Finance]
    J --> K{Finance approve?}
    K -- Tidak --> C
    K -- Ya --> L[Status: FINANCE_APPROVAL selesai]
    L --> M[Status: LOCKED - data tidak bisa diubah lagi]
    M --> N[Proses pembayaran dilakukan di luar sistem/manual transfer]
    N --> O[Update Status: PAID]
    O --> P[Catat Audit Log: Proses Payroll setiap perubahan status]
    P --> Q[Lanjut ke alur Payslip - lihat B.7]
```

### B.7 Alur Payslip

```mermaid
flowchart TD
    A[Payroll berstatus LOCKED atau PAID] --> B[Sistem generate payslip per karyawan otomatis]
    B --> C[Karyawan membuka menu Payslip]
    C --> D{Payslip tersedia untuk periode dipilih?}
    D -- Tidak --> E[Tampilkan pesan: payslip belum tersedia]
    D -- Ya --> F[Tampilkan detail: gaji pokok, tunjangan, potongan, total bersih, status bayar]
    F --> G[Tombol Download PDF - rencana fitur lanjutan]
    F --> H[Kirim notifikasi: payslip tersedia]
    H --> I[Catat Audit Log: Akses Payslip - opsional]
```

### B.8 Alur Audit Log

```mermaid
flowchart TD
    A[User melakukan aksi penting di sistem] --> B{Aksi termasuk kategori tercatat?}
    B -- Tidak --> Z[Tidak dicatat]
    B -- Ya, contoh: Login/Submit Presensi/Approve Cuti/Update Karyawan/Proses Payroll --> C[Sistem rekam: user_id, action, module, timestamp]
    C --> D{Ada perubahan data before/after?}
    D -- Ya --> E[Simpan detail perubahan dalam format JSON]
    D -- Tidak --> F[Simpan deskripsi aksi singkat]
    E --> G[Simpan record ke tabel audit_logs]
    F --> G
    G --> H[Super Admin & Admin HR dapat melihat & filter audit log]
    H --> I[Audit log bersifat append-only, tidak bisa diedit/dihapus]
```
