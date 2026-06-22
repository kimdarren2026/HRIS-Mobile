<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\OfficeLocation;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase20AttendanceAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $financeUser;
    private User $adminHrUser;
    private User $employeeUser;
    private Employee $financeEmployee;
    private Employee $hrEmployee;
    private Employee $regularEmployee;
    private OfficeLocation $office;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
        Storage::fake('local');

        $dept     = Department::create(['name' => 'Finance', 'description' => '']);
        $position = Position::create(['name' => 'Analyst', 'department_id' => $dept->id]);

        $base = [
            'department_id'     => $dept->id,
            'position_id'       => $position->id,
            'join_date'         => '2026-01-01',
            'employment_status' => 'active',
            'phone_number'      => '+62812345678',
        ];

        $this->financeUser  = User::factory()->create(['role' => 'finance',   'is_active' => true]);
        $this->adminHrUser  = User::factory()->create(['role' => 'admin_hr',  'is_active' => true]);
        $this->employeeUser = User::factory()->create(['role' => 'employee',  'is_active' => true]);

        $this->financeEmployee  = Employee::create(['user_id' => $this->financeUser->id,  'nik' => 'P20-FIN-01'] + $base);
        $this->hrEmployee       = Employee::create(['user_id' => $this->adminHrUser->id,  'nik' => 'P20-HR-01'] + $base);
        $this->regularEmployee  = Employee::create(['user_id' => $this->employeeUser->id, 'nik' => 'P20-EMP-01'] + $base);

        $this->office = OfficeLocation::create([
            'name'          => 'HQ',
            'latitude'      => -6.2000000,
            'longitude'     => 106.8166660,
            'radius_meters' => 100,
            'is_active'     => true,
        ]);
    }

    private function coordsWithin(): array
    {
        return ['lat' => -6.2001000, 'lng' => 106.8166660];
    }

    private function coordsOutside(): array
    {
        return ['lat' => -6.2100000, 'lng' => 106.8166660];
    }

    private function photo(): UploadedFile
    {
        return UploadedFile::fake()->image('selfie.jpg', 200, 200)->size(100);
    }

    // ── Part A: Finance inside radius → auto-approved ──────────────────────────

    public function test_finance_checkin_inside_radius_auto_approved(): void
    {
        $coords = $this->coordsWithin();

        $this->actingAs($this->financeUser)->post('/attendance/check-in', [
            ...$coords,
            'photo' => $this->photo(),
        ])->assertRedirect('/attendance/history');

        $record = AttendanceRecord::where('employee_id', $this->financeEmployee->id)->first();
        $this->assertNotNull($record);
        $this->assertSame('APPROVED', $record->status);
    }

    public function test_finance_checkin_ignores_employee_id_input_and_uses_own_employee(): void
    {
        $otherEmployee = Employee::factory()->create();

        $this->actingAs($this->financeUser)->post('/attendance/check-in', [
            ...$this->coordsWithin(),
            'employee_id' => $otherEmployee->id,
            'photo' => $this->photo(),
        ])->assertRedirect('/attendance/history');

        $this->assertDatabaseHas('attendance_records', [
            'employee_id' => $this->financeEmployee->id,
            'status' => 'APPROVED',
        ]);
        $this->assertDatabaseMissing('attendance_records', [
            'employee_id' => $otherEmployee->id,
        ]);
    }

    public function test_finance_approved_attendance_does_not_appear_in_hr_queue(): void
    {
        AttendanceRecord::create([
            'employee_id'    => $this->financeEmployee->id,
            'attendance_date'=> today(),
            'check_in_time'  => now(),
            'status'         => 'APPROVED',
        ]);

        $response = $this->actingAs($this->adminHrUser)->get('/hr/approval-queue');
        $response->assertOk();

        $pending = $response->viewData('pending');
        $this->assertSame(0, $pending->total());
    }

    // ── Part A: Finance outside radius → PENDING_REVIEW → appears in HR queue ─

    public function test_finance_checkin_outside_radius_creates_pending_review(): void
    {
        $coords = $this->coordsOutside();

        $this->actingAs($this->financeUser)->post('/attendance/check-in', [
            ...$coords,
            'photo'  => $this->photo(),
            'reason' => 'Working from client site today',
        ])->assertRedirect('/attendance/history');

        $record = AttendanceRecord::where('employee_id', $this->financeEmployee->id)->first();
        $this->assertNotNull($record);
        $this->assertSame('PENDING_REVIEW', $record->status);
    }

    public function test_finance_pending_attendance_appears_in_hr_queue(): void
    {
        AttendanceRecord::create([
            'employee_id'          => $this->financeEmployee->id,
            'attendance_date'      => today(),
            'check_in_time'        => now(),
            'status'               => 'PENDING_REVIEW',
            'out_of_radius_reason' => 'Client visit',
        ]);

        $response = $this->actingAs($this->adminHrUser)->get('/hr/approval-queue');
        $response->assertOk();

        $pending = $response->viewData('pending');
        $this->assertGreaterThan(0, $pending->total());

        $ids = $pending->map(fn ($r) => $r->employee_id)->toArray();
        $this->assertContains($this->financeEmployee->id, $ids);
    }

    // ── Part A: HR can approve finance pending attendance ──────────────────────

    public function test_hr_can_approve_finance_pending_attendance(): void
    {
        $record = AttendanceRecord::create([
            'employee_id'          => $this->financeEmployee->id,
            'attendance_date'      => today(),
            'check_in_time'        => now(),
            'status'               => 'PENDING_REVIEW',
            'out_of_radius_reason' => 'Client visit',
        ]);

        $this->actingAs($this->adminHrUser)
            ->post("/hr/attendance/{$record->id}/approve", ['approval_note' => 'Verified with manager'])
            ->assertRedirect();

        $this->assertSame('APPROVED', $record->fresh()->status);
        $this->assertSame($this->adminHrUser->id, $record->fresh()->approved_by);
    }

    public function test_hr_can_reject_finance_pending_attendance(): void
    {
        $record = AttendanceRecord::create([
            'employee_id'          => $this->financeEmployee->id,
            'attendance_date'      => today(),
            'check_in_time'        => now(),
            'status'               => 'PENDING_REVIEW',
            'out_of_radius_reason' => 'Unknown location',
        ]);

        $this->actingAs($this->adminHrUser)
            ->post("/hr/attendance/{$record->id}/reject", ['approval_note' => 'Location not verified by manager'])
            ->assertRedirect();

        $this->assertSame('REJECTED', $record->fresh()->status);
    }

    // ── Part A: Finance cannot approve attendance ──────────────────────────────

    public function test_finance_cannot_access_hr_approval_queue(): void
    {
        $this->actingAs($this->financeUser)
            ->get('/hr/approval-queue')
            ->assertForbidden();
    }

    public function test_finance_cannot_approve_attendance(): void
    {
        $record = AttendanceRecord::create([
            'employee_id'    => $this->regularEmployee->id,
            'attendance_date'=> today(),
            'check_in_time'  => now(),
            'status'         => 'PENDING_REVIEW',
        ]);

        $this->actingAs($this->financeUser)
            ->post("/hr/attendance/{$record->id}/approve")
            ->assertForbidden();

        $this->assertSame('PENDING_REVIEW', $record->fresh()->status);
    }

    public function test_finance_cannot_reject_attendance(): void
    {
        $record = AttendanceRecord::create([
            'employee_id'    => $this->regularEmployee->id,
            'attendance_date'=> today(),
            'check_in_time'  => now(),
            'status'         => 'PENDING_REVIEW',
        ]);

        $this->actingAs($this->financeUser)
            ->post("/hr/attendance/{$record->id}/reject", ['approval_note' => 'Some reason here!'])
            ->assertForbidden();

        $this->assertSame('PENDING_REVIEW', $record->fresh()->status);
    }

    // ── Part A: Employee/admin_hr preserve current behavior ───────────────────

    public function test_employee_checkin_inside_radius_auto_approved(): void
    {
        $coords = $this->coordsWithin();

        $this->actingAs($this->employeeUser)->post('/attendance/check-in', [
            ...$coords,
            'photo' => $this->photo(),
        ])->assertRedirect('/attendance/history');

        $record = AttendanceRecord::where('employee_id', $this->regularEmployee->id)->first();
        $this->assertSame('APPROVED', $record->status);
    }

    public function test_employee_checkin_outside_radius_creates_pending_review(): void
    {
        $coords = $this->coordsOutside();

        $this->actingAs($this->employeeUser)->post('/attendance/check-in', [
            ...$coords,
            'photo'  => $this->photo(),
            'reason' => 'Working from home today due to transport',
        ])->assertRedirect('/attendance/history');

        $record = AttendanceRecord::where('employee_id', $this->regularEmployee->id)->first();
        $this->assertSame('PENDING_REVIEW', $record->status);
    }

    public function test_hr_queue_query_is_status_based_not_role_based(): void
    {
        // Create PENDING_REVIEW records for different role employees
        AttendanceRecord::create([
            'employee_id'    => $this->financeEmployee->id,
            'attendance_date'=> today()->subDay(),
            'check_in_time'  => now()->subDay(),
            'status'         => 'PENDING_REVIEW',
        ]);
        AttendanceRecord::create([
            'employee_id'    => $this->regularEmployee->id,
            'attendance_date'=> today()->subDays(2),
            'check_in_time'  => now()->subDays(2),
            'status'         => 'PENDING_REVIEW',
        ]);
        // APPROVED should not appear
        AttendanceRecord::create([
            'employee_id'    => $this->hrEmployee->id,
            'attendance_date'=> today()->subDays(3),
            'check_in_time'  => now()->subDays(3),
            'status'         => 'APPROVED',
        ]);

        $response = $this->actingAs($this->adminHrUser)->get('/hr/approval-queue');
        $pending  = $response->viewData('pending');

        $this->assertSame(2, $pending->total());
    }
}
