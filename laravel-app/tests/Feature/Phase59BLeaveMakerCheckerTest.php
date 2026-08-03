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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Phase 59B — Leave maker-checker self-approval protection.
 *
 * Mirrors the maker-checker pattern already covered for Attendance in
 * AttendanceApprovalTest and for Expenses in Phase21MakerCheckerTest:
 * an admin_hr/super_admin approver may never approve or reject a leave
 * request that belongs to their own linked employee profile.
 *
 * Concurrency (row locking / atomic status transitions) is explicitly out of
 * scope for this phase — see Phase 59A finding #3/#4, deferred to Phase 59D.
 */
class Phase59BLeaveMakerCheckerTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private Position $position;

    private LeaveType $leaveType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::create(['name' => 'Synthetic Dept', 'description' => '']);
        $this->position = Position::create(['name' => 'Synthetic Role', 'department_id' => $this->department->id]);
        $this->leaveType = LeaveType::create(['name' => 'Annual Leave', 'deducts_balance' => true]);
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

    private function makePendingLeave(Employee $employee, string $start = '2026-07-01', string $end = '2026-07-01'): LeaveRequest
    {
        return LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => $start,
            'end_date' => $end,
            'total_days' => 1,
            'chargeable_days' => 1,
            'reason' => 'Synthetic leave reason for Phase 59B tests.',
            'status' => 'PENDING_HR',
        ]);
    }

    private function givenAnnualBalance(Employee $employee): LeaveBalance
    {
        return LeaveBalance::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => 2026,
            'total_quota' => 18,
            'used' => 0,
            'remaining' => 18,
        ]);
    }

    // ── 1 & 7 & 10 & 11 & 12. admin_hr cannot approve own leave; state untouched. ──
    public function test_admin_hr_cannot_approve_own_leave(): void
    {
        [$userA, $employeeA] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        $this->givenAnnualBalance($employeeA);
        $leave = $this->makePendingLeave($employeeA);

        $this->actingAs($userA)
            ->post("/hr/leave/{$leave->id}/approve", ['approval_note' => 'Self approve attempt'])
            ->assertForbidden();

        $fresh = $leave->fresh();
        $this->assertSame('PENDING_HR', $fresh->status);
        $this->assertNull($fresh->approved_by);
        $this->assertNull($fresh->approved_at);
        $this->assertSame(0, Notification::count());
    }

    // ── 2 & 8. admin_hr cannot reject own leave; status untouched. ──
    public function test_admin_hr_cannot_reject_own_leave(): void
    {
        [$userA, $employeeA] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        $leave = $this->makePendingLeave($employeeA);

        $this->actingAs($userA)
            ->post("/hr/leave/{$leave->id}/reject", ['approval_note' => 'Self reject attempt, ten chars+'])
            ->assertForbidden();

        $this->assertSame('PENDING_HR', $leave->fresh()->status);
    }

    // ── 3. super_admin (with employee profile) cannot approve own leave. ──
    public function test_super_admin_with_employee_profile_cannot_approve_own_leave(): void
    {
        [$superAdmin, $employee] = $this->makeUserWithEmployee('super_admin', 'super.a@example.test');
        $leave = $this->makePendingLeave($employee);

        $this->actingAs($superAdmin)
            ->post("/hr/leave/{$leave->id}/approve", ['approval_note' => 'Self approve attempt'])
            ->assertForbidden();

        $this->assertSame('PENDING_HR', $leave->fresh()->status);
    }

    // ── 4. super_admin (with employee profile) cannot reject own leave. ──
    public function test_super_admin_with_employee_profile_cannot_reject_own_leave(): void
    {
        [$superAdmin, $employee] = $this->makeUserWithEmployee('super_admin', 'super.a@example.test');
        $leave = $this->makePendingLeave($employee);

        $this->actingAs($superAdmin)
            ->post("/hr/leave/{$leave->id}/reject", ['approval_note' => 'Self reject attempt, ten chars+'])
            ->assertForbidden();

        $this->assertSame('PENDING_HR', $leave->fresh()->status);
    }

    // ── 5. Direct POST self-approve (no UI involved) still returns 403. ──
    public function test_direct_post_self_approve_returns_403(): void
    {
        [$userA, $employeeA] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        $leave = $this->makePendingLeave($employeeA);

        $response = $this->actingAs($userA)->post("/hr/leave/{$leave->id}/approve", [
            'approval_note' => 'Direct endpoint call, no UI involved',
        ]);

        $response->assertForbidden();
        $this->assertSame('PENDING_HR', $leave->fresh()->status);
    }

    // ── 6. Direct POST self-reject (no UI involved) still returns 403. ──
    public function test_direct_post_self_reject_returns_403(): void
    {
        [$userA, $employeeA] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        $leave = $this->makePendingLeave($employeeA);

        $response = $this->actingAs($userA)->post("/hr/leave/{$leave->id}/reject", [
            'approval_note' => 'Direct endpoint call, no UI involved',
        ]);

        $response->assertForbidden();
        $this->assertSame('PENDING_HR', $leave->fresh()->status);
    }

    // ── 9. Leave balance is untouched after a denied self-approve attempt. ──
    public function test_self_approve_denied_does_not_change_balance(): void
    {
        [$userA, $employeeA] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        $balance = $this->givenAnnualBalance($employeeA);
        $leave = $this->makePendingLeave($employeeA);

        $this->actingAs($userA)->post("/hr/leave/{$leave->id}/approve", ['approval_note' => 'x']);

        $fresh = $balance->fresh();
        $this->assertSame('0.00', $fresh->used);
        $this->assertSame('18.00', $fresh->remaining);
    }

    // ── 13. No notification is created after a denied self-reject attempt. ──
    public function test_self_reject_denied_creates_no_notification(): void
    {
        [$userA, $employeeA] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        $leave = $this->makePendingLeave($employeeA);

        $this->actingAs($userA)->post("/hr/leave/{$leave->id}/reject", ['approval_note' => 'Self reject attempt, ten chars+']);

        $this->assertSame(0, Notification::count());
    }

    // ── 14 & 15. No success audit log entry is written after authorization denies the request. ──
    public function test_denied_self_approval_and_self_rejection_write_no_success_audit_log(): void
    {
        [$userA, $employeeA] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        $leaveForApprove = $this->makePendingLeave($employeeA, '2026-07-01', '2026-07-01');
        $leaveForReject = $this->makePendingLeave($employeeA, '2026-07-05', '2026-07-05');

        $this->actingAs($userA)->post("/hr/leave/{$leaveForApprove->id}/approve", ['approval_note' => 'x']);
        $this->actingAs($userA)->post("/hr/leave/{$leaveForReject->id}/reject", ['approval_note' => 'Self reject attempt, ten chars+']);

        $this->assertFalse(AuditLog::where('action', 'approve_leave')->exists());
        $this->assertFalse(AuditLog::where('action', 'reject_leave')->exists());
    }

    // ── 16. admin_hr can approve another employee's leave request. ──
    public function test_admin_hr_can_approve_other_employee_leave(): void
    {
        [$userA] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        [, $employeeB] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');
        $leave = $this->makePendingLeave($employeeB);

        $this->actingAs($userA)
            ->post("/hr/leave/{$leave->id}/approve", ['approval_note' => 'Approved by A'])
            ->assertRedirect();

        $fresh = $leave->fresh();
        $this->assertSame('APPROVED', $fresh->status);
        $this->assertSame($userA->id, $fresh->approved_by);
    }

    // ── 17. admin_hr can reject another employee's leave request. ──
    public function test_admin_hr_can_reject_other_employee_leave(): void
    {
        [$userA] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        [, $employeeB] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');
        $leave = $this->makePendingLeave($employeeB);

        $this->actingAs($userA)
            ->post("/hr/leave/{$leave->id}/reject", ['approval_note' => 'Rejected by A, ten chars+'])
            ->assertRedirect();

        $this->assertSame('REJECTED', $leave->fresh()->status);
    }

    // ── 18. super_admin with an employee profile can approve another employee's leave. ──
    public function test_super_admin_with_employee_profile_can_approve_other_employee_leave(): void
    {
        [$superAdmin] = $this->makeUserWithEmployee('super_admin', 'super.a@example.test');
        [, $employeeB] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');
        $leave = $this->makePendingLeave($employeeB);

        $this->actingAs($superAdmin)
            ->post("/hr/leave/{$leave->id}/approve", ['approval_note' => 'Approved by super admin'])
            ->assertRedirect();

        $this->assertSame('APPROVED', $leave->fresh()->status);
    }

    // ── 19. super_admin without an employee profile can approve another employee's leave. ──
    public function test_super_admin_without_employee_profile_can_approve_other_employee_leave(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        [, $employeeB] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');
        $leave = $this->makePendingLeave($employeeB);

        $this->actingAs($superAdmin)
            ->post("/hr/leave/{$leave->id}/approve", ['approval_note' => 'Approved by super admin'])
            ->assertRedirect();

        $this->assertSame('APPROVED', $leave->fresh()->status);
    }

    // ── 20. An inactive admin_hr user cannot approve anyone's leave. ──
    public function test_inactive_admin_hr_cannot_approve(): void
    {
        [$inactiveUser] = $this->makeUserWithEmployee('admin_hr', 'inactive.a@example.test', false);
        [, $employeeB] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');
        $leave = $this->makePendingLeave($employeeB);

        $this->actingAs($inactiveUser)
            ->post("/hr/leave/{$leave->id}/approve", ['approval_note' => 'x'])
            ->assertForbidden();

        $this->assertSame('PENDING_HR', $leave->fresh()->status);
    }

    // ── 21. An inactive admin_hr user cannot reject anyone's leave. ──
    public function test_inactive_admin_hr_cannot_reject(): void
    {
        [$inactiveUser] = $this->makeUserWithEmployee('admin_hr', 'inactive.a@example.test', false);
        [, $employeeB] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');
        $leave = $this->makePendingLeave($employeeB);

        $this->actingAs($inactiveUser)
            ->post("/hr/leave/{$leave->id}/reject", ['approval_note' => 'ten characters minimum'])
            ->assertForbidden();

        $this->assertSame('PENDING_HR', $leave->fresh()->status);
    }

    // ── 22 & 23. A plain employee gets 403 on both approve and reject. ──
    public function test_plain_employee_gets_403_on_approve_and_reject(): void
    {
        [$employeeUser] = $this->makeUserWithEmployee('employee', 'employee.a@example.test');
        [, $employeeB] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');
        $leave = $this->makePendingLeave($employeeB);

        $this->actingAs($employeeUser)
            ->post("/hr/leave/{$leave->id}/approve", ['approval_note' => 'x'])
            ->assertForbidden();

        $this->actingAs($employeeUser)
            ->post("/hr/leave/{$leave->id}/reject", ['approval_note' => 'ten characters minimum'])
            ->assertForbidden();

        $this->assertSame('PENDING_HR', $leave->fresh()->status);
    }

    // ── 24. A finance user gets 403 on both approve and reject. ──
    public function test_finance_gets_403_on_approve_and_reject(): void
    {
        [$financeUser] = $this->makeUserWithEmployee('finance', 'finance.a@example.test');
        [, $employeeB] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');
        $leave = $this->makePendingLeave($employeeB);

        $this->actingAs($financeUser)
            ->post("/hr/leave/{$leave->id}/approve", ['approval_note' => 'x'])
            ->assertForbidden();

        $this->actingAs($financeUser)
            ->post("/hr/leave/{$leave->id}/reject", ['approval_note' => 'ten characters minimum'])
            ->assertForbidden();

        $this->assertSame('PENDING_HR', $leave->fresh()->status);
    }

    // ── 25, 26 & 27. Approval queue view: self-owned leave request is shown read-only. ──
    public function test_approval_queue_shows_self_owned_leave_as_read_only(): void
    {
        [$userA, $employeeA] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        $leave = $this->makePendingLeave($employeeA);

        $response = $this->actingAs($userA)->get('/hr/approval-queue');

        $response->assertOk();
        $response->assertSee('Anda tidak dapat memproses pengajuan cuti sendiri.');
        $response->assertDontSee("/hr/leave/{$leave->id}/approve", false);
        $response->assertDontSee("/hr/leave/{$leave->id}/reject", false);
    }

    // ── 28. Approval queue still shows approve/reject buttons for another employee's leave. ──
    public function test_approval_queue_shows_buttons_for_other_employee_leave(): void
    {
        [$userA] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        [, $employeeB] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');
        $leave = $this->makePendingLeave($employeeB);

        $response = $this->actingAs($userA)->get('/hr/approval-queue');

        $response->assertOk();
        $response->assertSee("/hr/leave/{$leave->id}/approve", false);
        $response->assertSee("/hr/leave/{$leave->id}/reject", false);
    }

    // ── 29 & 30. Approving another employee's leave still creates a notification and audit log. ──
    public function test_approving_other_employee_leave_still_creates_notification_and_audit_log(): void
    {
        [$userA] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        [, $employeeB] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');
        $leave = $this->makePendingLeave($employeeB);

        $this->actingAs($userA)->post("/hr/leave/{$leave->id}/approve", ['approval_note' => 'Approved by A']);

        $this->assertSame(1, Notification::count());
        $this->assertTrue(AuditLog::where('action', 'approve_leave')->exists());
    }

    // ── 31. Rejecting another employee's leave still works exactly as before. ──
    public function test_rejecting_other_employee_leave_still_works(): void
    {
        [$userA] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        [, $employeeB] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');
        $leave = $this->makePendingLeave($employeeB);

        $this->actingAs($userA)
            ->post("/hr/leave/{$leave->id}/reject", ['approval_note' => 'Rejected by A, ten chars+'])
            ->assertRedirect();

        $this->assertSame('REJECTED', $leave->fresh()->status);
        $this->assertSame(1, Notification::count());
        $this->assertTrue(AuditLog::where('action', 'reject_leave')->exists());
    }

    // ── 32. viewAttachment() (untouched by Phase 59B) still behaves as before. ──
    public function test_view_attachment_policy_still_works(): void
    {
        [$userA, $employeeA] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        [$employeeUser, $employeeB] = $this->makeUserWithEmployee('employee', 'employee.b@example.test');
        $ownLeave = $this->makePendingLeave($employeeA);
        $othersLeave = $this->makePendingLeave($employeeB, '2026-07-10', '2026-07-10');

        // admin_hr/super_admin may always view attachments (unchanged rule).
        $this->assertTrue(Gate::forUser($userA)->allows('viewAttachment', $othersLeave));

        // The owning employee may view their own attachment (unchanged rule).
        $this->assertTrue(Gate::forUser($employeeUser)->allows('viewAttachment', $othersLeave));

        // An unrelated employee may not view someone else's attachment (unchanged rule).
        $this->assertFalse(Gate::forUser($employeeUser)->allows('viewAttachment', $ownLeave));
    }
}
