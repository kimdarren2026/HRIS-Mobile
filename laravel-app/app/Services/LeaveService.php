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
        private readonly AnnualLeaveEntitlementService $entitlementService = new AnnualLeaveEntitlementService(),
    ) {}

    public function submit(Employee $employee, array $data, ?UploadedFile $attachment = null): LeaveRequest
    {
        return DB::transaction(function () use ($employee, $data, $attachment): LeaveRequest {
            // Phase 59D lock order: Employee -> LeaveRequest -> LeaveBalance.
            // Locking the employee row first serializes concurrent submissions
            // for the same employee, so the overlap check below and the
            // eventual create() always see a consistent, committed view of
            // that employee's existing requests — a second concurrent
            // submission cannot read the overlap check before the first one's
            // create() has committed.
            $lockedEmployee = Employee::whereKey($employee->id)->lockForUpdate()->first();

            if (! $lockedEmployee) {
                throw ValidationException::withMessages([
                    'employee' => 'Data karyawan tidak ditemukan.',
                ]);
            }

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

            // Phase 59D fix: start_date/end_date are cast as 'date' but Eloquent
            // still serializes them for storage using the connection's full
            // datetime format (Model::getDateFormat() falls back to the query
            // grammar's format, e.g. "Y-m-d H:i:s"), so the stored value is
            // e.g. "2026-08-03 00:00:00". A plain where('start_date', '<=', '2026-08-03')
            // compares that against the shorter date-only string and, for an
            // exact same-day boundary, string-sorts "2026-08-03 00:00:00" as
            // GREATER than "2026-08-03" — silently missing an identical-date
            // overlap. whereDate() compares only the date part on both sides,
            // fixing that false negative without changing what counts as an
            // overlap (still PENDING_HR/APPROVED requests whose date range
            // intersects, exactly as before).
            $overlaps = LeaveRequest::where('employee_id', $employee->id)
                ->whereIn('status', ['PENDING_HR', 'APPROVED'])
                ->whereDate('start_date', '<=', $endDate->toDateString())
                ->whereDate('end_date', '>=', $startDate->toDateString())
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
        });
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
        // Phase 58C correction: Annual Leave and Personal Leave draw from one
        // shared pool, so a pending half-day request under either type holds
        // balance from the same pool — must sum across all annual-entitlement
        // type ids, not just the one $leaveType happens to be.
        $typeIds = $this->isAnnualEntitlementType($leaveType)
            ? LeaveType::annualEntitlementTypeIds()
            : [$leaveType->id];

        return (int) LeaveRequest::where('employee_id', $employee->id)
            ->whereIn('leave_type_id', $typeIds)
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
        DB::transaction(function () use ($leaveRequest, $approver, $note): void {
            // Phase 59D lock order: Employee -> LeaveRequest -> LeaveBalance.
            // The employee row is the only row two DIFFERENT LeaveRequests
            // belonging to the same employee have in common, so locking it
            // first serializes concurrent approvals that would otherwise
            // race on the same LeaveBalance pool, and safely serializes the
            // first-time creation of that LeaveBalance row further below.
            $employee = Employee::whereKey($leaveRequest->employee_id)->lockForUpdate()->first();

            if (! $employee) {
                throw ValidationException::withMessages([
                    'employee' => 'Data karyawan untuk pengajuan cuti ini tidak ditemukan.',
                ]);
            }

            // Never trust the caller-supplied instance's status (e.g. a
            // route-model-bound object fetched before another request
            // committed a decision on this same row) — re-fetch and lock the
            // row itself, and make the final PENDING_HR decision only against
            // this fresh read.
            $fresh = LeaveRequest::whereKey($leaveRequest->id)->lockForUpdate()->first();

            if (! $fresh) {
                throw ValidationException::withMessages([
                    'leave_request' => 'Pengajuan cuti tidak ditemukan.',
                ]);
            }

            if ($fresh->status !== 'PENDING_HR') {
                throw ValidationException::withMessages([
                    'status' => 'Pengajuan cuti ini sudah diproses sebelumnya.',
                ]);
            }

            $fresh->loadMissing(['leaveType', 'employee']);

            if ($fresh->leaveType->deducts_balance) {
                // Deduct chargeable_days (working days, minus national holidays)
                // rather than the raw calendar span (policy points 2 & 3).
                // Requests created before this field existed fall back to
                // total_days so historical data keeps behaving as before.
                $deduction = $fresh->chargeable_days ?? $fresh->total_days;

                // Phase 58C correction: Annual Leave and Personal Leave share
                // one 18-day pool, so the balance row is always keyed by the
                // canonical annual-entitlement type id, never by the specific
                // request's own leave_type_id (which would otherwise create a
                // second, additive 18-day pool per type). If no canonical type
                // can be resolved, fail loudly rather than silently falling
                // back to the request's own type id (which would fabricate a
                // balance on the wrong pool).
                if ($this->isAnnualEntitlementType($fresh->leaveType)) {
                    $canonicalType = LeaveType::canonicalAnnualEntitlementType();

                    if ($canonicalType === null) {
                        throw ValidationException::withMessages([
                            'leave_type' => 'Jenis cuti tahunan kanonik (Annual Leave/Cuti Tahunan) tidak ditemukan. Persetujuan dibatalkan untuk mencegah pembuatan saldo pada pool yang salah.',
                        ]);
                    }

                    $balanceLeaveTypeId = $canonicalType->id;
                } else {
                    $balanceLeaveTypeId = $fresh->leave_type_id;
                }

                // Fallback path: normally leave:initialize-year (Phase 58C)
                // has already created the year's balance row with the correct
                // full/prorated entitlement. This only fires if approval
                // happens before that row exists yet. Locked explicitly (in
                // addition to the employee row above) so the balance check
                // and mutation below are atomic against any other reader of
                // this exact row.
                $balance = LeaveBalance::where('employee_id', $fresh->employee_id)
                    ->where('leave_type_id', $balanceLeaveTypeId)
                    ->where('year', $fresh->start_date->year)
                    ->lockForUpdate()
                    ->first();

                if (! $balance) {
                    // Safe from duplicate creation: the employee row lock
                    // above means only one approve()/reject()/submit() call
                    // for this employee can be inside this transaction at a
                    // time, so no concurrent request can race this create().
                    $balance = LeaveBalance::create(array_merge(
                        [
                            'employee_id'   => $fresh->employee_id,
                            'leave_type_id' => $balanceLeaveTypeId,
                            'year'          => $fresh->start_date->year,
                        ],
                        $this->defaultBalanceAttributes($fresh)
                    ));
                }

                if ($balance->remaining < $deduction) {
                    throw ValidationException::withMessages([
                        'balance' => 'Saldo cuti tidak mencukupi untuk permintaan ini.',
                    ]);
                }

                $balance->increment('used', $deduction);
                $balance->decrement('remaining', $deduction);
            }

            $fresh->update([
                'status'        => 'APPROVED',
                'approved_by'   => $approver->id,
                'approved_at'   => now(),
                'approval_note' => $note,
            ]);
        });
    }

    // Policy point: entitlement for annual/personal leave is full-or-prorated
    // per AnnualLeaveEntitlementService (Phase 58C), never the flat default.
    // Other balance-deducting leave types keep the pre-existing flat default.
    private function defaultBalanceAttributes(LeaveRequest $leaveRequest): array
    {
        if ($this->isAnnualEntitlementType($leaveRequest->leaveType)) {
            $entitlement = $this->entitlementService->calculate(
                $leaveRequest->employee,
                $leaveRequest->start_date->year,
            );
            $quota = $entitlement['final_entitlement'];
        } else {
            $quota = LeaveBalance::DEFAULT_ANNUAL_QUOTA;
        }

        return [
            'total_quota' => $quota,
            'used'        => 0,
            'remaining'   => $quota,
        ];
    }

    public function reject(LeaveRequest $leaveRequest, User $approver, string $note): void
    {
        DB::transaction(function () use ($leaveRequest, $approver, $note): void {
            // Same Employee -> LeaveRequest lock order as approve(), so a
            // concurrent approve()/reject() race on this same request always
            // serializes consistently and can never deadlock against each
            // other.
            $employee = Employee::whereKey($leaveRequest->employee_id)->lockForUpdate()->first();

            if (! $employee) {
                throw ValidationException::withMessages([
                    'employee' => 'Data karyawan untuk pengajuan cuti ini tidak ditemukan.',
                ]);
            }

            $fresh = LeaveRequest::whereKey($leaveRequest->id)->lockForUpdate()->first();

            if (! $fresh) {
                throw ValidationException::withMessages([
                    'leave_request' => 'Pengajuan cuti tidak ditemukan.',
                ]);
            }

            if ($fresh->status !== 'PENDING_HR') {
                throw ValidationException::withMessages([
                    'status' => 'Pengajuan cuti ini sudah diproses sebelumnya.',
                ]);
            }

            $fresh->update([
                'status'        => 'REJECTED',
                'approved_by'   => $approver->id,
                'approved_at'   => now(),
                'approval_note' => $note,
            ]);
        });
    }
}
