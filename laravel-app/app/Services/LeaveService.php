<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Holiday;
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
    // Policy point 1: annual leave applies only after 12 consecutive months of service.
    private const MIN_SERVICE_MONTHS = 12;

    // Policy point 1: max 2 chargeable working days per calendar month without special approval.
    private const MONTHLY_WORKING_DAY_CAP = 2;

    public function __construct(
        private readonly WorkingDayCalculator $calculator = new WorkingDayCalculator(),
    ) {}

    public function submit(Employee $employee, array $data, ?UploadedFile $attachment = null): LeaveRequest
    {
        $startDate    = Carbon::parse($data['start_date']);
        $endDate      = Carbon::parse($data['end_date']);
        $totalDays    = $startDate->diffInDays($endDate) + 1;
        $durationType = $data['duration_type'] ?? 'FULL_DAY';
        $isHalfDay    = $durationType === 'HALF_DAY';

        // Defensive invariant guard: start_date/end_date are date-only columns
        // and diffInDays always yields a whole number, so this can never
        // actually trip today. Kept in case a time-of-day input is ever added
        // later. Half-day leave itself is handled below via duration_type —
        // it is a supported, whole-day-equivalent request, not a fractional
        // total_days/chargeable_days value (half-day policy correction).
        if ($totalDays != floor($totalDays)) {
            throw ValidationException::withMessages([
                'start_date' => 'Tanggal cuti tidak valid.',
            ]);
        }

        if ($isHalfDay && ! $startDate->isSameDay($endDate)) {
            throw ValidationException::withMessages([
                'start_date' => 'Cuti setengah hari hanya boleh diajukan untuk satu tanggal kerja.',
            ]);
        }

        if ($isHalfDay && $this->isBlockedForHalfDay($startDate)) {
            throw ValidationException::withMessages([
                'start_date' => 'Cuti setengah hari tidak dapat diajukan pada akhir pekan atau hari libur.',
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

        // Half-day policy correction: for leave types that deduct annual
        // balance, a half-day request always charges exactly 1 whole day —
        // never a fraction. For non-deducting leave types the normal
        // calculator result is kept (chargeable_days is inert there anyway,
        // since approve() only touches balance when deducts_balance is true).
        if ($isHalfDay && $leaveType->deducts_balance) {
            $chargeableDays = 1;
        } else {
            $chargeableDays = $this->calculator->countChargeableDays(
                $startDate,
                $endDate,
                ! $leaveType->counts_calendar_days,
            );
        }

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
            'duration_type'  => $durationType,
            'total_days'     => $totalDays,
            'chargeable_days'=> $chargeableDays,
            'reason'         => $data['reason'],
            'attachment_path'=> $attachmentPath,
            'status'         => 'PENDING_HR',
        ]);
    }

    // Half-day policy point 7: cannot be submitted on Saturday, Sunday, or any
    // date already recorded in the holidays calendar. The holidays table has
    // no "type" column distinguishing national holidays / cuti bersama /
    // internal campus holidays (none of that is regulated in this system yet
    // — see WorkingDayCalculator/Holiday model), so every row in it is treated
    // uniformly as a blocking date, matching how the same table already
    // excludes dates from working-day/chargeable-day counting elsewhere.
    private function isBlockedForHalfDay(Carbon $date): bool
    {
        if ($date->isWeekend()) {
            return true;
        }

        return Holiday::whereDate('date', $date->toDateString())->exists();
    }

    // Half-day policy point 5: pending half-day requests hold 1 day of balance
    // without persisting a new "held" column (leave_balances is unaffected).
    // The hold is purely a display-time computation — see also item 10 of the
    // correction: existing decimal columns are already sufficient.
    public function heldHalfDayDays(Employee $employee, LeaveType $leaveType, int $year): int
    {
        return (int) LeaveRequest::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('duration_type', 'HALF_DAY')
            ->where('status', 'PENDING_HR')
            ->whereYear('start_date', $year)
            ->sum('chargeable_days');
    }

    private function isAnnualEntitlementType(LeaveType $leaveType): bool
    {
        return $leaveType->isAnnualEntitlementType();
    }

    // Policy point 1: "at least 12 consecutive months" — validated against join_date.
    // Exposed publicly so the request-page balance preview can check eligibility
    // without duplicating the rule (and without throwing, unlike the submit-time guard).
    public function isEligibleForAnnualLeave(Employee $employee, ?Carbon $asOf = null): bool
    {
        $eligibleFrom = $employee->join_date->copy()->addMonths(self::MIN_SERVICE_MONTHS);

        return ($asOf ?? Carbon::now())->gte($eligibleFrom);
    }

    private function assertEligibleForAnnualLeave(Employee $employee, Carbon $startDate): void
    {
        if (! $this->isEligibleForAnnualLeave($employee, $startDate)) {
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
