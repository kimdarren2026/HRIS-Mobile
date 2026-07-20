<?php

return [
    'title'   => 'Penggajian',
    'payslip' => 'Slip Gaji',
    'period'  => 'Periode Penggajian',

    // Dipetakan dari PayrollPeriod::status (nilai database TIDAK diubah)
    'status_labels' => [
        'DRAFT'             => 'Draf',
        'CALCULATED'        => 'Terhitung',
        'HR_REVIEW'         => 'Tinjauan HR',
        'FINANCE_APPROVAL'  => 'Persetujuan Keuangan',
        'LOCKED'            => 'Terkunci',
        'PAID'              => 'Dibayar',
    ],
];
