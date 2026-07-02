<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\User;
use App\Services\LeaveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LeaveServiceTest extends TestCase
{
    use RefreshDatabase;

    private LeaveService $service;
    private User $hrUser;
    private Employee $employee;
    private LeaveType $balanceType;
    private LeaveType $noBalanceType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(LeaveService::class);

        $dept     = Department::create(['name' => 'Engineering', 'description' => '']);
        $position = Position::create(['name' => 'Dev', 'department_id' => $dept->id]);

        $empUser = User::factory()->create(['role' => 'employee', 'is_active' => true]);
        $this->hrUser = User::factory()->create(['role' => 'admin_hr', 'is_active' => true]);

        $this->employee = Employee::create([
            'user_id'           => $empUser->id,
            'nik'               => 'NIK-LS-001',
            'department_id'     => $dept->id,
            'position_id'       => $position->id,
            'join_date'         => '2026-01-01',
            'employment_status' => 'active',
            'phone_number'      => '+62812000001',
        ]);

        $this->balanceType   = LeaveType::create(['name' => 'Annual Leave', 'deducts_balance' => true]);
        $this->noBalanceType = LeaveType::create(['name' => 'Sick Leave', 'deducts_balance' => false]);
    }

    private function makeRequest(LeaveType $type, int $days = 2, string $startDate = '2026-07-01'): LeaveRequest
    {
        $start = \Carbon\Carbon::parse($startDate);
        $end   = $start->copy()->addDays($days - 1);

        return LeaveRequest::create([
            'employee_id'   => $this->employee->id,
            'leave_type_id' => $type->id,
            'start_date'    => $start->toDateString(),
            'end_date'      => $end->toDateString(),
            'total_days'    => $days,
            'reason'        => 'Test leave reason.',
            'status'        => 'PENDING_HR',
        ]);
    }

    private function seedBalance(int $quota = 12, int $used = 0): LeaveBalance
    {
        return LeaveBalance::create([
            'employee_id'   => $this->employee->id,
            'leave_type_id' => $this->balanceType->id,
            'year'          => 2026,
            'total_quota'   => $quota,
            'used'          => $used,
            'remaining'     => $quota - $used,
        ]);
    }

    // ── Balance deduction ───────────────────────────────────────────────────

    public function test_approve_deducts_leave_balance(): void
    {
        $this->seedBalance(quota: 12, used: 0);
        $request = $this->makeRequest($this->balanceType, days: 3);

        $this->service->approve($request, $this->hrUser, 'Approved.');

        $balance = LeaveBalance::where('employee_id', $this->employee->id)
            ->where('leave_type_id', $this->balanceType->id)
            ->where('year', 2026)
            ->first();

        $this->assertSame('3.00', $balance->fresh()->used);
        $this->assertSame('9.00', $balance->fresh()->remaining);
        $this->assertSame('APPROVED', $request->fresh()->status);
    }

    public function test_approve_creates_balance_record_if_none_exists(): void
    {
        // No pre-seeded balance. Default quota is 18 per STIKES policy point 1.
        $request = $this->makeRequest($this->balanceType, days: 2);

        $this->service->approve($request, $this->hrUser, null);

        $balance = LeaveBalance::where('employee_id', $this->employee->id)
            ->where('leave_type_id', $this->balanceType->id)
            ->first();

        $this->assertNotNull($balance);
        $this->assertSame('2.00', $balance->used);
        $this->assertSame('16.00', $balance->remaining);
    }

    public function test_non_balance_leave_type_does_not_touch_balance(): void
    {
        $request = $this->makeRequest($this->noBalanceType, days: 5);

        $this->service->approve($request, $this->hrUser, null);

        $this->assertDatabaseMissing('leave_balances', ['employee_id' => $this->employee->id]);
        $this->assertSame('APPROVED', $request->fresh()->status);
    }

    // ── Idempotency guard ───────────────────────────────────────────────────

    public function test_approve_twice_does_not_double_deduct_balance(): void
    {
        $this->seedBalance(quota: 12, used: 0);
        $request = $this->makeRequest($this->balanceType, days: 3);

        $this->service->approve($request, $this->hrUser, 'First approval.');
        $this->service->approve($request->fresh(), $this->hrUser, 'Second call (should be no-op).');

        $balance = LeaveBalance::where('employee_id', $this->employee->id)->first();
        // used should still be 3, not 6
        $this->assertSame('3.00', $balance->used);
        $this->assertSame('9.00', $balance->remaining);
    }

    // ── Insufficient balance ────────────────────────────────────────────────

    public function test_approve_with_insufficient_balance_throws_validation_exception(): void
    {
        $this->seedBalance(quota: 12, used: 11); // only 1 day remaining
        $request = $this->makeRequest($this->balanceType, days: 3); // wants 3 days

        $this->expectException(ValidationException::class);

        $this->service->approve($request, $this->hrUser, null);
    }

    public function test_approve_with_insufficient_balance_does_not_change_request_status(): void
    {
        $this->seedBalance(quota: 12, used: 11); // only 1 day remaining
        $request = $this->makeRequest($this->balanceType, days: 3);

        try {
            $this->service->approve($request, $this->hrUser, null);
        } catch (ValidationException) {
            // expected
        }

        $this->assertSame('PENDING_HR', $request->fresh()->status);
    }

    public function test_approve_with_insufficient_balance_does_not_modify_balance(): void
    {
        $this->seedBalance(quota: 12, used: 11);
        $request = $this->makeRequest($this->balanceType, days: 3);

        try {
            $this->service->approve($request, $this->hrUser, null);
        } catch (ValidationException) {
            // expected
        }

        $balance = LeaveBalance::where('employee_id', $this->employee->id)->first();
        $this->assertSame('11.00', $balance->used);
        $this->assertSame('1.00', $balance->remaining);
    }

    // ── Exact balance boundary ──────────────────────────────────────────────

    public function test_approve_with_exact_remaining_balance_succeeds(): void
    {
        $this->seedBalance(quota: 12, used: 9); // exactly 3 remaining
        $request = $this->makeRequest($this->balanceType, days: 3);

        $this->service->approve($request, $this->hrUser, null);

        $balance = LeaveBalance::where('employee_id', $this->employee->id)->first();
        $this->assertSame('12.00', $balance->used);
        $this->assertSame('0.00', $balance->remaining);
        $this->assertSame('APPROVED', $request->fresh()->status);
    }
}
