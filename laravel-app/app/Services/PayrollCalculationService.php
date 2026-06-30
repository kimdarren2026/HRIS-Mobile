<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PayrollCalculationService
{
    // Fallback salary values — employees/positions have no salary column in Phase 7.
    // Replace with a real salary table in a future phase.
    private const DEMO_BASIC_SALARY = 5_000_000.00;
    private const DEMO_ALLOWANCE    = 500_000.00;

    public function calculate(PayrollPeriod $period, User $calculatedBy): void
    {
        // Demo calculation is not real payroll — block it from writing in production.
        if (app()->environment('production')) {
            throw new \RuntimeException('Payroll calculation is unavailable in production. External payroll integration is required.');
        }

        DB::transaction(function () use ($period, $calculatedBy): void {
            $employees = Employee::where('employment_status', 'active')->get();

            foreach ($employees as $employee) {
                $attendanceDays = AttendanceRecord::where('employee_id', $employee->id)
                    ->whereBetween('attendance_date', [$period->start_date, $period->end_date])
                    ->where('status', 'APPROVED')
                    ->count();

                $leaveDays = (float) LeaveRequest::where('employee_id', $employee->id)
                    ->where('status', 'APPROVED')
                    ->where('start_date', '<=', $period->end_date)
                    ->where('end_date', '>=', $period->start_date)
                    ->sum('total_days');

                $grossPay  = self::DEMO_BASIC_SALARY + self::DEMO_ALLOWANCE;
                $netSalary = $grossPay; // No deductions in Phase 7 foundation.

                PayrollRecord::updateOrCreate(
                    [
                        'payroll_period_id' => $period->id,
                        'employee_id'       => $employee->id,
                    ],
                    [
                        'basic_salary'         => self::DEMO_BASIC_SALARY,
                        'allowance'            => self::DEMO_ALLOWANCE,
                        'bonus'                => 0,
                        'overtime'             => 0,
                        'deduction'            => 0,
                        'late_deduction'       => 0,
                        'attendance_deduction' => 0,
                        'tax_bpjs'             => 0,
                        'net_salary'           => $netSalary,
                        'attendance_days'      => $attendanceDays,
                        'leave_days'           => $leaveDays,
                    ]
                );
            }

            $period->update([
                'status'         => 'CALCULATED',
                'calculated_by'  => $calculatedBy->id,
                'calculated_at'  => now(),
            ]);
        });
    }
}
