<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CompanyExpense;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

class Phase21AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $maker;
    private User $checker;
    private User $adminHrUser;
    private User $employeeUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);

        $dept     = Department::create(['name' => 'Ops', 'description' => '']);
        $position = Position::create(['name' => 'Staff', 'department_id' => $dept->id]);
        $base = [
            'department_id'     => $dept->id,
            'position_id'       => $position->id,
            'join_date'         => '2026-01-01',
            'employment_status' => 'active',
            'phone_number'      => '+62899999999',
        ];

        $this->superAdmin   = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->maker        = User::factory()->create(['role' => 'finance',     'is_active' => true]);
        $this->checker      = User::factory()->create(['role' => 'finance',     'is_active' => true]);
        $this->adminHrUser  = User::factory()->create(['role' => 'admin_hr',    'is_active' => true]);
        $this->employeeUser = User::factory()->create(['role' => 'employee',    'is_active' => true]);

        Employee::create(['user_id' => $this->maker->id,       'nik' => 'P21A-MK'] + $base);
        Employee::create(['user_id' => $this->checker->id,     'nik' => 'P21A-CK'] + $base);
        Employee::create(['user_id' => $this->adminHrUser->id, 'nik' => 'P21A-HR'] + $base);
    }

    private function makeExpense(string $status = 'DRAFT', array $overrides = []): CompanyExpense
    {
        return CompanyExpense::create(array_merge([
            'expense_number' => 'EXP-202607-8001',
            'category'       => 'OFFICE_SUPPLIES',
            'title'          => 'Audit Test Expense',
            'amount'         => 150000,
            'expense_date'   => '2026-07-01',
            'recipient_name' => 'Audit Vendor',
            'status'         => $status,
            'created_by'     => $this->maker->id,
        ], $overrides));
    }

    // ── Expense transitions create audit records ───────────────────────────────

    public function test_audit_record_created_for_expense_submit(): void
    {
        $expense = $this->makeExpense('DRAFT');

        $this->actingAs($this->maker)
            ->post("/finance/expenses/{$expense->id}/submit");

        $this->assertDatabaseHas('audit_logs', [
            'action'         => 'submit_expense',
            'module'         => 'expense',
            'user_id'        => $this->maker->id,
            'auditable_type' => CompanyExpense::class,
            'auditable_id'   => $expense->id,
        ]);
    }

    public function test_audit_record_created_for_expense_approve(): void
    {
        $expense = $this->makeExpense('SUBMITTED');

        $this->actingAs($this->checker)
            ->post("/finance/expenses/{$expense->id}/approve");

        $this->assertDatabaseHas('audit_logs', [
            'action'         => 'approve_expense',
            'module'         => 'expense',
            'user_id'        => $this->checker->id,
            'auditable_type' => CompanyExpense::class,
            'auditable_id'   => $expense->id,
        ]);
    }

    public function test_audit_record_created_for_expense_reject(): void
    {
        $expense = $this->makeExpense('SUBMITTED');

        $this->actingAs($this->checker)
            ->post("/finance/expenses/{$expense->id}/reject", [
                'rejection_note' => 'Missing receipt documentation',
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'action'         => 'reject_expense',
            'module'         => 'expense',
            'user_id'        => $this->checker->id,
            'auditable_type' => CompanyExpense::class,
            'auditable_id'   => $expense->id,
        ]);
    }

    public function test_audit_record_created_for_expense_mark_paid(): void
    {
        $expense = $this->makeExpense('APPROVED');

        $this->actingAs($this->checker)
            ->post("/finance/expenses/{$expense->id}/mark-paid", [
                'payment_reference' => 'TRF-AUDIT-001',
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'action'         => 'mark_expense_paid',
            'module'         => 'expense',
            'user_id'        => $this->checker->id,
            'auditable_type' => CompanyExpense::class,
            'auditable_id'   => $expense->id,
        ]);
    }

    // ── Payroll transitions create audit records ───────────────────────────────

    public function test_audit_record_created_for_payroll_mark_paid(): void
    {
        $period = PayrollPeriod::create([
            'name'       => 'Audit Payroll',
            'start_date' => '2026-07-01',
            'end_date'   => '2026-07-31',
            'status'     => 'LOCKED',
            'created_by' => $this->checker->id,
        ]);

        $this->actingAs($this->checker)
            ->post("/payroll/periods/{$period->id}/mark-paid", [
                'payment_reference' => 'PAY-AUDIT-001',
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'action'         => 'mark_payroll_paid',
            'module'         => 'payroll',
            'user_id'        => $this->checker->id,
            'auditable_type' => PayrollPeriod::class,
            'auditable_id'   => $period->id,
        ]);
    }

    public function test_audit_record_created_for_expense_create(): void
    {
        $this->actingAs($this->maker)
            ->post('/finance/expenses', [
                'category'       => 'OFFICE_SUPPLIES',
                'title'          => 'Audit Create Test',
                'amount'         => 100000,
                'expense_date'   => '2026-07-01',
                'recipient_name' => 'Some Vendor',
            ]);

        $expense = CompanyExpense::first();

        $this->assertDatabaseHas('audit_logs', [
            'action'         => 'create_expense',
            'module'         => 'expense',
            'user_id'        => $this->maker->id,
            'auditable_type' => CompanyExpense::class,
            'auditable_id'   => $expense->id,
        ]);
    }

    // ── Audit log stores old/new values ───────────────────────────────────────

    public function test_audit_log_stores_old_and_new_values_for_expense_submit(): void
    {
        $expense = $this->makeExpense('DRAFT');

        $this->actingAs($this->maker)
            ->post("/finance/expenses/{$expense->id}/submit");

        $log = AuditLog::where('action', 'submit_expense')
            ->where('auditable_id', $expense->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertNotNull($log->old_values);
        $this->assertNotNull($log->new_values);
        $this->assertSame('DRAFT', $log->old_values['status']);
        $this->assertSame('SUBMITTED', $log->new_values['status']);
    }

    // ── Sensitive values are masked ────────────────────────────────────────────

    public function test_sensitive_bank_account_is_masked_in_audit_log(): void
    {
        $dept     = Department::first();
        $position = Position::first();

        $empUser = User::factory()->create([
            'role'      => 'employee',
            'is_active' => true,
        ]);

        // HR creates an employee with a bank account number
        $this->actingAs($this->adminHrUser)
            ->post('/hr/employees', [
                'name'                => 'Budi Santoso',
                'email'               => 'budi@test.example',
                'password'            => 'password123',
                'nik'                 => 'EMP-P21-MASK',
                'department_id'       => $dept->id,
                'position_id'         => $position->id,
                'join_date'           => '2026-07-01',
                'employment_status'   => 'active',
                'phone_number'        => '+62812000001',
                'bank_account_number' => '1234567890123456',
            ]);

        $log = AuditLog::where('action', 'create_employee')->first();
        $this->assertNotNull($log);

        // Bank account must not appear in plain text in new_values
        if ($log->new_values && isset($log->new_values['bank_account_number'])) {
            $this->assertStringNotContainsString('1234567890', $log->new_values['bank_account_number']);
            $this->assertStringContainsString('*', $log->new_values['bank_account_number']);
        }

        // Full account number must not appear anywhere in the log description
        $this->assertStringNotContainsString('1234567890123456', $log->description);
    }

    // ── Audit log is append-only ───────────────────────────────────────────────

    public function test_audit_log_cannot_be_updated(): void
    {
        $log = AuditLog::create([
            'user_id'     => $this->maker->id,
            'action'      => 'test_action',
            'module'      => 'test',
            'description' => 'Test entry',
            'ip_address'  => '127.0.0.1',
        ]);

        $this->expectException(\RuntimeException::class);
        $log->update(['description' => 'Tampered']);
    }

    public function test_audit_log_cannot_be_deleted(): void
    {
        $log = AuditLog::create([
            'user_id'     => $this->maker->id,
            'action'      => 'test_action',
            'module'      => 'test',
            'description' => 'Test entry',
            'ip_address'  => '127.0.0.1',
        ]);

        $this->expectException(\RuntimeException::class);
        $log->delete();
    }

    // ── Audit log access control ───────────────────────────────────────────────

    public function test_employee_cannot_access_audit_logs(): void
    {
        $this->actingAs($this->employeeUser)
            ->get('/audit-logs')
            ->assertForbidden();
    }

    public function test_admin_hr_cannot_access_audit_logs(): void
    {
        $this->actingAs($this->adminHrUser)
            ->get('/audit-logs')
            ->assertForbidden();
    }

    public function test_finance_cannot_access_audit_logs(): void
    {
        $this->actingAs($this->maker)
            ->get('/audit-logs')
            ->assertForbidden();
    }

    public function test_guest_redirected_from_audit_logs(): void
    {
        $this->get('/audit-logs')->assertRedirect('/login');
    }

    public function test_super_admin_can_view_audit_log_index(): void
    {
        // Seed one log entry
        AuditLog::create([
            'user_id'     => $this->maker->id,
            'action'      => 'test_action',
            'module'      => 'test',
            'description' => 'Seeded for index test',
            'ip_address'  => '127.0.0.1',
        ]);

        $this->actingAs($this->superAdmin)
            ->get('/audit-logs')
            ->assertOk()
            ->assertSee('Audit');
    }

    public function test_super_admin_can_view_audit_log_detail(): void
    {
        $log = AuditLog::create([
            'user_id'     => $this->maker->id,
            'action'      => 'approve_expense',
            'module'      => 'expense',
            'description' => 'Detail view test log entry',
            'ip_address'  => '127.0.0.1',
        ]);

        $this->actingAs($this->superAdmin)
            ->get("/audit-logs/{$log->id}")
            ->assertOk()
            ->assertSee('approve expense');
    }

    // ── Audit IP + user_agent stored ──────────────────────────────────────────

    public function test_audit_log_stores_ip_and_user_agent(): void
    {
        $expense = $this->makeExpense('DRAFT');

        $this->actingAs($this->maker)
            ->post("/finance/expenses/{$expense->id}/submit");

        $log = AuditLog::where('action', 'submit_expense')->first();

        $this->assertNotNull($log);
        $this->assertNotNull($log->ip_address);
        // user_agent may be empty in test env but column should exist
        $this->assertArrayHasKey('user_agent', $log->getAttributes());
    }
}
