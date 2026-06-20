<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

class PayrollPeriodTest extends TestCase
{
    use RefreshDatabase;

    private User $financeUser;
    private User $superAdminUser;
    private User $adminHrUser;
    private User $employeeUser;
    private Employee $activeEmployee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);

        $dept     = Department::create(['name' => 'Engineering', 'description' => '']);
        $position = Position::create(['name' => 'Dev', 'department_id' => $dept->id]);

        $this->financeUser    = User::factory()->create(['role' => 'finance',     'is_active' => true]);
        $this->superAdminUser = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->adminHrUser    = User::factory()->create(['role' => 'admin_hr',    'is_active' => true]);
        $this->employeeUser   = User::factory()->create(['role' => 'employee',    'is_active' => true]);

        $this->activeEmployee = Employee::create([
            'user_id'           => $this->employeeUser->id,
            'nik'               => 'EMP-TEST-001',
            'department_id'     => $dept->id,
            'position_id'       => $position->id,
            'join_date'         => '2026-01-01',
            'employment_status' => 'active',
            'phone_number'      => '+62812345678',
        ]);
    }

    // ── List access ────────────────────────────────────────────────────────────

    public function test_finance_can_view_payroll_periods(): void
    {
        $this->actingAs($this->financeUser)->get('/payroll/periods')->assertOk();
    }

    public function test_super_admin_can_view_payroll_periods(): void
    {
        $this->actingAs($this->superAdminUser)->get('/payroll/periods')->assertOk();
    }

    public function test_admin_hr_can_view_payroll_periods(): void
    {
        $this->actingAs($this->adminHrUser)->get('/payroll/periods')->assertOk();
    }

    public function test_employee_cannot_access_payroll_periods(): void
    {
        $this->actingAs($this->employeeUser)->get('/payroll/periods')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/payroll/periods')->assertRedirect('/login');
    }

    // ── Create period ──────────────────────────────────────────────────────────

    public function test_finance_can_create_payroll_period(): void
    {
        $response = $this->actingAs($this->financeUser)->post('/payroll/periods', [
            'name'       => 'July 2026 Payroll',
            'start_date' => '2026-07-01',
            'end_date'   => '2026-07-31',
        ]);

        $response->assertRedirect('/payroll/periods');
        $this->assertDatabaseHas('payroll_periods', [
            'name'   => 'July 2026 Payroll',
            'status' => 'DRAFT',
        ]);
    }

    public function test_super_admin_can_create_payroll_period(): void
    {
        $this->actingAs($this->superAdminUser)->post('/payroll/periods', [
            'name'       => 'August 2026 Payroll',
            'start_date' => '2026-08-01',
            'end_date'   => '2026-08-31',
            'pay_date'   => '2026-09-05',
        ])->assertRedirect('/payroll/periods');

        $this->assertDatabaseHas('payroll_periods', ['name' => 'August 2026 Payroll']);
        $period = PayrollPeriod::where('name', 'August 2026 Payroll')->firstOrFail();
        $this->assertEquals('2026-09-05', $period->pay_date->toDateString());
    }

    public function test_admin_hr_cannot_create_payroll_period(): void
    {
        $this->actingAs($this->adminHrUser)->post('/payroll/periods', [
            'name'       => 'HR Period',
            'start_date' => '2026-07-01',
            'end_date'   => '2026-07-31',
        ])->assertForbidden();
    }

    public function test_employee_cannot_create_payroll_period(): void
    {
        $this->actingAs($this->employeeUser)->post('/payroll/periods', [
            'name'       => 'Employee Period',
            'start_date' => '2026-07-01',
            'end_date'   => '2026-07-31',
        ])->assertForbidden();
    }

    // ── Validation ─────────────────────────────────────────────────────────────

    public function test_end_date_before_start_date_is_rejected(): void
    {
        $this->actingAs($this->financeUser)->post('/payroll/periods', [
            'name'       => 'Bad Period',
            'start_date' => '2026-07-31',
            'end_date'   => '2026-07-01',
        ])->assertSessionHasErrors('end_date');
    }

    public function test_overlapping_payroll_period_is_rejected(): void
    {
        PayrollPeriod::create([
            'name'       => 'July 2026 Payroll',
            'start_date' => '2026-07-01',
            'end_date'   => '2026-07-31',
            'status'     => 'DRAFT',
            'created_by' => $this->financeUser->id,
        ]);

        $this->actingAs($this->financeUser)->post('/payroll/periods', [
            'name'       => 'Overlap Period',
            'start_date' => '2026-07-15',
            'end_date'   => '2026-08-15',
        ])->assertSessionHasErrors('start_date');
    }

    public function test_name_is_required(): void
    {
        $this->actingAs($this->financeUser)->post('/payroll/periods', [
            'start_date' => '2026-07-01',
            'end_date'   => '2026-07-31',
        ])->assertSessionHasErrors('name');
    }

    // ── Calculate ──────────────────────────────────────────────────────────────

    public function test_finance_can_calculate_draft_period(): void
    {
        $period = PayrollPeriod::create([
            'name'       => 'July 2026 Payroll',
            'start_date' => '2026-07-01',
            'end_date'   => '2026-07-31',
            'status'     => 'DRAFT',
            'created_by' => $this->financeUser->id,
        ]);

        $this->actingAs($this->financeUser)
            ->post("/payroll/periods/{$period->id}/calculate")
            ->assertRedirect('/payroll/periods');

        $period->refresh();
        $this->assertEquals('CALCULATED', $period->status);
        $this->assertNotNull($period->calculated_at);
        $this->assertEquals($this->financeUser->id, $period->calculated_by);
    }

    public function test_calculate_creates_payroll_records(): void
    {
        $period = PayrollPeriod::create([
            'name'       => 'July 2026 Payroll',
            'start_date' => '2026-07-01',
            'end_date'   => '2026-07-31',
            'status'     => 'DRAFT',
            'created_by' => $this->financeUser->id,
        ]);

        $this->actingAs($this->financeUser)
            ->post("/payroll/periods/{$period->id}/calculate");

        $this->assertDatabaseHas('payroll_records', [
            'payroll_period_id' => $period->id,
            'employee_id'       => $this->activeEmployee->id,
        ]);
    }

    public function test_recalculate_does_not_create_duplicate_records(): void
    {
        $period = PayrollPeriod::create([
            'name'       => 'July 2026 Payroll',
            'start_date' => '2026-07-01',
            'end_date'   => '2026-07-31',
            'status'     => 'DRAFT',
            'created_by' => $this->financeUser->id,
        ]);

        // First calculate
        $this->actingAs($this->financeUser)->post("/payroll/periods/{$period->id}/calculate");

        // Status is now CALCULATED — second attempt should be rejected (403)
        $this->actingAs($this->financeUser)->post("/payroll/periods/{$period->id}/calculate")
            ->assertForbidden();

        $this->assertEquals(1, PayrollRecord::where('payroll_period_id', $period->id)
            ->where('employee_id', $this->activeEmployee->id)
            ->count());
    }

    public function test_calculate_locked_period_is_rejected(): void
    {
        $period = PayrollPeriod::create([
            'name'       => 'Locked Payroll',
            'start_date' => '2026-06-01',
            'end_date'   => '2026-06-30',
            'status'     => 'LOCKED',
            'created_by' => $this->financeUser->id,
        ]);

        $this->actingAs($this->financeUser)
            ->post("/payroll/periods/{$period->id}/calculate")
            ->assertForbidden();
    }

    public function test_calculate_paid_period_is_rejected(): void
    {
        $period = PayrollPeriod::create([
            'name'       => 'Paid Payroll',
            'start_date' => '2026-05-01',
            'end_date'   => '2026-05-31',
            'status'     => 'PAID',
            'created_by' => $this->financeUser->id,
        ]);

        $this->actingAs($this->financeUser)
            ->post("/payroll/periods/{$period->id}/calculate")
            ->assertForbidden();
    }

    public function test_admin_hr_cannot_calculate_payroll(): void
    {
        $period = PayrollPeriod::create([
            'name'       => 'July 2026 Payroll',
            'start_date' => '2026-07-01',
            'end_date'   => '2026-07-31',
            'status'     => 'DRAFT',
            'created_by' => $this->financeUser->id,
        ]);

        $this->actingAs($this->adminHrUser)
            ->post("/payroll/periods/{$period->id}/calculate")
            ->assertForbidden();
    }

    public function test_employee_cannot_calculate_payroll(): void
    {
        $period = PayrollPeriod::create([
            'name'       => 'July 2026 Payroll',
            'start_date' => '2026-07-01',
            'end_date'   => '2026-07-31',
            'status'     => 'DRAFT',
            'created_by' => $this->financeUser->id,
        ]);

        $this->actingAs($this->employeeUser)
            ->post("/payroll/periods/{$period->id}/calculate")
            ->assertForbidden();
    }

    // ── Show detail ────────────────────────────────────────────────────────────

    public function test_finance_can_view_period_detail(): void
    {
        $period = PayrollPeriod::create([
            'name'       => 'July 2026 Payroll',
            'start_date' => '2026-07-01',
            'end_date'   => '2026-07-31',
            'status'     => 'DRAFT',
            'created_by' => $this->financeUser->id,
        ]);

        $this->actingAs($this->financeUser)
            ->get("/payroll/periods/{$period->id}")
            ->assertOk();
    }

    public function test_employee_cannot_view_period_detail(): void
    {
        $period = PayrollPeriod::create([
            'name'       => 'July 2026 Payroll',
            'start_date' => '2026-07-01',
            'end_date'   => '2026-07-31',
            'status'     => 'DRAFT',
            'created_by' => $this->financeUser->id,
        ]);

        $this->actingAs($this->employeeUser)
            ->get("/payroll/periods/{$period->id}")
            ->assertForbidden();
    }

    // ── Submit HR Review (CALCULATED → HR_REVIEW) ──────────────────────────────

    public function test_admin_hr_can_submit_calculated_payroll_to_hr_review(): void
    {
        $period = PayrollPeriod::create([
            'name'       => 'June 2026 Payroll',
            'start_date' => '2026-06-01',
            'end_date'   => '2026-06-30',
            'status'     => 'CALCULATED',
            'created_by' => $this->financeUser->id,
        ]);

        $this->actingAs($this->adminHrUser)
            ->post("/payroll/periods/{$period->id}/submit-hr-review")
            ->assertRedirect('/payroll/periods');

        $period->refresh();
        $this->assertEquals('HR_REVIEW', $period->status);
        $this->assertEquals($this->adminHrUser->id, $period->reviewed_by);
        $this->assertNotNull($period->reviewed_at);
    }

    public function test_employee_cannot_submit_payroll_to_hr_review(): void
    {
        $period = PayrollPeriod::create([
            'name'       => 'June 2026 Payroll',
            'start_date' => '2026-06-01',
            'end_date'   => '2026-06-30',
            'status'     => 'CALCULATED',
            'created_by' => $this->financeUser->id,
        ]);

        $this->actingAs($this->employeeUser)
            ->post("/payroll/periods/{$period->id}/submit-hr-review")
            ->assertForbidden();
    }

    public function test_finance_cannot_submit_payroll_to_hr_review(): void
    {
        $period = PayrollPeriod::create([
            'name'       => 'June 2026 Payroll',
            'start_date' => '2026-06-01',
            'end_date'   => '2026-06-30',
            'status'     => 'CALCULATED',
            'created_by' => $this->financeUser->id,
        ]);

        $this->actingAs($this->financeUser)
            ->post("/payroll/periods/{$period->id}/submit-hr-review")
            ->assertForbidden();
    }

    public function test_submit_hr_review_rejected_when_not_calculated(): void
    {
        $period = PayrollPeriod::create([
            'name'       => 'June 2026 Payroll',
            'start_date' => '2026-06-01',
            'end_date'   => '2026-06-30',
            'status'     => 'DRAFT',
            'created_by' => $this->financeUser->id,
        ]);

        $this->actingAs($this->adminHrUser)
            ->post("/payroll/periods/{$period->id}/submit-hr-review")
            ->assertForbidden();
    }

    // ── Finance Approve (HR_REVIEW → FINANCE_APPROVAL) ─────────────────────────

    public function test_finance_can_approve_hr_review_payroll(): void
    {
        $period = PayrollPeriod::create([
            'name'       => 'June 2026 Payroll',
            'start_date' => '2026-06-01',
            'end_date'   => '2026-06-30',
            'status'     => 'HR_REVIEW',
            'created_by' => $this->financeUser->id,
        ]);

        $this->actingAs($this->financeUser)
            ->post("/payroll/periods/{$period->id}/finance-approve")
            ->assertRedirect('/payroll/periods');

        $period->refresh();
        $this->assertEquals('FINANCE_APPROVAL', $period->status);
        $this->assertEquals($this->financeUser->id, $period->approved_by);
        $this->assertNotNull($period->approved_at);
    }

    public function test_admin_hr_cannot_finance_approve_payroll(): void
    {
        $period = PayrollPeriod::create([
            'name'       => 'June 2026 Payroll',
            'start_date' => '2026-06-01',
            'end_date'   => '2026-06-30',
            'status'     => 'HR_REVIEW',
            'created_by' => $this->financeUser->id,
        ]);

        $this->actingAs($this->adminHrUser)
            ->post("/payroll/periods/{$period->id}/finance-approve")
            ->assertForbidden();
    }

    public function test_finance_approve_rejected_when_not_hr_review(): void
    {
        $period = PayrollPeriod::create([
            'name'       => 'June 2026 Payroll',
            'start_date' => '2026-06-01',
            'end_date'   => '2026-06-30',
            'status'     => 'CALCULATED',
            'created_by' => $this->financeUser->id,
        ]);

        $this->actingAs($this->financeUser)
            ->post("/payroll/periods/{$period->id}/finance-approve")
            ->assertForbidden();
    }

    // ── Lock (FINANCE_APPROVAL → LOCKED) ───────────────────────────────────────

    public function test_finance_can_lock_finance_approval_payroll(): void
    {
        $period = PayrollPeriod::create([
            'name'       => 'June 2026 Payroll',
            'start_date' => '2026-06-01',
            'end_date'   => '2026-06-30',
            'status'     => 'FINANCE_APPROVAL',
            'created_by' => $this->financeUser->id,
        ]);

        $this->actingAs($this->financeUser)
            ->post("/payroll/periods/{$period->id}/lock")
            ->assertRedirect('/payroll/periods');

        $period->refresh();
        $this->assertEquals('LOCKED', $period->status);
        $this->assertEquals($this->financeUser->id, $period->locked_by);
        $this->assertNotNull($period->locked_at);
    }

    public function test_lock_rejected_when_not_finance_approval(): void
    {
        $period = PayrollPeriod::create([
            'name'       => 'June 2026 Payroll',
            'start_date' => '2026-06-01',
            'end_date'   => '2026-06-30',
            'status'     => 'HR_REVIEW',
            'created_by' => $this->financeUser->id,
        ]);

        $this->actingAs($this->financeUser)
            ->post("/payroll/periods/{$period->id}/lock")
            ->assertForbidden();
    }

    // ── Mark Paid (LOCKED → PAID) ───────────────────────────────────────────────

    public function test_finance_can_mark_locked_payroll_as_paid(): void
    {
        $period = PayrollPeriod::create([
            'name'       => 'June 2026 Payroll',
            'start_date' => '2026-06-01',
            'end_date'   => '2026-06-30',
            'status'     => 'LOCKED',
            'created_by' => $this->financeUser->id,
        ]);

        $this->actingAs($this->financeUser)
            ->post("/payroll/periods/{$period->id}/mark-paid")
            ->assertRedirect('/payroll/periods');

        $period->refresh();
        $this->assertEquals('PAID', $period->status);
        $this->assertEquals($this->financeUser->id, $period->paid_by);
        $this->assertNotNull($period->paid_at);
    }

    public function test_mark_paid_rejected_when_not_locked(): void
    {
        $period = PayrollPeriod::create([
            'name'       => 'June 2026 Payroll',
            'start_date' => '2026-06-01',
            'end_date'   => '2026-06-30',
            'status'     => 'FINANCE_APPROVAL',
            'created_by' => $this->financeUser->id,
        ]);

        $this->actingAs($this->financeUser)
            ->post("/payroll/periods/{$period->id}/mark-paid")
            ->assertForbidden();
    }

    // ── Phase 5/6 regression ───────────────────────────────────────────────────

    public function test_attendance_checkin_route_still_works(): void
    {
        $this->actingAs($this->employeeUser)->get('/attendance/checkin')->assertOk();
    }

    public function test_leave_request_route_still_works(): void
    {
        $this->actingAs($this->employeeUser)->get('/leave/request')->assertOk();
    }

    public function test_hr_approval_queue_still_works(): void
    {
        $this->actingAs($this->adminHrUser)->get('/hr/approval-queue')->assertOk();
    }
}
