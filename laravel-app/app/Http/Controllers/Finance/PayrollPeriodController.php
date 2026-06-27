<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePayrollPeriodRequest;
use App\Models\PayrollPeriod;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use App\Services\PayrollCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollPeriodController extends Controller
{
    public function __construct(
        private readonly PayrollCalculationService $calculationService,
        private readonly NotificationService $notifications,
    ) {}

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

    public function markPaid(Request $request, PayrollPeriod $payrollPeriod): RedirectResponse
    {
        Gate::authorize('markPaid', $payrollPeriod);

        abort_if($payrollPeriod->status === 'PAID', 422, 'Payroll period has already been marked as paid.');

        $data = $request->validate([
            'payment_reference' => ['required', 'string', 'max:100'],
            'payment_date'      => ['required', 'date'],
        ]);

        $old = $payrollPeriod->only(['status']);

        $payrollPeriod->update([
            'status'            => 'PAID',
            'paid_by'           => auth()->id(),
            'paid_at'           => now(),
            'payment_reference' => $data['payment_reference'],
            'payment_date'      => $data['payment_date'],
        ]);

        AuditLogService::log(
            auth()->user(),
            'mark_payroll_paid',
            'payroll',
            "Payroll period '{$payrollPeriod->name}' marked as paid. Ref: {$data['payment_reference']} Date: {$data['payment_date']}.",
            null,
            \App\Models\PayrollPeriod::class,
            $payrollPeriod->id,
            $old,
            ['status' => 'PAID', 'paid_by' => auth()->id(), 'payment_reference' => $data['payment_reference'], 'payment_date' => $data['payment_date']],
        );

        $payrollPeriod->loadMissing('payrollRecords.payslip', 'payrollRecords.employee.user');
        foreach ($payrollPeriod->payrollRecords as $record) {
            if ($record->payslip) {
                $record->payslip->update([
                    'payment_status'    => 'PAID',
                    'paid_at'           => now(),
                    'payment_reference' => $data['payment_reference'],
                ]);
            }
            if ($record->employee?->user) {
                $this->notifications->create(
                    $record->employee->user,
                    'Payslip available',
                    "Your payslip for {$payrollPeriod->name} is available.",
                    'payroll',
                    route('my.payroll.show', $record, false),
                    $record,
                );
            }
        }

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

    // Export bank payment list for manual bank transfer — finance/super_admin only.
    // Contains sensitive bank account data; served as streamed download, never stored publicly.
    public function exportPayments(PayrollPeriod $payrollPeriod): StreamedResponse
    {
        Gate::authorize('exportPayments', $payrollPeriod);

        $payrollPeriod->load([
            'payrollRecords.employee.user',
            'payrollRecords.employee.department',
            'payrollRecords.employee.position',
        ]);

        $filename = "payment-list-{$payrollPeriod->id}-" . now()->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($payrollPeriod): void {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'Employee ID', 'Employee Name', 'Department', 'Position',
                'Bank Name', 'Bank Account Number', 'Bank Account Name',
                'Net Salary', 'Payroll Period', 'Payment Reference',
            ]);

            foreach ($payrollPeriod->payrollRecords as $record) {
                $emp = $record->employee;
                fputcsv($out, [
                    $emp->nik ?? '',
                    $emp->user?->name ?? '',
                    $emp->department?->name ?? '',
                    $emp->position?->name ?? '',
                    $emp->bank_name ?? '',
                    $emp->bank_account_number ?? '',
                    $emp->user?->name ?? '',
                    $record->net_salary,
                    $payrollPeriod->name,
                    '',
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
