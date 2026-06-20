<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeaveAttachmentTest extends TestCase
{
    use RefreshDatabase;

    private function makeLeaveRequest(User $owner, ?string $path = 'leave-attachments/doc.pdf'): LeaveRequest
    {
        $employee = Employee::factory()->create(['user_id' => $owner->id]);
        $leaveType = LeaveType::factory()->create();

        return LeaveRequest::create([
            'employee_id'    => $employee->id,
            'leave_type_id'  => $leaveType->id,
            'start_date'     => now()->toDateString(),
            'end_date'       => now()->toDateString(),
            'total_days'     => 1,
            'reason'         => 'Test reason',
            'attachment_path'=> $path,
            'status'         => 'PENDING_HR',
        ]);
    }

    private function fakeFile(string $path): void
    {
        Storage::fake('local');
        Storage::disk('local')->put($path, 'fake-pdf-content');
    }

    public function test_employee_can_view_own_attachment(): void
    {
        $user = User::factory()->create(['role' => 'employee', 'is_active' => true]);
        $lr   = $this->makeLeaveRequest($user);
        $this->fakeFile($lr->attachment_path);

        $this->actingAs($user)
            ->get("/leave/attachment/{$lr->id}")
            ->assertOk();
    }

    public function test_employee_cannot_view_other_employee_attachment(): void
    {
        $owner  = User::factory()->create(['role' => 'employee', 'is_active' => true]);
        $other  = User::factory()->create(['role' => 'employee', 'is_active' => true]);
        $lr     = $this->makeLeaveRequest($owner);
        $this->fakeFile($lr->attachment_path);

        // other employee needs their own Employee record to pass auth
        Employee::factory()->create(['user_id' => $other->id]);

        $this->actingAs($other)
            ->get("/leave/attachment/{$lr->id}")
            ->assertForbidden();
    }

    public function test_admin_hr_can_view_attachment(): void
    {
        $owner = User::factory()->create(['role' => 'employee', 'is_active' => true]);
        $hr    = User::factory()->create(['role' => 'admin_hr', 'is_active' => true]);
        $lr    = $this->makeLeaveRequest($owner);
        $this->fakeFile($lr->attachment_path);

        $this->actingAs($hr)
            ->get("/leave/attachment/{$lr->id}")
            ->assertOk();
    }

    public function test_super_admin_can_view_attachment(): void
    {
        $owner = User::factory()->create(['role' => 'employee', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $lr    = $this->makeLeaveRequest($owner);
        $this->fakeFile($lr->attachment_path);

        $this->actingAs($admin)
            ->get("/leave/attachment/{$lr->id}")
            ->assertOk();
    }

    public function test_finance_cannot_view_attachment(): void
    {
        $owner   = User::factory()->create(['role' => 'employee', 'is_active' => true]);
        $finance = User::factory()->create(['role' => 'finance', 'is_active' => true]);
        $lr      = $this->makeLeaveRequest($owner);
        $this->fakeFile($lr->attachment_path);

        $this->actingAs($finance)
            ->get("/leave/attachment/{$lr->id}")
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $owner = User::factory()->create(['role' => 'employee', 'is_active' => true]);
        $lr    = $this->makeLeaveRequest($owner);

        $this->get("/leave/attachment/{$lr->id}")
            ->assertRedirect('/login');
    }

    public function test_returns_404_when_attachment_path_is_null(): void
    {
        $user = User::factory()->create(['role' => 'employee', 'is_active' => true]);
        $lr   = $this->makeLeaveRequest($user, null);
        Storage::fake('local');

        $this->actingAs($user)
            ->get("/leave/attachment/{$lr->id}")
            ->assertNotFound();
    }

    public function test_returns_404_when_file_missing_from_storage(): void
    {
        $user = User::factory()->create(['role' => 'employee', 'is_active' => true]);
        $lr   = $this->makeLeaveRequest($user);
        Storage::fake('local'); // file NOT created

        $this->actingAs($user)
            ->get("/leave/attachment/{$lr->id}")
            ->assertNotFound();
    }
}
