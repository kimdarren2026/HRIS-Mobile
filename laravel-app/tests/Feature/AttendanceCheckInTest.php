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

class AttendanceCheckInTest extends TestCase
{
    use RefreshDatabase;

    private User $employeeUser;
    private Employee $employee;
    private OfficeLocation $office;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable throttle so repeated POST calls in tests don't get rate-limited
        $this->withoutMiddleware(ThrottleRequests::class);

        Storage::fake('local');

        $dept     = Department::create(['name' => 'Engineering', 'description' => '']);
        $position = Position::create(['name' => 'Dev', 'department_id' => $dept->id]);

        $this->employeeUser = User::factory()->create([
            'role'      => 'employee',
            'is_active' => true,
        ]);

        $this->employee = Employee::create([
            'user_id'            => $this->employeeUser->id,
            'nik'                => 'NIK-TEST-001',
            'department_id'      => $dept->id,
            'position_id'        => $position->id,
            'join_date'          => '2026-01-01',
            'employment_status'  => 'active',
            'phone_number'       => '+62812345678',
            'address'            => 'Test Address',
            'bank_name'          => 'Test Bank',
            'bank_account_number'=> '1234567890',
        ]);

        // Jakarta Main Office — lat -6.200, lng 106.817, radius 100m
        $this->office = OfficeLocation::create([
            'name'          => 'Main Office',
            'latitude'      => -6.2000000,
            'longitude'     => 106.8166660,
            'radius_meters' => 100,
            'is_active'     => true,
        ]);
    }

    // Within 100m of office (lat difference ~11m)
    private function coordsWithin(): array
    {
        return ['lat' => -6.2001000, 'lng' => 106.8166660];
    }

    // ~1.1km from office
    private function coordsOutside(): array
    {
        return ['lat' => -6.2100000, 'lng' => 106.8166660];
    }

    private function validPhoto(): UploadedFile
    {
        return UploadedFile::fake()->image('selfie.jpg', 200, 200)->size(100);
    }

    // ── GET routes ──────────────────────────────────────────────────────────

    public function test_employee_can_open_checkin_page(): void
    {
        $this->actingAs($this->employeeUser)
            ->get('/attendance/checkin')
            ->assertOk();
    }

    public function test_employee_can_open_checkin_outside_page(): void
    {
        $this->actingAs($this->employeeUser)
            ->get('/attendance/checkin-outside')
            ->assertOk();
    }

    public function test_employee_can_open_history_page(): void
    {
        $this->actingAs($this->employeeUser)
            ->get('/attendance/history')
            ->assertOk();
    }

    public function test_guest_cannot_open_checkin_page(): void
    {
        $this->get('/attendance/checkin')->assertRedirect('/login');
    }

    // ── Check-in POST success cases ─────────────────────────────────────────

    public function test_checkin_within_radius_is_approved(): void
    {
        $coords = $this->coordsWithin();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($coords, ['photo' => $this->validPhoto()]))
            ->assertRedirect('/attendance/history');

        $record = AttendanceRecord::where('employee_id', $this->employee->id)->first();
        $this->assertNotNull($record);
        $this->assertSame('APPROVED', $record->status);
        Storage::disk('local')->assertExists($record->check_in_photo_path);
    }

    public function test_checkin_outside_radius_with_valid_reason_is_pending_review(): void
    {
        $coords = $this->coordsOutside();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($coords, [
                'photo'  => $this->validPhoto(),
                'reason' => 'Working from client site, approved by manager beforehand.',
            ]))
            ->assertRedirect('/attendance/history');

        $this->assertDatabaseHas('attendance_records', [
            'employee_id' => $this->employee->id,
            'status'      => 'PENDING_REVIEW',
        ]);
    }

    // ── Check-in POST failure cases ─────────────────────────────────────────

    public function test_checkin_outside_radius_without_reason_fails(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->coordsOutside(), ['photo' => $this->validPhoto()]))
            ->assertSessionHasErrors('reason');

        $this->assertDatabaseMissing('attendance_records', ['employee_id' => $this->employee->id]);
    }

    public function test_checkin_outside_radius_with_reason_too_short_fails(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->coordsOutside(), [
                'photo'  => $this->validPhoto(),
                'reason' => 'Too short',
            ]))
            ->assertSessionHasErrors('reason');
    }

    public function test_duplicate_checkin_same_day_is_rejected(): void
    {
        AttendanceRecord::create([
            'employee_id'     => $this->employee->id,
            'attendance_date' => today(),
            'check_in_time'   => now()->subHour(),
            'status'          => 'APPROVED',
        ]);

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->coordsWithin(), ['photo' => $this->validPhoto()]))
            ->assertSessionHasErrors('general');
    }

    public function test_guest_cannot_submit_checkin(): void
    {
        $this->post('/attendance/check-in', array_merge($this->coordsWithin(), ['photo' => $this->validPhoto()]))
            ->assertRedirect('/login');
    }

    public function test_photo_is_required(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', $this->coordsWithin())
            ->assertSessionHasErrors('photo');
    }

    public function test_non_image_file_is_rejected(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->coordsWithin(), [
                'photo' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('photo');
    }

    public function test_photo_larger_than_5mb_is_rejected(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->coordsWithin(), [
                'photo' => UploadedFile::fake()->image('big.jpg')->size(5121),
            ]))
            ->assertSessionHasErrors('photo');
    }

    public function test_invalid_latitude_is_rejected(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', [
                'lat'   => 99.999,
                'lng'   => 106.8166660,
                'photo' => $this->validPhoto(),
            ])
            ->assertSessionHasErrors('lat');
    }

    public function test_missing_gps_is_rejected(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', ['photo' => $this->validPhoto()])
            ->assertSessionHasErrors(['lat', 'lng']);
    }

    // ── distance_from_office storage ────────────────────────────────────────

    public function test_distance_from_office_is_stored_on_checkin_within_radius(): void
    {
        $coords = $this->coordsWithin();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($coords, ['photo' => $this->validPhoto()]))
            ->assertRedirect('/attendance/history');

        $record = AttendanceRecord::where('employee_id', $this->employee->id)->first();
        $this->assertNotNull($record);
        $this->assertNotNull($record->distance_from_office);
        $this->assertLessThanOrEqual($this->office->radius_meters, $record->distance_from_office);
    }

    public function test_distance_from_office_is_stored_on_checkin_outside_radius(): void
    {
        $coords = $this->coordsOutside();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($coords, [
                'photo'  => $this->validPhoto(),
                'reason' => 'Working from home, approved by manager.',
            ]))
            ->assertRedirect('/attendance/history');

        $record = AttendanceRecord::where('employee_id', $this->employee->id)->first();
        $this->assertNotNull($record);
        $this->assertNotNull($record->distance_from_office);
        $this->assertGreaterThan($this->office->radius_meters, $record->distance_from_office);
        $this->assertSame('PENDING_REVIEW', $record->status);
    }

    // ── HR Approval Queue access ────────────────────────────────────────────

    public function test_admin_hr_can_access_approval_queue(): void
    {
        $hr = User::factory()->create(['role' => 'admin_hr', 'is_active' => true]);
        $this->actingAs($hr)->get('/hr/approval-queue')->assertOk();
    }

    public function test_super_admin_can_access_approval_queue(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->actingAs($sa)->get('/hr/approval-queue')->assertOk();
    }

    public function test_finance_cannot_access_approval_queue(): void
    {
        $finance = User::factory()->create(['role' => 'finance', 'is_active' => true]);
        $this->actingAs($finance)->get('/hr/approval-queue')->assertForbidden();
    }

    public function test_employee_cannot_access_approval_queue(): void
    {
        $this->actingAs($this->employeeUser)->get('/hr/approval-queue')->assertForbidden();
    }

    // ── Approve / Reject ────────────────────────────────────────────────────

    private function createPendingRecord(): AttendanceRecord
    {
        return AttendanceRecord::create([
            'employee_id'          => $this->employee->id,
            'attendance_date'      => today(),
            'check_in_time'        => now()->subHour(),
            'check_in_lat'         => -6.2100000,
            'check_in_lng'         => 106.8166660,
            'check_in_photo_path'  => 'attendance/1/2026/06/fake.jpg',
            'status'               => 'PENDING_REVIEW',
            'out_of_radius_reason' => 'Working from client office today.',
        ]);
    }

    public function test_hr_can_approve_pending_attendance(): void
    {
        $record = $this->createPendingRecord();
        $hr     = User::factory()->create(['role' => 'admin_hr', 'is_active' => true]);

        $this->actingAs($hr)
            ->post("/hr/attendance/{$record->id}/approve", ['approval_note' => 'Client visit confirmed.'])
            ->assertRedirect();

        $this->assertDatabaseHas('attendance_records', ['id' => $record->id, 'status' => 'APPROVED']);
    }

    public function test_hr_can_reject_pending_attendance_with_note(): void
    {
        $record = $this->createPendingRecord();
        $hr     = User::factory()->create(['role' => 'admin_hr', 'is_active' => true]);

        $this->actingAs($hr)
            ->post("/hr/attendance/{$record->id}/reject", [
                'approval_note' => 'Insufficient justification provided.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attendance_records', ['id' => $record->id, 'status' => 'REJECTED']);
    }

    public function test_reject_without_note_fails(): void
    {
        $record = $this->createPendingRecord();
        $hr     = User::factory()->create(['role' => 'admin_hr', 'is_active' => true]);

        $this->actingAs($hr)
            ->post("/hr/attendance/{$record->id}/reject", ['approval_note' => ''])
            ->assertSessionHasErrors('approval_note');
    }

    public function test_reject_with_short_note_fails(): void
    {
        $record = $this->createPendingRecord();
        $hr     = User::factory()->create(['role' => 'admin_hr', 'is_active' => true]);

        $this->actingAs($hr)
            ->post("/hr/attendance/{$record->id}/reject", ['approval_note' => 'Too short'])
            ->assertSessionHasErrors('approval_note');
    }

    public function test_employee_cannot_approve_attendance(): void
    {
        $record = $this->createPendingRecord();

        $this->actingAs($this->employeeUser)
            ->post("/hr/attendance/{$record->id}/approve")
            ->assertForbidden();
    }

    public function test_finance_cannot_approve_attendance(): void
    {
        $record  = $this->createPendingRecord();
        $finance = User::factory()->create(['role' => 'finance', 'is_active' => true]);

        $this->actingAs($finance)
            ->post("/hr/attendance/{$record->id}/approve")
            ->assertForbidden();
    }

    // ── Protected photo access ──────────────────────────────────────────────

    private function createApprovedRecordWithPhoto(): AttendanceRecord
    {
        Storage::disk('local')->put('attendance/1/2026/06/test.jpg', 'fake-image-data');

        return AttendanceRecord::create([
            'employee_id'         => $this->employee->id,
            'attendance_date'     => today(),
            'check_in_time'       => now(),
            'check_in_photo_path' => 'attendance/1/2026/06/test.jpg',
            'status'              => 'APPROVED',
        ]);
    }

    public function test_employee_can_view_own_photo(): void
    {
        $record = $this->createApprovedRecordWithPhoto();

        $this->actingAs($this->employeeUser)
            ->get("/attendance/photo/{$record->id}")
            ->assertOk();
    }

    public function test_employee_cannot_view_other_employee_photo(): void
    {
        $dept = Department::first();
        $pos  = Position::first();

        $otherUser = User::factory()->create(['role' => 'employee', 'is_active' => true]);
        $otherEmp  = Employee::create([
            'user_id'             => $otherUser->id,
            'nik'                 => 'NIK-OTHER-002',
            'department_id'       => $dept->id,
            'position_id'         => $pos->id,
            'join_date'           => '2026-01-01',
            'employment_status'   => 'active',
            'phone_number'        => '+62812345679',
            'address'             => 'Other address',
            'bank_name'           => 'Test Bank',
            'bank_account_number' => '9999999999',
        ]);

        Storage::disk('local')->put('attendance/2/2026/06/other.jpg', 'fake-data');
        $record = AttendanceRecord::create([
            'employee_id'         => $otherEmp->id,
            'attendance_date'     => today(),
            'check_in_time'       => now(),
            'check_in_photo_path' => 'attendance/2/2026/06/other.jpg',
            'status'              => 'APPROVED',
        ]);

        $this->actingAs($this->employeeUser)
            ->get("/attendance/photo/{$record->id}")
            ->assertForbidden();
    }

    public function test_admin_hr_can_view_any_attendance_photo(): void
    {
        $record = $this->createApprovedRecordWithPhoto();
        $hr     = User::factory()->create(['role' => 'admin_hr', 'is_active' => true]);

        $this->actingAs($hr)
            ->get("/attendance/photo/{$record->id}")
            ->assertOk();
    }

    public function test_guest_cannot_access_photo(): void
    {
        $record = $this->createApprovedRecordWithPhoto();
        $this->get("/attendance/photo/{$record->id}")->assertRedirect('/login');
    }
}
