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
use Tests\TestCase;

/**
 * Phase 59C — active-session enforcement.
 *
 * Verifies EnsureUserIsActive: a user deactivated (is_active -> false) while
 * already logged in must be logged out and blocked on their NEXT request,
 * not merely prevented from logging in again. Every "mid-session" test below
 * deliberately mutates is_active via a separate query (never through the
 * $user object handed to actingAs()) so the transition is genuinely proven,
 * not assumed — the middleware must re-read the database, not trust a
 * cached model instance.
 *
 * Concurrency (row locking) is explicitly out of scope for this phase.
 */
class Phase59CActiveSessionEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private Position $position;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::create(['name' => 'Synthetic Dept', 'description' => '']);
        $this->position = Position::create(['name' => 'Synthetic Role', 'department_id' => $this->department->id]);
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

    private function deactivate(User $user): void
    {
        // Deliberately a separate write, decoupled from the $user instance
        // passed to actingAs(), so the test proves a real active -> inactive
        // database transition rather than starting the user out inactive.
        User::where('id', $user->id)->update(['is_active' => false]);
    }

    // ── 1 & 2. Guest can open the login page; repeated guest requests never loop. ──
    public function test_guest_can_open_login_page_without_redirect_loop(): void
    {
        $this->get('/login')->assertOk();
        $this->get('/login')->assertOk();
        $this->assertGuest();
    }

    // ── 34. A public/framework route (health check) remains reachable for guests. ──
    public function test_health_check_route_remains_reachable(): void
    {
        $this->get('/up')->assertOk();
    }

    // ── 3. Active employee can open the employee dashboard. ──
    public function test_active_employee_can_open_dashboard(): void
    {
        [$user] = $this->makeUserWithEmployee('employee', 'employee.a@example.test');

        $this->actingAs($user)->get('/employee/dashboard')->assertOk();
    }

    // ── 4. Active admin_hr can open the HR area. ──
    public function test_active_admin_hr_can_open_hr_area(): void
    {
        [$user] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');

        $this->actingAs($user)->get('/hr/approval-queue')->assertOk();
        $this->actingAs($user)->get('/admin/dashboard')->assertOk();
    }

    // ── 5. Active finance can open the finance area. ──
    public function test_active_finance_can_open_finance_area(): void
    {
        $user = User::factory()->create(['role' => 'finance', 'is_active' => true]);

        $this->actingAs($user)->get('/finance/dashboard')->assertOk();
    }

    // ── 6. Active super_admin can open the admin/HR area. ──
    public function test_active_super_admin_can_open_admin_area(): void
    {
        $user = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($user)->get('/admin/dashboard')->assertOk();
        $this->actingAs($user)->get('/hr/approval-queue')->assertOk();
    }

    // ── 7, 11, 12 & 13. Employee deactivated mid-session is blocked, redirected, ──
    // ── shown the message, and no longer authenticated. ──
    public function test_employee_deactivated_mid_session_is_blocked_on_next_request(): void
    {
        [$user] = $this->makeUserWithEmployee('employee', 'employee.a@example.test');

        $this->actingAs($user)->get('/employee/dashboard')->assertOk();

        $this->deactivate($user);

        $response = $this->get('/employee/dashboard');

        $response->assertRedirect('/login');
        $this->assertGuest();
        $response->assertSessionHasErrors(['email' => 'Akun Anda telah dinonaktifkan. Hubungi administrator.']);
    }

    // ── 8. admin_hr deactivated mid-session is blocked on next request. ──
    public function test_admin_hr_deactivated_mid_session_is_blocked_on_next_request(): void
    {
        [$user] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');

        $this->actingAs($user)->get('/hr/approval-queue')->assertOk();

        $this->deactivate($user);

        $this->get('/hr/approval-queue')->assertRedirect('/login');
        $this->assertGuest();
    }

    // ── 9. finance deactivated mid-session is blocked on next request. ──
    public function test_finance_deactivated_mid_session_is_blocked_on_next_request(): void
    {
        $user = User::factory()->create(['role' => 'finance', 'is_active' => true]);

        $this->actingAs($user)->get('/finance/dashboard')->assertOk();

        $this->deactivate($user);

        $this->get('/finance/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    // ── 10. super_admin deactivated mid-session is blocked on next request. ──
    public function test_super_admin_deactivated_mid_session_is_blocked_on_next_request(): void
    {
        $user = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($user)->get('/admin/dashboard')->assertOk();

        $this->deactivate($user);

        $this->get('/admin/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    // ── 14. The old session cannot be reused for a subsequent protected request either. ──
    public function test_old_session_cannot_be_reused_for_a_later_protected_request(): void
    {
        [$user] = $this->makeUserWithEmployee('employee', 'employee.a@example.test');

        $this->actingAs($user)->get('/employee/dashboard')->assertOk();
        $this->deactivate($user);

        $this->get('/employee/dashboard')->assertRedirect('/login');

        // A second, later request against the same (now guest) session context
        // must still be refused — not just the very first blocked request.
        $this->get('/employee/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    // ── 15. Reactivating the user does not resurrect the old session. ──
    public function test_reactivated_user_must_log_in_again_after_session_was_invalidated(): void
    {
        [$user] = $this->makeUserWithEmployee('employee', 'employee.a@example.test');

        $this->actingAs($user)->get('/employee/dashboard')->assertOk();
        $this->deactivate($user);
        $this->get('/employee/dashboard')->assertRedirect('/login');
        $this->assertGuest();

        // Reactivate the account.
        User::where('id', $user->id)->update(['is_active' => true]);

        // The old (already-invalidated) session must not silently regain access.
        $this->get('/employee/dashboard')->assertRedirect('/login');
        $this->assertGuest();

        // A fresh login (not the old session) works normally again.
        $this->actingAs($user->fresh())->get('/employee/dashboard')->assertOk();
    }

    // ── 16, 17 & 18. JSON request from a deactivated user gets 403 JSON, no HTML redirect. ──
    public function test_json_request_from_deactivated_user_returns_403_json(): void
    {
        [$user] = $this->makeUserWithEmployee('employee', 'employee.a@example.test');

        $this->actingAs($user)->getJson('/employee/dashboard')->assertOk();

        $this->deactivate($user);

        $response = $this->getJson('/employee/dashboard');

        $response->assertStatus(403);
        $response->assertHeader('content-type', 'application/json');
        $response->assertJson(['message' => 'Akun Anda telah dinonaktifkan.']);
    }

    // ── 19. The middleware stops the request before the target controller runs. ──
    // (Proven via business-data safety below: nothing about the leave request changes.)

    // ── 20-26. A leave approval attempt from a deactivated admin_hr session is ──
    // ── blocked before any mutation, notification, or audit log is written. ──
    public function test_inactive_admin_hr_cannot_approve_other_employee_leave_using_old_session(): void
    {
        [$approver] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        [, $employeeB] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');

        $leaveType = LeaveType::create(['name' => 'Annual Leave', 'deducts_balance' => true]);
        $balance = LeaveBalance::create([
            'employee_id' => $employeeB->id,
            'leave_type_id' => $leaveType->id,
            'year' => 2026,
            'total_quota' => 18,
            'used' => 0,
            'remaining' => 18,
        ]);
        $leave = LeaveRequest::create([
            'employee_id' => $employeeB->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-01',
            'total_days' => 1,
            'chargeable_days' => 1,
            'reason' => 'Synthetic leave reason for Phase 59C tests.',
            'status' => 'PENDING_HR',
        ]);

        // Session established while the approver was still active.
        $this->actingAs($approver)->get('/hr/approval-queue')->assertOk();

        $this->deactivate($approver);

        $response = $this->post("/hr/leave/{$leave->id}/approve", ['approval_note' => 'Attempt with stale session']);

        $response->assertRedirect('/login');
        $this->assertGuest();

        $fresh = $leave->fresh();
        $this->assertSame('PENDING_HR', $fresh->status);
        $this->assertNull($fresh->approved_by);
        $this->assertNull($fresh->approved_at);

        $freshBalance = $balance->fresh();
        $this->assertSame('0.00', $freshBalance->used);
        $this->assertSame('18.00', $freshBalance->remaining);

        $this->assertSame(0, Notification::count());
        $this->assertFalse(AuditLog::where('action', 'approve_leave')->exists());
    }

    // ── 27. An active admin_hr can still approve another employee's leave normally. ──
    public function test_active_admin_hr_can_still_approve_other_employee_leave(): void
    {
        [$approver] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');
        [, $employeeB] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');

        $leaveType = LeaveType::create(['name' => 'Annual Leave', 'deducts_balance' => true]);
        $leave = LeaveRequest::create([
            'employee_id' => $employeeB->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-01',
            'total_days' => 1,
            'chargeable_days' => 1,
            'reason' => 'Synthetic leave reason for Phase 59C tests.',
            'status' => 'PENDING_HR',
        ]);

        $this->actingAs($approver)
            ->post("/hr/leave/{$leave->id}/approve", ['approval_note' => 'Approved while active'])
            ->assertRedirect();

        $this->assertSame('APPROVED', $leave->fresh()->status);
        $this->assertSame($approver->id, $leave->fresh()->approved_by);
    }

    // ── 32. An active admin_hr/super_admin is still refused self-approval (Phase 59B intact). ──
    public function test_active_admin_hr_still_cannot_self_approve_leave(): void
    {
        [$approver, $ownEmployee] = $this->makeUserWithEmployee('admin_hr', 'admin.a@example.test');

        $leaveType = LeaveType::create(['name' => 'Annual Leave', 'deducts_balance' => true]);
        $leave = LeaveRequest::create([
            'employee_id' => $ownEmployee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-01',
            'total_days' => 1,
            'chargeable_days' => 1,
            'reason' => 'Synthetic leave reason for Phase 59C tests.',
            'status' => 'PENDING_HR',
        ]);

        $this->actingAs($approver)
            ->post("/hr/leave/{$leave->id}/approve", ['approval_note' => 'Self approve attempt'])
            ->assertForbidden();

        $this->assertSame('PENDING_HR', $leave->fresh()->status);
        // Forbidden here comes from LeaveRequestPolicy (Phase 59B), not a redirect —
        // confirms the new session middleware did not weaken or replace the Policy.
        $this->assertAuthenticated();
    }

    // ── 35. The middleware never flips is_active itself. ──
    public function test_middleware_does_not_change_is_active_value(): void
    {
        [$user] = $this->makeUserWithEmployee('employee', 'employee.a@example.test');

        $this->actingAs($user)->get('/employee/dashboard')->assertOk();
        $this->deactivate($user);

        $this->get('/employee/dashboard')->assertRedirect('/login');

        $this->assertFalse((bool) User::find($user->id)->is_active);
    }

    // ── 36. Blocking one user's request never touches another user's data. ──
    public function test_blocking_one_user_does_not_affect_another_users_data(): void
    {
        [$blockedUser] = $this->makeUserWithEmployee('employee', 'employee.a@example.test');
        [$otherUser] = $this->makeUserWithEmployee('admin_hr', 'admin.b@example.test');

        $this->actingAs($blockedUser)->get('/employee/dashboard')->assertOk();
        $this->deactivate($blockedUser);

        $this->get('/employee/dashboard')->assertRedirect('/login');

        $otherFresh = $otherUser->fresh();
        $this->assertTrue((bool) $otherFresh->is_active);
        $this->assertSame($otherUser->role, $otherFresh->role);
        $this->assertSame($otherUser->email, $otherFresh->email);
    }
}
