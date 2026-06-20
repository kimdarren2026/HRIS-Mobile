<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\OfficeLocation;
use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase19SelfServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $financeUser;
    private User $adminHrUser;
    private User $employeeUser;
    private User $superAdminUser;
    private User $financeUserNoEmp;
    private User $adminHrUserNoEmp;
    private Employee $financeEmployee;
    private Employee $adminHrEmployee;
    private Employee $employeeRecord;
    private PayrollPeriod $paidPeriod;
    private PayrollRecord $financePayrollRecord;

    protected function setUp(): void
    {
        parent::setUp();

        $dept     = Department::create(['name' => 'Operations', 'description' => '']);
        $position = Position::create(['name' => 'Analyst', 'department_id' => $dept->id]);

        $base = [
            'department_id'     => $dept->id,
            'position_id'       => $position->id,
            'join_date'         => '2026-01-01',
            'employment_status' => 'active',
            'phone_number'      => '+62800000001',
        ];

        // Users WITH linked employee records
        $this->financeUser   = User::factory()->create(['role' => 'finance',     'is_active' => true]);
        $this->adminHrUser   = User::factory()->create(['role' => 'admin_hr',    'is_active' => true]);
        $this->employeeUser  = User::factory()->create(['role' => 'employee',    'is_active' => true]);
        $this->superAdminUser = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->financeEmployee  = Employee::create(['user_id' => $this->financeUser->id,  'nik' => 'P19-FIN-01'] + $base);
        $this->adminHrEmployee  = Employee::create(['user_id' => $this->adminHrUser->id,  'nik' => 'P19-HR-01'] + $base);
        $this->employeeRecord   = Employee::create(['user_id' => $this->employeeUser->id, 'nik' => 'P19-EMP-01'] + $base);

        // Users WITHOUT linked employee records (for HasEmployee middleware tests)
        $this->financeUserNoEmp  = User::factory()->create(['role' => 'finance',  'is_active' => true]);
        $this->adminHrUserNoEmp  = User::factory()->create(['role' => 'admin_hr', 'is_active' => true]);

        // Payroll data for finance user's employee
        $this->paidPeriod = PayrollPeriod::create([
            'name'       => 'June 2026 Payroll',
            'start_date' => '2026-06-01',
            'end_date'   => '2026-06-30',
            'status'     => 'PAID',
            'created_by' => $this->financeUser->id,
        ]);

        $this->financePayrollRecord = PayrollRecord::create([
            'payroll_period_id'    => $this->paidPeriod->id,
            'employee_id'          => $this->financeEmployee->id,
            'basic_salary'         => '6000000',
            'allowance'            => '500000',
            'bonus'                => '0',
            'overtime'             => '0',
            'deduction'            => '0',
            'late_deduction'       => '0',
            'attendance_deduction' => '0',
            'tax_bpjs'             => '300000',
            'net_salary'           => '6200000',
            'attendance_days'      => 22,
            'leave_days'           => '0',
        ]);

        OfficeLocation::create([
            'name'          => 'Test Office',
            'latitude'      => -6.2,
            'longitude'     => 106.816,
            'radius_meters' => 100,
            'is_active'     => true,
        ]);
    }

    // ── Part A: Finance self-service ──────────────────────────────────────────

    public function test_finance_with_employee_can_access_attendance_checkin(): void
    {
        $this->actingAs($this->financeUser)
            ->get('/attendance/checkin')
            ->assertOk();
    }

    public function test_finance_with_employee_can_access_attendance_history(): void
    {
        $this->actingAs($this->financeUser)
            ->get('/attendance/history')
            ->assertOk();
    }

    public function test_finance_with_employee_can_access_leave_request(): void
    {
        LeaveType::create(['name' => 'Annual Leave', 'deducts_balance' => true]);

        $this->actingAs($this->financeUser)
            ->get('/leave/request')
            ->assertOk();
    }

    public function test_finance_with_employee_can_access_leave_history(): void
    {
        $this->actingAs($this->financeUser)
            ->get('/leave/history')
            ->assertOk();
    }

    public function test_finance_with_employee_can_access_own_payroll_index(): void
    {
        $this->actingAs($this->financeUser)
            ->get('/my/payroll')
            ->assertOk();
    }

    public function test_finance_with_employee_can_access_own_profile(): void
    {
        $this->actingAs($this->financeUser)
            ->get('/my/profile')
            ->assertOk();
    }

    public function test_finance_still_forbidden_from_hr_approval_queue(): void
    {
        $this->actingAs($this->financeUser)
            ->get('/hr/approval-queue')
            ->assertForbidden();
    }

    public function test_finance_with_employee_forbidden_from_employee_dashboard(): void
    {
        $this->actingAs($this->financeUser)
            ->get('/employee/dashboard')
            ->assertForbidden();
    }

    // ── Part A: Admin HR self-service ─────────────────────────────────────────

    public function test_admin_hr_with_employee_can_access_attendance_checkin(): void
    {
        $this->actingAs($this->adminHrUser)
            ->get('/attendance/checkin')
            ->assertOk();
    }

    public function test_admin_hr_with_employee_can_access_own_profile(): void
    {
        $this->actingAs($this->adminHrUser)
            ->get('/my/profile')
            ->assertOk();
    }

    public function test_admin_hr_with_employee_can_access_own_payroll(): void
    {
        $this->actingAs($this->adminHrUser)
            ->get('/my/payroll')
            ->assertOk();
    }

    public function test_admin_hr_with_employee_can_access_leave_history(): void
    {
        $this->actingAs($this->adminHrUser)
            ->get('/leave/history')
            ->assertOk();
    }

    public function test_admin_hr_still_forbidden_from_payroll_export(): void
    {
        $this->actingAs($this->adminHrUser)
            ->get("/payroll/periods/{$this->paidPeriod->id}/export")
            ->assertForbidden();
    }

    public function test_admin_hr_still_forbidden_from_finance_approve(): void
    {
        $period = PayrollPeriod::create([
            'name'       => 'HR Review Period',
            'start_date' => '2026-08-01',
            'end_date'   => '2026-08-31',
            'status'     => 'HR_REVIEW',
            'created_by' => $this->financeUser->id,
        ]);

        $this->actingAs($this->adminHrUser)
            ->post("/payroll/periods/{$period->id}/finance-approve")
            ->assertForbidden();
    }

    // ── Part A: HasEmployee middleware blocks users without employee record ────

    public function test_finance_without_employee_cannot_access_attendance_checkin(): void
    {
        $this->actingAs($this->financeUserNoEmp)
            ->get('/attendance/checkin')
            ->assertForbidden();
    }

    public function test_finance_without_employee_cannot_access_own_payroll(): void
    {
        $this->actingAs($this->financeUserNoEmp)
            ->get('/my/payroll')
            ->assertForbidden();
    }

    public function test_admin_hr_without_employee_cannot_access_own_profile(): void
    {
        $this->actingAs($this->adminHrUserNoEmp)
            ->get('/my/profile')
            ->assertForbidden();
    }

    public function test_admin_hr_without_employee_cannot_access_leave_history(): void
    {
        $this->actingAs($this->adminHrUserNoEmp)
            ->get('/leave/history')
            ->assertForbidden();
    }

    // ── Part B: Settings access control ──────────────────────────────────────

    public function test_employee_forbidden_from_settings_page(): void
    {
        $this->actingAs($this->employeeUser)
            ->get('/settings')
            ->assertForbidden();
    }

    public function test_finance_forbidden_from_settings_page(): void
    {
        $this->actingAs($this->financeUser)
            ->get('/settings')
            ->assertForbidden();
    }

    public function test_admin_hr_can_access_settings_page(): void
    {
        $this->actingAs($this->adminHrUser)
            ->get('/settings')
            ->assertOk();
    }

    public function test_super_admin_can_access_settings_page(): void
    {
        $this->actingAs($this->superAdminUser)
            ->get('/settings')
            ->assertOk();
    }

    public function test_settings_page_shows_real_office_name(): void
    {
        $this->actingAs($this->adminHrUser)
            ->get('/settings')
            ->assertOk()
            ->assertSee('Test Office');
    }

    public function test_settings_page_shows_real_office_radius(): void
    {
        $this->actingAs($this->adminHrUser)
            ->get('/settings')
            ->assertOk()
            ->assertSee('100');
    }

    public function test_settings_page_has_functional_manage_location_link(): void
    {
        $office = OfficeLocation::where('is_active', true)->first();

        $this->actingAs($this->adminHrUser)
            ->get('/settings')
            ->assertOk()
            ->assertSee('settings/locations/' . $office->id . '/edit');
    }

    public function test_settings_page_has_functional_manage_leave_types_link(): void
    {
        $this->actingAs($this->adminHrUser)
            ->get('/settings')
            ->assertOk()
            ->assertSee('settings/leave-types');
    }

    public function test_settings_page_does_not_have_broken_manage_payroll_button(): void
    {
        $response = $this->actingAs($this->adminHrUser)
            ->get('/settings');

        $response->assertOk();
        // Should not contain the old broken standalone button
        $response->assertDontSee('Manage Payroll Rules');
    }

    // ── Part B: Office location management ───────────────────────────────────

    public function test_admin_hr_can_access_office_location_edit(): void
    {
        $office = OfficeLocation::where('is_active', true)->first();

        $this->actingAs($this->adminHrUser)
            ->get("/settings/locations/{$office->id}/edit")
            ->assertOk()
            ->assertSee($office->name);
    }

    public function test_employee_forbidden_from_office_location_edit(): void
    {
        $office = OfficeLocation::where('is_active', true)->first();

        $this->actingAs($this->employeeUser)
            ->get("/settings/locations/{$office->id}/edit")
            ->assertForbidden();
    }

    public function test_finance_forbidden_from_office_location_edit(): void
    {
        $office = OfficeLocation::where('is_active', true)->first();

        $this->actingAs($this->financeUser)
            ->get("/settings/locations/{$office->id}/edit")
            ->assertForbidden();
    }

    public function test_admin_hr_can_update_office_location_radius(): void
    {
        $office = OfficeLocation::where('is_active', true)->first();

        $this->actingAs($this->adminHrUser)
            ->put("/settings/locations/{$office->id}", [
                'name'          => $office->name,
                'latitude'      => $office->latitude,
                'longitude'     => $office->longitude,
                'radius_meters' => 200,
            ])
            ->assertRedirect('/settings');

        $this->assertEquals(200, $office->fresh()->radius_meters);
    }

    // ── Part B: Leave type management ────────────────────────────────────────

    public function test_admin_hr_can_access_leave_types_management(): void
    {
        LeaveType::create(['name' => 'Annual Leave', 'deducts_balance' => true]);

        $this->actingAs($this->adminHrUser)
            ->get('/settings/leave-types')
            ->assertOk()
            ->assertSee('Annual Leave');
    }

    public function test_finance_forbidden_from_leave_types_management(): void
    {
        $this->actingAs($this->financeUser)
            ->get('/settings/leave-types')
            ->assertForbidden();
    }

    public function test_employee_forbidden_from_leave_types_management(): void
    {
        $this->actingAs($this->employeeUser)
            ->get('/settings/leave-types')
            ->assertForbidden();
    }

    public function test_admin_hr_can_create_leave_type(): void
    {
        $this->actingAs($this->adminHrUser)
            ->post('/settings/leave-types', [
                'name'            => 'Maternity Leave',
                'deducts_balance' => '1',
            ])
            ->assertRedirect('/settings/leave-types');

        $this->assertDatabaseHas('leave_types', ['name' => 'Maternity Leave']);
    }

    public function test_finance_cannot_create_leave_type(): void
    {
        $this->actingAs($this->financeUser)
            ->post('/settings/leave-types', [
                'name' => 'Hack Leave Type',
            ])
            ->assertForbidden();
    }

    public function test_admin_hr_can_update_leave_type(): void
    {
        $leaveType = LeaveType::create(['name' => 'Old Name', 'deducts_balance' => true]);

        $this->actingAs($this->adminHrUser)
            ->put("/settings/leave-types/{$leaveType->id}", [
                'name'            => 'Updated Name',
                'deducts_balance' => '0',
            ])
            ->assertRedirect('/settings/leave-types');

        $this->assertDatabaseHas('leave_types', ['id' => $leaveType->id, 'name' => 'Updated Name']);
    }

    // ── Part C: Attendance uses configured radius ─────────────────────────────

    public function test_attendance_service_uses_configured_radius_from_db(): void
    {
        $office = OfficeLocation::where('is_active', true)->first();
        $office->update(['radius_meters' => 500]);

        // Re-fetch to confirm DB value was stored
        $this->assertEquals(500, $office->fresh()->radius_meters);

        // Service reads from DB, not hardcoded value
        $service = app(\App\Services\AttendanceService::class);
        $fetched = $service->getActiveOffice();
        $this->assertEquals(500, $fetched->radius_meters);
    }

    // ── Profile redirect: all roles with employee redirect to my.profile ──────

    public function test_profile_route_redirects_finance_with_employee_to_my_profile(): void
    {
        $this->actingAs($this->financeUser)
            ->get('/profile')
            ->assertRedirect(route('my.profile'));
    }

    public function test_profile_route_redirects_admin_hr_with_employee_to_my_profile(): void
    {
        $this->actingAs($this->adminHrUser)
            ->get('/profile')
            ->assertRedirect(route('my.profile'));
    }

    public function test_my_profile_shows_employee_name_for_finance(): void
    {
        $this->actingAs($this->financeUser)
            ->get('/my/profile')
            ->assertOk()
            ->assertSee($this->financeUser->name);
    }

    public function test_my_profile_shows_employee_name_for_admin_hr(): void
    {
        $this->actingAs($this->adminHrUser)
            ->get('/my/profile')
            ->assertOk()
            ->assertSee($this->adminHrUser->name);
    }
}
