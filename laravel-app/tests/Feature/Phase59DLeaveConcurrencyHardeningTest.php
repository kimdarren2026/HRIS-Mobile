<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Notification;
use App\Models\Position;
use App\Models\User;
use App\Services\LeaveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Phase 59D — leave concurrency hardening.
 *
 * Test database engine: SQLite `:memory:` (phpunit.xml). Per the Phase 59D
 * brief, SQLite's query grammar compiles lockForUpdate()/sharedLock() to an
 * EMPTY string (Illuminate\Database\Query\Grammars\SQLiteGrammar::compileLock()
 * always returns ''), so no true row-level lock is exercised by these tests.
 * What IS proven here:
 *
 *  - VERIFIED BY AUTOMATED FUNCTIONAL TEST: business outcomes (no double
 *    deduction, no negative balance, only one notification/audit log per
 *    successful action, status transitions are one-shot) using deterministic
 *    stale-model-instance technique (two separately fetched PHP objects of
 *    the same row, proving the service re-reads status/balance from the
 *    database inside the transaction rather than trusting either object).
 *  - VERIFIED BY SOURCE/LOCKING REVIEW: lockForUpdate() calls are present
 *    inside DB::transaction() in LeaveService, in a consistent
 *    Employee -> LeaveRequest -> LeaveBalance order across submit/approve/reject.
 *  - NOT TRUE-PARALLEL VERIFIED LOCALLY: no assertion here claims two
 *    genuinely concurrent database connections were exercised.
 */
class Phase59DLeaveConcurrencyHardeningTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private Position $position;

    private LeaveType $leaveType;

    private LeaveType $plainLeaveType;

    private LeaveService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(LeaveService::class);
        $this->department = Department::create(['name' => 'Synthetic Dept', 'description' => '']);
        $this->position = Position::create(['name' => 'Synthetic Role', 'department_id' => $this->department->id]);
        $this->leaveType = LeaveType::create(['name' => 'Annual Leave', 'deducts_balance' => true]);
        // Used for tests focused purely on overlap behavior, so multi-day
        // ranges never incidentally trip the unrelated monthly-cap/eligibility
        // rules that only apply to the annual-entitlement leave type.
        $this->plainLeaveType = LeaveType::create(['name' => 'Compassionate Leave', 'deducts_balance' => false]);
    }

    private function makeUserWithEmployee(string $role, string $email, bool $isActive = true): array
    {
        $user = User::factory()->create(['email' => $email, 'role' => $role, 'is_active' => $isActive]);
        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'employment_status' => 'active',
        ]);

        return [$user, $employee];
    }

    private function makePendingLeave(Employee $employee, string $start = '2026-07-01', string $end = '2026-07-01', int $days = 1): LeaveRequest
    {
        return LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => $start,
            'end_date' => $end,
            'total_days' => $days,
            'chargeable_days' => $days,
            'reason' => 'Synthetic leave reason for Phase 59D tests.',
            'status' => 'PENDING_HR',
        ]);
    }

    private function givenAnnualBalance(Employee $employee, int $quota = 18): LeaveBalance
    {
        return LeaveBalance::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => 2026,
            'total_quota' => $quota,
            'used' => 0,
            'remaining' => $quota,
        ]);
    }

    /** Two separately-fetched PHP objects of the same row — simulates two requests that both read the row before either committed. */
    private function staleReads(LeaveRequest $leaveRequest): array
    {
        return [LeaveRequest::find($leaveRequest->id), LeaveRequest::find($leaveRequest->id)];
    }

    // ================================================================
    // APPROVAL REQUEST YANG SAMA (1-8)
    // ================================================================

    // 1, 2, 4, 5, 6: first approval succeeds; second call using a stale (still
    // PENDING_HR in memory) instance fails in a controlled way; status/approver
    // fields are not overwritten by the losing call.
    public function test_second_approval_with_stale_instance_fails_controlled_and_does_not_overwrite(): void
    {
        [$hrUser] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        [, $employeeB] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');
        $this->givenAnnualBalance($employeeB);
        $leave = $this->makePendingLeave($employeeB);
        [$staleA, $staleB] = $this->staleReads($leave);

        $this->service->approve($staleA, $hrUser, 'First approval.');

        try {
            $this->service->approve($staleB, $hrUser, 'Second (stale) approval attempt.');
            $this->fail('Expected ValidationException for a stale second approval.');
        } catch (ValidationException) {
            // expected — controlled failure, not a silent success or a 500
        }

        $fresh = $leave->fresh();
        $this->assertSame('APPROVED', $fresh->status);
        $this->assertSame($hrUser->id, $fresh->approved_by);
        $this->assertNotNull($fresh->approved_at);
        $this->assertSame('First approval.', $fresh->approval_note);
    }

    // 3: balance is only decremented once across the two stale-instance calls.
    public function test_double_approval_via_stale_instances_deducts_balance_only_once(): void
    {
        [$hrUser] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        [, $employeeB] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');
        $balance = $this->givenAnnualBalance($employeeB);
        $leave = $this->makePendingLeave($employeeB);
        [$staleA, $staleB] = $this->staleReads($leave);

        $this->service->approve($staleA, $hrUser, 'First.');
        try {
            $this->service->approve($staleB, $hrUser, 'Second.');
        } catch (ValidationException) {
        }

        $fresh = $balance->fresh();
        $this->assertSame('1.00', $fresh->used);
        $this->assertSame('17.00', $fresh->remaining);
    }

    // 7, 8: only one notification and one approve_leave audit log entry are
    // ever created via the controller route, regardless of a repeat attempt.
    public function test_repeat_approve_request_creates_only_one_notification_and_audit_log(): void
    {
        [$hrUser] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        [, $employeeB] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');
        $this->givenAnnualBalance($employeeB);
        $leave = $this->makePendingLeave($employeeB);

        $this->actingAs($hrUser)->post("/hr/leave/{$leave->id}/approve", ['approval_note' => 'First.'])->assertRedirect();
        $this->actingAs($hrUser)->post("/hr/leave/{$leave->id}/approve", ['approval_note' => 'Second.']);

        $this->assertSame(1, Notification::count());
        $this->assertSame(1, AuditLog::where('action', 'approve_leave')->count());
    }

    // ================================================================
    // APPROVE VS REJECT (9-16)
    // ================================================================

    // 9, 10: approve wins first; a subsequent reject on a stale instance fails controlled, status stays APPROVED.
    public function test_approve_then_stale_reject_fails_controlled_status_stays_approved(): void
    {
        [$hrUser] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        [, $employeeB] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');
        $this->givenAnnualBalance($employeeB);
        $leave = $this->makePendingLeave($employeeB);
        [$staleA, $staleB] = $this->staleReads($leave);

        $this->service->approve($staleA, $hrUser, 'Approved first.');

        try {
            $this->service->reject($staleB, $hrUser, 'Attempted reject after approval race.');
            $this->fail('Expected ValidationException rejecting an already-approved request.');
        } catch (ValidationException) {
        }

        $this->assertSame('APPROVED', $leave->fresh()->status);
    }

    // 11: balance only deducted once even when a losing reject is attempted afterward.
    public function test_approve_then_stale_reject_balance_only_deducted_once(): void
    {
        [$hrUser] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        [, $employeeB] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');
        $balance = $this->givenAnnualBalance($employeeB);
        $leave = $this->makePendingLeave($employeeB);
        [$staleA, $staleB] = $this->staleReads($leave);

        $this->service->approve($staleA, $hrUser, 'Approved.');
        try {
            $this->service->reject($staleB, $hrUser, 'Losing reject.');
        } catch (ValidationException) {
        }

        $fresh = $balance->fresh();
        $this->assertSame('1.00', $fresh->used);
        $this->assertSame('17.00', $fresh->remaining);
    }

    // 12: the losing reject via the controller route never creates a rejection notification/audit log.
    public function test_approve_then_losing_reject_request_creates_no_reject_notification_or_audit(): void
    {
        [$hrUser] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        [, $employeeB] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');
        $this->givenAnnualBalance($employeeB);
        $leave = $this->makePendingLeave($employeeB);

        $this->actingAs($hrUser)->post("/hr/leave/{$leave->id}/approve", ['approval_note' => 'Approved.'])->assertRedirect();
        $this->actingAs($hrUser)->post("/hr/leave/{$leave->id}/reject", ['approval_note' => 'Too late, already approved.']);

        $this->assertFalse(AuditLog::where('action', 'reject_leave')->exists());
        $this->assertSame(1, Notification::count()); // only the approval notification
    }

    // 13, 14: reject wins first; a subsequent approve on a stale instance fails controlled, status stays REJECTED.
    public function test_reject_then_stale_approve_fails_controlled_status_stays_rejected(): void
    {
        [$hrUser] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        [, $employeeB] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');
        $this->givenAnnualBalance($employeeB);
        $leave = $this->makePendingLeave($employeeB);
        [$staleA, $staleB] = $this->staleReads($leave);

        $this->service->reject($staleA, $hrUser, 'Rejected first.');

        try {
            $this->service->approve($staleB, $hrUser, 'Attempted approve after rejection race.');
            $this->fail('Expected ValidationException approving an already-rejected request.');
        } catch (ValidationException) {
        }

        $this->assertSame('REJECTED', $leave->fresh()->status);
    }

    // 15: balance is never deducted when reject wins the race.
    public function test_reject_then_stale_approve_does_not_deduct_balance(): void
    {
        [$hrUser] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        [, $employeeB] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');
        $balance = $this->givenAnnualBalance($employeeB);
        $leave = $this->makePendingLeave($employeeB);
        [$staleA, $staleB] = $this->staleReads($leave);

        $this->service->reject($staleA, $hrUser, 'Rejected.');
        try {
            $this->service->approve($staleB, $hrUser, 'Losing approve.');
        } catch (ValidationException) {
        }

        $fresh = $balance->fresh();
        $this->assertSame('0.00', $fresh->used);
        $this->assertSame('18.00', $fresh->remaining);
    }

    // 16: the losing approve via the controller route never creates an approval notification/audit log.
    public function test_reject_then_losing_approve_request_creates_no_approve_notification_or_audit(): void
    {
        [$hrUser] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        [, $employeeB] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');
        $this->givenAnnualBalance($employeeB);
        $leave = $this->makePendingLeave($employeeB);

        $this->actingAs($hrUser)->post("/hr/leave/{$leave->id}/reject", ['approval_note' => 'Rejected first, ten chars+'])->assertRedirect();
        $this->actingAs($hrUser)->post("/hr/leave/{$leave->id}/approve", ['approval_note' => 'Too late.']);

        $this->assertFalse(AuditLog::where('action', 'approve_leave')->exists());
        $this->assertSame(1, Notification::count()); // only the rejection notification
    }

    // ================================================================
    // DOUBLE REJECT (17-20)
    // ================================================================

    // 17, 18: first reject succeeds; second stale reject fails controlled.
    public function test_first_reject_succeeds_second_stale_reject_fails_controlled(): void
    {
        [$hrUser] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        [, $employeeB] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');
        $leave = $this->makePendingLeave($employeeB);
        [$staleA, $staleB] = $this->staleReads($leave);

        $this->service->reject($staleA, $hrUser, 'First rejection.');

        try {
            $this->service->reject($staleB, $hrUser, 'Second rejection attempt.');
            $this->fail('Expected ValidationException for a stale second rejection.');
        } catch (ValidationException) {
        }

        $fresh = $leave->fresh();
        $this->assertSame('REJECTED', $fresh->status);
        $this->assertSame('First rejection.', $fresh->approval_note);
    }

    // 19, 20: only one rejection notification and one reject_leave audit log entry are ever created via the route.
    public function test_repeat_reject_request_creates_only_one_notification_and_audit_log(): void
    {
        [$hrUser] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        [, $employeeB] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');
        $leave = $this->makePendingLeave($employeeB);

        $this->actingAs($hrUser)->post("/hr/leave/{$leave->id}/reject", ['approval_note' => 'First rejection, ten chars+'])->assertRedirect();
        $this->actingAs($hrUser)->post("/hr/leave/{$leave->id}/reject", ['approval_note' => 'Second rejection, ten chars+']);

        $this->assertSame(1, Notification::count());
        $this->assertSame(1, AuditLog::where('action', 'reject_leave')->count());
    }

    // ================================================================
    // DUA LEAVE REQUEST, SATU SALDO (21-25)
    // ================================================================

    // 21: balance 18, two requests each needing 10 — only one can succeed, final balance is 8, never negative.
    public function test_two_requests_needing_10_each_from_balance_18_only_one_succeeds(): void
    {
        [$hrUser] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        [, $employee] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');
        $balance = $this->givenAnnualBalance($employee, quota: 18);

        $requestA = $this->makePendingLeave($employee, '2026-01-05', '2026-01-14', days: 10);
        $requestB = $this->makePendingLeave($employee, '2026-03-02', '2026-03-11', days: 10);

        $this->service->approve($requestA, $hrUser, 'Approve A.');

        $failed = false;
        try {
            $this->service->approve($requestB, $hrUser, 'Approve B.');
        } catch (ValidationException) {
            $failed = true;
        }

        $this->assertTrue($failed, 'Second approval should fail because remaining balance is now insufficient.');
        $this->assertSame('APPROVED', $requestA->fresh()->status);
        $this->assertSame('PENDING_HR', $requestB->fresh()->status);

        $fresh = $balance->fresh();
        $this->assertSame('8.00', $fresh->remaining);
        $this->assertGreaterThanOrEqual(0.0, (float) $fresh->remaining);
    }

    // 22: balance 18, two requests each needing 9 — both succeed sequentially, final balance is 0, no lost update.
    public function test_two_requests_needing_9_each_from_balance_18_both_succeed(): void
    {
        [$hrUser] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        [, $employee] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');
        $balance = $this->givenAnnualBalance($employee, quota: 18);

        $requestA = $this->makePendingLeave($employee, '2026-01-05', '2026-01-13', days: 9);
        $requestB = $this->makePendingLeave($employee, '2026-03-02', '2026-03-10', days: 9);

        $this->service->approve($requestA, $hrUser, 'Approve A.');
        $this->service->approve($requestB, $hrUser, 'Approve B.');

        $this->assertSame('APPROVED', $requestA->fresh()->status);
        $this->assertSame('APPROVED', $requestB->fresh()->status);
        $this->assertSame('0.00', $balance->fresh()->remaining);
        $this->assertSame('18.00', $balance->fresh()->used);
    }

    // 23: approving two requests for an employee with no pre-existing balance row never creates a duplicate row.
    public function test_missing_leave_balance_not_duplicated_when_two_requests_approved(): void
    {
        [$hrUser] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        [, $employee] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');
        $employee->update(['join_date' => '2020-01-01']); // long tenure -> full entitlement, no seeded balance row

        $requestA = $this->makePendingLeave($employee, '2026-01-05', '2026-01-06', days: 2);
        $requestB = $this->makePendingLeave($employee, '2026-03-02', '2026-03-03', days: 2);

        $this->service->approve($requestA, $hrUser, 'Approve A.');
        $this->service->approve($requestB, $hrUser, 'Approve B.');

        $this->assertSame(
            1,
            LeaveBalance::where('employee_id', $employee->id)
                ->where('leave_type_id', $this->leaveType->id)
                ->where('year', 2026)
                ->count()
        );
    }

    // 24: an insufficient-balance failure leaves no partial state (status and balance both untouched).
    public function test_insufficient_balance_does_not_leave_partial_state(): void
    {
        [$hrUser] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        [, $employee] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');
        $balance = $this->givenAnnualBalance($employee, quota: 5);
        $request = $this->makePendingLeave($employee, '2026-01-05', '2026-01-14', days: 10);

        try {
            $this->service->approve($request, $hrUser, 'Not enough balance.');
            $this->fail('Expected ValidationException for insufficient balance.');
        } catch (ValidationException) {
        }

        $this->assertSame('PENDING_HR', $request->fresh()->status);
        $this->assertNull($request->fresh()->approved_by);
        $fresh = $balance->fresh();
        $this->assertSame('0.00', $fresh->used);
        $this->assertSame('5.00', $fresh->remaining);
    }

    // 25: same failure — the whole transaction rolls back, not just the balance half of it.
    public function test_failed_approval_transaction_rolls_back_status_and_balance_together(): void
    {
        [$hrUser] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        [, $employee] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');
        $balance = $this->givenAnnualBalance($employee, quota: 2);
        $request = $this->makePendingLeave($employee, '2026-01-05', '2026-01-14', days: 10);

        try {
            $this->service->approve($request, $hrUser, null);
        } catch (ValidationException) {
        }

        $this->assertSame('PENDING_HR', $request->fresh()->status);
        $this->assertNull($request->fresh()->approval_note);
        $this->assertSame('2.00', $balance->fresh()->remaining);
    }

    // ================================================================
    // SUBMISSION OVERLAP (26-37)
    // ================================================================

    // 26: two identical sequential submissions for the same employee — only one record is created, the second gets a controlled overlap error.
    public function test_two_identical_submissions_only_one_record_created(): void
    {
        [, $employee] = $this->makeUserWithEmployee('employee', 'employee.a@example.test');
        $employee->update(['join_date' => '2020-01-01']);

        $this->service->submit($employee, [
            'leave_type_id' => $this->leaveType->id,
            'start_date' => '2026-08-03',
            'end_date' => '2026-08-03',
            'reason' => 'First submission.',
        ]);

        try {
            $this->service->submit($employee, [
                'leave_type_id' => $this->leaveType->id,
                'start_date' => '2026-08-03',
                'end_date' => '2026-08-03',
                'reason' => 'Identical second submission.',
            ]);
            $this->fail('Expected ValidationException for an identical overlapping submission.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('start_date', $e->errors());
        }

        $this->assertSame(1, LeaveRequest::where('employee_id', $employee->id)->count());
    }

    private function submitOverlapCase(Employee $employee, string $start, string $end): void
    {
        $this->service->submit($employee, [
            'leave_type_id' => $this->leaveType->id,
            'start_date' => $start,
            'end_date' => $end,
            'reason' => 'Overlap case.',
        ]);
    }

    // 27: partial overlap starting before and ending inside the existing range is rejected.
    public function test_partial_overlap_from_start_is_rejected(): void
    {
        [, $employee] = $this->makeUserWithEmployee('employee', 'employee.a@example.test');
        $employee->update(['join_date' => '2020-01-01']);
        $this->makePendingLeave($employee, '2026-08-10', '2026-08-15');

        $this->expectException(ValidationException::class);
        $this->submitOverlapCase($employee, '2026-08-05', '2026-08-12');
    }

    // 28: partial overlap starting inside and ending after the existing range is rejected.
    public function test_partial_overlap_from_end_is_rejected(): void
    {
        [, $employee] = $this->makeUserWithEmployee('employee', 'employee.a@example.test');
        $employee->update(['join_date' => '2020-01-01']);
        $this->makePendingLeave($employee, '2026-08-10', '2026-08-15');

        $this->expectException(ValidationException::class);
        $this->submitOverlapCase($employee, '2026-08-13', '2026-08-20');
    }

    // 29: a new request entirely inside an existing range is rejected.
    public function test_new_request_inside_existing_range_is_rejected(): void
    {
        [, $employee] = $this->makeUserWithEmployee('employee', 'employee.a@example.test');
        $employee->update(['join_date' => '2020-01-01']);
        $this->makePendingLeave($employee, '2026-08-01', '2026-08-20');

        $this->expectException(ValidationException::class);
        $this->submitOverlapCase($employee, '2026-08-05', '2026-08-10');
    }

    // 30: a new request that wraps entirely around an existing range is rejected.
    public function test_new_request_wrapping_existing_range_is_rejected(): void
    {
        [, $employee] = $this->makeUserWithEmployee('employee', 'employee.a@example.test');
        $employee->update(['join_date' => '2020-01-01']);
        $this->makePendingLeave($employee, '2026-08-05', '2026-08-10');

        $this->expectException(ValidationException::class);
        $this->submitOverlapCase($employee, '2026-08-01', '2026-08-20');
    }

    // 31: adjacent (non-overlapping, touching) dates are still allowed, per existing rules.
    // Uses the non-annual leave type so the (unrelated, out-of-scope) monthly
    // working-day cap never incidentally interferes with a pure overlap test.
    public function test_adjacent_dates_are_still_allowed(): void
    {
        [, $employee] = $this->makeUserWithEmployee('employee', 'employee.a@example.test');
        $this->makePendingLeave($employee, '2026-08-01', '2026-08-05');
        LeaveRequest::where('employee_id', $employee->id)->update(['leave_type_id' => $this->plainLeaveType->id]);

        $request = $this->service->submit($employee, [
            'leave_type_id' => $this->plainLeaveType->id,
            'start_date' => '2026-08-06',
            'end_date' => '2026-08-06',
            'reason' => 'Adjacent, not overlapping.',
        ]);

        $this->assertSame('PENDING_HR', $request->status);
    }

    // 32: a different employee can submit the exact same dates without being blocked.
    public function test_different_employee_can_submit_same_dates(): void
    {
        [, $employeeA] = $this->makeUserWithEmployee('employee', 'employee.a@example.test');
        [, $employeeB] = $this->makeUserWithEmployee('employee', 'employee.b@example.test');
        $this->makePendingLeave($employeeA, '2026-08-05', '2026-08-10');
        LeaveRequest::where('employee_id', $employeeA->id)->update(['leave_type_id' => $this->plainLeaveType->id]);

        $request = $this->service->submit($employeeB, [
            'leave_type_id' => $this->plainLeaveType->id,
            'start_date' => '2026-08-05',
            'end_date' => '2026-08-10',
            'reason' => 'Different employee, same dates.',
        ]);

        $this->assertSame('PENDING_HR', $request->status);
    }

    // 33: a REJECTED existing request does not block a new overlapping submission (existing rule, unchanged).
    public function test_rejected_status_does_not_block_overlap(): void
    {
        [, $employee] = $this->makeUserWithEmployee('employee', 'employee.a@example.test');
        $rejected = $this->makePendingLeave($employee, '2026-08-05', '2026-08-10');
        $rejected->update(['status' => 'REJECTED', 'leave_type_id' => $this->plainLeaveType->id]);

        $request = $this->service->submit($employee, [
            'leave_type_id' => $this->plainLeaveType->id,
            'start_date' => '2026-08-05',
            'end_date' => '2026-08-10',
            'reason' => 'Same period as a rejected request.',
        ]);

        $this->assertSame('PENDING_HR', $request->status);
    }

    // 34: PENDING_HR and APPROVED existing requests still block overlap (existing rule, unchanged).
    public function test_pending_and_approved_status_still_block_overlap(): void
    {
        [, $employeeA] = $this->makeUserWithEmployee('employee', 'employee.a@example.test');
        [, $employeeB] = $this->makeUserWithEmployee('employee', 'employee.b@example.test');
        $employeeA->update(['join_date' => '2020-01-01']);
        $employeeB->update(['join_date' => '2020-01-01']);

        $this->makePendingLeave($employeeA, '2026-08-05', '2026-08-10'); // stays PENDING_HR
        $approved = $this->makePendingLeave($employeeB, '2026-08-05', '2026-08-10');
        $approved->update(['status' => 'APPROVED']);

        try {
            $this->submitOverlapCase($employeeA, '2026-08-05', '2026-08-10');
            $this->fail('Expected overlap against PENDING_HR request.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('start_date', $e->errors());
        }

        try {
            $this->submitOverlapCase($employeeB, '2026-08-05', '2026-08-10');
            $this->fail('Expected overlap against APPROVED request.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('start_date', $e->errors());
        }
    }

    // 35: half-day overlap follows the existing (unchanged) logic — a half-day request on an already-booked full day still overlaps.
    public function test_half_day_overlap_follows_existing_logic(): void
    {
        [, $employee] = $this->makeUserWithEmployee('employee', 'employee.a@example.test');
        $employee->update(['join_date' => '2020-01-01']);
        $this->makePendingLeave($employee, '2026-08-05', '2026-08-05');

        try {
            $this->service->submit($employee, [
                'leave_type_id' => $this->leaveType->id,
                'start_date' => '2026-08-05',
                'end_date' => '2026-08-05',
                'duration_type' => 'HALF_DAY',
                'reason' => 'Half day on an already-booked day.',
            ]);
            $this->fail('Expected overlap for half-day on an already-booked date.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('start_date', $e->errors());
        }
    }

    // 36: notification for submission is only created for the successful request, never for the rejected duplicate.
    public function test_notification_submission_only_created_for_successful_request(): void
    {
        [$employeeUser, $employee] = $this->makeUserWithEmployee('employee', 'employee.a@example.test');
        $employee->update(['join_date' => '2020-01-01']);
        $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test'); // notification recipient

        $this->actingAs($employeeUser)->post('/leave/request', [
            'leave_type_id' => $this->leaveType->id,
            'start_date' => '2026-08-05',
            'end_date' => '2026-08-05',
            'reason' => 'First submission.',
        ])->assertRedirect();

        $countAfterFirst = Notification::count();
        $this->assertGreaterThan(0, $countAfterFirst);

        $this->actingAs($employeeUser)->post('/leave/request', [
            'leave_type_id' => $this->leaveType->id,
            'start_date' => '2026-08-05',
            'end_date' => '2026-08-05',
            'reason' => 'Overlapping duplicate.',
        ]);

        $this->assertSame($countAfterFirst, Notification::count());
    }

    // 37: audit log for submission is only created for the successful request (existing flow does create one — LeaveController::store()).
    public function test_audit_log_submission_only_created_for_successful_request(): void
    {
        [$employeeUser, $employee] = $this->makeUserWithEmployee('employee', 'employee.a@example.test');
        $employee->update(['join_date' => '2020-01-01']);

        $this->actingAs($employeeUser)->post('/leave/request', [
            'leave_type_id' => $this->leaveType->id,
            'start_date' => '2026-08-05',
            'end_date' => '2026-08-05',
            'reason' => 'First submission.',
        ])->assertRedirect();

        $this->actingAs($employeeUser)->post('/leave/request', [
            'leave_type_id' => $this->leaveType->id,
            'start_date' => '2026-08-05',
            'end_date' => '2026-08-05',
            'reason' => 'Overlapping duplicate.',
        ]);

        $this->assertSame(1, AuditLog::where('action', 'create_leave_request')->count());
    }

    // ================================================================
    // REGRESSION (38-48)
    // ================================================================

    // 38, 39: Phase 59B self-approval/self-rejection protection is intact.
    public function test_self_approval_and_self_rejection_still_return_403(): void
    {
        [$hrUser, $ownEmployee] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        $leaveA = $this->makePendingLeave($ownEmployee, '2026-01-05', '2026-01-05');
        $leaveB = $this->makePendingLeave($ownEmployee, '2026-02-05', '2026-02-05');

        $this->actingAs($hrUser)
            ->post("/hr/leave/{$leaveA->id}/approve", ['approval_note' => 'Self approve attempt'])
            ->assertForbidden();

        $this->actingAs($hrUser)
            ->post("/hr/leave/{$leaveB->id}/reject", ['approval_note' => 'Self reject attempt, ten chars+'])
            ->assertForbidden();
    }

    // 40: Phase 59C session enforcement still blocks a deactivated session.
    public function test_inactive_session_is_still_blocked_from_approving(): void
    {
        [$hrUser] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        [, $employeeB] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');
        $leave = $this->makePendingLeave($employeeB);

        $this->actingAs($hrUser)->get('/hr/approval-queue')->assertOk();
        User::where('id', $hrUser->id)->update(['is_active' => false]);

        $response = $this->post("/hr/leave/{$leave->id}/approve", ['approval_note' => 'x']);

        $response->assertRedirect('/login');
        $this->assertGuest();
        $this->assertSame('PENDING_HR', $leave->fresh()->status);
    }

    // 41: active admin_hr can still approve another employee's leave.
    public function test_active_admin_hr_can_still_approve_other_employee_leave(): void
    {
        [$hrUser] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        [, $employeeB] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');
        $this->givenAnnualBalance($employeeB);
        $leave = $this->makePendingLeave($employeeB);

        $this->actingAs($hrUser)
            ->post("/hr/leave/{$leave->id}/approve", ['approval_note' => 'Approved.'])
            ->assertRedirect();

        $this->assertSame('APPROVED', $leave->fresh()->status);
    }

    // 42: active super_admin can still approve another employee's leave.
    public function test_active_super_admin_can_still_approve_other_employee_leave(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        [, $employeeB] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');
        $this->givenAnnualBalance($employeeB);
        $leave = $this->makePendingLeave($employeeB);

        $this->actingAs($superAdmin)
            ->post("/hr/leave/{$leave->id}/approve", ['approval_note' => 'Approved by super admin.'])
            ->assertRedirect();

        $this->assertSame('APPROVED', $leave->fresh()->status);
    }

    // 43: a plain employee still cannot approve anyone's leave.
    public function test_plain_employee_still_cannot_approve(): void
    {
        [$employeeUser] = $this->makeUserWithEmployee('employee', 'employee.a@example.test');
        [, $employeeB] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');
        $leave = $this->makePendingLeave($employeeB);

        $this->actingAs($employeeUser)
            ->post("/hr/leave/{$leave->id}/approve", ['approval_note' => 'x'])
            ->assertForbidden();
    }

    // 44: finance still cannot approve.
    public function test_finance_still_cannot_approve(): void
    {
        $financeUser = User::factory()->create(['role' => 'finance', 'is_active' => true]);
        [, $employeeB] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');
        $leave = $this->makePendingLeave($employeeB);

        $this->actingAs($financeUser)
            ->post("/hr/leave/{$leave->id}/approve", ['approval_note' => 'x'])
            ->assertForbidden();
    }

    // 45: annual entitlement calculation (quota derivation) is unchanged.
    public function test_annual_entitlement_calculation_unchanged(): void
    {
        [$hrUser] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        [, $employee] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');
        $employee->update(['join_date' => '2020-01-01']); // long tenure -> full 18-day entitlement
        $request = $this->makePendingLeave($employee, '2026-01-05', '2026-01-06', days: 2);

        $this->service->approve($request, $hrUser, 'Approved.');

        $balance = LeaveBalance::where('employee_id', $employee->id)->first();
        $this->assertSame('18.00', $balance->total_quota);
        $this->assertSame('16.00', $balance->remaining);
    }

    // 46: half-day deduction (always exactly 1 whole day) is unchanged.
    public function test_half_day_deduction_unchanged(): void
    {
        [$hrUser] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        [, $employee] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');
        $employee->update(['join_date' => '2020-01-01']);
        $this->givenAnnualBalance($employee, quota: 18);

        $request = $this->service->submit($employee, [
            'leave_type_id' => $this->leaveType->id,
            'start_date' => '2026-08-03', // Monday
            'end_date' => '2026-08-03',
            'duration_type' => 'HALF_DAY',
            'reason' => 'Half day test.',
        ]);

        $this->service->approve($request, $hrUser, 'Approved.');

        $balance = LeaveBalance::where('employee_id', $employee->id)->first();
        $this->assertSame('1.00', $balance->fresh()->used);
        $this->assertSame('17.00', $balance->fresh()->remaining);
    }

    // 47: notification for a valid, successful approval still works.
    public function test_notification_for_valid_approval_still_works(): void
    {
        [$hrUser] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        [, $employeeB] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');
        $this->givenAnnualBalance($employeeB);
        $leave = $this->makePendingLeave($employeeB);

        $this->actingAs($hrUser)->post("/hr/leave/{$leave->id}/approve", ['approval_note' => 'Approved.']);

        $this->assertSame(1, Notification::count());
        $this->assertSame('leave', Notification::first()->type);
    }

    // 48: audit log for a valid, successful approval still works.
    public function test_audit_log_for_valid_approval_still_works(): void
    {
        [$hrUser] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        [, $employeeB] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');
        $this->givenAnnualBalance($employeeB);
        $leave = $this->makePendingLeave($employeeB);

        $this->actingAs($hrUser)->post("/hr/leave/{$leave->id}/approve", ['approval_note' => 'Approved.']);

        $this->assertTrue(AuditLog::where('action', 'approve_leave')->exists());
    }
}
