<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Employee\AttendanceController;
use App\Http\Controllers\Employee\LeaveController;
use App\Http\Controllers\Employee\PayrollController;
use App\Http\Controllers\Employee\ProfileController;
use App\Http\Controllers\Finance\PayrollPeriodController;
use App\Http\Controllers\HR\AttendanceApprovalController;
use App\Http\Controllers\HR\EmployeeController as HREmployeeController;
use App\Http\Controllers\HR\LeaveApprovalController;
use Illuminate\Support\Facades\Route;

// ── Preview (design reference, no auth) ────────────────────────────────────
$screens = [
    ['label' => '01 Login',                                     'uri' => '/login',                  'view' => 'pages.auth.login'],
    ['label' => '02 Employee Dashboard',                        'uri' => '/employee/dashboard',     'view' => 'pages.employee.dashboard'],
    ['label' => '03A Attendance Check-in Within Radius',        'uri' => '/attendance/checkin',     'view' => 'pages.attendance.checkin'],
    ['label' => '03B Attendance Check-in Outside Radius',       'uri' => '/attendance/checkin-outside', 'view' => 'pages.attendance.checkin-outside'],
    ['label' => '04 Attendance History',                        'uri' => '/attendance/history',     'view' => 'pages.attendance.history'],
    ['label' => '05 Leave Request',                             'uri' => '/leave/request',          'view' => 'pages.leave.request'],
    ['label' => '06 Leave History',                             'uri' => '/leave/history',          'view' => 'pages.leave.history'],
    ['label' => '07 Payslip Detail',                            'uri' => '/payslip/detail',         'view' => 'pages.payslip.detail'],
    ['label' => '08 HR Approval Queue',                         'uri' => '/hr/approval-queue',      'view' => 'pages.hr.approval-queue'],
    ['label' => '09 Employee Management',                       'uri' => '/hr/employees',           'view' => 'pages.hr.employees'],
    ['label' => '10 Payroll Periods',                           'uri' => '/payroll/periods',        'view' => 'pages.payroll.periods'],
    ['label' => '11 Reports & Analytics',                       'uri' => '/reports',                'view' => 'pages.reports.index'],
    ['label' => '12 Employee Profile',                          'uri' => '/profile',                'view' => 'pages.profile.show'],
    ['label' => '13 Admin HR Dashboard',                        'uri' => '/admin/dashboard',        'view' => 'pages.admin.dashboard'],
    ['label' => '14 Finance Payroll Dashboard',                 'uri' => '/finance/dashboard',      'view' => 'pages.finance.dashboard'],
    ['label' => '15 System Settings',                           'uri' => '/settings',               'view' => 'pages.settings.index'],
];

Route::redirect('/', '/login');

// ── Auth routes ─────────────────────────────────────────────────────────────
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,15')
    ->name('login.attempt');
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ── Protected selfie + leave attachment (auth only; policy in controller) ────
Route::middleware('auth')->group(function (): void {
    Route::get('/attendance/photo/{attendanceRecord}', [AttendanceController::class, 'photo']);
    Route::get('/leave/attachment/{leaveRequest}', [LeaveController::class, 'attachment'])
        ->name('leave.attachment');
});

// ── Employee routes ──────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:employee'])->group(function (): void {
    Route::get('/employee/dashboard', [DashboardController::class, 'employeeDashboard']);

    // Attendance — functional (Phase 5)
    Route::get('/attendance/checkin',         [AttendanceController::class, 'showCheckIn']);
    Route::get('/attendance/checkin-outside', [AttendanceController::class, 'showCheckIn']);
    Route::post('/attendance/check-in',       [AttendanceController::class, 'checkIn'])
        ->middleware('throttle:10,1');
    Route::get('/attendance/history', [AttendanceController::class, 'history']);

    // Leave — functional (Phase 6)
    Route::get('/leave/request',  [LeaveController::class, 'showRequest']);
    Route::post('/leave/request', [LeaveController::class, 'store'])
        ->middleware('throttle:10,1');
    Route::get('/leave/history',  [LeaveController::class, 'history']);

    // Payroll — employee self-service (Phase 8)
    Route::get('/my/payroll',                        [PayrollController::class, 'index'])->name('my.payroll.index');
    Route::get('/my/payroll/{payrollRecord}',         [PayrollController::class, 'show'])->name('my.payroll.show');
    Route::get('/my/payroll/{payrollRecord}/print',   [PayrollController::class, 'printSlip'])->name('my.payroll.print');

    // Employee self-profile (Phase 11)
    Route::get('/my/profile', [ProfileController::class, 'show'])->name('my.profile');

    // Static views (Phase 1-4, preserved)
    Route::view('/payslip/detail',  'pages.payslip.detail');
});

