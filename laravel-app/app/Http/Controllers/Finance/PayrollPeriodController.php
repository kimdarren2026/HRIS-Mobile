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

        $payrollPeriod->update(['status' => 'HR_REVIEW']);

        AuditLogService::log(
            auth()->user(),
            'submit_payroll_hr_review',
            'payroll',
            "Payroll period '{$payrollPeriod->name}' submitted for HR review by " . auth()->user()->name . '.'
        );

        return redirect('/payroll/periods')->with('success', "Payroll '{$payrollPeriod->name}' submitted for HR review.");
    }
}
