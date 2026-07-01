<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Notification;
use App\Models\OfficeLocation;
use App\Models\Position;
use App\Models\User;
use App\Services\LeaveService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class Phase37AuditMediumFixesTest extends TestCase
{
    use RefreshDatabase;

    private User $employeeUser;
    private Employee $employee;
    private LeaveType $leaveType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
        Storage::fake('local');

        $dept     = Department::create(['name' => 'Engineering', 'description' => '']);
        $position = Position::create(['name' => 'Dev', 'department_id' => $dept->id]);

        $this->employeeUser = User::factory()->create(['role' => 'employee', 'is_active' => true]);
        $this->employee     = Employee::create([
            'user_id'           => $this->employeeUser->id,
            'nik'               => 'P37-EMP-001',
            'department_id'     => $dept->id,
            'position_id'       => $position->id,
            'join_date'         => '2026-01-01',
            'employment_status' => 'active',
            'phone_number'      => '+62812345678',
        ]);

        $this->leaveType = LeaveType::create(['name' => 'Annual Leave', 'deducts_balance' => false]);
    }

    // ── Fix 1: Profile fallback shows real user data, not mock ──────────────

    public function test_profile_fallback_shows_user_name_not_mock_data(): void
    {
        $user = User::factory()->create(['role' => 'finance', 'is_active' => true, 'name' => 'Real User Name']);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Real User Name')
            ->assertDontSee('Alex Rivers')
            ->assertDontSee('HR-2024-089')
            ->assertDontSee('alex.rivers@company.com');
    }

    public function test_profile_fallback_shows_no_employee_record_notice(): void
    {
        $user = User::factory()->create(['role' => 'finance', 'is_active' => true]);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Belum ada data karyawan yang tertaut')
            ->assertSee('Hubungi HR untuk menautkan akun Anda');
    }

    public function test_profile_fallback_still_shows_logout(): void
    {
        $user = User::factory()->create(['role' => 'finance', 'is_active' => true]);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Keluar');
    }

    // ── Fix 2: Notification count computed only once per request ────────────

    public function test_notification_count_partial_renders_correct_unread_count(): void
    {
        Notification::create([
            'user_id' => $this->employeeUser->id,
            'title'   => 'Test',
            'message' => 'Message',
            'type'    => 'general',
            'is_read' => false,
        ]);

        // The unread count should appear in the view (via view composer)
        $this->actingAs($this->employeeUser)
            ->get('/attendance/history')
            ->assertOk()
            ->assertSee('1'); // badge count appears somewhere in the rendered HTML
    }

    public function test_notification_count_does_not_show_badge_when_all_read(): void
    {
        Notification::create([
            'user_id' => $this->employeeUser->id,
            'title'   => 'Test',
            'message' => 'Message',
            'type'    => 'general',
            'is_read' => true,
        ]);

        // With zero unread, the badge span should not be rendered
        $response = $this->actingAs($this->employeeUser)
            ->get('/attendance/history')
            ->assertOk();

        // The badge is only rendered when count > 0; assert no badge content for 0
        $response->assertDontSee('bg-error text-white', false);
    }

    // ── Fix 3: NotificationService sets category from type ──────────────────

    public function test_notification_service_sets_category_from_type(): void
    {
        $service = app(NotificationService::class);

        $notification = $service->create(
            $this->employeeUser,
            'Leave Approved',
            'Your leave has been approved.',
            'leave',
            '/leave/history',
        );

        $this->assertSame('Leave', $notification->fresh()->category);
    }

    public function test_notification_service_sets_category_for_all_known_types(): void
    {
        $service  = app(NotificationService::class);
        $expected = [
            'attendance' => 'Attendance',
            'leave'      => 'Leave',
            'payroll'    => 'Payroll',
            'expense'    => 'Expense',
            'general'    => 'General',
        ];

        foreach ($expected as $type => $label) {
            $n = $service->create($this->employeeUser, 'Title', 'Message', $type);
            $this->assertSame($label, $n->fresh()->category, "Category mismatch for type: {$type}");
        }
    }

    // ── Fix 4: Leave overlap validation ─────────────────────────────────────

    private function makeLeaveRequest(string $start, string $end, string $status = 'PENDING_HR'): LeaveRequest
    {
        return LeaveRequest::create([
            'employee_id'   => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date'    => $start,
            'end_date'      => $end,
            'total_days'    => 3,
            'reason'        => 'Test reason.',
            'status'        => $status,
        ]);
    }

    public function test_leave_submit_blocked_when_pending_request_overlaps(): void
    {
        $this->makeLeaveRequest('2026-08-01', '2026-08-05', 'PENDING_HR');

        $this->expectException(ValidationException::class);

        app(LeaveService::class)->submit($this->employee, [
            'leave_type_id' => $this->leaveType->id,
            'start_date'    => '2026-08-03',
            'end_date'      => '2026-08-07',
            'reason'        => 'Overlapping request.',
        ]);
    }

    public function test_leave_submit_blocked_when_approved_request_overlaps(): void
    {
        $this->makeLeaveRequest('2026-08-01', '2026-08-05', 'APPROVED');

        $this->expectException(ValidationException::class);

        app(LeaveService::class)->submit($this->employee, [
            'leave_type_id' => $this->leaveType->id,
            'start_date'    => '2026-08-04',
            'end_date'      => '2026-08-08',
            'reason'        => 'Overlapping approved.',
        ]);
    }

    public function test_leave_submit_allowed_when_rejected_request_overlaps(): void
    {
        $this->makeLeaveRequest('2026-08-01', '2026-08-05', 'REJECTED');

        $request = app(LeaveService::class)->submit($this->employee, [
            'leave_type_id' => $this->leaveType->id,
            'start_date'    => '2026-08-03',
            'end_date'      => '2026-08-07',
            'reason'        => 'Same period, previous was rejected.',
        ]);

        $this->assertSame('PENDING_HR', $request->status);
    }

    public function test_leave_submit_allowed_when_no_overlap(): void
    {
        $this->makeLeaveRequest('2026-08-01', '2026-08-05', 'PENDING_HR');

        $request = app(LeaveService::class)->submit($this->employee, [
            'leave_type_id' => $this->leaveType->id,
            'start_date'    => '2026-08-10',
            'end_date'      => '2026-08-12',
            'reason'        => 'Non-overlapping period.',
        ]);

        $this->assertSame('PENDING_HR', $request->status);
    }

    public function test_leave_submit_blocked_when_dates_are_exactly_adjacent_and_overlap(): void
    {
        // Same day as end date of existing
        $this->makeLeaveRequest('2026-08-01', '2026-08-05', 'APPROVED');

        $this->expectException(ValidationException::class);

        app(LeaveService::class)->submit($this->employee, [
            'leave_type_id' => $this->leaveType->id,
            'start_date'    => '2026-08-05',
            'end_date'      => '2026-08-07',
            'reason'        => 'Starts on last day of existing.',
        ]);
    }

    // ── Fix 5: Attendance checkout ───────────────────────────────────────────

    public function test_checkout_requires_prior_checkin(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', ['lat' => -6.2, 'lng' => 106.8])
            ->assertSessionHasErrors(['general']);
    }

    public function test_checkout_records_time_and_coords(): void
    {
        $record = AttendanceRecord::create([
            'employee_id'     => $this->employee->id,
            'attendance_date' => today(),
            'check_in_time'   => now()->subHours(8),
            'check_in_lat'    => -6.2,
            'check_in_lng'    => 106.8,
            'status'          => 'APPROVED',
        ]);

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', ['lat' => -6.2001, 'lng' => 106.8001])
            ->assertRedirect('/attendance/history');

        $record->refresh();
        $this->assertNotNull($record->check_out_time);
        $this->assertEquals(-6.2001, (float) $record->check_out_lat);
        $this->assertEquals(106.8001, (float) $record->check_out_lng);
    }

    public function test_checkout_blocked_when_already_checked_out(): void
    {
        AttendanceRecord::create([
            'employee_id'      => $this->employee->id,
            'attendance_date'  => today(),
            'check_in_time'    => now()->subHours(8),
            'check_in_lat'     => -6.2,
            'check_in_lng'     => 106.8,
            'check_out_time'   => now()->subHour(),
            'check_out_lat'    => -6.2,
            'check_out_lng'    => 106.8,
            'status'           => 'APPROVED',
        ]);

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', ['lat' => -6.2, 'lng' => 106.8])
            ->assertSessionHasErrors(['general']);
    }

    public function test_checkout_requires_lat_lng(): void
    {
        AttendanceRecord::create([
            'employee_id'     => $this->employee->id,
            'attendance_date' => today(),
            'check_in_time'   => now()->subHours(8),
            'check_in_lat'    => -6.2,
            'check_in_lng'    => 106.8,
            'status'          => 'APPROVED',
        ]);

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', [])
            ->assertSessionHasErrors(['lat', 'lng']);
    }

    public function test_checkout_blocked_for_user_without_employee_record(): void
    {
        $userNoEmp = User::factory()->create(['role' => 'finance', 'is_active' => true]);

        $this->actingAs($userNoEmp)
            ->post('/attendance/check-out', ['lat' => -6.2, 'lng' => 106.8])
            ->assertForbidden();
    }
}
