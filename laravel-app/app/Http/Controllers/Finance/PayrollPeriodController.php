<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePayrollPeriodRequest;
use App\Models\PayrollPeriod;
use App\Services\AuditLogService;
use App\Services\PayrollCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollPeriodController extends Controller
{
    public function __construct(private readonly PayrollCalculationService $calculationService) {}

    public function index(): View
    {
        Gate::authorize('viewAny', PayrollPeriod::class);

        $periods = PayrollPeriod::with(['creator', 'payrollRecords'])
            ->orderByDesc('start_date')
            ->paginate(15);

        $summary = [
            'total_periods'    => PayrollPeriod::count(),
            'draft_count'      => PayrollPeriod::where('status', 'DRAFT')->count(),
            'calculated_count' => PayrollPeriod::where('status', 'CALCULATED')->count(),
        ];

        return view('pages.payroll.periods', compact('periods', 'summary'));
    }

    public function store(StorePayrollPeriodRequest $request): RedirectResponse
    {
        $period = PayrollPeriod::create([
            ...$request->validated(),
            'status'     => 'DRAFT',
            'created_by' => auth()->id(),
        ]);

        AuditLogService::log(
            auth()->user(),
            'create_payroll_period',
            'payroll',
            "Payroll period '{$period->name}' created by " . auth()->user()->name . '.'
        );

        return redirect('/payroll/periods')->with('success', "Payroll period '{$period->name}' created.");
    }

    public function show(PayrollPeriod $payrollPeriod): View
    {
        Gate::authorize('view', $payrollPeriod);

        $payrollPeriod->load(['creator', 'payrollRecords.employee.user', 'payrollRecords.employee.department']);

        $totals = [
            'employee_count' => $payrollPeriod->payrollRecords->count(),
            'gross_pay'      => $payrollPeriod->payrollRecords->sum(
                fn ($r) => (float) $r->basic_salary + (float) $r->allowance
            ),
            'net_pay'        => $payrollPeriod->payrollRecords->sum(fn ($r) => (float) $r->net_salary),
        ];

        return view('pages.payroll.period-show', compact('payrollPeriod', 'totals'));
    }

    public function calculate(PayrollPeriod $payrollPeriod): RedirectResponse
    {
        Gate::authorize('calculate', $payrollPeriod);

        $this->calculationService->calculate($payrollPeriod, auth()->user());

        AuditLogService::log(
            auth()->user(),
            'calculate_payroll',
            'payroll',
            "Payroll period '{$payrollPeriod->name}' calculated by " . auth()->user()->name . '.'
        );

        return redirect('/payroll/periods')->with('success', "Payroll '{$payrollPeriod->name}' calculated successfully.");
    }

    public function submitHrReview(PayrollPeriod $payrollPeriod): RedirectResponse
    {
        Gate::authorize('submitHrReview', $payrollPeriod);

        $payrollPeriod->update([
            'status'      => 'HR_REVIEW',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        AuditLogService::log(
            auth()->user(),
            'submit_payroll_hr_review',
            'payroll',
            "Payroll period '{$payrollPeriod->name}' submitted for HR review by " . auth()->user()->name . '.'
        );

        return redirect('/payroll/periods')->with('success', "Payroll '{$payrollPeriod->name}' submitted for HR review.");
    }

    public function financeApprove(PayrollPeriod $payrollPeriod): RedirectResponse
    {
        Gate::authorize('financeApprove', $payrollPeriod);

        $payrollPeriod->update([
            'status'      => 'FINANCE_APPROVAL',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        AuditLogService::log(
            auth()->user(),
            'finance_approve_payroll',
            'payroll',
            "Payroll period '{$payrollPeriod->name}' approved by finance: " . auth()->user()->name . '.'
        );

        return redirect('/payroll/periods')->with('success', "Payroll '{$payrollPeriod->name}' approved by finance.");
    }

    public function lock(PayrollPeriod $payrollPeriod): RedirectResponse
    {
        Gate::authorize('lock', $payrollPeriod);

        $payrollPeriod->update([
            'status'    => 'LOCKED',
            'locked_by' => auth()->id(),
            'locked_at' => now(),
        ]);

        AuditLogService::log(
            auth()->user(),
            'lock_payroll',
            'payroll',
            "Payroll period '{$payrollPeriod->name}' locked by " . auth()->user()->name . '.'
        );

        return redirect('/payroll/periods')->with('success', "Payroll '{$payrollPeriod->name}' locked.");
    }

    public function markPaid(PayrollPeriod $payrollPeriod): RedirectResponse
    {
        Gate::authorize('markPaid', $payrollPeriod);

        $payrollPeriod->update([
            'status'  => 'PAID',
            'paid_by' => auth()->id(),
            'paid_at' => now(),
        ]);

        AuditLogService::log(
            auth()->user(),
            'mark_payroll_paid',
            'payroll',
            "Payroll period '{$payrollPeriod->name}' marked as paid by " . auth()->user()->name . '.'
        );

        return redirect('/payroll/periods')->with('success', "Payroll '{$payrollPeriod->name}' marked as paid.");
    }

    public function export(PayrollPeriod $payrollPeriod): StreamedResponse
    {
        Gate::authorize('export', $payrollPeriod);

        $payrollPeriod->load('payrollRecords.employee.user');

        $filename = "payroll-period-{$payrollPeriod->id}.csv";

        return response()->streamDownload(function () use ($payrollPeriod): void {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'Employee Name', 'Employee Number', 'Attendance Days', 'Leave Days',
                'Basic Salary', 'Allowance', 'Deduction', 'Net Salary', 'Period Status',
            ]);

            foreach ($payrollPeriod->payrollRecords as $record) {
                fputcsv($out, [
                    $record->employee->user?->name ?? '',
                    $record->employee->nik ?? '',
                    $record->attendance_days,
                    $record->leave_days,
                    $record->basic_salary,
                    $record->allowance,
                    $record->deduction,
                    $record->net_salary,
                    $payrollPeriod->status,
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
