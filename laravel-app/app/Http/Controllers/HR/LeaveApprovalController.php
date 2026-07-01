<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Services\AuditLogService;
use App\Services\LeaveService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LeaveApprovalController extends Controller
{
    public function __construct(
        private readonly LeaveService $leaveService,
        private readonly NotificationService $notifications,
    ) {}

    public function approve(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        abort_unless($leaveRequest->status === 'PENDING_HR', 422);

        $this->leaveService->approve($leaveRequest, auth()->user(), $request->input('approval_note'));

        AuditLogService::log(
            auth()->user(),
            'approve_leave',
            'leave',
            "Leave #{$leaveRequest->id} approved by " . auth()->user()->name . '.'
        );

        $leaveRequest->loadMissing('employee.user');
        if ($leaveRequest->employee?->user) {
            $this->notifications->create(
                $leaveRequest->employee->user,
                'Cuti disetujui',
                'Pengajuan cuti Anda telah disetujui.',
                'leave',
                '/leave/history',
                $leaveRequest,
            );
        }

        return back()->with('success', 'Pengajuan cuti berhasil disetujui.');
    }

    public function reject(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        abort_unless($leaveRequest->status === 'PENDING_HR', 422);

        $request->validate([
            'approval_note' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'approval_note.required' => 'Alasan penolakan wajib diisi.',
            'approval_note.min'      => 'Alasan penolakan minimal 10 karakter.',
        ]);

        $this->leaveService->reject($leaveRequest, auth()->user(), $request->approval_note);

        AuditLogService::log(
            auth()->user(),
            'reject_leave',
            'leave',
            "Leave #{$leaveRequest->id} rejected by " . auth()->user()->name
                . '. Note: ' . $request->approval_note
        );

        $leaveRequest->loadMissing('employee.user');
        if ($leaveRequest->employee?->user) {
            $this->notifications->create(
                $leaveRequest->employee->user,
                'Cuti ditolak',
                'Pengajuan cuti Anda telah ditolak.',
                'leave',
                '/leave/history',
                $leaveRequest,
            );
        }

        return back()->with('success', 'Pengajuan cuti berhasil ditolak.');
    }
}
