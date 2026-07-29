<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\OfficeLocation;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 58G: restores the out-of-radius HR review flow that Phase 58F removed.
 * Enumerates the 26 scenarios from the Phase 58G spec directly, on top of the
 * updated Phase 58E/58F/30/20 regression tests.
 */
class Phase58GRestoreOutsideRadiusReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $employeeUser;
    private Employee $employee;
    private OfficeLocation $office;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
        Storage::fake('local');

        $dept     = Department::create(['name' => 'Engineering', 'description' => '']);
        $position = Position::create(['name' => 'Dev', 'department_id' => $dept->id]);

        $this->employeeUser = User::factory()->create(['role' => 'employee', 'is_active' => true]);

        $this->employee = Employee::create([
            'user_id'             => $this->employeeUser->id,
            'nik'                 => 'P58G-EMP-001',
            'department_id'       => $dept->id,
            'position_id'         => $position->id,
            'join_date'           => '2026-01-01',
            'employment_status'   => 'active',
            'phone_number'        => '+62812345678',
            'address'             => 'Test Address',
            'bank_name'           => 'Test Bank',
            'bank_account_number' => '1234567890',
        ]);

        $this->office = OfficeLocation::create([
            'name'          => 'Main Office',
            'latitude'      => -6.2000000,
            'longitude'     => 106.8166660,
            'radius_meters' => 100,
            'is_active'     => true,
        ]);
    }

    private function photo(): UploadedFile
    {
        return UploadedFile::fake()->image('selfie.jpg', 200, 200)->size(100);
    }

    private function validReason(): string
    {
        return 'Kunjungan klien di luar kantor hari ini.';
    }

    private function latAtDistance(float $meters): float
    {
        return round((float) $this->office->latitude - ($meters * 180 / (M_PI * 6371000)), 7);
    }

    private function officeLng(): float
    {
        return (float) $this->office->longitude;
    }

    private function makeApprovedRecordToday(): AttendanceRecord
    {
        return AttendanceRecord::create([
            'employee_id'     => $this->employee->id,
            'attendance_date' => today(),
            'check_in_time'   => now()->subHours(2),
            'check_in_lat'    => $this->latAtDistance(10),
            'check_in_lng'    => $this->officeLng(),
            'status'          => 'APPROVED',
        ]);
    }

    private function makeHrApprover(): User
    {
        $hr   = User::factory()->create(['role' => 'admin_hr', 'is_active' => true]);
        $dept = Department::create(['name' => 'HR Dept '.uniqid(), 'description' => '']);
        $pos  = Position::create(['name' => 'HR Role '.uniqid(), 'department_id' => $dept->id]);

        Employee::factory()->create([
            'user_id'           => $hr->id,
            'department_id'     => $dept->id,
            'position_id'       => $pos->id,
            'employment_status' => 'active',
        ]);

        return $hr;
    }

    // ── 1. Distance sama dengan radius → APPROVED ────────────────────────────

    public function test_1_distance_equal_to_radius_is_approved(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', [
                'lat' => $this->latAtDistance(100), 'lng' => $this->officeLng(),
                'accuracy' => 5, 'photo' => $this->photo(),
            ])
            ->assertRedirect('/attendance/history');

        $this->assertDatabaseHas('attendance_records', [
            'employee_id' => $this->employee->id, 'status' => 'APPROVED',
        ]);
    }

    // ── 2. Distance di bawah radius → APPROVED ───────────────────────────────

    public function test_2_distance_below_radius_is_approved(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', [
                'lat' => $this->latAtDistance(10), 'lng' => $this->officeLng(),
                'accuracy' => 5, 'photo' => $this->photo(),
            ])
            ->assertRedirect('/attendance/history');

        $this->assertDatabaseHas('attendance_records', [
            'employee_id' => $this->employee->id, 'status' => 'APPROVED',
        ]);
    }

    // ── 3. Distance di atas radius tanpa alasan → ditolak ────────────────────

    public function test_3_distance_above_radius_without_reason_is_rejected(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', [
                'lat' => $this->latAtDistance(200), 'lng' => $this->officeLng(),
                'accuracy' => 5, 'photo' => $this->photo(),
            ])
            ->assertSessionHasErrors('reason');

        $this->assertDatabaseMissing('attendance_records', ['employee_id' => $this->employee->id]);
    }

    // ── 4. Distance di atas radius dengan alasan valid → PENDING_REVIEW ──────

    public function test_4_distance_above_radius_with_valid_reason_is_pending_review(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', [
                'lat' => $this->latAtDistance(200), 'lng' => $this->officeLng(),
                'accuracy' => 5, 'photo' => $this->photo(), 'reason' => $this->validReason(),
            ])
            ->assertRedirect('/attendance/history');

        $this->assertDatabaseHas('attendance_records', [
            'employee_id' => $this->employee->id, 'status' => 'PENDING_REVIEW',
        ]);
    }

    // ── 5-9. Outside check-in stores reason/distance/accuracy/selfie + notifies HR ──

    public function test_5_through_9_outside_checkin_persists_everything_and_notifies_hr(): void
    {
        User::factory()->create(['role' => 'admin_hr', 'is_active' => true]);
        User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', [
                'lat' => $this->latAtDistance(200), 'lng' => $this->officeLng(),
                'accuracy' => 12.5, 'photo' => $this->photo(), 'reason' => $this->validReason(),
            ])
            ->assertRedirect('/attendance/history');

        $record = AttendanceRecord::where('employee_id', $this->employee->id)->first();

        $this->assertSame($this->validReason(), $record->out_of_radius_reason);       // 5
        $this->assertEqualsWithDelta(200.0, (float) $record->distance_from_office, 1.0); // 6
        $this->assertEqualsWithDelta(12.5, (float) $record->check_in_accuracy, 0.01);  // 7
        Storage::disk('local')->assertExists($record->check_in_photo_path);            // 8
        $this->assertSame(2, Notification::count());                                   // 9
    }

    // ── 10. Inside check-in tidak membuat notification review ───────────────

    public function test_10_inside_checkin_does_not_create_notification(): void
    {
        User::factory()->create(['role' => 'admin_hr', 'is_active' => true]);
        User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', [
                'lat' => $this->latAtDistance(10), 'lng' => $this->officeLng(),
                'accuracy' => 5, 'photo' => $this->photo(),
            ]);

        $this->assertSame(0, Notification::count());
    }

    // ── 11-12. Accuracy besar tapi distance di dalam radius → APPROVED, no LOCATION_UNCERTAIN ──

    public function test_11_and_12_large_accuracy_inside_radius_is_approved_not_uncertain(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', [
                'lat' => $this->latAtDistance(50), 'lng' => $this->officeLng(),
                'accuracy' => 80, 'photo' => $this->photo(),
            ])
            ->assertRedirect('/attendance/history')
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('attendance_records', [
            'employee_id' => $this->employee->id, 'status' => 'APPROVED',
        ]);
    }

    // ── 13-15. Frontend: reason field + button label ─────────────────────────

    public function test_13_frontend_js_shows_reason_field_when_outside_radius(): void
    {
        $html = $this->actingAs($this->employeeUser)->get('/attendance/checkin')->assertOk()->getContent();

        $this->assertStringContainsString("isOutsideRadius", $html);
        $this->assertStringContainsString("reasonSection.classList.remove('hidden')", $html);
    }

    public function test_14_frontend_js_hides_reason_field_when_inside_radius(): void
    {
        $html = $this->actingAs($this->employeeUser)->get('/attendance/checkin')->assertOk()->getContent();

        $this->assertStringContainsString("reasonSection.classList.add('hidden')", $html);
        $this->assertMatchesRegularExpression('/id="reason-section"[^>]*hidden/s', $html);
    }

    public function test_15_outside_radius_button_label_is_kirim_untuk_review_hr(): void
    {
        $html = $this->actingAs($this->employeeUser)->get('/attendance/checkin')->assertOk()->getContent();

        $this->assertStringContainsString("Kirim untuk Review HR", $html);
        $this->assertStringContainsString("Konfirmasi Absen Masuk", $html);
    }

    // ── 16-18. Check-out inside/outside/large-accuracy all succeed ──────────

    public function test_16_checkout_inside_radius_succeeds(): void
    {
        $this->makeApprovedRecordToday();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', [
                'lat' => $this->latAtDistance(10), 'lng' => $this->officeLng(), 'accuracy' => 5,
            ])
            ->assertRedirect('/attendance/history');
    }

    public function test_17_checkout_outside_radius_succeeds(): void
    {
        $this->makeApprovedRecordToday();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', [
                'lat' => $this->latAtDistance(500), 'lng' => $this->officeLng(), 'accuracy' => 5,
            ])
            ->assertRedirect('/attendance/history');

        $record = AttendanceRecord::where('employee_id', $this->employee->id)->first();
        $this->assertNotNull($record->check_out_time);
    }

    public function test_18_checkout_with_large_accuracy_succeeds(): void
    {
        $this->makeApprovedRecordToday();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', [
                'lat' => $this->latAtDistance(10), 'lng' => $this->officeLng(), 'accuracy' => 999,
            ])
            ->assertRedirect('/attendance/history');
    }

    // ── 19-20. Check-out outside radius: no new PENDING_REVIEW, no notification ──

    public function test_19_checkout_outside_radius_does_not_create_pending_review(): void
    {
        $record = $this->makeApprovedRecordToday();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', [
                'lat' => $this->latAtDistance(500), 'lng' => $this->officeLng(), 'accuracy' => 5,
            ]);

        $this->assertSame(1, AttendanceRecord::where('employee_id', $this->employee->id)->count());
        $this->assertSame('APPROVED', $record->fresh()->status);
    }

    public function test_20_checkout_outside_radius_does_not_create_notification(): void
    {
        User::factory()->create(['role' => 'admin_hr', 'is_active' => true]);
        $this->makeApprovedRecordToday();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', [
                'lat' => $this->latAtDistance(500), 'lng' => $this->officeLng(), 'accuracy' => 5,
            ]);

        $this->assertSame(0, Notification::count());
    }

    // ── 21. Check-out menyimpan check_out_accuracy ───────────────────────────

    public function test_21_checkout_stores_check_out_accuracy(): void
    {
        $this->makeApprovedRecordToday();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', [
                'lat' => $this->latAtDistance(10), 'lng' => $this->officeLng(), 'accuracy' => 33.5,
            ]);

        $record = AttendanceRecord::where('employee_id', $this->employee->id)->first();
        $this->assertEqualsWithDelta(33.5, (float) $record->check_out_accuracy, 0.01);
    }

    // ── 22. GPS/coordinates tidak tersedia tetap ditolak ─────────────────────

    public function test_22_checkin_missing_coordinates_is_rejected(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', ['photo' => $this->photo()])
            ->assertSessionHasErrors(['lat', 'lng', 'accuracy']);

        $this->assertDatabaseMissing('attendance_records', ['employee_id' => $this->employee->id]);
    }

    public function test_22_checkout_missing_coordinates_is_rejected(): void
    {
        $this->makeApprovedRecordToday();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', [])
            ->assertSessionHasErrors(['lat', 'lng', 'accuracy']);

        $record = AttendanceRecord::where('employee_id', $this->employee->id)->first();
        $this->assertNull($record->check_out_time);
    }

    // ── 23. Backend tidak mempercayai status radius dari client ─────────────

    public function test_23_backend_ignores_client_supplied_radius_status(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', [
                'lat' => $this->latAtDistance(200), 'lng' => $this->officeLng(),
                'accuracy' => 5, 'photo' => $this->photo(),
                'status' => 'APPROVED', 'classification' => 'INSIDE_RADIUS',
            ])
            ->assertSessionHasErrors('reason');

        $this->assertDatabaseMissing('attendance_records', ['employee_id' => $this->employee->id]);
    }

    // ── 24. Approval queue dapat membaca PENDING_REVIEW baru ────────────────

    public function test_24_approval_queue_reads_new_pending_review(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', [
                'lat' => $this->latAtDistance(200), 'lng' => $this->officeLng(),
                'accuracy' => 5, 'photo' => $this->photo(), 'reason' => $this->validReason(),
            ]);

        $hr = $this->makeHrApprover();

        $response = $this->actingAs($hr)->get('/hr/approval-queue')->assertOk();
        $pending  = $response->viewData('pending');

        $this->assertSame(1, $pending->total());
        $this->assertSame($this->employee->id, $pending->first()->employee_id);
    }

    // ── 25. HR dapat approve/reject check-in luar radius ─────────────────────

    public function test_25_hr_can_approve_outside_radius_checkin(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', [
                'lat' => $this->latAtDistance(200), 'lng' => $this->officeLng(),
                'accuracy' => 5, 'photo' => $this->photo(), 'reason' => $this->validReason(),
            ]);

        $record = AttendanceRecord::where('employee_id', $this->employee->id)->first();
        $hr     = $this->makeHrApprover();

        $this->actingAs($hr)
            ->post("/hr/attendance/{$record->id}/approve", ['approval_note' => 'Verified.'])
            ->assertRedirect();

        $this->assertSame('APPROVED', $record->fresh()->status);
    }

    public function test_25_hr_can_reject_outside_radius_checkin(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', [
                'lat' => $this->latAtDistance(200), 'lng' => $this->officeLng(),
                'accuracy' => 5, 'photo' => $this->photo(), 'reason' => $this->validReason(),
            ]);

        $record = AttendanceRecord::where('employee_id', $this->employee->id)->first();
        $hr     = $this->makeHrApprover();

        $this->actingAs($hr)
            ->post("/hr/attendance/{$record->id}/reject", ['approval_note' => 'Location not verified.'])
            ->assertRedirect();

        $this->assertSame('REJECTED', $record->fresh()->status);
    }

    // ── 26. Data attendance lama tetap dapat dibaca ──────────────────────────

    public function test_26_old_attendance_data_remains_readable(): void
    {
        AttendanceRecord::create([
            'employee_id'          => $this->employee->id,
            'attendance_date'      => today()->subDays(3),
            'check_in_time'        => now()->subDays(3),
            'check_in_lat'         => $this->latAtDistance(200),
            'check_in_lng'         => $this->officeLng(),
            'status'               => 'PENDING_REVIEW',
            'out_of_radius_reason' => 'Legacy record predating Phase 58G.',
        ]);
        AttendanceRecord::create([
            'employee_id'     => $this->employee->id,
            'attendance_date' => today()->subDays(4),
            'check_in_time'   => now()->subDays(4),
            'status'          => 'APPROVED',
        ]);

        $this->assertDatabaseCount('attendance_records', 2);

        $this->actingAs($this->employeeUser)
            ->get('/attendance/history')
            ->assertOk()
            ->assertSee('Menunggu Review HR');
    }
}
