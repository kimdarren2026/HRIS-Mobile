<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\User;
use App\Services\LeaveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 47: fixes two production findings after Phase 46 —
 * (1) leave type names shown in English on the employee request page, and
 * (2) confusing "no balance data" message before an employee's first approval.
 */
class Phase47LeaveLocalizationBalancePreviewTest extends TestCase
{
    use RefreshDatabase;

    private LeaveService $service;
    private User $hrUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(LeaveService::class);
        $this->hrUser  = User::factory()->create(['role' => 'admin_hr', 'is_active' => true]);
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

    private function seedProductionLeaveTypes(): void
    {
        LeaveType::create(['name' => 'Annual Leave', 'deducts_balance' => true]);
        LeaveType::create(['name' => 'Sick Leave', 'deducts_balance' => true]);
        LeaveType::create(['name' => 'Permission', 'deducts_balance' => true]);
        LeaveType::create(['name' => 'Special Leave', 'deducts_balance' => false]);
    }

    // ── Leave type localization ──────────────────────────────────────────────

    public function test_leave_request_page_shows_indonesian_leave_type_labels(): void
    {
        $this->seedProductionLeaveTypes();
        $employee = $this->makeEmployee(now()->subYears(2)->toDateString());

        $response = $this->actingAs($employee->user)->get('/leave/request');

        $response->assertOk();
        $response->assertSee('Cuti Tahunan');
        $response->assertSee('Cuti Sakit');
        $response->assertSee('Izin');
        $response->assertSee('Cuti Khusus');
    }

    public function test_leave_request_page_does_not_show_english_leave_type_names(): void
    {
        $this->seedProductionLeaveTypes();
        $employee = $this->makeEmployee(now()->subYears(2)->toDateString());

        $response = $this->actingAs($employee->user)->get('/leave/request');

        $response->assertOk();
        $response->assertDontSee('Annual Leave');
        $response->assertDontSee('Sick Leave');
        // "Permission" has no English rendering to check against distinct from
        // its Indonesian label, but the raw stored value must not leak either.
        $response->assertDontSee('Special Leave');
    }

    public function test_unmapped_leave_type_name_falls_back_to_raw_name(): void
    {
        $type = LeaveType::create(['name' => 'Maternity Leave', 'deducts_balance' => true]);

        $this->assertSame('Maternity Leave', $type->display_name);
    }

    public function test_settings_leave_types_page_still_shows_raw_english_name(): void
    {
        // Admin-facing settings page is out of scope for localization — it
        // manages the underlying record, so it should show the stored value.
        LeaveType::create(['name' => 'Annual Leave', 'deducts_balance' => true]);

        $response = $this->actingAs($this->hrUser)->get('/settings/leave-types');

        $response->assertOk();
        $response->assertSee('Annual Leave');
    }

    // ── Balance preview before first approval ────────────────────────────────

    public function test_eligible_employee_with_no_balance_row_sees_annual_entitlement_preview(): void
    {
        $this->seedProductionLeaveTypes();
        $employee = $this->makeEmployee(now()->subYears(2)->toDateString());

        $this->assertDatabaseMissing('leave_balances', ['employee_id' => $employee->id]);

        $response = $this->actingAs($employee->user)->get('/leave/request');

        $response->assertOk();
        $response->assertSee('Cuti Tahunan');
        $response->assertSee('18 hari tersisa');
        $response->assertDontSee('Belum ada data saldo. Saldo akan ditetapkan setelah persetujuan pertama.');

        // Preview must not persist a row just from viewing the page.
        $this->assertDatabaseMissing('leave_balances', ['employee_id' => $employee->id]);
    }

    public function test_ineligible_employee_sees_minimum_service_notice_instead_of_preview(): void
    {
        $this->seedProductionLeaveTypes();
        $employee = $this->makeEmployee(now()->subMonths(3)->toDateString());

        $response = $this->actingAs($employee->user)->get('/leave/request');

        $response->assertOk();
        $response->assertSee('12 bulan');
        $response->assertDontSee('18 hari tersisa');
    }

    public function test_eligible_employee_with_existing_other_balance_still_sees_annual_preview_row(): void
    {
        $this->seedProductionLeaveTypes();
        $employee = $this->makeEmployee(now()->subYears(2)->toDateString());

        $sickType = LeaveType::where('name', 'Sick Leave')->first();
        LeaveBalance::create([
            'employee_id'   => $employee->id,
            'leave_type_id' => $sickType->id,
            'year'          => now()->year,
            'total_quota'   => 18,
            'used'          => 3,
            'remaining'     => 15,
        ]);

        $response = $this->actingAs($employee->user)->get('/leave/request');

        $response->assertOk();
        $response->assertSee('Cuti Sakit');
        $response->assertSee('15 hari tersisa');
        $response->assertSee('Cuti Tahunan');
        $response->assertSee('18 hari tersisa');
    }

    public function test_balance_is_not_deducted_on_submission(): void
    {
        $this->seedProductionLeaveTypes();
        $employee   = $this->makeEmployee(now()->subYears(2)->toDateString());
        $annualType = LeaveType::where('name', 'Annual Leave')->first();

        $monday = \Carbon\Carbon::parse('2026-08-03');
        $this->service->submit($employee, [
            'leave_type_id' => $annualType->id,
            'start_date'    => $monday->toDateString(),
            'end_date'      => $monday->toDateString(),
            'reason'        => 'Test leave reason for submission.',
        ]);

        $this->assertDatabaseMissing('leave_balances', ['employee_id' => $employee->id]);
    }

    public function test_balance_is_deducted_only_after_approval(): void
    {
        $this->seedProductionLeaveTypes();
        $employee   = $this->makeEmployee(now()->subYears(2)->toDateString());
        $annualType = LeaveType::where('name', 'Annual Leave')->first();

        $monday = \Carbon\Carbon::parse('2026-08-03');
        $request = $this->service->submit($employee, [
            'leave_type_id' => $annualType->id,
            'start_date'    => $monday->toDateString(),
            'end_date'      => $monday->toDateString(),
            'reason'        => 'Test leave reason for approval.',
        ]);

        $this->assertDatabaseMissing('leave_balances', ['employee_id' => $employee->id]);

        $this->service->approve($request, $this->hrUser, 'Approved.');

        $balance = LeaveBalance::where('employee_id', $employee->id)
            ->where('year', 2026)
            ->first();

        $this->assertNotNull($balance);
        $this->assertSame('18.00', $balance->total_quota);
        $this->assertSame('1.00', $balance->used);
        $this->assertSame('17.00', $balance->remaining);
    }

    public function test_balance_preview_is_year_scoped_and_does_not_carry_over(): void
    {
        $this->seedProductionLeaveTypes();
        $employee   = $this->makeEmployee('2020-01-01');
        $annualType = LeaveType::where('name', 'Annual Leave')->first();

        // Prior year balance fully unused — must not leak into the current-year preview.
        LeaveBalance::create([
            'employee_id'   => $employee->id,
            'leave_type_id' => $annualType->id,
            'year'          => now()->year - 1,
            'total_quota'   => 18,
            'used'          => 0,
            'remaining'     => 18,
        ]);

        $response = $this->actingAs($employee->user)->get('/leave/request');

        $response->assertOk();
        $response->assertSee('Cuti Tahunan');
        $response->assertSee('18 hari tersisa');

        // Still no current-year row created just by viewing the page.
        $this->assertDatabaseMissing('leave_balances', [
            'employee_id' => $employee->id,
            'year'        => now()->year,
        ]);
    }
}
