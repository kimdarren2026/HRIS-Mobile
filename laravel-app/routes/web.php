<?php

use App\Http\Controllers\AuthController;
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

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,15')
    ->name('login.attempt');
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'role:employee'])->group(function (): void {
    Route::view('/employee/dashboard', 'pages.employee.dashboard');
    Route::view('/attendance/checkin', 'pages.attendance.checkin');
    Route::view('/attendance/checkin-outside', 'pages.attendance.checkin-outside');
    Route::view('/attendance/history', 'pages.attendance.history');
    Route::view('/leave/request', 'pages.leave.request');
    Route::view('/leave/history', 'pages.leave.history');
    Route::view('/payslip/detail', 'pages.payslip.detail');
});

Route::middleware(['auth', 'role:admin_hr,super_admin'])->group(function (): void {
    Route::view('/admin/dashboard', 'pages.admin.dashboard');
    Route::view('/hr/approval-queue', 'pages.hr.approval-queue');
    Route::view('/hr/employees', 'pages.hr.employees');
    Route::view('/settings', 'pages.settings.index');
});

Route::middleware(['auth', 'role:finance,super_admin'])->group(function (): void {
    Route::view('/finance/dashboard', 'pages.finance.dashboard');
    Route::view('/payroll/periods', 'pages.payroll.periods');
});

Route::middleware(['auth', 'role:admin_hr,finance,super_admin'])->group(function (): void {
    Route::view('/reports', 'pages.reports.index');
});

Route::middleware(['auth', 'role:employee,admin_hr,finance,super_admin'])->group(function (): void {
    Route::view('/profile', 'pages.profile.show');
});

Route::get('/preview', fn () => view('pages.preview.index', [
    'screens' => $screens,
]));
