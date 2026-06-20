<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyPayrollTest extends TestCase
{
    use RefreshDatabase;

    private User $employeeUser;
    private User $otherEmployeeUser;
    private User $financeUser;
    private Employee $employee;
    private Employee $otherEmployee;
    private PayrollPeriod $calculatedPeriod;
    private PayrollPeriod $draftPeriod;
    private PayrollRecord $ownRecord;
    private PayrollRecord $otherRecord;

    protected function setUp(): void
    {
        parent::setUp();

        $dept     = Department::create(['name' => 'Engineering', 'description' => '']);
        $position = Position::create(['name' => 'Dev', 'department_id' => $dept->id]);

        $this->employeeUser      = User::factory()->create(['role' => 'employee',  'is_active' => true]);
        $this->otherEmployeeUser = User::factory()->create(['role' => 'employee',  'is_active' => true]);
        $this->financeUser       = User::factory()->create(['role' => 'finance',   'is_active' => true]);

        $base = [
            'department_id'     => $dept->id,
            'position_id'       => $position->id,
            'join_date'         => '2026-01-01',
            'employment_status' => 'active',
            'phone_number'      => '+62812345678',
        ];

        $this->employee      = Employee::create(['user_id' => $this->employeeUser->id,      'nik' => 'EMP-001'] + $base);
        $this->otherEmployee = Employee::create(['user_id' => $this->otherEmployeeUser->id, 'nik' => 'EMP-002'] + $base);

        $this->calculatedPeriod = PayrollPeriod::create([
            'name'       => 'June 2026 Payroll',
            'start_date' => '2026-06-01',
            'end_date'   => '2026-06-30',
            'status'     => 'CALCULATED',
            'created_by' => $this->financeUser->id,
        ]);

        $this->draftPeriod = PayrollPeriod::create([
            'name'       => 'July 2026 Payroll',
            'start_date' => '2026-07-01',
            'end_date'   => '2026-07-31',
            'status'     => 'DRAFT',
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
            'leave_days'           => '0',
        ];

        $this->ownRecord   = PayrollRecord::create(['employee_id' => $this->employee->id]      + $recordBase);
        $this->otherRecord = PayrollRecord::create(['employee_id' => $this->otherEmployee->id] + $recordBase);
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_employee_can_access_my_payroll(): void
    {
        $this->actingAs($this->employeeUser)->get('/my/payroll')->assertOk();
    }

    public function test_guest_is_redirected_from_my_payroll(): void
    {
        $this->get('/my/payroll')->assertRedirect('/login');
    }

    public function test_employee_sees_only_own_payroll_records(): void
    {
        $response = $this->actingAs($this->employeeUser)->get('/my/payroll');

        $response->assertOk();
        $response->assertSee($this->calculatedPeriod->name);
        $response->assertDontSee($this->otherEmployee->nik);
    }

    public function test_draft_period_records_not_visible_to_employee(): void
    {
        PayrollRecord::create([
            'payroll_period_id'    => $this->draftPeriod->id,
            'employee_id'          => $this->employee->id,
            'basic_salary'         => '5000000',
            'allowance'            => '0',
            'bonus'                => '0',
            'overtime'             => '0',
            'deduction'            => '0',
            'late_deduction'       => '0',
            'attendance_deduction' => '0',
            'tax_bpjs'             => '0',
            'net_salary'           => '5000000',
            'attendance_days'      => 22,
            'leave_days'           => '0',
        ]);

        $response = $this->actingAs($this->employeeUser)->get('/my/payroll');

        $response->assertOk();
        $response->assertDontSee($this->draftPeriod->name);
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function test_employee_can_view_own_payroll_detail(): void
    {
        $this->actingAs($this->employeeUser)
            ->get("/my/payroll/{$this->ownRecord->id}")
            ->assertOk()
            ->assertSee($this->calculatedPeriod->name);
    }

    public function test_employee_cannot_view_another_employee_payroll_detail(): void
    {
        $this->actingAs($this->employeeUser)
            ->get("/my/payroll/{$this->otherRecord->id}")
            ->assertForbidden();
    }

    public function test_employee_cannot_view_draft_period_payroll_detail(): void
    {
        $draftRecord = PayrollRecord::create([
            'payroll_period_id'    => $this->draftPeriod->id,
            'employee_id'          => $this->employee->id,
            'basic_salary'         => '5000000',
            'allowance'            => '0',
            'bonus'                => '0',
            'overtime'             => '0',
            'deduction'            => '0',
            'late_deduction'       => '0',
            'attendance_deduction' => '0',
            'tax_bpjs'             => '0',
            'net_salary'           => '5000000',
            'attendance_days'      => 22,
            'leave_days'           => '0',
        ]);

        $this->actingAs($this->employeeUser)
            ->get("/my/payroll/{$draftRecord->id}")
            ->assertForbidden();
    }

    // ── Role restrictions ─────────────────────────────────────────────────────

    public function test_employee_still_cannot_access_payroll_periods(): void
    {
        $this->actingAs($this->employeeUser)->get('/payroll/periods')->assertForbidden();
    }

    public function test_finance_can_still_access_payroll_periods(): void
    {
        $this->actingAs($this->financeUser)->get('/payroll/periods')->assertOk();
    }

    public function test_finance_cannot_access_my_payroll_routes(): void
    {
        $this->actingAs($this->financeUser)->get('/my/payroll')->assertForbidden();
    }

    // ── Regression ────────────────────────────────────────────────────────────

    public function test_attendance_checkin_route_still_works(): void
    {
        $this->actingAs($this->employeeUser)->get('/attendance/checkin')->assertOk();
    }

    public function test_leave_request_route_still_works(): void
    {
        $this->actingAs($this->employeeUser)->get('/leave/request')->assertOk();
    }
}
