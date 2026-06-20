<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\PayrollRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PayrollController extends Controller
{
    private const VISIBLE_STATUSES = ['CALCULATED', 'HR_REVIEW', 'FINANCE_APPROVAL', 'LOCKED', 'PAID'];

    public function index(Request $request): View
    {
        $employee = $request->user()->employee;

        abort_unless($employee !== null, 403);

        $records = PayrollRecord::where('employee_id', $employee->id)
            ->whereHas('payrollPeriod', fn ($q) => $q->whereIn('status', self::VISIBLE_STATUSES))
            ->with(['payrollPeriod'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('payroll.my.index', compact('records'));
    }

    public function show(PayrollRecord $payrollRecord): View
    {
        $payrollRecord->load('payrollPeriod');

        Gate::authorize('view', $payrollRecord);

        $payrollRecord->load(['employee.user', 'employee.position', 'employee.department']);

        return view('payroll.my.show', compact('payrollRecord'));
    }

    public function printSlip(PayrollRecord $payrollRecord): View
    {
        $payrollRecord->load('payrollPeriod');

        Gate::authorize('print', $payrollRecord);

        $payrollRecord->load(['employee.user', 'employee.position', 'employee.department']);

        return view('payroll.my.print', compact('payrollRecord'));
    }
}
