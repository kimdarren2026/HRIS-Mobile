<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\User;
use App\Services\LeaveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

class Phase38AuditLowPriorityTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $hrUser;
    private User $financeUser;
    private User $employeeUser;
    private Employee $employee;
    private LeaveType $balanceLeaveType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);

        $dept     = Department::create(['name' => 'Engineering', 'description' => '']);
        $position = Position::create(['name' => 'Dev', 'department_id' => $dept->id]);

        $this->superAdmin  = User::factory()->create(['role' => 'super_admin',  'is_active' => true]);
        $this->hrUser      = User::factory()->create(['role' => 'admin_hr',     'is_active' => true]);
        $this->financeUser = User::factory()->create(['role' => 'finance',      'is_active' => true]);
        $this->employeeUser = User::factory()->create(['role' => 'employee',    'is_active' => true]);

        $this->employee = Employee::create([
            'user_id'           => $this->employeeUser->id,
            'nik'               => 'P38-EMP-001',
            'department_id'     => $dept->id,
            'position_id'       => $position->id,
            'join_date'         => '2026-01-01',
            'employment_status' => 'active',
            'phone_number'      => '+62812345678',
        ]);

        $this->balanceLeaveType = LeaveType::create(['name' => 'Annual Leave', 'deducts_balance' => true]);
    }

    // ── Fix 1: Orphan blade removed ────────────────────────────────────────────

    public function test_hr_employees_route_returns_200_for_admin_hr(): void
    {
        $this->actingAs($this->hrUser)
            ->get('/hr/employees')
            ->assertOk();
    }

    public function test_orphan_blade_file_no_longer_exists(): void
    {
        $this->assertFileDoesNotExist(
            resource_path('views/pages/hr/employees.blade.php')
        );
    }

    // ── Fix 2: Payslip detail — empty state, no fake data ─────────────────────

    public function test_payslip_detail_is_accessible_to_employee(): void
    {
        $this->actingAs($this->employeeUser)
            ->get('/payslip/detail')
            ->assertOk();
    }

    public function test_payslip_detail_shows_empty_state_message(): void
    {
        $this->actingAs($this->employeeUser)
            ->get('/payslip/detail')
            ->assertOk()
            ->assertSee('No Payslip Data Available')
            ->assertSee('external payroll integration');
    }

    public function test_payslip_detail_shows_no_fake_salary_data(): void
    {
        $this->actingAs($this->employeeUser)
            ->get('/payslip/detail')
            ->assertOk()
            ->assertDontSee('4,250')
            ->assertDontSee('Alex Rivers')
            ->assertDontSee('Basic Salary')
            ->assertDontSee('Bank Transfer Successful');
    }

    public function test_payslip_detail_forbidden_for_non_employee(): void
    {
        $this->actingAs($this->hrUser)
            ->get('/payslip/detail')
            ->assertForbidden();
    }

    public function test_payslip_detail_redirects_guest(): void
    {
        $this->get('/payslip/detail')->assertRedirect('/login');
    }

    // ── Fix 3: AuditLogController uses ROLE_SUPER_ADMIN constant ──────────────

    public function test_super_admin_can_access_audit_log_index(): void
    {
        AuditLog::create([
            'user_id'     => $this->superAdmin->id,
            'action'      => 'test_action',
            'module'      => 'test',
            'description' => 'Test entry.',
        ]);

        $this->actingAs($this->superAdmin)
            ->get('/audit-logs')
            ->assertOk();
    }

    public function test_super_admin_can_view_audit_log_detail(): void
    {
        $log = AuditLog::create([
            'user_id'     => $this->superAdmin->id,
            'action'      => 'test_action',
            'module'      => 'test',
            'description' => 'Test entry.',
        ]);

        $this->actingAs($this->superAdmin)
            ->get("/audit-logs/{$log->id}")
            ->assertOk();
    }

    public function test_admin_hr_cannot_access_audit_log_index(): void
    {
        $this->actingAs($this->hrUser)
            ->get('/audit-logs')
            ->assertForbidden();
    }

    public function test_finance_cannot_access_audit_log_index(): void
    {
        $this->actingAs($this->financeUser)
            ->get('/audit-logs')
            ->assertForbidden();
    }

    // ── Fix 4: Leave quota uses DEFAULT_ANNUAL_QUOTA constant ─────────────────

    public function test_leave_balance_has_default_annual_quota_constant(): void
    {
        $this->assertSame(12, LeaveBalance::DEFAULT_ANNUAL_QUOTA);
    }

    public function test_approve_creates_balance_with_default_annual_quota(): void
    {
        $leaveRequest = LeaveRequest::create([
            'employee_id'   => $this->employee->id,
            'leave_type_id' => $this->balanceLeaveType->id,
            'start_date'    => '2026-08-01',
            'end_date'      => '2026-08-02',
            'total_days'    => 2,
            'reason'        => 'Vacation',
            'status'        => 'PENDING_HR',
        ]);

        app(LeaveService::class)->approve($leaveRequest, $this->hrUser, null);

        $this->assertDatabaseHas('leave_balances', [
            'employee_id'   => $this->employee->id,
            'leave_type_id' => $this->balanceLeaveType->id,
            'year'          => 2026,
            'total_quota'   => LeaveBalance::DEFAULT_ANNUAL_QUOTA,
            'used'          => 2,
            'remaining'     => LeaveBalance::DEFAULT_ANNUAL_QUOTA - 2,
        ]);
    }

    // ── Fix 5: LeaveService loads leaveType explicitly before access ───────────

    public function test_leave_approve_works_when_leave_type_not_pre_loaded(): void
    {
        $leaveRequest = LeaveRequest::create([
            'employee_id'   => $this->employee->id,
            'leave_type_id' => $this->balanceLeaveType->id,
            'start_date'    => '2026-09-01',
            'end_date'      => '2026-09-01',
            'total_days'    => 1,
            'reason'        => 'Test',
            'status'        => 'PENDING_HR',
        ]);

        // Reload from DB without eager-loading to confirm loadMissing handles it
        $fresh = LeaveRequest::find($leaveRequest->id);
        $this->assertFalse($fresh->relationLoaded('leaveType'));

        app(LeaveService::class)->approve($fresh, $this->hrUser, null);

        $this->assertDatabaseHas('leave_requests', [
            'id'     => $leaveRequest->id,
            'status' => 'APPROVED',
        ]);
    }

    public function test_leave_approve_idempotent_when_leave_type_not_pre_loaded(): void
    {
        $leaveType = LeaveType::create(['name' => 'Sick Leave', 'deducts_balance' => false]);

        $leaveRequest = LeaveRequest::create([
            'employee_id'   => $this->employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date'    => '2026-10-01',
            'end_date'      => '2026-10-01',
            'total_days'    => 1,
            'reason'        => 'Sick',
            'status'        => 'PENDING_HR',
        ]);

        $fresh = LeaveRequest::find($leaveRequest->id);
        app(LeaveService::class)->approve($fresh, $this->hrUser, null);

        // No balance record created for non-deducting type
        $this->assertDatabaseMissing('leave_balances', [
            'employee_id'   => $this->employee->id,
            'leave_type_id' => $leaveType->id,
        ]);

        $this->assertDatabaseHas('leave_requests', [
            'id'     => $leaveRequest->id,
            'status' => 'APPROVED',
        ]);
    }
}
