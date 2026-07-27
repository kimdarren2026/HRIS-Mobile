<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\AnnualLeaveEntitlementService;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Phase 58C: opening/transition balance for the annual leave entitlement pool.
 *
 * Annual Leave and Personal Leave share ONE 18-day/year pool (they already
 * share one monthly cap in LeaveService) — there is exactly one editable
 * balance per employee per year here, always keyed by the canonical
 * annual-entitlement leave type (LeaveType::canonicalAnnualEntitlementType()).
 * There is intentionally no per-leave-type selector: offering one would let
 * HR create a second, additive 18-day pool for the same employee/year.
 */
class LeaveBalanceController extends Controller
{
    public function __construct(
        private readonly AnnualLeaveEntitlementService $entitlementService,
    ) {}

    public function index(Request $request): View
    {
        $year = (int) ($request->query('year') ?: now()->year);

        $employees = Employee::with('user')
            ->where('employment_status', 'active')
            ->when($request->query('search'), function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"))
                        ->orWhere('nik', 'like', "%{$search}%");
                });
            })
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        $entitlements = $employees->getCollection()->mapWithKeys(
            fn (Employee $employee) => [$employee->id => $this->entitlementService->calculate($employee, $year)]
        );

        return view('pages.hr.leave-balances.index', compact('employees', 'year', 'entitlements'));
    }

    public function edit(Request $request, Employee $employee): View
    {
        $year = (int) ($request->query('year') ?: now()->year);

        $leaveType = LeaveType::canonicalAnnualEntitlementType();
        abort_if($leaveType === null, 404, 'Tidak ada jenis cuti dengan hak tahunan yang dikonfigurasi.');

        $entitlement = $this->entitlementService->calculate($employee, $year);
        $balance = LeaveBalance::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $year)
            ->first();

        $pooledTypeNames = LeaveType::all()
            ->filter(fn (LeaveType $type) => $type->isAnnualEntitlementType())
            ->pluck('name')
            ->values();

        $hasApprovedUsage = $this->hasApprovedUsage($employee, $year);

        return view('pages.hr.leave-balances.edit', compact(
            'employee', 'year', 'leaveType', 'entitlement', 'balance', 'hasApprovedUsage', 'pooledTypeNames'
        ));
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'year'                  => ['required', 'integer', 'min:2000', 'max:2100'],
            'pre_system_used_days'  => ['required', 'numeric', 'min:0'],
            'opening_adjustment'    => ['nullable', 'numeric'],
            'effective_date'        => ['nullable', 'date'],
            // Every manual opening-balance entry requires a note (policy point
            // D), not only when opening_adjustment is non-zero — recording
            // pre_system_used_days alone is already a manual adjustment.
            'reason'                => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $leaveType = LeaveType::canonicalAnnualEntitlementType();
        abort_if($leaveType === null, 404, 'Tidak ada jenis cuti dengan hak tahunan yang dikonfigurasi.');

        $year = (int) $validated['year'];

        if ($this->hasApprovedUsage($employee, $year)) {
            return back()->withErrors([
                'general' => 'Saldo tahun ini sudah memiliki cuti yang disetujui; penyesuaian saldo awal tidak dapat dilakukan melalui halaman ini.',
            ])->withInput();
        }

        $entitlement = $this->entitlementService->calculate($employee, $year);

        if (! $entitlement['eligible']) {
            return back()->withErrors([
                'general' => 'Pegawai belum memenuhi masa kerja 12 bulan untuk tahun ini; tidak ada hak cuti yang dapat ditetapkan.',
            ])->withInput();
        }

        $total             = $entitlement['final_entitlement'];
        $preSystemUsedDays = (float) $validated['pre_system_used_days'];
        // Opening adjustment: positive ADDS to the balance, negative SUBTRACTS.
        // remaining = entitlement - pre_system_used_days + opening_adjustment.
        $openingAdjustment = (float) ($validated['opening_adjustment'] ?? 0);
        $remaining         = $total - $preSystemUsedDays + $openingAdjustment;
        $usedTotal         = $total - $remaining;

        if ($remaining < 0) {
            return back()->withErrors([
                'pre_system_used_days' => 'Saldo akhir tidak boleh negatif. Total pemakaian melebihi hak cuti tahun ini.',
            ])->withInput();
        }

        if ($remaining > $total) {
            return back()->withErrors([
                'opening_adjustment' => 'Saldo akhir tidak boleh melebihi hak cuti tahun ini tanpa proses exception resmi.',
            ])->withInput();
        }

        $existing = LeaveBalance::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $year)
            ->first();
        $old = $existing?->only([
            'total_quota', 'used', 'remaining', 'pre_system_used_days', 'opening_adjustment', 'effective_date', 'reason',
        ]);

        $balance = LeaveBalance::updateOrCreate(
            ['employee_id' => $employee->id, 'leave_type_id' => $leaveType->id, 'year' => $year],
            [
                'total_quota'           => $total,
                'used'                  => $usedTotal,
                'remaining'             => $remaining,
                'pre_system_used_days'  => $preSystemUsedDays,
                'opening_adjustment'    => $openingAdjustment,
                'effective_date'        => $validated['effective_date'] ?? null,
                'reason'                => $validated['reason'],
                'created_by'            => auth()->id(),
            ]
        );

        AuditLogService::log(
            auth()->user(),
            'set_opening_leave_balance',
            'leave',
            "Saldo awal cuti (pool tahunan) tahun {$year} untuk employee #{$employee->id} ditetapkan oleh " . auth()->user()->name . ": {$validated['reason']}",
            null,
            LeaveBalance::class,
            $balance->id,
            $old,
            $balance->only([
                'total_quota', 'used', 'remaining', 'pre_system_used_days', 'opening_adjustment', 'effective_date', 'reason',
            ]),
        );

        return redirect()
            ->route('hr.leave-balances.edit', ['employee' => $employee->id, 'year' => $year])
            ->with('success', 'Saldo awal cuti berhasil disimpan.');
    }

    // Approved usage against ANY leave type in the shared annual-entitlement
    // pool blocks opening-balance edits — not just the canonical type's own id.
    private function hasApprovedUsage(Employee $employee, int $year): bool
    {
        return LeaveRequest::where('employee_id', $employee->id)
            ->whereIn('leave_type_id', LeaveType::annualEntitlementTypeIds())
            ->where('status', 'APPROVED')
            ->whereYear('start_date', $year)
            ->exists();
    }
}
