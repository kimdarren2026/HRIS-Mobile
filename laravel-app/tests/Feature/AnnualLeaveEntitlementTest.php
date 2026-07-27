<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\User;
use App\Services\AnnualLeaveEntitlementService;
use App\Services\LeaveService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Phase 58C: annual leave entitlement (18 days/year, 12-month eligibility,
 * proration, transition opening balance, Jan-1 reset via leave:initialize-year).
 */
class AnnualLeaveEntitlementTest extends TestCase
{
    use RefreshDatabase;

    private AnnualLeaveEntitlementService $service;
    private LeaveType $annualLeaveType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(AnnualLeaveEntitlementService::class);
        $this->annualLeaveType = LeaveType::create(['name' => 'Annual Leave', 'deducts_balance' => true]);
    }

    private function makeEmployee(?string $joinDate, string $status = 'active'): Employee
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
            'employment_status' => $status,
            'phone_number'      => '+62812000000',
        ]);
    }

    // ── 1. join_date stays per-employee, never homogenized ──────────────────

    public function test_employees_keep_distinct_join_dates(): void
    {
        $a = $this->makeEmployee('2024-03-10');
        $b = $this->makeEmployee('2022-11-05');

        $this->assertNotSame($a->join_date->toDateString(), $b->join_date->toDateString());
        $this->assertSame('2024-03-10', $a->fresh()->join_date->toDateString());
        $this->assertSame('2022-11-05', $b->fresh()->join_date->toDateString());
    }

    // ── 2-8. Entitlement formula ─────────────────────────────────────────────

    public function test_employee_under_12_months_gets_zero_entitlement(): void
    {
        $employee = $this->makeEmployee(now()->subMonths(6)->toDateString());

        $result = $this->service->calculate($employee, (int) now()->year);

        $this->assertFalse($result['eligible']);
        $this->assertSame(0, $result['final_entitlement']);
    }

    public function test_employee_eligible_before_january_first_gets_18_days(): void
    {
        $employee = $this->makeEmployee('2020-01-01');

        $result = $this->service->calculate($employee, 2026);

        $this->assertTrue($result['eligible']);
        $this->assertSame(18, $result['final_entitlement']);
    }

    public function test_january_anniversary_gets_18_days(): void
    {
        // join 2025-01-15 → eligibility 2026-01-15 (within year 2026, month 1).
        $employee = $this->makeEmployee('2025-01-15');

        $result = $this->service->calculate($employee, 2026);

        $this->assertSame(18, $result['final_entitlement']);
    }

    public function test_july_anniversary_gets_9_days(): void
    {
        $employee = $this->makeEmployee('2025-07-10');

        $result = $this->service->calculate($employee, 2026);

        $this->assertSame(6, $result['months_remaining']);
        $this->assertEquals(9.0, $result['raw_entitlement']);
        $this->assertSame(9, $result['final_entitlement']);
    }

    public function test_august_anniversary_floors_7_5_to_7_days(): void
    {
        $employee = $this->makeEmployee('2025-08-10');

        $result = $this->service->calculate($employee, 2026);

        $this->assertEquals(7.5, $result['raw_entitlement']);
        $this->assertSame(7, $result['final_entitlement']);
    }

    public function test_october_anniversary_floors_4_5_to_4_days(): void
    {
        $employee = $this->makeEmployee('2025-10-10');

        $result = $this->service->calculate($employee, 2026);

        $this->assertEquals(4.5, $result['raw_entitlement']);
        $this->assertSame(4, $result['final_entitlement']);
    }

    public function test_december_anniversary_floors_1_5_to_1_day(): void
    {
        $employee = $this->makeEmployee('2025-12-10');

        $result = $this->service->calculate($employee, 2026);

        $this->assertEquals(1.5, $result['raw_entitlement']);
        $this->assertSame(1, $result['final_entitlement']);
    }

    // ── 9. Mid-month anniversary still counts as a full month ───────────────

    public function test_mid_month_anniversary_counts_as_full_month(): void
    {
        $early = $this->makeEmployee('2025-08-01'); // eligibility 2026-08-01
        $late  = $this->makeEmployee('2025-08-31'); // eligibility 2026-08-31, same month

        $resultEarly = $this->service->calculate($early, 2026);
        $resultLate  = $this->service->calculate($late, 2026);

        $this->assertSame($resultEarly['final_entitlement'], $resultLate['final_entitlement']);
        $this->assertSame(7, $resultLate['final_entitlement']);
    }

    // ── 10. Leap-year join date (Feb 29) is handled safely ──────────────────

    public function test_february_29_join_date_is_handled_safely(): void
    {
        $employee = $this->makeEmployee('2024-02-29');

        // addMonthsNoOverflow(12) on 2024-02-29 must clamp to 2025-02-28, never
        // overflow into March (which would silently shift the anniversary month
        // and understate the prorated entitlement).
        $result = $this->service->calculate($employee, 2025);

        $this->assertSame('2025-02-28', $result['eligibility_date']->toDateString());
        $this->assertTrue($result['eligible']);
        $this->assertSame(11, $result['months_remaining']);
        $this->assertSame(16, $result['final_entitlement']);
    }

    // ── 11. Year after proration gets the full 18 days ───────────────────────

    public function test_year_following_proration_gets_full_18_days(): void
    {
        $employee = $this->makeEmployee('2025-08-10');

        $firstYear  = $this->service->calculate($employee, 2026);
        $secondYear = $this->service->calculate($employee, 2027);

        $this->assertSame(7, $firstYear['final_entitlement']);
        $this->assertSame(18, $secondYear['final_entitlement']);
    }

    // ── 12-13. Entitlement bounds ────────────────────────────────────────────

    public function test_entitlement_never_exceeds_18(): void
    {
        $employee = $this->makeEmployee('1990-01-01');

        $result = $this->service->calculate($employee, 2026);

        $this->assertLessThanOrEqual(18, $result['final_entitlement']);
    }

    public function test_entitlement_is_never_negative(): void
    {
        $employee = $this->makeEmployee(now()->addYear()->toDateString()); // future join date

        $result = $this->service->calculate($employee, (int) now()->year);

        $this->assertGreaterThanOrEqual(0, $result['final_entitlement']);
        $this->assertSame(0, $result['final_entitlement']);
    }

    // ── 15. Missing join_date never fabricates an entitlement ───────────────

    public function test_missing_join_date_does_not_fabricate_entitlement(): void
    {
        $employee = new Employee([
            'nik'               => 'NIK-INVALID',
            'join_date'         => null,
            'employment_status' => 'active',
            'phone_number'      => '+62812000000',
        ]);

        $result = $this->service->calculate($employee, 2026);

        $this->assertTrue($result['needs_hr_correction']);
        $this->assertFalse($result['eligible']);
        $this->assertSame(0, $result['final_entitlement']);
    }

    // ── 31. Prorated entitlement is always a whole number (no 0.5 stored) ───

    public function test_prorated_entitlement_never_stores_half_day(): void
    {
        foreach (range(1, 12) as $month) {
            $employee = $this->makeEmployee(sprintf('2025-%02d-10', $month));
            $result   = $this->service->calculate($employee, 2026);

            $this->assertSame(0.0, fmod((float) $result['final_entitlement'], 1.0));
        }
    }

    // ── 14, 24-28. leave:initialize-year command ─────────────────────────────

    public function test_command_creates_balances_for_eligible_active_employees(): void
    {
        $this->makeEmployee('2020-01-01'); // eligible, full quota

        Artisan::call('leave:initialize-year', ['year' => 2027]);

        $this->assertDatabaseHas('leave_balances', [
            'leave_type_id' => $this->annualLeaveType->id,
            'year'          => 2027,
            'total_quota'   => 18,
            'remaining'     => 18,
        ]);
    }

    public function test_command_skips_inactive_employees(): void
    {
        $this->makeEmployee('2020-01-01', 'resigned');

        Artisan::call('leave:initialize-year', ['year' => 2027]);

        $this->assertDatabaseMissing('leave_balances', ['year' => 2027]);
    }

    public function test_command_does_not_carry_over_previous_year_remaining(): void
    {
        $employee = $this->makeEmployee('2020-01-01');
        LeaveBalance::create([
            'employee_id' => $employee->id, 'leave_type_id' => $this->annualLeaveType->id,
            'year' => 2026, 'total_quota' => 18, 'used' => 13, 'remaining' => 5,
        ]);

        Artisan::call('leave:initialize-year', ['year' => 2027]);

        $newBalance = LeaveBalance::where('employee_id', $employee->id)->where('year', 2027)->first();
        $this->assertNotNull($newBalance);
        $this->assertSame('18.00', $newBalance->total_quota);
        $this->assertSame('0.00', $newBalance->used);
    }

    public function test_command_preserves_prior_year_history(): void
    {
        $employee = $this->makeEmployee('2020-01-01');
        LeaveBalance::create([
            'employee_id' => $employee->id, 'leave_type_id' => $this->annualLeaveType->id,
            'year' => 2026, 'total_quota' => 18, 'used' => 13, 'remaining' => 5,
        ]);

        Artisan::call('leave:initialize-year', ['year' => 2027]);

        $oldBalance = LeaveBalance::where('employee_id', $employee->id)->where('year', 2026)->first();
        $this->assertSame('5.00', $oldBalance->fresh()->remaining);
    }

    public function test_command_is_safe_to_run_twice_without_duplicating(): void
    {
        $this->makeEmployee('2020-01-01');

        Artisan::call('leave:initialize-year', ['year' => 2027]);
        Artisan::call('leave:initialize-year', ['year' => 2027]);

        $this->assertSame(1, LeaveBalance::where('year', 2027)->count());
    }

    public function test_command_dry_run_does_not_write_database(): void
    {
        $this->makeEmployee('2020-01-01');

        Artisan::call('leave:initialize-year', ['year' => 2027, '--dry-run' => true]);

        $this->assertDatabaseMissing('leave_balances', ['year' => 2027]);
    }

    public function test_command_creates_audit_log_entry(): void
    {
        $this->makeEmployee('2020-01-01');

        Artisan::call('leave:initialize-year', ['year' => 2027]);

        $this->assertDatabaseHas('audit_logs', ['action' => 'initialize_annual_leave_balance']);
    }

    public function test_dry_run_does_not_create_audit_log(): void
    {
        $this->makeEmployee('2020-01-01');

        Artisan::call('leave:initialize-year', ['year' => 2027, '--dry-run' => true]);

        $this->assertDatabaseMissing('audit_logs', ['action' => 'initialize_annual_leave_balance']);
    }

    // ── Shared annual-entitlement pool (Annual Leave + Personal Leave) ───────

    public function test_annual_and_personal_leave_share_one_annual_balance(): void
    {
        $personalLeaveType = LeaveType::create(['name' => 'Personal Leave', 'deducts_balance' => true]);
        $employee = $this->makeEmployee('2020-01-01');
        $hrUser   = $this->actingAdmin();
        $service  = app(LeaveService::class);

        // Command must create exactly ONE pool, not one per matching leave type.
        Artisan::call('leave:initialize-year', ['year' => 2027]);

        $this->assertSame(1, LeaveBalance::where('employee_id', $employee->id)->where('year', 2027)->count());
        $balance = LeaveBalance::where('employee_id', $employee->id)->where('year', 2027)->first();
        $this->assertSame('18.00', $balance->total_quota);

        // Approve 2 days of Annual Leave against the pool.
        $annualRequest = LeaveRequest::create([
            'employee_id' => $employee->id, 'leave_type_id' => $this->annualLeaveType->id,
            'start_date' => '2027-08-03', 'end_date' => '2027-08-04', 'total_days' => 2,
            'chargeable_days' => 2, 'reason' => 'Test annual leave.', 'status' => 'PENDING_HR',
        ]);
        $service->approve($annualRequest, $hrUser, 'Approved.');

        $this->assertSame(1, LeaveBalance::where('employee_id', $employee->id)->where('year', 2027)->count());
        $this->assertSame('16.00', $balance->fresh()->remaining);

        // Approve 1 day of Personal Leave — must draw from the SAME pool.
        $personalRequest = LeaveRequest::create([
            'employee_id' => $employee->id, 'leave_type_id' => $personalLeaveType->id,
            'start_date' => '2027-09-01', 'end_date' => '2027-09-01', 'total_days' => 1,
            'chargeable_days' => 1, 'reason' => 'Test personal leave.', 'status' => 'PENDING_HR',
        ]);
        $service->approve($personalRequest, $hrUser, 'Approved.');

        $this->assertSame(1, LeaveBalance::where('employee_id', $employee->id)->where('year', 2027)->count());
        $this->assertSame('15.00', $balance->fresh()->remaining);

        // Total entitlement across the employee's year stays 18, never 36.
        $totalEntitlement = LeaveBalance::where('employee_id', $employee->id)->where('year', 2027)->sum('total_quota');
        $this->assertEquals(18.0, (float) $totalEntitlement);

        // Running the command again does not create a second pool.
        Artisan::call('leave:initialize-year', ['year' => 2027]);
        $this->assertSame(1, LeaveBalance::where('employee_id', $employee->id)->where('year', 2027)->count());
    }

    public function test_canonical_annual_balance_does_not_depend_on_leave_type_id_order(): void
    {
        // Drop setUp's Annual Leave row and recreate both types with Personal
        // Leave getting the LOWER id — the resolver must still pick Annual
        // Leave as the pool owner by name priority, not by id.
        LeaveType::where('id', $this->annualLeaveType->id)->delete();

        $personalLeaveType = LeaveType::create(['name' => 'Personal Leave', 'deducts_balance' => true]);
        $annualLeaveType   = LeaveType::create(['name' => 'Annual Leave', 'deducts_balance' => true]);
        $this->assertLessThan($annualLeaveType->id, $personalLeaveType->id);

        $canonical = LeaveType::canonicalAnnualEntitlementType();
        $this->assertNotNull($canonical);
        $this->assertSame($annualLeaveType->id, $canonical->id);
        $this->assertSame('Annual Leave', $canonical->name);

        $employee = $this->makeEmployee('2020-01-01');
        $hrUser   = $this->actingAdmin();
        $service  = app(LeaveService::class);

        Artisan::call('leave:initialize-year', ['year' => 2028]);

        $this->assertSame(1, LeaveBalance::where('employee_id', $employee->id)->where('year', 2028)->count());
        $balance = LeaveBalance::where('employee_id', $employee->id)->where('year', 2028)->first();
        $this->assertSame($annualLeaveType->id, $balance->leave_type_id);
        $this->assertSame('18.00', $balance->total_quota);

        // Personal Leave (lower id) never gets its own balance row.
        $this->assertDatabaseMissing('leave_balances', ['leave_type_id' => $personalLeaveType->id]);

        // Approving Personal Leave deducts from the Annual-Leave-owned pool.
        $personalRequest = LeaveRequest::create([
            'employee_id' => $employee->id, 'leave_type_id' => $personalLeaveType->id,
            'start_date' => '2028-09-01', 'end_date' => '2028-09-01', 'total_days' => 1,
            'chargeable_days' => 1, 'reason' => 'Test personal leave.', 'status' => 'PENDING_HR',
        ]);
        $service->approve($personalRequest, $hrUser, 'Approved.');

        $balance = $balance->fresh();
        $this->assertSame($annualLeaveType->id, $balance->leave_type_id);
        $this->assertSame('17.00', $balance->remaining);

        $totalEntitlement = LeaveBalance::where('employee_id', $employee->id)->where('year', 2028)->sum('total_quota');
        $this->assertEquals(18.0, (float) $totalEntitlement);
    }

    public function test_missing_canonical_annual_leave_type_fails_safely(): void
    {
        // No 'Annual Leave' or 'Personal Leave' configured at all.
        LeaveType::where('id', $this->annualLeaveType->id)->delete();
        $this->assertNull(LeaveType::canonicalAnnualEntitlementType());

        $employee = $this->makeEmployee('2020-01-01');

        // Command must fail clearly (non-zero exit) and write nothing —
        // never fabricate a balance under some other/random leave type.
        $exitCode = Artisan::call('leave:initialize-year', ['year' => 2029]);
        $this->assertNotSame(0, $exitCode);
        $this->assertSame(0, LeaveBalance::where('year', 2029)->count());

        // Admin opening-balance page must also refuse, not silently proceed.
        $admin    = $this->actingAdmin();
        $response = $this->actingAs($admin)->get(
            route('hr.leave-balances.edit', ['employee' => $employee->id, 'year' => 2029])
        );
        $response->assertStatus(404);
    }

    // ── 23. Duplicate employee + leave_type + year is rejected ──────────────

    public function test_duplicate_balance_row_is_rejected_by_unique_constraint(): void
    {
        $employee = $this->makeEmployee('2020-01-01');
        LeaveBalance::create([
            'employee_id' => $employee->id, 'leave_type_id' => $this->annualLeaveType->id,
            'year' => 2027, 'total_quota' => 18, 'used' => 0, 'remaining' => 18,
        ]);

        $this->expectException(QueryException::class);
        LeaveBalance::create([
            'employee_id' => $employee->id, 'leave_type_id' => $this->annualLeaveType->id,
            'year' => 2027, 'total_quota' => 18, 'used' => 0, 'remaining' => 18,
        ]);
    }

    // ── 16-19, 22, 29. Opening balance admin UI ───────────────────────────────

    private function actingAdmin(): User
    {
        return User::factory()->create(['role' => 'admin_hr', 'is_active' => true]);
    }

    public function test_opening_balance_for_long_tenured_employee_starts_from_18_days(): void
    {
        $employee = $this->makeEmployee('2020-01-01');
        $admin    = $this->actingAdmin();

        $response = $this->actingAs($admin)->put(route('hr.leave-balances.update', $employee), [
            'year'                 => 2026,
            'leave_type_id'        => $this->annualLeaveType->id,
            'pre_system_used_days' => 0,
            'reason'               => 'Saldo awal transisi HRIS, tidak ada pemakaian sebelum sistem.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leave_balances', [
            'employee_id' => $employee->id, 'year' => 2026, 'total_quota' => 18, 'remaining' => 18,
        ]);
    }

    public function test_pre_system_usage_reduces_opening_balance(): void
    {
        $employee = $this->makeEmployee('2020-01-01');
        $admin    = $this->actingAdmin();

        $this->actingAs($admin)->put(route('hr.leave-balances.update', $employee), [
            'year'                 => 2026,
            'leave_type_id'        => $this->annualLeaveType->id,
            'pre_system_used_days' => 6,
            'reason'               => 'Pemakaian Januari-Juni sebelum HRIS digunakan.',
        ]);

        $this->assertDatabaseHas('leave_balances', [
            'employee_id' => $employee->id, 'year' => 2026,
            'total_quota' => 18, 'used' => 6, 'remaining' => 12,
        ]);
    }

    public function test_setting_opening_balance_does_not_change_join_date(): void
    {
        $employee = $this->makeEmployee('2020-01-01');
        $admin    = $this->actingAdmin();

        $this->actingAs($admin)->put(route('hr.leave-balances.update', $employee), [
            'year'                 => 2026,
            'leave_type_id'        => $this->annualLeaveType->id,
            'pre_system_used_days' => 4,
            'reason'               => 'Penyesuaian saldo transisi.',
        ]);

        $this->assertSame('2020-01-01', $employee->fresh()->join_date->toDateString());
    }

    public function test_opening_balance_adjustment_requires_a_reason(): void
    {
        $employee = $this->makeEmployee('2020-01-01');
        $admin    = $this->actingAdmin();

        $response = $this->actingAs($admin)->put(route('hr.leave-balances.update', $employee), [
            'year'                 => 2026,
            'leave_type_id'        => $this->annualLeaveType->id,
            'pre_system_used_days' => 4,
        ]);

        $response->assertSessionHasErrors('reason');
        $this->assertDatabaseMissing('leave_balances', ['employee_id' => $employee->id]);
    }

    public function test_employee_cannot_update_opening_balance(): void
    {
        $employee     = $this->makeEmployee('2020-01-01');
        $otherEmployee = $this->makeEmployee('2021-01-01');

        $response = $this->actingAs($otherEmployee->user)->put(route('hr.leave-balances.update', $employee), [
            'year'                 => 2026,
            'leave_type_id'        => $this->annualLeaveType->id,
            'pre_system_used_days' => 0,
            'reason'               => 'Attempted unauthorized change.',
        ]);

        $response->assertStatus(403);
    }

    public function test_finance_cannot_update_opening_balance(): void
    {
        $employee    = $this->makeEmployee('2020-01-01');
        $financeUser = User::factory()->create(['role' => 'finance', 'is_active' => true]);

        $response = $this->actingAs($financeUser)->put(route('hr.leave-balances.update', $employee), [
            'year'                 => 2026,
            'leave_type_id'        => $this->annualLeaveType->id,
            'pre_system_used_days' => 0,
            'reason'               => 'Attempted unauthorized change.',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_hr_and_super_admin_can_update_opening_balance(): void
    {
        $employee   = $this->makeEmployee('2020-01-01');
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $response = $this->actingAs($superAdmin)->put(route('hr.leave-balances.update', $employee), [
            'year'                 => 2026,
            'leave_type_id'        => $this->annualLeaveType->id,
            'pre_system_used_days' => 0,
            'reason'               => 'Saldo awal ditetapkan oleh super admin.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leave_balances', ['employee_id' => $employee->id, 'year' => 2026]);
    }

    public function test_opening_balance_adjustment_creates_audit_log(): void
    {
        $employee = $this->makeEmployee('2020-01-01');
        $admin    = $this->actingAdmin();

        $this->actingAs($admin)->put(route('hr.leave-balances.update', $employee), [
            'year'                 => 2026,
            'leave_type_id'        => $this->annualLeaveType->id,
            'pre_system_used_days' => 4,
            'reason'               => 'Penyesuaian saldo transisi.',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'set_opening_leave_balance',
            'user_id' => $admin->id,
        ]);
    }

    // ── opening_adjustment semantics: positive adds, negative subtracts ──────
    // remaining = entitlement - pre_system_used_days + opening_adjustment

    public function test_zero_adjustment_leaves_balance_as_entitlement_minus_usage(): void
    {
        $employee = $this->makeEmployee('2020-01-01');
        $admin    = $this->actingAdmin();

        $this->actingAs($admin)->put(route('hr.leave-balances.update', $employee), [
            'year'                 => 2026,
            'pre_system_used_days' => 4,
            'opening_adjustment'   => 0,
            'reason'               => 'Tidak ada penyesuaian tambahan.',
        ]);

        $this->assertDatabaseHas('leave_balances', [
            'employee_id' => $employee->id, 'year' => 2026,
            'total_quota' => 18, 'used' => 4, 'remaining' => 14,
        ]);
    }

    public function test_positive_adjustment_adds_to_balance(): void
    {
        $employee = $this->makeEmployee('2020-01-01');
        $admin    = $this->actingAdmin();

        $this->actingAs($admin)->put(route('hr.leave-balances.update', $employee), [
            'year'                 => 2026,
            'pre_system_used_days' => 4,
            'opening_adjustment'   => 1,
            'reason'               => 'Koreksi penambahan saldo terverifikasi.',
        ]);

        $this->assertDatabaseHas('leave_balances', [
            'employee_id' => $employee->id, 'year' => 2026, 'remaining' => 15,
        ]);
    }

    public function test_negative_adjustment_subtracts_from_balance(): void
    {
        $employee = $this->makeEmployee('2020-01-01');
        $admin    = $this->actingAdmin();

        $this->actingAs($admin)->put(route('hr.leave-balances.update', $employee), [
            'year'                 => 2026,
            'pre_system_used_days' => 4,
            'opening_adjustment'   => -1,
            'reason'               => 'Koreksi pengurangan saldo terverifikasi.',
        ]);

        $this->assertDatabaseHas('leave_balances', [
            'employee_id' => $employee->id, 'year' => 2026, 'remaining' => 13,
        ]);
    }

    public function test_negative_resulting_balance_is_rejected(): void
    {
        $employee = $this->makeEmployee('2020-01-01');
        $admin    = $this->actingAdmin();

        $response = $this->actingAs($admin)->put(route('hr.leave-balances.update', $employee), [
            'year'                 => 2026,
            'pre_system_used_days' => 18,
            'opening_adjustment'   => -5,
            'reason'               => 'Percobaan saldo negatif.',
        ]);

        $response->assertSessionHasErrors('pre_system_used_days');
        $this->assertDatabaseMissing('leave_balances', ['employee_id' => $employee->id, 'year' => 2026]);
    }

    public function test_balance_exceeding_entitlement_is_rejected(): void
    {
        $employee = $this->makeEmployee('2020-01-01');
        $admin    = $this->actingAdmin();

        $response = $this->actingAs($admin)->put(route('hr.leave-balances.update', $employee), [
            'year'                 => 2026,
            'pre_system_used_days' => 0,
            'opening_adjustment'   => 5,
            'reason'               => 'Percobaan saldo melebihi hak tahunan.',
        ]);

        $response->assertSessionHasErrors('opening_adjustment');
        $this->assertDatabaseMissing('leave_balances', ['employee_id' => $employee->id, 'year' => 2026]);
    }

    public function test_nonzero_adjustment_without_reason_is_rejected(): void
    {
        $employee = $this->makeEmployee('2020-01-01');
        $admin    = $this->actingAdmin();

        $response = $this->actingAs($admin)->put(route('hr.leave-balances.update', $employee), [
            'year'                 => 2026,
            'pre_system_used_days' => 4,
            'opening_adjustment'   => 1,
        ]);

        $response->assertSessionHasErrors('reason');
        $this->assertDatabaseMissing('leave_balances', ['employee_id' => $employee->id, 'year' => 2026]);
    }
}
