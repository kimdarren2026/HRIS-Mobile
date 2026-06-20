<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Services\AuditLogService;
use App\Services\LeaveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LeaveApprovalController extends Controller
{
    public function __construct(private readonly LeaveService $leaveService) {}

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

        return back()->with('success', 'Leave request approved.');
    }

    public function reject(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        abort_unless($leaveRequest->status === 'PENDING_HR', 422);

        $request->validate([
            'approval_note' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'approval_note.required' => 'Rejection reason is required.',
            'approval_note.min'      => 'Rejection reason must be at least 10 characters.',
        ]);

        $this->leaveService->reject($leaveRequest, auth()->user(), $request->approval_note);

        AuditLogService::log(
            auth()->user(),
            'reject_leave',
            'leave',
            "Leave #{$leaveRequest->id} rejected by " . auth()->user()->name
                . '. Note: ' . $request->approval_note
        );

        return back()->with('success', 'Leave request rejected.');
    }
}
