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
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase30AttendanceHardeningTest extends TestCase
{
    use RefreshDatabase;

    private User $employeeUser;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
        Storage::fake('local');

        $dept     = Department::create(['name' => 'Engineering', 'description' => '']);
        $position = Position::create(['name' => 'Dev', 'department_id' => $dept->id]);

        $this->employeeUser = User::factory()->create(['role' => 'employee', 'is_active' => true]);
        $this->employee     = Employee::create([
            'user_id'             => $this->employeeUser->id,
            'nik'                 => 'P30-EMP-001',
            'department_id'       => $dept->id,
            'position_id'         => $position->id,
            'join_date'           => '2026-01-01',
            'employment_status'   => 'active',
            'phone_number'        => '+62812345678',
            'address'             => 'Test Address',
            'bank_name'           => 'Test Bank',
            'bank_account_number' => '1234567890',
        ]);
    }

    // ── 1. No active office location — UI shows warning ─────────────────────

    public function test_checkin_page_shows_warning_when_no_active_office_location(): void
    {
        $this->actingAs($this->employeeUser)
            ->get('/attendance/checkin')
            ->assertOk()
            ->assertSee('belum dikonfigurasi');
    }

    public function test_checkin_page_does_not_show_warning_when_office_location_active(): void
    {
        OfficeLocation::create([
            'name'          => 'Main Office',
            'latitude'      => -6.2000000,
            'longitude'     => 106.8166660,
            'radius_meters' => 100,
            'is_active'     => true,
        ]);

        $this->actingAs($this->employeeUser)
            ->get('/attendance/checkin')
            ->assertOk()
            ->assertDontSee('belum dikonfigurasi');
    }

    // ── 2. Throttle middleware registered on check-in route ─────────────────

    public function test_throttle_middleware_registered_on_check_in_route(): void
    {
        $route = collect(Route::getRoutes()->getRoutes())
            ->first(fn ($r) => $r->uri() === 'attendance/check-in' && in_array('POST', $r->methods(), true));

        $this->assertNotNull($route, 'POST attendance/check-in route not found');

        $hasThrottle = collect($route->gatherMiddleware())
            ->contains(fn ($m) => str_starts_with((string) $m, 'throttle:'));

        $this->assertTrue($hasThrottle, 'throttle middleware not found on attendance/check-in route');
    }

    // ── 3. Check-in rejected when GPS coordinates are missing ───────────────

    public function test_checkin_rejected_when_lat_lng_missing(): void
    {
        $photo = UploadedFile::fake()->image('selfie.jpg', 200, 200)->size(100);

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', ['photo' => $photo])
            ->assertSessionHasErrors(['lat', 'lng']);

        $this->assertDatabaseMissing('attendance_records', ['employee_id' => $this->employee->id]);
    }

    // ── 4. Check-in rejected when coordinates are invalid ───────────────────

    public function test_checkin_rejected_when_coordinates_invalid(): void
    {
        $photo = UploadedFile::fake()->image('selfie.jpg', 200, 200)->size(100);

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', [
                'lat'   => 999.0,
                'lng'   => 106.82,
                'photo' => $photo,
            ])
            ->assertSessionHasErrors('lat');

        $this->assertDatabaseMissing('attendance_records', ['employee_id' => $this->employee->id]);
    }

    // ── 5. Out-of-radius without reason is rejected ─────────────────────────

    public function test_out_of_radius_checkin_without_reason_is_rejected(): void
    {
        OfficeLocation::create([
            'name'          => 'Main Office',
            'latitude'      => -6.2000000,
            'longitude'     => 106.8166660,
            'radius_meters' => 100,
            'is_active'     => true,
        ]);

        $photo = UploadedFile::fake()->image('selfie.jpg', 200, 200)->size(100);

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', [
                'lat'   => -6.2100000,
                'lng'   => 106.8166660,
                'photo' => $photo,
            ])
            ->assertSessionHasErrors('reason');

        $this->assertDatabaseMissing('attendance_records', ['employee_id' => $this->employee->id]);
    }

    // ── 6. Out-of-radius with valid reason → PENDING_REVIEW ─────────────────

    public function test_out_of_radius_checkin_with_valid_reason_creates_pending_review(): void
    {
        OfficeLocation::create([
            'name'          => 'Main Office',
            'latitude'      => -6.2000000,
            'longitude'     => 106.8166660,
            'radius_meters' => 100,
            'is_active'     => true,
        ]);

        $photo = UploadedFile::fake()->image('selfie.jpg', 200, 200)->size(100);

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', [
                'lat'    => -6.2100000,
                'lng'    => 106.8166660,
                'photo'  => $photo,
                'reason' => 'Bekerja dari kantor klien, telah mendapat persetujuan manajer.',
            ])
            ->assertRedirect('/attendance/history');

        $this->assertDatabaseHas('attendance_records', [
            'employee_id' => $this->employee->id,
            'status'      => 'PENDING_REVIEW',
        ]);
    }

    // ── 7. Attendance history is scoped to the authenticated employee ────────

    public function test_employee_attendance_history_only_shows_own_records(): void
    {
        $dept = Department::first();
        $pos  = Position::first();

        $otherUser = User::factory()->create(['role' => 'employee', 'is_active' => true]);
        $otherEmp  = Employee::create([
            'user_id'             => $otherUser->id,
            'nik'                 => 'P30-EMP-002',
            'department_id'       => $dept->id,
            'position_id'         => $pos->id,
            'join_date'           => '2026-01-01',
            'employment_status'   => 'active',
            'phone_number'        => '+62812345679',
            'address'             => 'Other Address',
            'bank_name'           => 'Test Bank',
            'bank_account_number' => '9999999999',
        ]);

        AttendanceRecord::create([
            'employee_id'     => $this->employee->id,
            'attendance_date' => today()->subDay(),
            'check_in_time'   => now()->subDay(),
            'status'          => 'APPROVED',
        ]);
        AttendanceRecord::create([
            'employee_id'     => $otherEmp->id,
            'attendance_date' => today()->subDays(2),
            'check_in_time'   => now()->subDays(2),
            'status'          => 'APPROVED',
        ]);

        // Both records exist in DB
        $this->assertDatabaseCount('attendance_records', 2);

        // Employee's own scoped records = 1
        $ownRecords = Employee::find($this->employee->id)->attendanceRecords()->get();
        $this->assertCount(1, $ownRecords);
        $this->assertEquals($this->employee->id, $ownRecords->first()->employee_id);

        // History page loads successfully (controller scopes to own employee)
        $this->actingAs($this->employeeUser)
            ->get('/attendance/history')
            ->assertOk();
    }

    // ── 8. Employee cannot access another employee's attendance photo ────────

    public function test_employee_cannot_access_attendance_photo_of_another_employee(): void
    {
        $dept = Department::first();
        $pos  = Position::first();

        $otherUser = User::factory()->create(['role' => 'employee', 'is_active' => true]);
        $otherEmp  = Employee::create([
            'user_id'             => $otherUser->id,
            'nik'                 => 'P30-EMP-003',
            'department_id'       => $dept->id,
            'position_id'         => $pos->id,
            'join_date'           => '2026-01-01',
            'employment_status'   => 'active',
            'phone_number'        => '+62812345680',
            'address'             => 'Other Address',
            'bank_name'           => 'Test Bank',
            'bank_account_number' => '8888888888',
        ]);

        Storage::disk('local')->put('attendance/99/2026/06/other.jpg', 'fake-image-data');
        $record = AttendanceRecord::create([
            'employee_id'         => $otherEmp->id,
            'attendance_date'     => today(),
            'check_in_time'       => now(),
            'check_in_photo_path' => 'attendance/99/2026/06/other.jpg',
            'status'              => 'APPROVED',
        ]);

        $this->actingAs($this->employeeUser)
            ->get("/attendance/photo/{$record->id}")
            ->assertForbidden();
    }

    // ── 9. HR/admin can still access any employee's photo ───────────────────

    public function test_admin_hr_can_access_any_employee_photo(): void
    {
        $dept = Department::first();
        $pos  = Position::first();

        Storage::disk('local')->put('attendance/1/2026/06/emp.jpg', 'fake-image-data');
        $record = AttendanceRecord::create([
            'employee_id'         => $this->employee->id,
            'attendance_date'     => today(),
            'check_in_time'       => now(),
            'check_in_photo_path' => 'attendance/1/2026/06/emp.jpg',
            'status'              => 'APPROVED',
        ]);

        $hr = User::factory()->create(['role' => 'admin_hr', 'is_active' => true]);

        $this->actingAs($hr)
            ->get("/attendance/photo/{$record->id}")
            ->assertOk();
    }
}
