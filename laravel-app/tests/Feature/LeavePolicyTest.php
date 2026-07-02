<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\User;
use App\Services\LeaveService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * STIKES Advaita leave policy tests (Phase 46).
 *
 * Scope covered here:
 * - 12-month eligibility gate for annual/personal leave (policy point 1).
 * - 2-working-day monthly cap for annual/personal leave (policy point 1).
 * - Working-day calculation excludes weekends and national holidays (points 2 & 3).
 * - Balance deduction happens on approval only, using chargeable (working) days.
 * - No half-day leave: schema has no fraction field, computed days are always whole (point 6).
 * - Annual reset: unused balance never carries over into the next calendar year,
 *   which new-year entitlement always starts at the full 18-day quota (point 8).
 *   Implemented via the existing year-scoped LeaveBalance architecture
 *   (unique per employee_id + leave_type_id + year, lazily created on first
 *   approval in that year) — no scheduler/cron job was needed or added.
 *
 * NOT covered here (documented limitation, not implemented this phase):
 * - Maternity/miscarriage leave and menstrual rest do not exist as LeaveType
 *   rows in this system yet, so the "counts_calendar_days" calendar-day path
 *   and the sick-leave-with/without-document distinction cannot be exercised
 *   against real data. See report for details.
 */
class LeavePolicyTest extends TestCase
{
    use RefreshDatabase;

    private LeaveService $service;
    private User $hrUser;
    private LeaveType $annualLeaveType;
    private LeaveType $personalLeaveType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(LeaveService::class);
        $this->hrUser  = User::factory()->create(['role' => 'admin_hr', 'is_active' => true]);

