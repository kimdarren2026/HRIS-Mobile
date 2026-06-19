<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceApprovalController extends Controller
{
    public function index(): View
    {
        $pending = AttendanceRecord::with(['employee.user', 'employee.department'])
            ->where('status', 'PENDING_REVIEW')
            ->orderByDesc('check_in_time')
            ->paginate(20);

        return view('pages.hr.approval-queue', compact('pending'));
    }

    public function approve(Request $request, AttendanceRecord $attendanceRecord): RedirectResponse
    {
        abort_unless($attendanceRecord->status === 'PENDING_REVIEW', 422);

        $attendanceRecord->update([
            'status'        => 'APPROVED',
            'approved_by'   => auth()->id(),
            'approved_at'   => now(),
            'approval_note' => $request->input('approval_note'),
        ]);

        AuditLogService::log(
            auth()->user(),
            'approve_attendance',
            'attendance',
            "Attendance #{$attendanceRecord->id} approved by " . auth()->user()->name . '.'
        );

        return back()->with('success', 'Presensi berhasil disetujui.');
    }

    public function reject(Request $request, AttendanceRecord $attendanceRecord): RedirectResponse
    {
        abort_unless($attendanceRecord->status === 'PENDING_REVIEW', 422);

        $request->validate([
            'approval_note' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'approval_note.required' => 'Catatan penolakan wajib diisi.',
            'approval_note.min'      => 'Catatan penolakan minimal 10 karakter.',
            'approval_note.max'      => 'Catatan penolakan maksimal 1000 karakter.',
        ]);

        $attendanceRecord->update([
            'status'        => 'REJECTED',
            'approved_by'   => auth()->id(),
            'approved_at'   => now(),
            'approval_note' => $request->approval_note,
        ]);

        AuditLogService::log(
            auth()->user(),
            'reject_attendance',
            'attendance',
            "Attendance #{$attendanceRecord->id} rejected by " . auth()->user()->name
                . '. Note: ' . $request->approval_note
        );

        return back()->with('success', 'Presensi berhasil ditolak.');
    }
}
