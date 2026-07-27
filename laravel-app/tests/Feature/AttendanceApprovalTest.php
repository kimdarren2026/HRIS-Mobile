<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceApprovalTest extends TestCase
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

    private function makeAdminHrWithEmployee(string $email): array
    {
        $user = User::factory()->create(['email' => $email, 'role' => 'admin_hr', 'is_active' => true]);
        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'employment_status' => 'active',
        ]);

        return [$user, $employee];
    }

    private function makePendingAttendance(Employee $employee, string $date = '2026-07-01'): AttendanceRecord
    {
        return AttendanceRecord::create([
            'employee_id' => $employee->id,
            'attendance_date' => $date,
            'check_in_time' => $date.' 08:00:00',
            'status' => 'PENDING_REVIEW',
            'out_of_radius_reason' => 'Kunjungan lapangan (data sintetis).',
        ]);
    }

    // 1 & 2. admin_hr A cannot approve their own out-of-radius attendance.
    public function test_admin_hr_cannot_approve_own_attendance(): void
    {
        [$userA, $employeeA] = $this->makeAdminHrWithEmployee('admin.a@example.test');
        $record = $this->makePendingAttendance($employeeA);

        $this->actingAs($userA)
            ->post("/hr/attendance/{$record->id}/approve", ['approval_note' => 'Self approve attempt'])
            ->assertForbidden();

        $this->assertSame('PENDING_REVIEW', $record->fresh()->status);
    }

    // 3. admin_hr A cannot reject their own attendance either.
    public function test_admin_hr_cannot_reject_own_attendance(): void
    {
        [$userA, $employeeA] = $this->makeAdminHrWithEmployee('admin.a@example.test');
        $record = $this->makePendingAttendance($employeeA);

        $this->actingAs($userA)
            ->post("/hr/attendance/{$record->id}/reject", ['approval_note' => 'Self reject attempt, ten chars+'])
            ->assertForbidden();

        $this->assertSame('PENDING_REVIEW', $record->fresh()->status);
    }

    // 4. admin_hr B can approve admin_hr A's attendance.
    public function test_admin_hr_b_can_approve_admin_hr_a_attendance(): void
    {
        [, $employeeA] = $this->makeAdminHrWithEmployee('admin.a@example.test');
        [$userB] = $this->makeAdminHrWithEmployee('admin.b@example.test');
        $record = $this->makePendingAttendance($employeeA);

        $this->actingAs($userB)
            ->post("/hr/attendance/{$record->id}/approve", ['approval_note' => 'Approved by B'])
            ->assertRedirect();

        $this->assertSame('APPROVED', $record->fresh()->status);
        $this->assertSame($userB->id, $record->fresh()->approved_by);
    }

    // 5 & 6. admin_hr A can approve admin_hr B's attendance (cross-approval both ways works).
    public function test_admin_hr_a_can_approve_admin_hr_b_attendance(): void
    {
        [$userA] = $this->makeAdminHrWithEmployee('admin.a@example.test');
        [, $employeeB] = $this->makeAdminHrWithEmployee('admin.b@example.test');
        $record = $this->makePendingAttendance($employeeB);

        $this->actingAs($userA)
            ->post("/hr/attendance/{$record->id}/approve", ['approval_note' => 'Approved by A'])
            ->assertRedirect();

        $this->assertSame('APPROVED', $record->fresh()->status);
        $this->assertSame($userA->id, $record->fresh()->approved_by);
    }

    // 7. A plain employee can never approve or reject, self or otherwise.
    public function test_plain_employee_cannot_approve_or_reject(): void
    {
        $employeeUser = User::factory()->create(['role' => 'employee', 'is_active' => true]);
        $employee = Employee::factory()->create([
            'user_id' => $employeeUser->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'employment_status' => 'active',
        ]);
        $record = $this->makePendingAttendance($employee);

        $this->actingAs($employeeUser)
            ->post("/hr/attendance/{$record->id}/approve", ['approval_note' => 'x'])
            ->assertForbidden();

        $this->actingAs($employeeUser)
            ->post("/hr/attendance/{$record->id}/reject", ['approval_note' => 'ten characters minimum'])
            ->assertForbidden();

        $this->assertSame('PENDING_REVIEW', $record->fresh()->status);
    }

    // 8. Hitting the endpoint directly (bypassing any UI) still refuses self-approval.
    public function test_direct_request_still_rejects_self_approval(): void
    {
        [$userA, $employeeA] = $this->makeAdminHrWithEmployee('admin.a@example.test');
        $record = $this->makePendingAttendance($employeeA);

        $response = $this->actingAs($userA)->post("/hr/attendance/{$record->id}/approve", [
            'approval_note' => 'Direct endpoint call, no UI involved',
        ]);

        $response->assertForbidden();
        $this->assertSame('PENDING_REVIEW', $record->fresh()->status);
    }

    // 9. Self-approval attempt never creates a success notification.
    public function test_self_approval_attempt_does_not_create_notification(): void
    {
        [$userA, $employeeA] = $this->makeAdminHrWithEmployee('admin.a@example.test');
        $record = $this->makePendingAttendance($employeeA);

        $this->actingAs($userA)->post("/hr/attendance/{$record->id}/approve", ['approval_note' => 'x']);

        $this->assertSame(0, Notification::count());
        $this->assertFalse(AuditLog::where('action', 'approve_attendance')->exists());
    }

    // 10. Self-approval attempt never fills approver fields.
    public function test_self_approval_attempt_does_not_fill_approver_fields(): void
    {
        [$userA, $employeeA] = $this->makeAdminHrWithEmployee('admin.a@example.test');
        $record = $this->makePendingAttendance($employeeA);

        $this->actingAs($userA)->post("/hr/attendance/{$record->id}/approve", ['approval_note' => 'x']);

        $fresh = $record->fresh();
        $this->assertNull($fresh->approved_by);
        $this->assertNull($fresh->approved_at);
    }

    // 1. admin_hr without an employee profile is refused approving anyone's attendance.
    public function test_admin_hr_without_employee_profile_cannot_approve(): void
    {
        $adminNoProfile = User::factory()->create(['role' => 'admin_hr', 'is_active' => true]);
        [, $employeeB] = $this->makeAdminHrWithEmployee('admin.b@example.test');
        $record = $this->makePendingAttendance($employeeB);

        $this->actingAs($adminNoProfile)
            ->post("/hr/attendance/{$record->id}/approve", ['approval_note' => 'x'])
            ->assertForbidden();

        $this->assertSame('PENDING_REVIEW', $record->fresh()->status);
    }

    // 2. Same, for reject.
    public function test_admin_hr_without_employee_profile_cannot_reject(): void
    {
        $adminNoProfile = User::factory()->create(['role' => 'admin_hr', 'is_active' => true]);
        [, $employeeB] = $this->makeAdminHrWithEmployee('admin.b@example.test');
        $record = $this->makePendingAttendance($employeeB);

        $this->actingAs($adminNoProfile)
            ->post("/hr/attendance/{$record->id}/reject", ['approval_note' => 'ten characters minimum'])
            ->assertForbidden();

        $this->assertSame('PENDING_REVIEW', $record->fresh()->status);
    }

    // 3 & 4. Neither attempt above creates a notification, and status stays pending.
    public function test_admin_hr_without_employee_profile_attempts_create_no_notification(): void
    {
        $adminNoProfile = User::factory()->create(['role' => 'admin_hr', 'is_active' => true]);
        [, $employeeB] = $this->makeAdminHrWithEmployee('admin.b@example.test');
        $record = $this->makePendingAttendance($employeeB);

        $this->actingAs($adminNoProfile)->post("/hr/attendance/{$record->id}/approve", ['approval_note' => 'x']);
        $this->actingAs($adminNoProfile)->post("/hr/attendance/{$record->id}/reject", ['approval_note' => 'ten characters minimum']);

        $this->assertSame(0, Notification::count());
        $this->assertSame('PENDING_REVIEW', $record->fresh()->status);
    }

    // 5. approved_by/approved_at stay empty after a denied attempt from an admin_hr with no employee profile.
    public function test_admin_hr_without_employee_profile_attempt_leaves_approver_fields_empty(): void
    {
        $adminNoProfile = User::factory()->create(['role' => 'admin_hr', 'is_active' => true]);
        [, $employeeB] = $this->makeAdminHrWithEmployee('admin.b@example.test');
        $record = $this->makePendingAttendance($employeeB);

        $this->actingAs($adminNoProfile)->post("/hr/attendance/{$record->id}/approve", ['approval_note' => 'x']);

        $fresh = $record->fresh();
        $this->assertNull($fresh->approved_by);
        $this->assertNull($fresh->approved_at);
        $this->assertNull($fresh->approval_note);
    }

    // 6. super_admin without an employee profile is exempt from the admin_hr profile
    // requirement — it must always retain override capability (e.g. before Rama/Joshua's
    // admin_hr employee profiles exist, the system cannot be left with zero approvers).
    public function test_super_admin_without_employee_profile_can_approve_others_attendance(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        [, $employeeB] = $this->makeAdminHrWithEmployee('admin.b@example.test');
        $record = $this->makePendingAttendance($employeeB);

        $this->actingAs($superAdmin)
            ->post("/hr/attendance/{$record->id}/approve", ['approval_note' => 'Approved by super admin'])
            ->assertRedirect();

        $this->assertSame('APPROVED', $record->fresh()->status);
        $this->assertSame($superAdmin->id, $record->fresh()->approved_by);
    }

    // 7. super_admin WITH an employee profile still cannot approve their own attendance.
    public function test_super_admin_with_employee_profile_cannot_self_approve(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $employee = Employee::factory()->create([
            'user_id' => $superAdmin->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'employment_status' => 'active',
        ]);
        $record = $this->makePendingAttendance($employee);

        $this->actingAs($superAdmin)
            ->post("/hr/attendance/{$record->id}/approve", ['approval_note' => 'Self approve attempt'])
            ->assertForbidden();

        $this->assertSame('PENDING_REVIEW', $record->fresh()->status);
    }

    // 8. Two admin_hr users who both have active employee profiles can still approve
    // each other (the profile requirement doesn't accidentally block cross-approval).
    public function test_admin_hr_a_and_b_with_employee_profiles_can_still_approve_each_other(): void
    {
        [$userA, $employeeA] = $this->makeAdminHrWithEmployee('admin.a@example.test');
        [$userB, $employeeB] = $this->makeAdminHrWithEmployee('admin.b@example.test');

        $recordA = $this->makePendingAttendance($employeeA, '2026-07-01');
        $recordB = $this->makePendingAttendance($employeeB, '2026-07-02');

        $this->actingAs($userB)
            ->post("/hr/attendance/{$recordA->id}/approve", ['approval_note' => 'B approves A'])
            ->assertRedirect();

        $this->actingAs($userA)
            ->post("/hr/attendance/{$recordB->id}/approve", ['approval_note' => 'A approves B'])
            ->assertRedirect();

        $this->assertSame('APPROVED', $recordA->fresh()->status);
        $this->assertSame('APPROVED', $recordB->fresh()->status);
    }

    // Approval queue view: self-owned pending record is shown read-only, no approve/reject form.
    public function test_approval_queue_shows_self_owned_record_as_read_only(): void
    {
        [$userA, $employeeA] = $this->makeAdminHrWithEmployee('admin.a@example.test');
        $record = $this->makePendingAttendance($employeeA);

        $response = $this->actingAs($userA)->get('/hr/approval-queue');

        $response->assertOk();
        $response->assertSee('Tidak dapat menyetujui pengajuan sendiri');
        $response->assertDontSee("/hr/attendance/{$record->id}/approve", false);
        $response->assertDontSee("/hr/attendance/{$record->id}/reject", false);
    }
}
