<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeaveRequest;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
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
        // created lazily on first approval). Show the entitlement as a preview
        // instead of persisting a row just because the employee opened this page.
        $hasAnnualBalanceRow = $balances->contains(
            fn (LeaveBalance $balance) => $balance->leaveType->isAnnualEntitlementType()
        );

        if ($annualLeaveEligible && ! $hasAnnualBalanceRow) {
            $annualType = $leaveTypes->first(fn (LeaveType $type) => $type->isAnnualEntitlementType());

            if ($annualType) {
                $held = $this->leaveService->heldHalfDayDays($employee, $annualType, $year);

                $balanceRows->push([
                    'label'     => $annualType->display_name,
                    'remaining' => max(0, LeaveBalance::DEFAULT_ANNUAL_QUOTA - $held),
                ]);
            }
        }

        return view('pages.leave.request', compact(
            'leaveTypes', 'balanceRows', 'annualLeaveEligible', 'year'
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
