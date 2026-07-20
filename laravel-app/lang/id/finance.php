<?php

return [
    'title'           => 'Keuangan',
    'company_expense' => 'Pengeluaran Perusahaan',

    // Dipetakan dari App\Models\CompanyExpense::CATEGORIES (nilai database TIDAK diubah)
    'category_labels' => [
        'BUSINESS_TRAVEL'        => 'Perjalanan Dinas',
        'TRANSPORT'              => 'Transportasi',
        'ACCOMMODATION'          => 'Akomodasi',
        'MEALS'                  => 'Konsumsi',
        'OFFICE_SUPPLIES'        => 'Perlengkapan Kantor',
        'VENDOR_PAYMENT'         => 'Pembayaran Vendor',
        'EMPLOYEE_REIMBURSEMENT' => 'Reimbursement Pegawai',
        'OTHER'                  => 'Lainnya',
    ],

    // Dipetakan dari App\Models\CompanyExpense::STATUSES (nilai database TIDAK diubah)
    'status_labels' => [
        'DRAFT'     => 'Draf',
        'SUBMITTED' => 'Diajukan',
        'APPROVED'  => 'Disetujui',
        'REJECTED'  => 'Ditolak',
        'PAID'      => 'Dibayar',
    ],
];
