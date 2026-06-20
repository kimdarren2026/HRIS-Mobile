<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const PAYROLL_VISIBLE_STATUSES = ['CALCULATED', 'HR_REVIEW', 'FINANCE_APPROVAL', 'LOCKED', 'PAID'];

    public function adminDashboard(): View
    {
        $totalEmployees     = Employee::count();
        $pendingLeave       = LeaveRequest::where('status', 'PENDING_HR')->count();
        $pendingAttendance  = AttendanceRecord::where('status', 'PENDING_REVIEW')->count();
        $approvedAttendance = AttendanceRecord::where('status', 'APPROVED')->count();
        $rejectedAttendance = AttendanceRecord::where('status', 'REJECTED')->count();
        $approvedLeave      = LeaveRequest::where('status', 'APPROVED')->count();
        $rejectedLeave      = LeaveRequest::where('status', 'REJECTED')->count();
        $latestPeriod       = PayrollPeriod::latest()->first();

        return view('pages.admin.dashboard', compact(
            'totalEmployees',
            'pendingLeave',
            'pendingAttendance',
            'approvedAttendance',
            'rejectedAttendance',
            'approvedLeave',
            'rejectedLeave',
            'latestPeriod',
        ));
    }

    public function financeDashboard(): View
    {
        $statuses  = ['DRAFT', 'CALCULATED', 'HR_REVIEW', 'FINANCE_APPROVAL', 'LOCKED', 'PAID'];
        $rawCounts = PayrollPeriod::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statusCounts   = collect($statuses)->mapWithKeys(fn ($s) => [$s => (int) ($rawCounts[$s] ?? 0)]);
        $latestPeriods  = PayrollPeriod::latest()->limit(3)->get();
        $totalEmployees = Employee::count();

        return view('pages.finance.dashboard', compact('statusCounts', 'latestPeriods', 'totalEmployees'));
    }

    public function employeeDashboard(): View
    {
        $employee          = auth()->user()->employee;
        $todayRecord       = null;
        $latestLeave       = null;
        $latestPayroll     = null;
        $leaveRemaining    = 0;
        $pendingLeaveCount = 0;

        if ($employee) {
            $todayRecord = AttendanceRecord::where('employee_id', $employee->id)
                ->whereDate('attendance_date', today())
                ->first();
            $latestLeave = LeaveRequest::where('employee_id', $employee->id)
                ->latest()->first();
            $latestPayroll = PayrollRecord::where('employee_id', $employee->id)
                ->whereHas('payrollPeriod', fn ($q) => $q->whereIn('status', self::PAYROLL_VISIBLE_STATUSES))
                ->with(['payrollPeriod'])
                ->latest()->first();
            $leaveRemaining = (int) LeaveBalance::where('employee_id', $employee->id)
                ->where('year', now()->year)
                ->sum('remaining');
            $pendingLeaveCount = LeaveRequest::where('employee_id', $employee->id)
                ->where('status', 'PENDING_HR')
                ->count();
        }

        return view('pages.employee.dashboard', compact(
            'employee',
            'todayRecord',
            'latestLeave',
            'latestPayroll',
            'leaveRemaining',
            'pendingLeaveCount',
        ));
    }
}
