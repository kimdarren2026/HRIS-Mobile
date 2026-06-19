<?php

use Illuminate\Support\Facades\Route;

$screens = [
    ['label' => '01 Login', 'uri' => '/login', 'view' => 'pages.auth.login'],
    ['label' => '02 Employee Dashboard', 'uri' => '/employee/dashboard', 'view' => 'pages.employee.dashboard'],
    ['label' => '03A Attendance Check-in Within Radius', 'uri' => '/attendance/checkin', 'view' => 'pages.attendance.checkin'],
    ['label' => '03B Attendance Check-in Outside Radius', 'uri' => '/attendance/checkin-outside', 'view' => 'pages.attendance.checkin-outside'],
    ['label' => '04 Attendance History', 'uri' => '/attendance/history', 'view' => 'pages.attendance.history'],
    ['label' => '05 Leave Request', 'uri' => '/leave/request', 'view' => 'pages.leave.request'],
    ['label' => '06 Leave History', 'uri' => '/leave/history', 'view' => 'pages.leave.history'],
    ['label' => '07 Payslip Detail', 'uri' => '/payslip/detail', 'view' => 'pages.payslip.detail'],
    ['label' => '08 HR Approval Queue', 'uri' => '/hr/approval-queue', 'view' => 'pages.hr.approval-queue'],
    ['label' => '09 Employee Management', 'uri' => '/hr/employees', 'view' => 'pages.hr.employees'],
    ['label' => '10 Payroll Periods', 'uri' => '/payroll/periods', 'view' => 'pages.payroll.periods'],
    ['label' => '11 Reports & Analytics', 'uri' => '/reports', 'view' => 'pages.reports.index'],
    ['label' => '12 Employee Profile', 'uri' => '/profile', 'view' => 'pages.profile.show'],
    ['label' => '13 Admin HR Dashboard', 'uri' => '/admin/dashboard', 'view' => 'pages.admin.dashboard'],
    ['label' => '14 Finance Payroll Dashboard', 'uri' => '/finance/dashboard', 'view' => 'pages.finance.dashboard'],
    ['label' => '15 System Settings', 'uri' => '/settings', 'view' => 'pages.settings.index'],
];

Route::redirect('/', '/login');

foreach ($screens as $screen) {
    Route::get($screen['uri'], fn () => view($screen['view']));
}

Route::get('/preview', fn () => view('pages.preview.index', [
    'screens' => $screens,
]));
