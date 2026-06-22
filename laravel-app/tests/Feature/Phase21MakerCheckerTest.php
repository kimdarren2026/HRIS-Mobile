<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CompanyExpense;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

class Phase21MakerCheckerTest extends TestCase
{
    use RefreshDatabase;

    private User $maker;   // creates expenses
    private User $checker; // approves/pays expenses (different from maker)
    private User $adminHrUser;
    private User $employeeUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);

        $dept     = Department::create(['name' => 'Finance', 'description' => '']);
        $position = Position::create(['name' => 'Officer', 'department_id' => $dept->id]);
        $base = [
            'department_id'     => $dept->id,
            'position_id'       => $position->id,
            'join_date'         => '2026-01-01',
            'employment_status' => 'active',
            'phone_number'      => '+62811111111',
        ];

        $this->maker       = User::factory()->create(['role' => 'finance',     'is_active' => true]);
        $this->checker     = User::factory()->create(['role' => 'finance',     'is_active' => true]);
        $this->adminHrUser = User::factory()->create(['role' => 'admin_hr',    'is_active' => true]);
        $this->employeeUser= User::factory()->create(['role' => 'employee',    'is_active' => true]);

        Employee::create(['user_id' => $this->maker->id,   'nik' => 'P21-MK-01'] + $base);
        Employee::create(['user_id' => $this->checker->id, 'nik' => 'P21-CK-01'] + $base);
    }

    private function makeExpense(string $status = 'SUBMITTED', array $overrides = []): CompanyExpense
    {
        return CompanyExpense::create(array_merge([
            'expense_number' => 'EXP-202606-9001',
            'category'       => 'OFFICE_SUPPLIES',
            'title'          => 'Maker-Checker Test',
            'amount'         => 200000,
            'expense_date'   => '2026-06-22',
            'recipient_name' => 'Vendor Y',
            'status'         => $status,
            'created_by'     => $this->maker->id,
        ], $overrides));
    }

    // ── Maker cannot approve own expense ──────────────────────────────────────

    public function test_expense_creator_cannot_approve_own_submitted_expense(): void
    {
        $expense = $this->makeExpense('SUBMITTED');

        $this->actingAs($this->maker)
            ->post("/finance/expenses/{$expense->id}/approve")
            ->assertForbidden();

        $this->assertSame('SUBMITTED', $expense->fresh()->status);
    }

    public function test_expense_creator_cannot_reject_own_submitted_expense(): void
    {
        $expense = $this->makeExpense('SUBMITTED');

        $this->actingAs($this->maker)
            ->post("/finance/expenses/{$expense->id}/reject", [
                'rejection_note' => 'Self-reject attempt here!',
            ])
            ->assertForbidden();

        $this->assertSame('SUBMITTED', $expense->fresh()->status);
    }

    public function test_expense_creator_cannot_mark_own_expense_paid(): void
    {
        $expense = $this->makeExpense('APPROVED');

        $this->actingAs($this->maker)
            ->post("/finance/expenses/{$expense->id}/mark-paid", [
                'payment_reference' => 'SELF-PAY-001',
            ])
            ->assertForbidden();

        $this->assertSame('APPROVED', $expense->fresh()->status);
    }

    // ── super_admin created expense cannot self-approve ───────────────────────

    public function test_super_admin_creator_cannot_approve_own_expense(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $expense = $this->makeExpense('SUBMITTED', ['created_by' => $superAdmin->id]);

        $this->actingAs($superAdmin)
            ->post("/finance/expenses/{$expense->id}/approve")
            ->assertForbidden();
    }

    // ── Checker (different finance user) can approve ───────────────────────────

    public function test_second_finance_user_can_approve_submitted_expense(): void
    {
        $expense = $this->makeExpense('SUBMITTED'); // created_by = maker

        $this->actingAs($this->checker) // different user
            ->post("/finance/expenses/{$expense->id}/approve")
            ->assertRedirect();

        $fresh = $expense->fresh();
        $this->assertSame('APPROVED', $fresh->status);
        $this->assertSame($this->checker->id, $fresh->approved_by);
        $this->assertNotNull($fresh->approved_at);
    }

    public function test_checker_can_mark_approved_expense_paid(): void
    {
        $expense = $this->makeExpense('APPROVED'); // created_by = maker

        $this->actingAs($this->checker) // different user
            ->post("/finance/expenses/{$expense->id}/mark-paid", [
                'payment_reference' => 'TRF-P21-001',
            ])
            ->assertRedirect();

        $fresh = $expense->fresh();
        $this->assertSame('PAID', $fresh->status);
        $this->assertSame($this->checker->id, $fresh->paid_by);
        $this->assertSame('TRF-P21-001', $fresh->payment_reference);
    }

    // ── Rejection records rejected_by + rejected_at ───────────────────────────

    public function test_reject_records_rejected_by_and_timestamp(): void
    {
        $expense = $this->makeExpense('SUBMITTED');

        $this->actingAs($this->checker)
            ->post("/finance/expenses/{$expense->id}/reject", [
                'rejection_note' => 'Missing supporting invoice documents',
            ])
            ->assertRedirect();

        $fresh = $expense->fresh();
        $this->assertSame('REJECTED', $fresh->status);
        $this->assertSame($this->checker->id, $fresh->rejected_by);
        $this->assertNotNull($fresh->rejected_at);
        $this->assertSame('Missing supporting invoice documents', $fresh->rejection_note);
    }

    // ── Rejection requires a reason ────────────────────────────────────────────

    public function test_rejection_requires_reason_of_at_least_10_chars(): void
    {
        $expense = $this->makeExpense('SUBMITTED');

        $this->actingAs($this->checker)
            ->post("/finance/expenses/{$expense->id}/reject", ['rejection_note' => 'Too short'])
            ->assertSessionHasErrors('rejection_note');

        $this->actingAs($this->checker)
            ->post("/finance/expenses/{$expense->id}/reject", ['rejection_note' => ''])
            ->assertSessionHasErrors('rejection_note');
    }

    // ── Rejected expense can be edited and resubmitted ────────────────────────

    public function test_rejected_expense_can_be_edited_and_resubmitted(): void
    {
        $expense = $this->makeExpense('REJECTED', [
            'rejection_note' => 'Please add invoice number',
            'rejected_by'    => $this->checker->id,
            'rejected_at'    => now(),
        ]);

        // Maker edits rejected expense
        $this->actingAs($this->maker)
            ->put("/finance/expenses/{$expense->id}", [
                'category'       => 'OFFICE_SUPPLIES',
                'title'          => 'Revised Stationery Purchase',
                'amount'         => 200000,
                'expense_date'   => '2026-06-22',
                'recipient_name' => 'Vendor Y Updated',
            ])
            ->assertRedirect();

        $fresh = $expense->fresh();
        $this->assertSame('DRAFT', $fresh->status);
        $this->assertNull($fresh->rejection_note);
        $this->assertNull($fresh->rejected_by);

        // Maker resubmits
        $this->actingAs($this->maker)
            ->post("/finance/expenses/{$expense->id}/submit")
            ->assertRedirect();

        $this->assertSame('SUBMITTED', $expense->fresh()->status);

        // Checker approves the resubmitted expense
        $this->actingAs($this->checker)
            ->post("/finance/expenses/{$expense->id}/approve")
            ->assertRedirect();

        $this->assertSame('APPROVED', $expense->fresh()->status);
    }

    // ── Duplicate mark-paid is rejected ───────────────────────────────────────

    public function test_duplicate_expense_mark_paid_is_rejected(): void
    {
        $expense = $this->makeExpense('PAID', [
            'paid_by'           => $this->checker->id,
            'paid_at'           => now(),
            'payment_reference' => 'ALREADY-PAID',
        ]);

        // Expense is already PAID — policy gate rejects (status != APPROVED)
        $this->actingAs($this->checker)
            ->post("/finance/expenses/{$expense->id}/mark-paid", [
                'payment_reference' => 'DUPLICATE-PAY',
            ])
            ->assertForbidden();

        // Confirm status unchanged
        $this->assertSame('PAID', $expense->fresh()->status);
        $this->assertSame('ALREADY-PAID', $expense->fresh()->payment_reference);
    }

    // ── Duplicate payroll mark-paid is rejected ────────────────────────────────

    public function test_duplicate_payroll_mark_paid_is_rejected(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $period = \App\Models\PayrollPeriod::create([
            'name'               => 'Test Payroll',
            'start_date'         => '2026-06-01',
            'end_date'           => '2026-06-30',
            'status'             => 'PAID',
            'created_by'         => $superAdmin->id,
            'payment_reference'  => 'ALREADY-PAID-PAY',
        ]);

        // Status is PAID, not LOCKED — policy denies
        $this->actingAs($superAdmin)
            ->post("/payroll/periods/{$period->id}/mark-paid", [
                'payment_reference' => 'DUPLICATE-PAY',
            ])
            ->assertForbidden();

        $this->assertSame('PAID', $period->fresh()->status);
    }

    // ── Payroll mark-paid requires payment_reference ───────────────────────────

    public function test_payroll_mark_paid_requires_payment_reference(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $period = \App\Models\PayrollPeriod::create([
            'name'       => 'Test Payroll Ref Required',
            'start_date' => '2026-07-01',
            'end_date'   => '2026-07-31',
            'status'     => 'LOCKED',
            'created_by' => $superAdmin->id,
        ]);

        $this->actingAs($superAdmin)
            ->post("/payroll/periods/{$period->id}/mark-paid", [])
            ->assertSessionHasErrors('payment_reference');

        $this->assertSame('LOCKED', $period->fresh()->status);
    }
}
