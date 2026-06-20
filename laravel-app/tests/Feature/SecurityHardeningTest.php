<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private User $employeeUser;
    private User $otherEmployeeUser;
    private User $adminHrUser;
    private User $financeUser;
    private User $superAdminUser;
    private Employee $employee;
    private Employee $otherEmployee;
    private PayrollPeriod $calculatedPeriod;
    private PayrollRecord $ownRecord;
    private PayrollRecord $otherRecord;

    protected function setUp(): void
    {
        parent::setUp();

        $dept     = Department::create(['name' => 'Engineering', 'description' => '']);
        $position = Position::create(['name' => 'Dev', 'department_id' => $dept->id]);

        $this->employeeUser      = User::factory()->create(['role' => 'employee',    'is_active' => true]);
        $this->otherEmployeeUser = User::factory()->create(['role' => 'employee',    'is_active' => true]);
        $this->adminHrUser       = User::factory()->create(['role' => 'admin_hr',    'is_active' => true]);
        $this->financeUser       = User::factory()->create(['role' => 'finance',     'is_active' => true]);
        $this->superAdminUser    = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $base = [
            'department_id'     => $dept->id,
            'position_id'       => $position->id,
            'join_date'         => '2026-01-01',
            'employment_status' => 'active',
            'phone_number'      => '+62812345678',
        ];

        $this->employee      = Employee::create(['user_id' => $this->employeeUser->id,      'nik' => 'SEC-001'] + $base);
        $this->otherEmployee = Employee::create(['user_id' => $this->otherEmployeeUser->id, 'nik' => 'SEC-002'] + $base);

        $this->calculatedPeriod = PayrollPeriod::create([
            'name'       => 'June 2026 Payroll',
            'start_date' => '2026-06-01',
            'end_date'   => '2026-06-30',
            'status'     => 'CALCULATED',
            'created_by' => $this->financeUser->id,
        ]);

        $recordBase = [
            'payroll_period_id'    => $this->calculatedPeriod->id,
            'basic_salary'         => '5000000',
            'allowance'            => '500000',
            'bonus'                => '0',
            'overtime'             => '0',
            'deduction'            => '0',
            'late_deduction'       => '0',
            'attendance_deduction' => '0',
            'tax_bpjs'             => '250000',
            'net_salary'           => '5250000',
            'attendance_days'      => 22,
            'leave_days'           => '1',
        ];

        $this->ownRecord   = PayrollRecord::create(['employee_id' => $this->employee->id]      + $recordBase);
        $this->otherRecord = PayrollRecord::create(['employee_id' => $this->otherEmployee->id] + $recordBase);
    }

    // ── Unauthenticated redirects ─────────────────────────────────────────────

    public function test_guest_redirected_from_employee_dashboard(): void
    {
        $this->get('/employee/dashboard')->assertRedirect('/login');
    }

    public function test_guest_redirected_from_admin_dashboard(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_guest_redirected_from_finance_dashboard(): void
    {
        $this->get('/finance/dashboard')->assertRedirect('/login');
    }

    public function test_guest_redirected_from_attendance_checkin(): void
    {
        $this->get('/attendance/checkin')->assertRedirect('/login');
    }

    public function test_guest_redirected_from_leave_request(): void
    {
        $this->get('/leave/request')->assertRedirect('/login');
    }

    public function test_guest_redirected_from_payroll_periods(): void
    {
        $this->get('/payroll/periods')->assertRedirect('/login');
    }

    public function test_guest_redirected_from_employee_directory(): void
    {
        $this->get('/employees')->assertRedirect('/login');
    }

    public function test_guest_redirected_from_my_payroll(): void
    {
        $this->get('/my/payroll')->assertRedirect('/login');
    }

    public function test_guest_redirected_from_hr_approval_queue(): void
    {
        $this->get('/hr/approval-queue')->assertRedirect('/login');
    }

    // ── Employee cross-boundary access ────────────────────────────────────────

    public function test_employee_forbidden_from_hr_employee_directory(): void
    {
        $this->actingAs($this->employeeUser)
            ->get('/employees')
            ->assertForbidden();
    }

    public function test_employee_forbidden_from_admin_dashboard(): void
    {
        $this->actingAs($this->employeeUser)
            ->get('/admin/dashboard')
            ->assertForbidden();
    }

    public function test_employee_forbidden_from_finance_dashboard(): void
    {
        $this->actingAs($this->employeeUser)
            ->get('/finance/dashboard')
            ->assertForbidden();
    }

    public function test_employee_forbidden_from_payroll_periods_index(): void
    {
        $this->actingAs($this->employeeUser)
            ->get('/payroll/periods')
            ->assertForbidden();
    }

    public function test_employee_forbidden_from_payroll_period_detail(): void
    {
        $this->actingAs($this->employeeUser)
            ->get("/payroll/periods/{$this->calculatedPeriod->id}")
            ->assertForbidden();
    }

    public function test_employee_forbidden_from_payroll_csv_export(): void
    {
        $this->actingAs($this->employeeUser)
            ->get("/payroll/periods/{$this->calculatedPeriod->id}/export")
            ->assertForbidden();
    }

    public function test_employee_forbidden_from_another_employee_payroll_detail(): void
    {
        $this->actingAs($this->employeeUser)
            ->get("/my/payroll/{$this->otherRecord->id}")
            ->assertForbidden();
    }

    public function test_employee_forbidden_from_another_employee_payslip_print(): void
    {
        $this->actingAs($this->employeeUser)
            ->get("/my/payroll/{$this->otherRecord->id}/print")
            ->assertForbidden();
    }

    public function test_employee_forbidden_from_hr_approval_queue(): void
    {
        $this->actingAs($this->employeeUser)
            ->get('/hr/approval-queue')
            ->assertForbidden();
    }

    public function test_employee_forbidden_from_create_payroll_period(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/payroll/periods', [
                'name'       => 'Hack Period',
                'start_date' => '2026-08-01',
                'end_date'   => '2026-08-31',
            ])
            ->assertForbidden();
    }

    public function test_employee_forbidden_from_payroll_calculate(): void
    {
        $this->actingAs($this->employeeUser)
            ->post("/payroll/periods/{$this->calculatedPeriod->id}/calculate")
            ->assertForbidden();
    }

    public function test_employee_forbidden_from_payroll_submit_hr_review(): void
    {
        $this->actingAs($this->employeeUser)
            ->post("/payroll/periods/{$this->calculatedPeriod->id}/submit-hr-review")
            ->assertForbidden();
    }

    public function test_employee_forbidden_from_payroll_finance_approve(): void
    {
        $this->actingAs($this->employeeUser)
            ->post("/payroll/periods/{$this->calculatedPeriod->id}/finance-approve")
            ->assertForbidden();
    }

    public function test_employee_forbidden_from_payroll_lock(): void
    {
        $this->actingAs($this->employeeUser)
            ->post("/payroll/periods/{$this->calculatedPeriod->id}/lock")
            ->assertForbidden();
    }

    public function test_employee_forbidden_from_payroll_mark_paid(): void
    {
        $this->actingAs($this->employeeUser)
            ->post("/payroll/periods/{$this->calculatedPeriod->id}/mark-paid")
            ->assertForbidden();
    }

    // ── Finance cross-boundary access ─────────────────────────────────────────

    public function test_finance_forbidden_from_employee_directory(): void
    {
        $this->actingAs($this->financeUser)
            ->get('/employees')
            ->assertForbidden();
    }

    public function test_finance_forbidden_from_employee_detail(): void
    {
        $this->actingAs($this->financeUser)
            ->get("/employees/{$this->employee->id}")
            ->assertForbidden();
    }

    public function test_finance_forbidden_from_employee_create_form(): void
    {
        $this->actingAs($this->financeUser)
            ->get('/employees/create')
            ->assertForbidden();
    }

    public function test_finance_forbidden_from_employee_edit_form(): void
    {
        $this->actingAs($this->financeUser)
            ->get("/employees/{$this->employee->id}/edit")
            ->assertForbidden();
    }

    public function test_finance_forbidden_from_hr_approval_queue(): void
    {
        $this->actingAs($this->financeUser)
            ->get('/hr/approval-queue')
            ->assertForbidden();
    }

    public function test_finance_forbidden_from_submit_hr_review(): void
    {
        $this->actingAs($this->financeUser)
            ->post("/payroll/periods/{$this->calculatedPeriod->id}/submit-hr-review")
            ->assertForbidden();
    }

    public function test_finance_forbidden_from_employee_dashboard(): void
    {
        $this->actingAs($this->financeUser)
            ->get('/employee/dashboard')
            ->assertForbidden();
    }

    public function test_finance_forbidden_from_employee_my_payroll(): void
    {
        $this->actingAs($this->financeUser)
            ->get('/my/payroll')
            ->assertForbidden();
    }

    // ── HR cross-boundary access ──────────────────────────────────────────────

    public function test_admin_hr_forbidden_from_finance_dashboard(): void
    {
        $this->actingAs($this->adminHrUser)
            ->get('/finance/dashboard')
            ->assertForbidden();
    }

    public function test_admin_hr_forbidden_from_payroll_csv_export(): void
    {
        $this->actingAs($this->adminHrUser)
            ->get("/payroll/periods/{$this->calculatedPeriod->id}/export")
            ->assertForbidden();
    }

    public function test_admin_hr_forbidden_from_finance_approve(): void
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

    public function test_admin_hr_forbidden_from_payroll_lock(): void
    {
        $period = PayrollPeriod::create([
            'name'       => 'Finance Approved Period',
            'start_date' => '2026-09-01',
            'end_date'   => '2026-09-30',
            'status'     => 'FINANCE_APPROVAL',
            'created_by' => $this->financeUser->id,
        ]);

        $this->actingAs($this->adminHrUser)
            ->post("/payroll/periods/{$period->id}/lock")
            ->assertForbidden();
    }

    public function test_admin_hr_forbidden_from_payroll_mark_paid(): void
    {
        $period = PayrollPeriod::create([
            'name'       => 'Locked Period',
            'start_date' => '2026-10-01',
            'end_date'   => '2026-10-31',
            'status'     => 'LOCKED',
            'created_by' => $this->financeUser->id,
        ]);

        $this->actingAs($this->adminHrUser)
            ->post("/payroll/periods/{$period->id}/mark-paid")
            ->assertForbidden();
    }

    public function test_admin_hr_forbidden_from_create_payroll_period(): void
    {
        $this->actingAs($this->adminHrUser)
            ->post('/payroll/periods', [
                'name'       => 'HR Created Period',
                'start_date' => '2026-11-01',
                'end_date'   => '2026-11-30',
            ])
            ->assertForbidden();
    }

    public function test_admin_hr_forbidden_from_employee_dashboard(): void
    {
        $this->actingAs($this->adminHrUser)
            ->get('/employee/dashboard')
            ->assertForbidden();
    }

    public function test_admin_hr_forbidden_from_my_payroll(): void
    {
        $this->actingAs($this->adminHrUser)
            ->get('/my/payroll')
            ->assertForbidden();
    }

    // ── Payroll approval invalid state transitions ────────────────────────────

    public function test_invalid_transition_submit_hr_review_from_draft_returns_403(): void
    {
        $draft = PayrollPeriod::create([
            'name'       => 'Draft Period',
            'start_date' => '2026-08-01',
            'end_date'   => '2026-08-31',
            'status'     => 'DRAFT',
            'created_by' => $this->financeUser->id,
        ]);

        $this->actingAs($this->adminHrUser)
            ->post("/payroll/periods/{$draft->id}/submit-hr-review")
            ->assertForbidden();
    }

    public function test_invalid_transition_finance_approve_from_draft_returns_403(): void
    {
        $draft = PayrollPeriod::create([
            'name'       => 'Draft Period 2',
            'start_date' => '2026-09-01',
            'end_date'   => '2026-09-30',
            'status'     => 'DRAFT',
            'created_by' => $this->financeUser->id,
        ]);

        $this->actingAs($this->financeUser)
            ->post("/payroll/periods/{$draft->id}/finance-approve")
            ->assertForbidden();
    }

    public function test_invalid_transition_lock_from_hr_review_returns_403(): void
    {
        $hrReview = PayrollPeriod::create([
            'name'       => 'HR Review Period 2',
            'start_date' => '2026-10-01',
            'end_date'   => '2026-10-31',
            'status'     => 'HR_REVIEW',
            'created_by' => $this->financeUser->id,
        ]);

        $this->actingAs($this->financeUser)
            ->post("/payroll/periods/{$hrReview->id}/lock")
            ->assertForbidden();
    }

    public function test_invalid_transition_mark_paid_from_calculated_returns_403(): void
    {
        $this->actingAs($this->financeUser)
            ->post("/payroll/periods/{$this->calculatedPeriod->id}/mark-paid")
            ->assertForbidden();
    }

    // ── Protected attachments ─────────────────────────────────────────────────

    public function test_employee_cannot_access_other_employee_leave_attachment(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('leave/test-attachment.pdf', 'fake-pdf-content');

        $leaveType = LeaveType::create(['name' => 'Annual', 'max_days' => 12, 'is_active' => true]);

        $otherLeaveRequest = LeaveRequest::create([
            'employee_id'     => $this->otherEmployee->id,
            'leave_type_id'   => $leaveType->id,
            'start_date'      => '2026-07-01',
            'end_date'        => '2026-07-03',
            'total_days'      => 3,
            'reason'          => 'Vacation',
            'status'          => 'PENDING_HR',
            'attachment_path' => 'leave/test-attachment.pdf',
        ]);

        $this->actingAs($this->employeeUser)
            ->get(route('leave.attachment', $otherLeaveRequest))
            ->assertForbidden();
    }

    public function test_finance_cannot_access_leave_attachment(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('leave/test-attachment-2.pdf', 'fake-pdf-content');

        $leaveType = LeaveType::create(['name' => 'Annual', 'max_days' => 12, 'is_active' => true]);

        $leaveRequest = LeaveRequest::create([
            'employee_id'     => $this->employee->id,
            'leave_type_id'   => $leaveType->id,
            'start_date'      => '2026-07-01',
            'end_date'        => '2026-07-03',
            'total_days'      => 3,
            'reason'          => 'Vacation',
            'status'          => 'PENDING_HR',
            'attachment_path' => 'leave/test-attachment-2.pdf',
        ]);

        $this->actingAs($this->financeUser)
            ->get(route('leave.attachment', $leaveRequest))
            ->assertForbidden();
    }

    public function test_employee_can_access_own_leave_attachment(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('leave/own-attachment.pdf', 'fake-pdf-content');

        $leaveType = LeaveType::create(['name' => 'Annual', 'max_days' => 12, 'is_active' => true]);

        $ownLeaveRequest = LeaveRequest::create([
            'employee_id'     => $this->employee->id,
            'leave_type_id'   => $leaveType->id,
            'start_date'      => '2026-07-01',
            'end_date'        => '2026-07-03',
            'total_days'      => 3,
            'reason'          => 'Vacation',
            'status'          => 'PENDING_HR',
            'attachment_path' => 'leave/own-attachment.pdf',
        ]);

        $this->actingAs($this->employeeUser)
            ->get(route('leave.attachment', $ownLeaveRequest))
            ->assertOk();
    }

    public function test_admin_hr_can_access_any_leave_attachment(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('leave/hr-attachment.pdf', 'fake-pdf-content');

        $leaveType = LeaveType::create(['name' => 'Annual', 'max_days' => 12, 'is_active' => true]);

        $leaveRequest = LeaveRequest::create([
            'employee_id'     => $this->employee->id,
            'leave_type_id'   => $leaveType->id,
            'start_date'      => '2026-07-01',
            'end_date'        => '2026-07-03',
            'total_days'      => 3,
            'reason'          => 'Vacation',
            'status'          => 'PENDING_HR',
            'attachment_path' => 'leave/hr-attachment.pdf',
        ]);

        $this->actingAs($this->adminHrUser)
            ->get(route('leave.attachment', $leaveRequest))
            ->assertOk();
    }

    // ── Error page rendering ──────────────────────────────────────────────────

    public function test_404_page_renders_for_unknown_route(): void
    {
        $response = $this->get('/this-route-does-not-exist-phase14');
        $response->assertNotFound();
        $response->assertSee('404');
        $response->assertSee('Not Found');
    }

    public function test_403_page_renders_for_forbidden_route(): void
    {
        $response = $this->actingAs($this->employeeUser)
            ->get('/admin/dashboard');

        $response->assertForbidden();
        $response->assertSee('403');
        $response->assertSee('Forbidden');
    }

    public function test_403_page_has_dashboard_link_for_authenticated_user(): void
    {
        $response = $this->actingAs($this->employeeUser)
            ->get('/admin/dashboard');

        $response->assertForbidden();
        $response->assertSee('/employee/dashboard');
    }

    public function test_404_page_has_login_link_for_guest(): void
    {
        $response = $this->get('/this-route-definitely-does-not-exist');
        $response->assertNotFound();
        $response->assertSee('Login');
    }

    public function test_error_pages_do_not_expose_stack_traces(): void
    {
        $response = $this->actingAs($this->employeeUser)
            ->get('/admin/dashboard');

        $response->assertForbidden();
        $response->assertDontSee('vendor/laravel');
        $response->assertDontSee('Stack trace');
        $response->assertDontSee('#0 ');
    }

    // ── Super admin access ────────────────────────────────────────────────────

    public function test_super_admin_can_access_admin_dashboard(): void
    {
        $this->actingAs($this->superAdminUser)
            ->get('/admin/dashboard')
            ->assertOk();
    }

    public function test_super_admin_can_access_finance_dashboard(): void
    {
        $this->actingAs($this->superAdminUser)
            ->get('/finance/dashboard')
            ->assertOk();
    }

    public function test_super_admin_can_access_employee_directory(): void
    {
        $this->actingAs($this->superAdminUser)
            ->get('/employees')
            ->assertOk();
    }

    public function test_super_admin_can_access_payroll_periods(): void
    {
        $this->actingAs($this->superAdminUser)
            ->get('/payroll/periods')
            ->assertOk();
    }

    public function test_super_admin_can_export_payroll_csv(): void
    {
        $this->actingAs($this->superAdminUser)
            ->get("/payroll/periods/{$this->calculatedPeriod->id}/export")
            ->assertOk();
    }

    public function test_super_admin_forbidden_from_employee_dashboard(): void
    {
        $this->actingAs($this->superAdminUser)
            ->get('/employee/dashboard')
            ->assertForbidden();
    }

    // ── My profile scoping ────────────────────────────────────────────────────

    public function test_employee_can_access_own_profile(): void
    {
        $this->actingAs($this->employeeUser)
            ->get('/my/profile')
            ->assertOk();
    }

    public function test_admin_hr_forbidden_from_my_profile_route(): void
    {
        $this->actingAs($this->adminHrUser)
            ->get('/my/profile')
            ->assertForbidden();
    }

    public function test_finance_forbidden_from_my_profile_route(): void
    {
        $this->actingAs($this->financeUser)
            ->get('/my/profile')
            ->assertForbidden();
    }
}
