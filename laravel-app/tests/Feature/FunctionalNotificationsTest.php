<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\CompanyExpense;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Notification;
use App\Models\OfficeLocation;
use App\Models\Position;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FunctionalNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private User $employeeUser;
    private User $hrUser;
    private User $financeUser;
    private User $superAdminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
        Storage::fake('local');

        $this->employeeUser   = User::factory()->create(['role' => 'employee',    'is_active' => true]);
        $this->hrUser         = User::factory()->create(['role' => 'admin_hr',    'is_active' => true]);
        $this->financeUser    = User::factory()->create(['role' => 'finance',     'is_active' => true]);
        $this->superAdminUser = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    }

    private function makeEmployee(User $user, string $nik = 'NIK-FN-001'): Employee
    {
        $dept     = Department::create(['name' => 'Test', 'description' => '']);
        $position = Position::create(['name' => 'Tester', 'department_id' => $dept->id]);

        return Employee::create([
            'user_id'           => $user->id,
            'nik'               => $nik,
            'department_id'     => $dept->id,
            'position_id'       => $position->id,
            'join_date'         => '2026-01-01',
            'employment_status' => 'active',
            'phone_number'      => '+62811000001',
        ]);
    }

    private function makeOffice(): OfficeLocation
    {
        return OfficeLocation::create([
            'name'          => 'Main Office',
            'latitude'      => -6.2000000,
            'longitude'     => 106.8166660,
            'radius_meters' => 100,
            'is_active'     => true,
        ]);
    }

    private function makeNotification(User $user, array $data = []): Notification
    {
        return Notification::create(array_merge([
            'user_id' => $user->id,
            'title'   => 'Test Notification',
            'message' => 'Test message content.',
            'type'    => 'general',
            'is_read' => false,
        ], $data));
    }

    // ── Authorization ──────────────────────────────────────────────────────────

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/notifications')->assertRedirect('/login');       // 1
    }

    public function test_employee_can_list_notifications(): void
    {
        $this->actingAs($this->employeeUser)->get('/notifications')->assertOk();  // 1
    }

    public function test_admin_hr_can_list_notifications(): void
    {
        $this->actingAs($this->hrUser)->get('/notifications')->assertOk();        // 1
    }

    public function test_finance_can_list_notifications(): void
    {
        $this->actingAs($this->financeUser)->get('/notifications')->assertOk();   // 1
    }

    public function test_super_admin_can_list_notifications(): void
    {
        $this->actingAs($this->superAdminUser)->get('/notifications')->assertOk(); // 1
    }

    public function test_user_cannot_view_other_users_notification(): void
    {
        $other = $this->makeNotification($this->hrUser, ['title' => 'HR Only']);

        $this->actingAs($this->employeeUser)
            ->get("/notifications/{$other->id}")
            ->assertNotFound();                                       // 1

        $this->assertDatabaseHas('notifications', ['id' => $other->id]); // 2
        $this->assertFalse($other->fresh()->is_read);                 // 3 — not auto-marked read via someone else's request
    }

    // ── Read / mark-read ───────────────────────────────────────────────────────

    public function test_mark_notification_as_read(): void
    {
        $n = $this->makeNotification($this->employeeUser);

        $this->actingAs($this->employeeUser)
            ->patch("/notifications/{$n->id}/read")
            ->assertRedirect(route('notifications.index'));           // 1

        $this->assertTrue($n->fresh()->is_read);                     // 2
    }

    public function test_mark_all_notifications_as_read(): void
    {
        $this->makeNotification($this->employeeUser);
        $this->makeNotification($this->employeeUser);

        $this->actingAs($this->employeeUser)
            ->patch('/notifications/read-all')
            ->assertRedirect(route('notifications.index'));           // 1

        $unread = Notification::where('user_id', $this->employeeUser->id)
            ->where('is_read', false)->count();
        $this->assertSame(0, $unread);                               // 2

        $read = Notification::where('user_id', $this->employeeUser->id)
            ->where('is_read', true)->count();
        $this->assertSame(2, $read);                                 // 3
    }

    // ── Detail page ────────────────────────────────────────────────────────────

    public function test_notification_show_page_loads(): void
    {
        $n = $this->makeNotification($this->employeeUser, [
            'title'   => 'Payslip Ready',
            'message' => 'Your payslip for June 2026 is available.',
        ]);

        $response = $this->actingAs($this->employeeUser)
            ->get("/notifications/{$n->id}");

        $response->assertOk();                                       // 1
        $response->assertSee('Payslip Ready');                       // 2
        $response->assertSee('Your payslip for June 2026 is available.'); // 3
    }

    // ── Auto-read on open ──────────────────────────────────────────────────────

    public function test_opening_unread_notification_marks_it_as_read(): void
    {
        $n = $this->makeNotification($this->employeeUser);
        $this->assertFalse($n->is_read);

        $this->actingAs($this->employeeUser)
            ->get("/notifications/{$n->id}")
            ->assertOk();                                               // 1

        $this->assertTrue($n->fresh()->is_read);                       // 2
    }

    public function test_opening_already_read_notification_keeps_read_state_unchanged(): void
    {
        \Illuminate\Support\Facades\Date::setTestNow('2026-07-01 10:00:00');
        $n = $this->makeNotification($this->employeeUser, ['is_read' => true]);
        $originalUpdatedAt = $n->fresh()->updated_at;

        \Illuminate\Support\Facades\Date::setTestNow('2026-07-01 10:05:00');

        $this->actingAs($this->employeeUser)
            ->get("/notifications/{$n->id}")
            ->assertOk();                                               // 1

        $fresh = $n->fresh();
        $this->assertTrue($fresh->is_read);                            // 2
        $this->assertTrue($originalUpdatedAt->equalTo($fresh->updated_at)); // 3 — untouched, no redundant write

        \Illuminate\Support\Facades\Date::setTestNow();
    }

    public function test_unread_count_decreases_after_opening_notification(): void
    {
        $n1 = $this->makeNotification($this->employeeUser);
        $this->makeNotification($this->employeeUser);

        $this->assertSame(2, Notification::where('user_id', $this->employeeUser->id)
            ->where('is_read', false)->count());                        // 1

        $this->actingAs($this->employeeUser)
            ->get("/notifications/{$n1->id}")
            ->assertOk();                                               // 2

        $this->assertSame(1, Notification::where('user_id', $this->employeeUser->id)
            ->where('is_read', false)->count());                        // 3

        // Badge on another page reflects the updated unread count.
        $dashboard = $this->actingAs($this->employeeUser)->get('/employee/dashboard');
        $dashboard->assertOk();                                         // 4
        $this->assertMatchesRegularExpression(
            '/bg-danger[^>]*>\s*1\s*<\/span>/',
            $dashboard->getContent()
        );                                                               // 5
    }

    // ── Return URL navigation ──────────────────────────────────────────────────

    public function test_notification_index_accepts_safe_return_url(): void
    {
        $this->actingAs($this->employeeUser)
            ->get('/notifications?return_url=/employee/dashboard')
            ->assertOk()
            ->assertSee('/employee/dashboard', false);                  // 1
    }

    public function test_notification_show_preserves_return_url(): void
    {
        $n = $this->makeNotification($this->employeeUser);

        $this->actingAs($this->employeeUser)
            ->get("/notifications/{$n->id}?return_url=/employee/dashboard")
            ->assertOk()
            ->assertSee('/employee/dashboard', false);                  // 1

        $this->assertTrue($n->fresh()->is_read);                       // 2 — auto-read does not interfere with return_url
    }

    public function test_mark_read_preserves_return_url(): void
    {
        $n = $this->makeNotification($this->employeeUser);

        $this->actingAs($this->employeeUser)
            ->patch("/notifications/{$n->id}/read", ['return_url' => '/employee/dashboard'])
            ->assertRedirect(route('notifications.index', ['return_url' => '/employee/dashboard'])); // 1
    }

    public function test_read_all_preserves_return_url(): void
    {
        $this->actingAs($this->employeeUser)
            ->patch('/notifications/read-all', ['return_url' => '/attendance/history'])
            ->assertRedirect(route('notifications.index', ['return_url' => '/attendance/history'])); // 1
    }

    public function test_unsafe_return_url_is_rejected_on_index(): void
    {
        // External URL — must not appear in rendered HTML
        $this->actingAs($this->employeeUser)
            ->get('/notifications?return_url=https://evil.com')
            ->assertOk()
            ->assertDontSee('https://evil.com', false);                 // 1

        // Protocol-relative URL
        $this->actingAs($this->employeeUser)
            ->get('/notifications?return_url=//evil.com')
            ->assertOk()
            ->assertDontSee('//evil.com', false);                       // 2

        // Private file path
        $this->actingAs($this->employeeUser)
            ->get('/notifications?return_url=/attendance/photo/1')
            ->assertOk()
            ->assertDontSee('/attendance/photo/1', false);              // 3

        // No leading slash
        $this->actingAs($this->employeeUser)
            ->get('/notifications?return_url=evil.com/path')
            ->assertOk()
            ->assertDontSee('evil.com/path', false);                    // 4
    }

    public function test_mark_read_unsafe_return_url_falls_back_to_index(): void
    {
        $n = $this->makeNotification($this->employeeUser);

        $this->actingAs($this->employeeUser)
            ->patch("/notifications/{$n->id}/read", ['return_url' => 'https://evil.com'])
            ->assertRedirect(route('notifications.index'));              // 1
    }

    public function test_read_all_unsafe_return_url_falls_back_to_index(): void
    {
        $this->actingAs($this->employeeUser)
            ->patch('/notifications/read-all', ['return_url' => '//evil.com'])
            ->assertRedirect(route('notifications.index'));              // 1
    }

    public function test_user_cannot_mark_read_other_users_notification(): void
    {
        $other = $this->makeNotification($this->hrUser);

        $this->actingAs($this->employeeUser)
            ->patch("/notifications/{$other->id}/read")
            ->assertNotFound();                                         // 1

        $this->assertFalse($other->fresh()->is_read);                  // 2
    }

    // ── Safe action URL ────────────────────────────────────────────────────────

    public function test_safe_action_url_allows_valid_internal_paths(): void
    {
        $service = app(NotificationService::class);

        $this->assertNull($service->safeActionUrl(null));                                          // 1
        $this->assertSame('/hr/approval-queue',    $service->safeActionUrl('/hr/approval-queue')); // 2
        $this->assertSame('/attendance/history',   $service->safeActionUrl('/attendance/history')); // 3
        $this->assertSame('/finance/expenses/42',  $service->safeActionUrl('/finance/expenses/42')); // 4
        $this->assertSame('/notifications',        $service->safeActionUrl('/notifications'));      // 5
    }

    public function test_safe_action_url_rejects_absolute_and_protocol_urls(): void
    {
        $service = app(NotificationService::class);

        $this->assertNull($service->safeActionUrl('https://evil.com'));        // 1
        $this->assertNull($service->safeActionUrl('http://evil.com'));         // 2
        $this->assertNull($service->safeActionUrl('//evil.com/path'));         // 3
        $this->assertNull($service->safeActionUrl('javascript:alert(1)'));     // 4
        $this->assertNull($service->safeActionUrl('relative-no-leading-slash')); // 5
    }

    public function test_safe_action_url_rejects_private_paths(): void
    {
        $service = app(NotificationService::class);

        $this->assertNull($service->safeActionUrl('/attendance/photo/1'));     // 1
        $this->assertNull($service->safeActionUrl('/leave/attachment/1'));     // 2
        $this->assertNull($service->safeActionUrl('/storage/app/receipts/x')); // 3
        $this->assertNull($service->safeActionUrl("\x00control"));             // 4
    }

    // ── Notification events ────────────────────────────────────────────────────

    public function test_outside_radius_check_in_notifies_hr_and_super_admin(): void
    {
        $this->makeEmployee($this->employeeUser);
        $this->makeOffice();

        $response = $this->actingAs($this->employeeUser)->post('/attendance/check-in', [
            'lat'    => -6.2100000, // ~1.1 km outside radius
            'lng'    => 106.8166660,
            'reason' => 'Remote work — client site',
            'photo'  => UploadedFile::fake()->image('selfie.jpg', 200, 200)->size(80),
        ]);

        $response->assertRedirect();                                       // 1

        // Expects exactly one notification per target role user (1 HR + 1 SA = 2)
        $this->assertSame(2, Notification::count());                       // 2
        $this->assertSame(1, Notification::where('user_id', $this->hrUser->id)->count()); // 3
        $this->assertSame('attendance', Notification::first()->type);      // 4
    }

    public function test_within_radius_check_in_does_not_notify(): void
    {
        $this->makeEmployee($this->employeeUser);
        $this->makeOffice();

        $this->actingAs($this->employeeUser)->post('/attendance/check-in', [
            'lat'   => -6.2001000, // within 100 m
            'lng'   => 106.8166660,
            'photo' => UploadedFile::fake()->image('selfie.jpg', 200, 200)->size(80),
        ])->assertRedirect();                                               // 1

        $this->assertSame(0, Notification::count());                       // 2
    }

    public function test_attendance_approval_notifies_employee(): void
    {
        $employee = $this->makeEmployee($this->employeeUser);
        $record   = AttendanceRecord::create([
            'employee_id'    => $employee->id,
            'attendance_date'=> today(),
            'check_in_time'  => now(),
            'check_in_lat'   => -6.21,
            'check_in_lng'   => 106.82,
            'status'         => 'PENDING_REVIEW',
        ]);

        $this->actingAs($this->hrUser)
            ->post("/hr/attendance/{$record->id}/approve", ['approval_note' => 'Verified.'])
            ->assertRedirect();                                             // 1

        $this->assertSame(1, Notification::count());                       // 2
        $this->assertSame($this->employeeUser->id, Notification::first()->user_id); // 3
        $this->assertSame('attendance', Notification::first()->type);      // 4
    }

    public function test_leave_submission_notifies_hr_roles(): void
    {
        // Annual leave requires >= 12 months of service (policy point 1) and
        // start_date must not be in the past, so both are derived from "now"
        // rather than hardcoded so the test keeps passing regardless of when it runs.
        $employee = $this->makeEmployee($this->employeeUser);
        $employee->update(['join_date' => now()->subMonths(13)->startOfMonth()]);

        $leaveType = LeaveType::create(['name' => 'Annual', 'days_allowed' => 12]);
        \App\Models\LeaveBalance::create([
            'employee_id'   => $employee->id,
            'leave_type_id' => $leaveType->id,
            'year'          => now()->year,
            'total_quota'   => 12,
            'used'          => 0,
            'remaining'     => 12,
        ]);

        // addWeekday() lands on a future Mon-Fri date, satisfying both the
        // "not in the past" validation rule and the 2-working-day monthly cap
        // (a single weekday request never exceeds it).
        $startDate = now()->addWeekday()->toDateString();

        $this->actingAs($this->employeeUser)->post('/leave/request', [
            'leave_type_id' => $leaveType->id,
            'start_date'    => $startDate,
            'end_date'      => $startDate,
            'reason'        => 'Personal matter',
        ])->assertRedirect();                                               // 1

        // Both hrUser (admin_hr) and superAdminUser (super_admin) should be notified
        $this->assertSame(2, Notification::count());                       // 2
        $this->assertSame('leave', Notification::first()->type);           // 3
    }

    public function test_leave_approval_notifies_requester(): void
    {
        $employee  = $this->makeEmployee($this->employeeUser);
        $leaveType = LeaveType::create(['name' => 'Annual', 'days_allowed' => 12]);
        $leave = LeaveRequest::create([
            'employee_id'   => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date'    => '2026-07-10',
            'end_date'      => '2026-07-10',
            'total_days'    => 1,
            'reason'        => 'Personal',
            'status'        => 'PENDING_HR',
        ]);

        $this->actingAs($this->hrUser)
            ->post("/hr/leave/{$leave->id}/approve", ['approval_note' => 'Approved.'])
            ->assertRedirect();                                             // 1

        $this->assertSame(1, Notification::count());                       // 2
        $this->assertSame($this->employeeUser->id, Notification::first()->user_id); // 3
        $this->assertSame('leave', Notification::first()->type);           // 4
    }

    public function test_expense_submission_notifies_eligible_checkers_not_creator(): void
    {
        $checker = User::factory()->create(['role' => 'finance', 'is_active' => true]);
        $expense = CompanyExpense::create([
            'expense_number' => 'EXP-FN-001',
            'category'       => 'OFFICE_SUPPLIES',
            'title'          => 'Notification Test Expense',
            'amount'         => 150000,
            'expense_date'   => '2026-06-22',
            'recipient_name' => 'Vendor Z',
            'status'         => 'DRAFT',
            'created_by'     => $this->financeUser->id,
        ]);

        $this->actingAs($this->financeUser)
            ->post("/finance/expenses/{$expense->id}/submit")
            ->assertRedirect();                                            // 1

        // Creator must NOT receive a notification
        $creatorCount = Notification::where('user_id', $this->financeUser->id)->count();
        $this->assertSame(0, $creatorCount);                               // 2

        // At least the checker and super_admin should be notified
        $this->assertTrue(Notification::count() >= 1);                    // 3
        $this->assertSame('expense', Notification::first()->type);         // 4

        // Checker (different finance user) should receive one
        $this->assertSame(1, Notification::where('user_id', $checker->id)->count()); // 5
    }
}
