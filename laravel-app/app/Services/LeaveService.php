<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveService
{
    // STIKES Advaita policy point 1 & 5: Personal Leave is chargeable against
    // the same annual entitlement as Annual Leave. Matched case-insensitively
    // by name since leave types have no dedicated "category" field. NOTE:
    // ProductionSeeder seeds a type named "Permission" (not "Personal Leave"),
    // so that seeder's data is NOT recognized as annual-entitlement here.
    private const ANNUAL_ENTITLEMENT_TYPE_NAMES = ['annual leave', 'personal leave'];

    // Policy point 1: annual leave applies only after 12 consecutive months of service.
    private const MIN_SERVICE_MONTHS = 12;

    // Policy point 1: max 2 chargeable working days per calendar month without special approval.
    private const MONTHLY_WORKING_DAY_CAP = 2;

    public function __construct(
        private readonly WorkingDayCalculator $calculator = new WorkingDayCalculator(),
    ) {}

    public function submit(Employee $employee, array $data, ?UploadedFile $attachment = null): LeaveRequest
    {
        $startDate = Carbon::parse($data['start_date']);
        $endDate   = Carbon::parse($data['end_date']);
        $totalDays = $startDate->diffInDays($endDate) + 1;

        // Policy point 6: half-day leave is not supported. start_date/end_date
        // are date-only columns and diffInDays always yields a whole number, so
        // this can never actually trip today — it documents/guards the invariant
        // in case a time-of-day or duration-fraction input is ever added later.
        if ($totalDays != floor($totalDays)) {
            throw ValidationException::withMessages([
                'start_date' => 'Tidak tersedia cuti setengah hari.',
            ]);
        }

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

        $leaveType = LeaveType::findOrFail($data['leave_type_id']);

        $chargeableDays = $this->calculator->countChargeableDays(
            $startDate,
            $endDate,
            ! $leaveType->counts_calendar_days,
        );

        if ($this->isAnnualEntitlementType($leaveType)) {
            $this->assertEligibleForAnnualLeave($employee, $startDate);
            $this->assertWithinMonthlyCap($employee, $startDate, $endDate);
        }

        $attachmentPath = $attachment?->store('leave-attachments', 'local');

        return LeaveRequest::create([
            'employee_id'    => $employee->id,
            'leave_type_id'  => $data['leave_type_id'],
            'start_date'     => $startDate->toDateString(),
            'end_date'       => $endDate->toDateString(),
            'total_days'     => $totalDays,
            'chargeable_days'=> $chargeableDays,
            'reason'         => $data['reason'],
            'attachment_path'=> $attachmentPath,
            'status'         => 'PENDING_HR',
        ]);
    }

    private function isAnnualEntitlementType(LeaveType $leaveType): bool
    {
        return in_array(strtolower($leaveType->name), self::ANNUAL_ENTITLEMENT_TYPE_NAMES, true);
    }

    // Policy point 1: "at least 12 consecutive months" — validated against join_date.
    private function assertEligibleForAnnualLeave(Employee $employee, Carbon $startDate): void
    {
        $eligibleFrom = $employee->join_date->copy()->addMonths(self::MIN_SERVICE_MONTHS);

        if ($startDate->lt($eligibleFrom)) {
            throw ValidationException::withMessages([
                'start_date' => 'Cuti tahunan hanya dapat digunakan setelah masa kerja minimal 12 bulan.',
            ]);
        }
    }

    // Policy point 1: max 2 chargeable working days/month, "except with special
    // approval from direct supervisor and authorized official". The system has
    // no multi-level special-approval workflow, so — to avoid inventing a fake
    // approval role or silently allowing an over-cap request — this is enforced
    // as a hard block at submission time rather than a bypassable warning.
    private function assertWithinMonthlyCap(Employee $employee, Carbon $startDate, Carbon $endDate): void
    {
        $cursor    = $startDate->copy()->startOfMonth();
        $lastMonth = $endDate->copy()->startOfMonth();

        while ($cursor->lte($lastMonth)) {
            $monthStart = $cursor->copy()->startOfMonth();
            $monthEnd   = $cursor->copy()->endOfMonth();

            $overlapStart = $startDate->greaterThan($monthStart) ? $startDate->copy() : $monthStart->copy();
            $overlapEnd   = $endDate->lessThan($monthEnd) ? $endDate->copy() : $monthEnd->copy();

            $newDaysInMonth      = $this->calculator->countWorkingDays($overlapStart, $overlapEnd);
            $existingDaysInMonth = $this->existingChargeableDaysInMonth($employee, $monthStart, $monthEnd);

            if (($newDaysInMonth + $existingDaysInMonth) > self::MONTHLY_WORKING_DAY_CAP) {
                throw ValidationException::withMessages([
                    'start_date' => 'Penggunaan cuti tahunan maksimal 2 hari kerja per bulan, kecuali dengan persetujuan khusus dari atasan langsung dan pejabat berwenang.',
                ]);
            }

            $cursor->addMonth();
        }
    }

    private function existingChargeableDaysInMonth(Employee $employee, Carbon $monthStart, Carbon $monthEnd): int
    {
        $requests = LeaveRequest::with('leaveType')
            ->where('employee_id', $employee->id)
            ->whereIn('status', ['PENDING_HR', 'APPROVED'])
            ->where('start_date', '<=', $monthEnd->toDateString())
            ->where('end_date', '>=', $monthStart->toDateString())
            ->get(['id', 'leave_type_id', 'start_date', 'end_date'])
            ->filter(fn (LeaveRequest $request) => $this->isAnnualEntitlementType($request->leaveType));

        $total = 0;
        foreach ($requests as $request) {
            $overlapStart = $request->start_date->greaterThan($monthStart) ? $request->start_date->copy() : $monthStart->copy();
            $overlapEnd   = $request->end_date->lessThan($monthEnd) ? $request->end_date->copy() : $monthEnd->copy();
            $total += $this->calculator->countWorkingDays($overlapStart, $overlapEnd);
        }

        return $total;
    }

    public function approve(LeaveRequest $leaveRequest, User $approver, ?string $note): void
    {
        // Idempotency guard: prevent double-approval from causing double deduction
        if ($leaveRequest->status === 'APPROVED') {
            return;
        }

        DB::transaction(function () use ($leaveRequest, $approver, $note): void {
            $leaveRequest->loadMissing('leaveType');
            if ($leaveRequest->leaveType->deducts_balance) {
                // Deduct chargeable_days (working days, minus national holidays)
                // rather than the raw calendar span (policy points 2 & 3).
                // Requests created before this field existed fall back to
                // total_days so historical data keeps behaving as before.
                $deduction = $leaveRequest->chargeable_days ?? $leaveRequest->total_days;

                $balance = LeaveBalance::firstOrCreate(
                    [
                        'employee_id'   => $leaveRequest->employee_id,
                        'leave_type_id' => $leaveRequest->leave_type_id,
                        'year'          => $leaveRequest->start_date->year,
                    ],
                    [
                        'total_quota' => LeaveBalance::DEFAULT_ANNUAL_QUOTA,
                        'used'        => 0,
                        'remaining'   => LeaveBalance::DEFAULT_ANNUAL_QUOTA,
                    ]
                );

                if ($balance->remaining < $deduction) {
                    throw ValidationException::withMessages([
                        'balance' => 'Saldo cuti tidak mencukupi untuk permintaan ini.',
                    ]);
                }

                $balance->increment('used', $deduction);
                $balance->decrement('remaining', $deduction);
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
