<?php

namespace Tests\Feature;

use App\Models\CompanyExpense;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase20FinanceExpenseTest extends TestCase
{
    use RefreshDatabase;

    private User $financeUser;
    private User $financeUser2;   // acts as maker (creator) so financeUser can be checker
    private User $superAdminUser;
    private User $adminHrUser;
    private User $employeeUser;
    private Employee $financeEmployee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
        Storage::fake('local');

        $dept     = Department::create(['name' => 'Operations', 'description' => '']);
        $position = Position::create(['name' => 'Staff', 'department_id' => $dept->id]);

        $base = [
            'department_id'     => $dept->id,
            'position_id'       => $position->id,
            'join_date'         => '2026-01-01',
            'employment_status' => 'active',
            'phone_number'      => '+62800000001',
        ];

        $this->financeUser    = User::factory()->create(['role' => 'finance',     'is_active' => true]);
        $this->financeUser2   = User::factory()->create(['role' => 'finance',     'is_active' => true]);
        $this->superAdminUser = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->adminHrUser    = User::factory()->create(['role' => 'admin_hr',    'is_active' => true]);
        $this->employeeUser   = User::factory()->create(['role' => 'employee',    'is_active' => true]);

        $this->financeEmployee = Employee::create(['user_id' => $this->financeUser->id, 'nik' => 'P20-FIN-EXP'] + $base);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'category'       => 'OFFICE_SUPPLIES',
            'title'          => 'Stationery Purchase',
            'amount'         => 500000,
            'expense_date'   => '2026-06-20',
            'recipient_name' => 'Toko ABC',
        ], $overrides);
    }

    /**
     * Create an expense where financeUser2 is the maker (creator).
     * This lets financeUser act as checker (approver/payer) without violating maker-checker.
     */
    private function makeExpense(string $status = 'DRAFT', array $overrides = []): CompanyExpense
    {
        return CompanyExpense::create(array_merge([
            'expense_number' => 'EXP-202606-0001',
            'category'       => 'OFFICE_SUPPLIES',
            'title'          => 'Test Expense',
            'amount'         => 100000,
            'expense_date'   => '2026-06-20',
            'recipient_name' => 'Vendor X',
            'status'         => $status,
            'created_by'     => $this->financeUser2->id,   // maker = financeUser2
        ], $overrides));
    }

    // ── Access control: list ───────────────────────────────────────────────────

    public function test_finance_can_view_expense_list(): void
    {
        $this->actingAs($this->financeUser)->get('/finance/expenses')->assertOk();
    }

    public function test_super_admin_can_view_expense_list(): void
    {
        $this->actingAs($this->superAdminUser)->get('/finance/expenses')->assertOk();
    }

    public function test_admin_hr_can_view_expense_list(): void
    {
        $this->actingAs($this->adminHrUser)->get('/finance/expenses')->assertOk();
    }

    public function test_employee_cannot_access_expense_list(): void
    {
        $this->actingAs($this->employeeUser)->get('/finance/expenses')->assertForbidden();
    }

    public function test_guest_redirected_from_expense_list(): void
    {
        $this->get('/finance/expenses')->assertRedirect('/login');
    }

    // ── Create expense ─────────────────────────────────────────────────────────

    public function test_finance_can_create_expense(): void
    {
        $response = $this->actingAs($this->financeUser)
            ->post('/finance/expenses', $this->validPayload());

        $response->assertRedirect();
        $this->assertDatabaseHas('company_expenses', [
            'title'      => 'Stationery Purchase',
            'status'     => 'DRAFT',
            'created_by' => $this->financeUser->id,
        ]);
    }

    public function test_admin_hr_can_create_expense(): void
    {
        $this->actingAs($this->adminHrUser)
            ->post('/finance/expenses', $this->validPayload(['title' => 'HR Travel']))
            ->assertRedirect();

        $this->assertDatabaseHas('company_expenses', ['title' => 'HR Travel']);
    }

    public function test_employee_cannot_create_expense(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/finance/expenses', $this->validPayload())
            ->assertForbidden();
    }

    public function test_create_expense_validates_amount(): void
    {
        $this->actingAs($this->financeUser)
            ->post('/finance/expenses', $this->validPayload(['amount' => -100]))
            ->assertSessionHasErrors('amount');

        $this->actingAs($this->financeUser)
            ->post('/finance/expenses', $this->validPayload(['amount' => 0]))
            ->assertSessionHasErrors('amount');
    }

    public function test_create_expense_validates_category(): void
    {
        $this->actingAs($this->financeUser)
            ->post('/finance/expenses', $this->validPayload(['category' => 'INVALID_CAT']))
            ->assertSessionHasErrors('category');
    }

    public function test_expense_number_is_auto_generated(): void
    {
        $this->actingAs($this->financeUser)
            ->post('/finance/expenses', $this->validPayload());

        $expense = CompanyExpense::first();
        $this->assertNotEmpty($expense->expense_number);
        $this->assertStringContainsString('EXP-', $expense->expense_number);
    }

    // ── View expense detail ────────────────────────────────────────────────────

    public function test_finance_can_view_expense_detail(): void
    {
        $expense = $this->makeExpense();
        $this->actingAs($this->financeUser)
            ->get("/finance/expenses/{$expense->id}")
            ->assertOk()
            ->assertSee($expense->title);
    }

    public function test_admin_hr_can_view_expense_detail(): void
    {
        $expense = $this->makeExpense();
        $this->actingAs($this->adminHrUser)
            ->get("/finance/expenses/{$expense->id}")
            ->assertOk();
    }

    public function test_employee_cannot_view_finance_expense_detail(): void
    {
        $expense = $this->makeExpense();
        $this->actingAs($this->employeeUser)
            ->get("/finance/expenses/{$expense->id}")
            ->assertForbidden();
    }

    // ── Submit expense ─────────────────────────────────────────────────────────

    public function test_finance_can_submit_draft_expense(): void
    {
        $expense = $this->makeExpense('DRAFT');

        $this->actingAs($this->financeUser)
            ->post("/finance/expenses/{$expense->id}/submit")
            ->assertRedirect();

        $this->assertSame('SUBMITTED', $expense->fresh()->status);
    }

    public function test_cannot_submit_already_submitted_expense(): void
    {
        $expense = $this->makeExpense('SUBMITTED');

        $this->actingAs($this->financeUser)
            ->post("/finance/expenses/{$expense->id}/submit")
            ->assertForbidden();
    }

    // ── Approve expense (financeUser = checker, financeUser2 = maker) ──────────

    public function test_finance_can_approve_submitted_expense(): void
    {
        $expense = $this->makeExpense('SUBMITTED'); // created_by = financeUser2

        $this->actingAs($this->financeUser) // different user → maker-checker satisfied
            ->post("/finance/expenses/{$expense->id}/approve")
            ->assertRedirect();

        $fresh = $expense->fresh();
        $this->assertSame('APPROVED', $fresh->status);
        $this->assertSame($this->financeUser->id, $fresh->approved_by);
        $this->assertNotNull($fresh->approved_at);
    }

    public function test_super_admin_can_approve_submitted_expense(): void
    {
        $expense = $this->makeExpense('SUBMITTED'); // created_by = financeUser2

        $this->actingAs($this->superAdminUser) // different user → OK
            ->post("/finance/expenses/{$expense->id}/approve")
            ->assertRedirect();

        $this->assertSame('APPROVED', $expense->fresh()->status);
    }

    public function test_admin_hr_cannot_approve_expense(): void
    {
        $expense = $this->makeExpense('SUBMITTED');

        $this->actingAs($this->adminHrUser)
            ->post("/finance/expenses/{$expense->id}/approve")
            ->assertForbidden();

        $this->assertSame('SUBMITTED', $expense->fresh()->status);
    }

    public function test_cannot_approve_draft_expense(): void
    {
        $expense = $this->makeExpense('DRAFT');

        $this->actingAs($this->financeUser)
            ->post("/finance/expenses/{$expense->id}/approve")
            ->assertForbidden();
    }

    // ── Reject expense ─────────────────────────────────────────────────────────

    public function test_finance_can_reject_submitted_expense(): void
    {
        $expense = $this->makeExpense('SUBMITTED'); // created_by = financeUser2

        $this->actingAs($this->financeUser) // different user → OK
            ->post("/finance/expenses/{$expense->id}/reject", [
                'rejection_note' => 'Insufficient documentation provided',
            ])
            ->assertRedirect();

        $fresh = $expense->fresh();
        $this->assertSame('REJECTED', $fresh->status);
        $this->assertNotEmpty($fresh->rejection_note);
    }

    public function test_reject_requires_rejection_note(): void
    {
        $expense = $this->makeExpense('SUBMITTED');

        $this->actingAs($this->financeUser)
            ->post("/finance/expenses/{$expense->id}/reject", ['rejection_note' => 'short'])
            ->assertSessionHasErrors('rejection_note');
    }

    public function test_admin_hr_cannot_reject_expense(): void
    {
        $expense = $this->makeExpense('SUBMITTED');

        $this->actingAs($this->adminHrUser)
            ->post("/finance/expenses/{$expense->id}/reject", ['rejection_note' => 'Some reason here please'])
            ->assertForbidden();
    }

    // ── Mark as paid (financeUser = checker, financeUser2 = maker) ────────────

    public function test_finance_can_mark_approved_expense_as_paid(): void
    {
        $expense = $this->makeExpense('APPROVED'); // created_by = financeUser2

        $this->actingAs($this->financeUser) // different user → maker-checker satisfied
            ->post("/finance/expenses/{$expense->id}/mark-paid", [
                'payment_reference' => 'TRF-20260622-001',
            ])
            ->assertRedirect();

        $fresh = $expense->fresh();
        $this->assertSame('PAID', $fresh->status);
        $this->assertSame($this->financeUser->id, $fresh->paid_by);
        $this->assertNotNull($fresh->paid_at);
        $this->assertSame('TRF-20260622-001', $fresh->payment_reference);
    }

    public function test_mark_paid_without_payment_reference_is_allowed(): void
    {
        $expense = $this->makeExpense('APPROVED'); // created_by = financeUser2

        $this->actingAs($this->financeUser) // different user → maker-checker satisfied
            ->post("/finance/expenses/{$expense->id}/mark-paid", [])
            ->assertRedirect();

        $this->assertSame('PAID', $expense->fresh()->status);
    }

    public function test_admin_hr_cannot_mark_expense_as_paid(): void
    {
        $expense = $this->makeExpense('APPROVED');

        $this->actingAs($this->adminHrUser)
            ->post("/finance/expenses/{$expense->id}/mark-paid")
            ->assertForbidden();

        $this->assertSame('APPROVED', $expense->fresh()->status);
    }

    public function test_cannot_mark_draft_expense_as_paid(): void
    {
        $expense = $this->makeExpense('DRAFT');

        $this->actingAs($this->financeUser)
            ->post("/finance/expenses/{$expense->id}/mark-paid")
            ->assertForbidden();
    }

    public function test_cannot_mark_submitted_expense_as_paid(): void
    {
        $expense = $this->makeExpense('SUBMITTED');

        $this->actingAs($this->financeUser)
            ->post("/finance/expenses/{$expense->id}/mark-paid")
            ->assertForbidden();
    }

    // ── Receipt protection ─────────────────────────────────────────────────────

    public function test_employee_cannot_access_receipt(): void
    {
        $expense = $this->makeExpense('PAID', ['receipt_path' => 'expenses/receipts/2026/06/test.pdf']);
        Storage::disk('local')->put('expenses/receipts/2026/06/test.pdf', 'fake-content');

        $this->actingAs($this->employeeUser)
            ->get("/finance/expenses/{$expense->id}/receipt")
            ->assertForbidden();
    }

    public function test_finance_can_access_receipt(): void
    {
        $expense = $this->makeExpense('PAID', ['receipt_path' => 'expenses/receipts/2026/06/test.pdf']);
        Storage::disk('local')->put('expenses/receipts/2026/06/test.pdf', 'fake-content');

        $this->actingAs($this->financeUser)
            ->get("/finance/expenses/{$expense->id}/receipt")
            ->assertOk();
    }

    // ── Edit expense ───────────────────────────────────────────────────────────

    public function test_finance_can_edit_draft_expense(): void
    {
        $expense = $this->makeExpense('DRAFT');

        $this->actingAs($this->financeUser)
            ->put("/finance/expenses/{$expense->id}", $this->validPayload(['title' => 'Updated Title']))
            ->assertRedirect();

        $this->assertSame('Updated Title', $expense->fresh()->title);
    }

    public function test_cannot_edit_submitted_expense(): void
    {
        $expense = $this->makeExpense('SUBMITTED');

        $this->actingAs($this->financeUser)
            ->put("/finance/expenses/{$expense->id}", $this->validPayload(['title' => 'Hacked']))
            ->assertForbidden();
    }

    public function test_can_edit_rejected_expense(): void
    {
        $expense = $this->makeExpense('REJECTED');

        $this->actingAs($this->financeUser)
            ->put("/finance/expenses/{$expense->id}", $this->validPayload(['title' => 'Revised Expense']))
            ->assertRedirect();

        $this->assertSame('Revised Expense', $expense->fresh()->title);
        $this->assertSame('DRAFT', $expense->fresh()->status);
    }

    // ── Receipt upload ─────────────────────────────────────────────────────────

    public function test_finance_can_create_expense_with_receipt(): void
    {
        $receipt = UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf');

        $this->actingAs($this->financeUser)
            ->post('/finance/expenses', $this->validPayload() + ['receipt' => $receipt])
            ->assertRedirect();

        $expense = CompanyExpense::first();
        $this->assertNotNull($expense->receipt_path);
        $this->assertTrue(Storage::disk('local')->exists($expense->receipt_path));
    }

    public function test_receipt_rejects_invalid_mime(): void
    {
        $bad = UploadedFile::fake()->create('malware.exe', 10, 'application/x-msdownload');

        $this->actingAs($this->financeUser)
            ->post('/finance/expenses', $this->validPayload() + ['receipt' => $bad])
            ->assertSessionHasErrors('receipt');
    }

    // ── Full workflow (financeUser creates, financeUser2 approves+pays) ────────

    public function test_full_expense_workflow_draft_to_paid(): void
    {
        // Create & submit by financeUser (maker)
        $this->actingAs($this->financeUser)
            ->post('/finance/expenses', $this->validPayload());
        $expense = CompanyExpense::first();
        $this->assertSame('DRAFT', $expense->status);

        $this->actingAs($this->financeUser)
            ->post("/finance/expenses/{$expense->id}/submit");
        $this->assertSame('SUBMITTED', $expense->fresh()->status);

        // Approve & mark paid by financeUser2 (checker — different from maker)
        $this->actingAs($this->financeUser2)
            ->post("/finance/expenses/{$expense->id}/approve");
        $this->assertSame('APPROVED', $expense->fresh()->status);

        $this->actingAs($this->financeUser2)
            ->post("/finance/expenses/{$expense->id}/mark-paid", ['payment_reference' => 'REF-001']);
        $fresh = $expense->fresh();
        $this->assertSame('PAID', $fresh->status);
        $this->assertSame('REF-001', $fresh->payment_reference);
    }
}