// ── HR / Super Admin routes ──────────────────────────────────────────────────
Route::middleware(['auth', 'role:admin_hr,super_admin'])->group(function (): void {
    Route::get('/admin/dashboard', [DashboardController::class, 'adminDashboard']);

    // Attendance approval — functional (Phase 5)
    Route::get('/hr/approval-queue',                           [AttendanceApprovalController::class, 'index']);
    Route::post('/hr/attendance/{attendanceRecord}/approve',   [AttendanceApprovalController::class, 'approve']);
    Route::post('/hr/attendance/{attendanceRecord}/reject',    [AttendanceApprovalController::class, 'reject']);

    // Leave approval — functional (Phase 6)
    Route::post('/hr/leave/{leaveRequest}/approve', [LeaveApprovalController::class, 'approve']);
    Route::post('/hr/leave/{leaveRequest}/reject',  [LeaveApprovalController::class, 'reject']);

    // Payroll HR review — submit CALCULATED → HR_REVIEW (admin_hr + super_admin)
    Route::post('/payroll/periods/{payrollPeriod}/submit-hr-review', [PayrollPeriodController::class, 'submitHrReview'])->name('payroll.periods.submit-hr-review');

    // Employee master data (Phase 11)
    Route::get('/employees',                 [HREmployeeController::class, 'index'])->name('employees.index');
    Route::get('/employees/create',          [HREmployeeController::class, 'create'])->name('employees.create');
    Route::post('/employees',                [HREmployeeController::class, 'store'])->name('employees.store');
    Route::get('/employees/{employee}',      [HREmployeeController::class, 'show'])->name('employees.show');
    Route::get('/employees/{employee}/edit', [HREmployeeController::class, 'edit'])->name('employees.edit');
    Route::put('/employees/{employee}',      [HREmployeeController::class, 'update'])->name('employees.update');

    // Static views (Phase 1-4, preserved)
    Route::view('/hr/employees', 'pages.hr.employees');
    Route::view('/settings',     'pages.settings.index');
});

// ── Finance / Super Admin routes ─────────────────────────────────────────────
Route::middleware(['auth', 'role:finance,super_admin'])->group(function (): void {
    Route::get('/finance/dashboard', [DashboardController::class, 'financeDashboard']);

    // Payroll management — create/calculate/approve/lock/pay/export restricted to finance + super_admin
    Route::post('/payroll/periods',                                        [PayrollPeriodController::class, 'store'])->name('payroll.periods.store');
    Route::post('/payroll/periods/{payrollPeriod}/calculate',              [PayrollPeriodController::class, 'calculate'])->name('payroll.periods.calculate');
    Route::post('/payroll/periods/{payrollPeriod}/finance-approve',        [PayrollPeriodController::class, 'financeApprove'])->name('payroll.periods.finance-approve');
    Route::post('/payroll/periods/{payrollPeriod}/lock',                   [PayrollPeriodController::class, 'lock'])->name('payroll.periods.lock');
    Route::post('/payroll/periods/{payrollPeriod}/mark-paid',              [PayrollPeriodController::class, 'markPaid'])->name('payroll.periods.mark-paid');
    Route::get('/payroll/periods/{payrollPeriod}/export',                  [PayrollPeriodController::class, 'export'])->name('payroll.periods.export');
});

// ── Payroll view — finance, super_admin, admin_hr (review) ───────────────────
Route::middleware(['auth', 'role:admin_hr,finance,super_admin'])->group(function (): void {
    Route::get('/payroll/periods',                [PayrollPeriodController::class, 'index'])->name('payroll.periods.index');
    Route::get('/payroll/periods/{payrollPeriod}', [PayrollPeriodController::class, 'show'])->name('payroll.periods.show');
});

// ── HR + Finance + Super Admin ───────────────────────────────────────────────
Route::middleware(['auth', 'role:admin_hr,finance,super_admin'])->group(function (): void {
    Route::view('/reports', 'pages.reports.index');
});

// ── All authenticated users ───────────────────────────────────────────────────
Route::middleware(['auth', 'role:employee,admin_hr,finance,super_admin'])->group(function (): void {
    Route::view('/profile', 'pages.profile.show');
});

// ── Design preview (unauthenticated) ────────────────────────────────────────
Route::get('/preview', fn () => view('pages.preview.index', ['screens' => $screens]));
