<?php

return [

    // Navigasi bawah / menu utama — dipakai berulang di banyak halaman
    'nav_home'          => 'Beranda',
    'nav_employees'     => 'Pegawai',
    'nav_approvals'     => 'Persetujuan',
    'nav_reports'       => 'Laporan',
    'nav_settings'      => 'Pengaturan',
    'nav_profile'       => 'Profil',
    'nav_payroll'       => 'Penggajian',
    'nav_payslip'       => 'Slip Gaji',
    'nav_notifications' => 'Notifikasi',
    'nav_users'         => 'Pengguna',
    'nav_audit'         => 'Audit',
    'nav_attendance'    => 'Kehadiran',
    'nav_leave'         => 'Cuti',

    // Badge/label status
    'badge_coming_soon' => 'Segera Hadir',

    // Aksi umum
    'action_save'      => 'Simpan',
    'action_cancel'    => 'Batal',
    'action_edit'      => 'Ubah',
    'action_delete'    => 'Hapus',
    'action_approve'   => 'Setujui',
    'action_reject'    => 'Tolak',
    'action_back'      => 'Kembali',
    'action_back_home' => 'Kembali ke Beranda',
    'action_view_all'  => 'Lihat Semua',
    'action_view_details' => 'Lihat Detail',

    // Status kehadiran/cuti/approval
    'status_active'          => 'Aktif',
    'status_inactive'        => 'Tidak Aktif',
    'status_pending_review'  => 'Menunggu Peninjauan',
    'status_pending_hr'      => 'Menunggu HR',
    'status_approved'        => 'Disetujui',
    'status_rejected'        => 'Ditolak',

    // Umum
    'success' => 'Berhasil',
    'error'   => 'Terjadi Kesalahan',

    // Dipetakan dari kolom users.role (nilai database TIDAK diubah)
    'role_labels' => [
        'super_admin' => 'Super Admin',
        'admin_hr'    => 'Admin HR',
        'finance'     => 'Keuangan',
        'employee'    => 'Pegawai',
    ],
];