        $this->annualLeaveType   = LeaveType::create(['name' => 'Annual Leave', 'deducts_balance' => true]);
        $this->personalLeaveType = LeaveType::create(['name' => 'Personal Leave', 'deducts_balance' => true]);
    }

    private function makeEmployee(string $joinDate): Employee
    {
        $dept     = Department::create(['name' => 'Engineering '.uniqid(), 'description' => '']);
        $position = Position::create(['name' => 'Dev', 'department_id' => $dept->id]);
        $user     = User::factory()->create(['role' => 'employee', 'is_active' => true]);

        return Employee::create([
            'user_id'           => $user->id,
            'nik'               => 'NIK-'.uniqid(),
            'department_id'     => $dept->id,
            'position_id'       => $position->id,
            'join_date'         => $joinDate,
            'employment_status' => 'active',
            'phone_number'      => '+62812000000',
        ]);
    }

    private function submitAnnual(Employee $employee, string $start, string $end, ?LeaveType $type = null): LeaveRequest
    {
        return $this->service->submit($employee, [
            'leave_type_id' => ($type ?? $this->annualLeaveType)->id,
            'start_date'    => $start,
            'end_date'      => $end,
            'reason'        => 'Test leave reason for policy tests.',
        ]);
    }

    // ── Eligibility (12 consecutive months) ─────────────────────────────────

    public function test_employee_under_12_months_cannot_submit_annual_leave(): void
    {
        $employee = $this->makeEmployee(now()->subMonths(6)->toDateString());

        $this->expectException(ValidationException::class);
        $this->submitAnnual($employee, now()->addDays(10)->toDateString(), now()->addDays(10)->toDateString());
    }

    public function test_employee_at_12_months_can_submit_annual_leave(): void
    {
        $employee = $this->makeEmployee(now()->subMonths(12)->subDay()->toDateString());

        $start = Carbon::parse(now())->addDays(10);
        // Skip to a Monday-Friday day to avoid weekend/holiday interference.
        while ($start->isWeekend()) {
            $start->addDay();
        }

        $request = $this->submitAnnual($employee, $start->toDateString(), $start->toDateString());

        $this->assertSame('PENDING_HR', $request->status);
    }

    // ── Monthly cap (max 2 chargeable working days/month) ───────────────────

    public function test_request_within_two_working_days_per_month_is_allowed(): void
    {
        $employee = $this->makeEmployee(now()->subYears(2)->toDateString());

        $monday = Carbon::parse('2026-08-03'); // Monday
        $tuesday = $monday->copy()->addDay();

        $request = $this->submitAnnual($employee, $monday->toDateString(), $tuesday->toDateString());

        $this->assertSame('2.00', (string) $request->chargeable_days);
    }

    public function test_request_over_two_working_days_per_month_is_blocked(): void
    {
        $employee = $this->makeEmployee(now()->subYears(2)->toDateString());

        $monday = Carbon::parse('2026-08-03'); // Monday
        $wednesday = $monday->copy()->addDays(2);

        $this->expectException(ValidationException::class);
        $this->submitAnnual($employee, $monday->toDateString(), $wednesday->toDateString());
    }

    public function test_second_request_pushing_month_total_over_cap_is_blocked(): void
    {
        $employee = $this->makeEmployee(now()->subYears(2)->toDateString());

        $monday = Carbon::parse('2026-08-03');
        $this->submitAnnual($employee, $monday->toDateString(), $monday->toDateString());

        $wednesday = $monday->copy()->addDays(2);
        $thursday  = $monday->copy()->addDays(3);

        $this->expectException(ValidationException::class);
        $this->submitAnnual($employee, $wednesday->toDateString(), $thursday->toDateString());
    }

    public function test_request_spanning_weekend_only_counts_working_days(): void
    {
        $employee = $this->makeEmployee(now()->subYears(2)->toDateString());

        // Friday 2026-08-07 - Monday 2026-08-10: 2 chargeable working days (Fri, Mon).
        $friday = Carbon::parse('2026-08-07');
        $monday = Carbon::parse('2026-08-10');

        $request = $this->submitAnnual($employee, $friday->toDateString(), $monday->toDateString());

        $this->assertSame('2.00', (string) $request->chargeable_days);
        $this->assertSame('4.00', (string) $request->total_days);
    }

    public function test_request_spanning_national_holiday_excludes_it_from_chargeable_days(): void
    {
        $employee = $this->makeEmployee(now()->subYears(2)->toDateString());

        // Monday 2026-08-03 - Tuesday 2026-08-04, with Tuesday as a national holiday.
        Holiday::create(['date' => '2026-08-04', 'name' => 'Test Holiday']);

        $monday  = Carbon::parse('2026-08-03');
        $tuesday = Carbon::parse('2026-08-04');

        $request = $this->submitAnnual($employee, $monday->toDateString(), $tuesday->toDateString());

        $this->assertSame('1.00', (string) $request->chargeable_days);
    }

    // ── Approval-time deduction using chargeable days ────────────────────────

    public function test_approval_deducts_chargeable_days_not_calendar_days(): void
    {
        $employee = $this->makeEmployee(now()->subYears(2)->toDateString());
        LeaveBalance::create([
            'employee_id'   => $employee->id,
            'leave_type_id' => $this->annualLeaveType->id,
            'year'          => 2026,
            'total_quota'   => 18,
            'used'          => 0,
            'remaining'     => 18,
        ]);

        // Friday - Monday: 4 calendar days, but only 2 working days.
        $friday = Carbon::parse('2026-08-07');
        $monday = Carbon::parse('2026-08-10');
        $request = $this->submitAnnual($employee, $friday->toDateString(), $monday->toDateString());

        $this->service->approve($request, $this->hrUser, 'Approved.');

        $balance = LeaveBalance::where('employee_id', $employee->id)->first();
        $this->assertSame('2.00', $balance->fresh()->used);
        $this->assertSame('16.00', $balance->fresh()->remaining);
    }

    public function test_pending_request_does_not_deduct_balance(): void
    {
        $employee = $this->makeEmployee(now()->subYears(2)->toDateString());

        $monday = Carbon::parse('2026-08-03');
        $this->submitAnnual($employee, $monday->toDateString(), $monday->toDateString());

        $this->assertDatabaseMissing('leave_balances', ['employee_id' => $employee->id]);
    }

    public function test_rejected_request_does_not_deduct_balance(): void
    {
        $employee = $this->makeEmployee(now()->subYears(2)->toDateString());

        $monday = Carbon::parse('2026-08-03');
        $request = $this->submitAnnual($employee, $monday->toDateString(), $monday->toDateString());

        $this->service->reject($request, $this->hrUser, 'Rejected for test reasons.');

        $this->assertDatabaseMissing('leave_balances', ['employee_id' => $employee->id]);
        $this->assertSame('REJECTED', $request->fresh()->status);
    }

    // ── Personal Leave shares the same annual-entitlement pool ──────────────

    public function test_personal_leave_counts_toward_same_monthly_cap_as_annual_leave(): void
    {
        $employee = $this->makeEmployee(now()->subYears(2)->toDateString());

        $monday  = Carbon::parse('2026-08-03');
        $tuesday = $monday->copy()->addDay();
        $this->submitAnnual($employee, $monday->toDateString(), $tuesday->toDateString(), $this->annualLeaveType);

        $wednesday = $monday->copy()->addDays(2);

        $this->expectException(ValidationException::class);
        $this->submitAnnual($employee, $wednesday->toDateString(), $wednesday->toDateString(), $this->personalLeaveType);
    }

    // ── No half-day leave (policy point 6) ───────────────────────────────────

    public function test_leave_requests_table_has_no_half_day_or_duration_fraction_column(): void
    {
        // start_date/end_date are date-only columns and total_days/chargeable_days
        // are server-computed from them — there is no user-facing half-day input
        // to disable. This test locks that schema invariant.
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('leave_requests');

        foreach (['is_half_day', 'half_day', 'duration_fraction', 'session', 'am_pm'] as $forbidden) {
            $this->assertNotContains($forbidden, $columns);
        }
    }

    public function test_submitted_leave_always_has_whole_number_day_counts(): void
    {
        $employee = $this->makeEmployee(now()->subYears(2)->toDateString());

        $monday = Carbon::parse('2026-08-03');
        $request = $this->submitAnnual($employee, $monday->toDateString(), $monday->toDateString());

        $this->assertEquals(1.0, (float) $request->total_days);
        $this->assertEquals(1.0, (float) $request->chargeable_days);
        $this->assertSame(0.0, fmod((float) $request->total_days, 1.0));
        $this->assertSame(0.0, fmod((float) $request->chargeable_days, 1.0));
    }

    // ── Annual reset / forfeiture (policy point 8) ───────────────────────────

    public function test_unused_previous_year_balance_does_not_carry_over_to_new_year(): void
    {
        $employee = $this->makeEmployee('2020-01-01');

        // Simulate a partially-used 2026 balance with 5 days left over.
        LeaveBalance::create([
            'employee_id'   => $employee->id,
            'leave_type_id' => $this->annualLeaveType->id,
            'year'          => 2026,
            'total_quota'   => 18,
            'used'          => 13,
            'remaining'     => 5,
        ]);

        // A 2027 request/approval must start from a fresh 18-day entitlement,
        // not from the 5 days remaining in 2026.
        $request2027 = $this->submitAnnual($employee, '2027-01-04', '2027-01-04');
        $this->service->approve($request2027, $this->hrUser, 'Approved.');

        $balance2026 = LeaveBalance::where('employee_id', $employee->id)->where('year', 2026)->first();
        $balance2027 = LeaveBalance::where('employee_id', $employee->id)->where('year', 2027)->first();

        // 2026 row is untouched (no carry-over, no retroactive deduction).
        $this->assertSame('5.00', $balance2026->fresh()->remaining);

        // 2027 starts from the full 18-day entitlement, only reduced by the new request.
        $this->assertNotNull($balance2027);
        $this->assertSame('18.00', $balance2027->total_quota);
        $this->assertSame('1.00', $balance2027->used);
        $this->assertSame('17.00', $balance2027->remaining);
    }

    public function test_unused_annual_leave_is_not_added_to_new_year_entitlement(): void
    {
        $employee = $this->makeEmployee('2020-01-01');

        // Employee never used any 2026 leave — 18 full days unused.
        LeaveBalance::create([
            'employee_id'   => $employee->id,
            'leave_type_id' => $this->annualLeaveType->id,
            'year'          => 2026,
            'total_quota'   => 18,
            'used'          => 0,
            'remaining'     => 18,
        ]);

        $request2027 = $this->submitAnnual($employee, '2027-01-04', '2027-01-04');
        $this->service->approve($request2027, $this->hrUser, 'Approved.');

        $balance2027 = LeaveBalance::where('employee_id', $employee->id)->where('year', 2027)->first();

        // Entitlement is exactly the default 18, not 18 + 18 leftover from 2026.
        $this->assertSame('18.00', $balance2027->total_quota);
        $this->assertSame('17.00', $balance2027->remaining);
    }

    public function test_leave_balance_is_scoped_per_calendar_year(): void
    {
        $employee = $this->makeEmployee('2020-01-01');

        LeaveBalance::create([
            'employee_id' => $employee->id, 'leave_type_id' => $this->annualLeaveType->id,
            'year' => 2026, 'total_quota' => 18, 'used' => 0, 'remaining' => 18,
        ]);
        LeaveBalance::create([
            'employee_id' => $employee->id, 'leave_type_id' => $this->annualLeaveType->id,
            'year' => 2027, 'total_quota' => 18, 'used' => 0, 'remaining' => 18,
        ]);

        $this->assertSame(2, LeaveBalance::where('employee_id', $employee->id)->count());
        $this->assertSame(1, LeaveBalance::where('employee_id', $employee->id)->where('year', 2026)->count());
    }
}
