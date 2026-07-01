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

        $leaveTypes = LeaveType::all();
        $balances   = LeaveBalance::with('leaveType')
            ->where('employee_id', $employee->id)
            ->where('year', now()->year)
            ->get()
            ->keyBy('leave_type_id');

        return view('pages.leave.request', compact('leaveTypes', 'balances'));
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
