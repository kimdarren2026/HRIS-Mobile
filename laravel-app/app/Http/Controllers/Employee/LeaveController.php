<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeaveRequest;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\AnnualLeaveEntitlementService;
use App\Services\AuditLogService;
use App\Services\LeaveService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LeaveController extends Controller
{
    public function __construct(
        private readonly LeaveService $leaveService,
        private readonly NotificationService $notifications,
        private readonly AnnualLeaveEntitlementService $entitlementService,
    ) {}

    public function showRequest(): View
    {
        $employee = auth()->user()->employee;
        abort_if($employee === null, 403);

        $year       = now()->year;
        $leaveTypes = LeaveType::all();
        $balances   = LeaveBalance::with('leaveType')
            ->where('employee_id', $employee->id)
            ->where('year', $year)
            ->get()
            ->keyBy('leave_type_id');

        $annualLeaveEligible = $this->leaveService->isEligibleForAnnualLeave($employee);

        $balanceRows = $balances->map(function (LeaveBalance $balance) use ($employee, $year) {
            // Half-day policy point 5: pending half-day requests hold 1 day
            // each from the displayed remaining balance (no column persisted —
            // see LeaveService::heldHalfDayDays()).
            $held = $this->leaveService->heldHalfDayDays($employee, $balance->leaveType, $year);

            return [
                'label'     => $balance->leaveType->display_name,
                'remaining' => max(0, (int) $balance->remaining - $held),
            ];
        })->values();

        // No approved leave yet this year means no LeaveBalance row exists (it is
        // created lazily on first approval, or by leave:initialize-year). Show
        // the entitlement as a preview instead of persisting a row just because
        // the employee opened this page.
        $hasAnnualBalanceRow = $balances->contains(
            fn (LeaveBalance $balance) => $balance->leaveType->isAnnualEntitlementType()
        );

        // Phase 58C: Annual Leave and Personal Leave share one 18-day pool —
        // always resolve the same canonical type LeaveService::approve() and
        // leave:initialize-year key their balance row by.
        $annualType    = LeaveType::canonicalAnnualEntitlementType();
        $annualSummary = null;

        if ($annualType) {
            $entitlement     = $this->entitlementService->calculate($employee, $year);
            $existingBalance = $balances->get($annualType->id);
            $entitlementDays = $existingBalance ? (int) $existingBalance->total_quota : $entitlement['final_entitlement'];
            $used            = $existingBalance ? (int) $existingBalance->used : 0;
            $remainingRaw    = $existingBalance ? (int) $existingBalance->remaining : $entitlementDays;
            $held            = $this->leaveService->heldHalfDayDays($employee, $annualType, $year);

            $statusLabel = match (true) {
                ! $entitlement['eligible']            => 'Belum Memenuhi Masa Kerja 12 Bulan',
                $entitlement['months_remaining'] < 12 => 'Hak Prorata',
                default                                => 'Hak Penuh',
            };

            $annualSummary = [
                'entitlement'      => $entitlementDays,
                'used'             => $used,
                'held'             => $held,
                'available'        => max(0, $remainingRaw - $held),
                'year'             => $year,
                'eligibility_date' => $entitlement['eligibility_date'],
                'status_label'     => $statusLabel,
                'is_prorata'       => $entitlement['eligible'] && $entitlement['months_remaining'] < 12,
            ];

            if ($annualLeaveEligible && ! $hasAnnualBalanceRow) {
                $balanceRows->push([
                    'label'     => $annualType->display_name,
                    'remaining' => max(0, $entitlementDays - $held),
                ]);
            }
        }

        return view('pages.leave.request', compact(
            'leaveTypes', 'balanceRows', 'annualLeaveEligible', 'year', 'annualSummary'
        ));
    }

    public function store(StoreLeaveRequest $request): RedirectResponse
    {
        $employee = auth()->user()->employee;
        abort_if($employee === null, 403);

        $leaveRequest = $this->leaveService->submit(
            $employee,
            $request->validated(),
            $request->file('attachment')
        );

        AuditLogService::log(
            auth()->user(),
            'create_leave_request',
            'leave',
            "Leave request #{$leaveRequest->id} submitted by " . auth()->user()->name . '.'
        );

        $this->notifications->notifyRoles(
            ['admin_hr', 'super_admin'],
            'Pengajuan cuti perlu ditinjau',
            'Ada pengajuan cuti yang menunggu review HR.',
            'leave',
            '/hr/approval-queue',
            $leaveRequest,
        );

        return redirect('/leave/history')->with('success', 'Pengajuan cuti berhasil dikirim.');
    }

    public function attachment(LeaveRequest $leaveRequest): Response
    {
        Gate::authorize('viewAttachment', $leaveRequest);

        $path = $leaveRequest->attachment_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    public function history(): View
    {
        $employee = auth()->user()->employee;
        abort_if($employee === null, 403);

        $status = request()->query('status');

        $query = $employee->leaveRequests()
            ->with('leaveType')
            ->orderByDesc('created_at');

        if ($status && in_array($status, ['PENDING_HR', 'APPROVED', 'REJECTED'])) {
            $query->where('status', $status);
        }

        $requests = $query->paginate(15);

        return view('pages.leave.history', compact('requests', 'status'));
    }
}
