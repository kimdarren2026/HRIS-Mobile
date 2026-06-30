<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveService
{
    public function submit(Employee $employee, array $data, ?UploadedFile $attachment = null): LeaveRequest
    {
        $startDate = Carbon::parse($data['start_date']);
        $endDate   = Carbon::parse($data['end_date']);
        $totalDays = $startDate->diffInDays($endDate) + 1;

        $overlaps = LeaveRequest::where('employee_id', $employee->id)
            ->whereIn('status', ['PENDING_HR', 'APPROVED'])
            ->where('start_date', '<=', $endDate->toDateString())
            ->where('end_date', '>=', $startDate->toDateString())
            ->exists();

        if ($overlaps) {
            throw ValidationException::withMessages([
                'start_date' => 'You already have a pending or approved leave request that overlaps with this period.',
            ]);
        }

        $attachmentPath = $attachment?->store('leave-attachments', 'local');

        return LeaveRequest::create([
            'employee_id'    => $employee->id,
            'leave_type_id'  => $data['leave_type_id'],
            'start_date'     => $startDate->toDateString(),
            'end_date'       => $endDate->toDateString(),
            'total_days'     => $totalDays,
            'reason'         => $data['reason'],
            'attachment_path'=> $attachmentPath,
            'status'         => 'PENDING_HR',
        ]);
    }

    public function approve(LeaveRequest $leaveRequest, User $approver, ?string $note): void
    {
        // Idempotency guard: prevent double-approval from causing double deduction
        if ($leaveRequest->status === 'APPROVED') {
            return;
        }

        DB::transaction(function () use ($leaveRequest, $approver, $note): void {
            if ($leaveRequest->leaveType->deducts_balance) {
                $balance = LeaveBalance::firstOrCreate(
                    [
                        'employee_id'   => $leaveRequest->employee_id,
                        'leave_type_id' => $leaveRequest->leave_type_id,
                        'year'          => $leaveRequest->start_date->year,
                    ],
                    ['total_quota' => 12, 'used' => 0, 'remaining' => 12]
                );

                if ($balance->remaining < $leaveRequest->total_days) {
                    throw ValidationException::withMessages([
                        'balance' => 'Saldo cuti tidak mencukupi untuk permintaan ini.',
                    ]);
                }

                $balance->increment('used', $leaveRequest->total_days);
                $balance->decrement('remaining', $leaveRequest->total_days);
            }

            $leaveRequest->update([
                'status'        => 'APPROVED',
                'approved_by'   => $approver->id,
                'approved_at'   => now(),
                'approval_note' => $note,
            ]);
        });
    }

    public function reject(LeaveRequest $leaveRequest, User $approver, string $note): void
    {
        $leaveRequest->update([
            'status'        => 'REJECTED',
            'approved_by'   => $approver->id,
            'approved_at'   => now(),
            'approval_note' => $note,
        ]);
    }
}
