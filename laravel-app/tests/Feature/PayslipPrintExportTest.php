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

class PayslipPrintExportTest extends TestCase
{
    use RefreshDatabase;

    private User $employeeUser;
    private User $otherEmployeeUser;
    private User $financeUser;
    private User $superAdminUser;
    private User $adminHrUser;
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

        $this->employeeUser      = User::factory()->create(['role' => 'employee',    'is_active' => true]);
        $this->otherEmployeeUser = User::factory()->create(['role' => 'employee',    'is_active' => true]);
        $this->financeUser       = User::factory()->create(['role' => 'finance',     'is_active' => true]);
        $this->superAdminUser    = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->adminHrUser       = User::factory()->create(['role' => 'admin_hr',    'is_active' => true]);

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
            'leave_days'           => '1',
        ];

        $this->ownRecord   = PayrollRecord::create(['employee_id' => $this->employee->id]      + $recordBase);
        $this->otherRecord = PayrollRecord::create(['employee_id' => $this->otherEmployee->id] + $recordBase);
    }

    // ── Print payslip — access ─────────────────────────────────────────────────

    public function test_employee_can_open_own_print_payslip(): void
    {
        $this->actingAs($this->employeeUser)
            ->get("/my/payroll/{$this->ownRecord->id}/print")
            ->assertOk()
            ->assertSee('SLIP GAJI')
            ->assertSee($this->calculatedPeriod->name);
    }

    public function test_employee_cannot_open_another_employee_print_payslip(): void
    {
        $this->actingAs($this->employeeUser)
            ->get("/my/payroll/{$this->otherRecord->id}/print")
            ->assertForbidden();
    }

    public function test_employee_cannot_print_draft_period_payslip(): void
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
            ->get("/my/payroll/{$draftRecord->id}/print")
            ->assertForbidden();
    }

    public function test_guest_cannot_access_print_payslip(): void
    {
        $this->get("/my/payroll/{$this->ownRecord->id}/print")
            ->assertRedirect('/login');
    }

    // ── Print payslip — UI ────────────────────────────────────────────────────

    public function test_employee_payroll_detail_has_print_link(): void
    {
        $this->actingAs($this->employeeUser)
            ->get("/my/payroll/{$this->ownRecord->id}")
            ->assertOk()
            ->assertSee(route('my.payroll.print', $this->ownRecord));
    }

    // ── Print payslip — content ───────────────────────────────────────────────

    public function test_print_payslip_shows_employee_fields(): void
    {
        $response = $this->actingAs($this->employeeUser)
            ->get("/my/payroll/{$this->ownRecord->id}/print")
            ->assertOk();

        $response->assertSee($this->employee->nik);
        $response->assertSee($this->employeeUser->name);
        $response->assertSee('Gaji Pokok');
        $response->assertSee('GAJI BERSIH');
        $response->assertSee('Hari Hadir');
        $response->assertSee('Hari Cuti');
    }

    // ── CSV Export — access ───────────────────────────────────────────────────

    public function test_finance_can_export_payroll_period_csv(): void
    {
        $this->actingAs($this->financeUser)
            ->get("/payroll/periods/{$this->calculatedPeriod->id}/export")
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_super_admin_can_export_payroll_period_csv(): void
    {
        $this->actingAs($this->superAdminUser)
            ->get("/payroll/periods/{$this->calculatedPeriod->id}/export")
            ->assertOk();
    }

    public function test_employee_cannot_export_payroll_period_csv(): void
    {
        $this->actingAs($this->employeeUser)
            ->get("/payroll/periods/{$this->calculatedPeriod->id}/export")
            ->assertForbidden();
    }

    public function test_admin_hr_cannot_export_payroll_period_csv(): void
    {
        $this->actingAs($this->adminHrUser)
            ->get("/payroll/periods/{$this->calculatedPeriod->id}/export")
            ->assertForbidden();
    }

    public function test_guest_cannot_export_payroll_period_csv(): void
    {
        $this->get("/payroll/periods/{$this->calculatedPeriod->id}/export")
            ->assertRedirect('/login');
    }

    // ── CSV Export — content ──────────────────────────────────────────────────

    public function test_csv_export_contains_expected_payroll_data(): void
    {
        $response = $this->actingAs($this->financeUser)
            ->get("/payroll/periods/{$this->calculatedPeriod->id}/export");

        $response->assertOk();

        $content = $response->streamedContent();

        // Headers row
        $this->assertStringContainsString('Employee Name', $content);
        $this->assertStringContainsString('Employee Number', $content);
        $this->assertStringContainsString('Net Salary', $content);
        $this->assertStringContainsString('Period Status', $content);

        // Employee data
        $this->assertStringContainsString($this->employeeUser->name, $content);
        $this->assertStringContainsString('EMP-001', $content);
        $this->assertStringContainsString('5250000', $content);
        $this->assertStringContainsString('CALCULATED', $content);
    }

    public function test_csv_filename_includes_period_id(): void
    {
        $response = $this->actingAs($this->financeUser)
            ->get("/payroll/periods/{$this->calculatedPeriod->id}/export");

        $response->assertOk();

        $expectedFilename = "payroll-period-{$this->calculatedPeriod->id}.csv";
        $response->assertHeader('Content-Disposition', "attachment; filename={$expectedFilename}");
    }

    // ── Finance period detail — Export CSV button visible ─────────────────────

    public function test_finance_period_detail_has_export_csv_link(): void
    {
        $this->actingAs($this->financeUser)
            ->get("/payroll/periods/{$this->calculatedPeriod->id}")
            ->assertOk()
            ->assertSee(route('payroll.periods.export', $this->calculatedPeriod));
    }

    public function test_admin_hr_period_detail_does_not_have_export_csv_link(): void
    {
        $this->actingAs($this->adminHrUser)
            ->get("/payroll/periods/{$this->calculatedPeriod->id}")
            ->assertOk()
            ->assertDontSee(route('payroll.periods.export', $this->calculatedPeriod));
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

    public function test_employee_payroll_detail_still_works(): void
    {
        $this->actingAs($this->employeeUser)
            ->get("/my/payroll/{$this->ownRecord->id}")
            ->assertOk()
            ->assertSee($this->calculatedPeriod->name);
    }

    public function test_employee_cannot_open_another_employee_payroll_detail(): void
    {
        $this->actingAs($this->employeeUser)
            ->get("/my/payroll/{$this->otherRecord->id}")
            ->assertForbidden();
    }

    public function test_finance_payroll_period_workflow_unaffected(): void
    {
        $this->actingAs($this->financeUser)
            ->get("/payroll/periods/{$this->calculatedPeriod->id}")
            ->assertOk();
    }
}
