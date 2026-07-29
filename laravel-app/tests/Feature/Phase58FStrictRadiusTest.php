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
 * Phase 58F: strict attendance radius. INSIDE_RADIUS is the only classification
 * that may ever create/update an AttendanceRecord for check-in or check-out.
 * OUTSIDE_RADIUS and LOCATION_UNCERTAIN are both rejected outright — there is
 * no more out-of-radius submission, PENDING_REVIEW-on-check-in, or HR
 * notification path. Legacy PENDING_REVIEW/APPROVED/REJECTED rows and the HR
 * approval queue must keep working unchanged.
 */
class Phase58FStrictRadiusTest extends TestCase
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
            'nik'                 => 'P58F-EMP-001',
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

    /**
     * Pure north/south latitude offset from the office by the given distance
     * in metres — for a pure latitude offset the haversine formula reduces to
     * distance = R * dLat(radians), matching AttendanceService exactly.
     */
    private function latAtDistance(float $meters): float
    {
        return round((float) $this->office->latitude - ($meters * 180 / (M_PI * 6371000)), 7);
    }

    private function officeLng(): float
    {
        return (float) $this->office->longitude;
    }

    // Well inside the 100m radius, tight accuracy.
    private function insideCoords(): array
    {
        return ['lat' => $this->latAtDistance(10), 'lng' => $this->officeLng(), 'accuracy' => 5];
    }

    // 200m out with tight accuracy — unambiguously OUTSIDE_RADIUS (dist - acc > radius).
    private function outsideCoords(): array
    {
        return ['lat' => $this->latAtDistance(200), 'lng' => $this->officeLng(), 'accuracy' => 5];
    }

    // 100m out with 30m accuracy — dist+acc=130>100 and dist-acc=70<100 → LOCATION_UNCERTAIN.
    private function uncertainCoords(): array
    {
        return ['lat' => $this->latAtDistance(100), 'lng' => $this->officeLng(), 'accuracy' => 30];
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

    // ── 1. Check-in inside radius berhasil ───────────────────────────────────

    public function test_checkin_inside_radius_succeeds(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->insideCoords(), ['photo' => $this->photo()]))
            ->assertRedirect('/attendance/history');

        $this->assertDatabaseHas('attendance_records', [
            'employee_id' => $this->employee->id,
            'status'      => 'APPROVED',
        ]);
    }

    // ── 2. Check-in outside radius ditolak ───────────────────────────────────

    public function test_checkin_outside_radius_is_rejected(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->outsideCoords(), ['photo' => $this->photo()]))
            ->assertSessionHasErrors('general');
    }

    // ── 3. Check-in uncertain ditolak ────────────────────────────────────────

    public function test_checkin_uncertain_is_rejected(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->uncertainCoords(), ['photo' => $this->photo()]))
            ->assertSessionHasErrors('general');
    }

    // ── 4. Outside tidak membuat attendance record ───────────────────────────

    public function test_outside_radius_does_not_create_attendance_record(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->outsideCoords(), ['photo' => $this->photo()]));

        $this->assertDatabaseMissing('attendance_records', ['employee_id' => $this->employee->id]);
    }

    // ── 5. Uncertain tidak membuat attendance record ─────────────────────────

    public function test_uncertain_does_not_create_attendance_record(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->uncertainCoords(), ['photo' => $this->photo()]));

        $this->assertDatabaseMissing('attendance_records', ['employee_id' => $this->employee->id]);
    }

    // ── 6. Outside tidak menyimpan selfie ────────────────────────────────────

    public function test_outside_radius_does_not_store_selfie(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->outsideCoords(), ['photo' => $this->photo()]));

        $this->assertEmpty(Storage::disk('local')->allFiles('attendance'));
    }

    // ── 7. Uncertain tidak menyimpan selfie ──────────────────────────────────

    public function test_uncertain_does_not_store_selfie(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->uncertainCoords(), ['photo' => $this->photo()]));

        $this->assertEmpty(Storage::disk('local')->allFiles('attendance'));
    }

    // ── 8. Outside tidak membuat notification ────────────────────────────────

    public function test_outside_radius_does_not_create_notification(): void
    {
        User::factory()->create(['role' => 'admin_hr', 'is_active' => true]);
        User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->outsideCoords(), ['photo' => $this->photo()]));

        $this->assertSame(0, Notification::count());
    }

    // ── 9. Outside tidak membuat PENDING_REVIEW ──────────────────────────────

    public function test_outside_radius_does_not_create_pending_review(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->outsideCoords(), ['photo' => $this->photo()]));

        $this->assertDatabaseMissing('attendance_records', [
            'employee_id' => $this->employee->id,
            'status'      => 'PENDING_REVIEW',
        ]);
        $this->assertSame(0, AttendanceRecord::where('employee_id', $this->employee->id)->count());
    }

    // ── 10. Backend mengabaikan status radius dari client ────────────────────

    public function test_backend_ignores_client_supplied_radius_status(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->outsideCoords(), [
                'photo'          => $this->photo(),
                'status'         => 'APPROVED',
                'classification' => 'INSIDE_RADIUS',
            ]))
            ->assertSessionHasErrors('general');

        $this->assertDatabaseMissing('attendance_records', ['employee_id' => $this->employee->id]);
    }

    // ── 11. Check-out inside radius berhasil ─────────────────────────────────

    public function test_checkout_inside_radius_succeeds(): void
    {
        $this->makeApprovedRecordToday();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', $this->insideCoords())
            ->assertRedirect('/attendance/history');

        $record = AttendanceRecord::where('employee_id', $this->employee->id)->first();
        $this->assertNotNull($record->check_out_time);
    }

    // ── 12. Check-out outside radius ditolak ─────────────────────────────────

    public function test_checkout_outside_radius_is_rejected(): void
    {
        $this->makeApprovedRecordToday();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', $this->outsideCoords())
            ->assertSessionHasErrors('general');
    }

    // ── 13. Check-out uncertain ditolak ──────────────────────────────────────

    public function test_checkout_uncertain_is_rejected(): void
    {
        $this->makeApprovedRecordToday();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', $this->uncertainCoords())
            ->assertSessionHasErrors('general');
    }

    // ── 14. Check-out ditolak tanpa mengubah record ──────────────────────────

    public function test_checkout_rejection_does_not_modify_record(): void
    {
        $record = $this->makeApprovedRecordToday();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', $this->outsideCoords());

        $fresh = $record->fresh();
        $this->assertNull($fresh->check_out_time);
        $this->assertNull($fresh->check_out_lat);
        $this->assertNull($fresh->check_out_lng);
        $this->assertNull($fresh->check_out_accuracy);

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', $this->uncertainCoords());

        $fresh = $record->fresh();
        $this->assertNull($fresh->check_out_time);
    }

    // ── 15. Data attendance lama PENDING_REVIEW tetap dapat dibaca ───────────

    public function test_legacy_pending_review_record_is_still_readable(): void
    {
        AttendanceRecord::create([
            'employee_id'          => $this->employee->id,
            'attendance_date'      => today()->subDay(),
            'check_in_time'        => now()->subDay(),
            'check_in_lat'         => $this->latAtDistance(200),
            'check_in_lng'         => $this->officeLng(),
            'status'               => 'PENDING_REVIEW',
            'out_of_radius_reason' => 'Kunjungan klien (data legacy sebelum Phase 58F).',
        ]);

        $html = $this->actingAs($this->employeeUser)
            ->get('/attendance/history')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Menunggu Review HR', $html);
        $this->assertDatabaseHas('attendance_records', [
            'employee_id' => $this->employee->id,
            'status'      => 'PENDING_REVIEW',
        ]);
    }

    // ── 16. Approval queue lama tetap berfungsi ──────────────────────────────

    public function test_legacy_approval_queue_still_works(): void
    {
        $hrUser = User::factory()->create(['role' => 'admin_hr', 'is_active' => true]);
        $dept   = Department::create(['name' => 'HR Dept', 'description' => '']);
        $pos    = Position::create(['name' => 'HR Role', 'department_id' => $dept->id]);
        Employee::factory()->create([
            'user_id'            => $hrUser->id,
            'department_id'      => $dept->id,
            'position_id'        => $pos->id,
            'employment_status'  => 'active',
        ]);

        $legacyRecord = AttendanceRecord::create([
            'employee_id'          => $this->employee->id,
            'attendance_date'      => today()->subDay(),
            'check_in_time'        => now()->subDay(),
            'status'               => 'PENDING_REVIEW',
            'out_of_radius_reason' => 'Legacy record predating Phase 58F.',
        ]);

        $this->actingAs($hrUser)->get('/hr/approval-queue')->assertOk();

        $this->actingAs($hrUser)
            ->post("/hr/attendance/{$legacyRecord->id}/approve", ['approval_note' => 'Verified legacy record'])
            ->assertRedirect();

        $this->assertSame('APPROVED', $legacyRecord->fresh()->status);
    }

    // ── 17. Field alasan luar radius tidak tampil pada UI ────────────────────

    public function test_out_of_radius_reason_field_is_not_present_in_checkin_ui(): void
    {
        $html = $this->actingAs($this->employeeUser)
            ->get('/attendance/checkin')
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('name="reason"', $html);
        $this->assertStringNotContainsString('Alasan absen di luar radius', $html);
        $this->assertStringNotContainsString('Kirim untuk Review HR', $html);
    }

    // ── 18/19. Tombol submit disabled saat outside/uncertain ─────────────────

    public function test_submit_button_starts_disabled_and_js_blocks_outside_and_uncertain(): void
    {
        $html = $this->actingAs($this->employeeUser)
            ->get('/attendance/checkin')
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/id="submit-btn"[^>]*disabled/s', $html);
        $this->assertStringContainsString("classification === 'OUTSIDE_RADIUS'", $html);
        $this->assertStringContainsString("classification === 'LOCATION_UNCERTAIN'", $html);
        $this->assertStringContainsString('locationBlocked = true', $html);
    }

    // ── 20. Radius dan accuracy tetap ditampilkan ────────────────────────────

    public function test_distance_and_accuracy_are_displayed(): void
    {
        $html = $this->actingAs($this->employeeUser)
            ->get('/attendance/checkin')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="gps-detail"', $html);
        $this->assertStringContainsString('id="radius-badge"', $html);
        $this->assertStringContainsString("'m dari kantor'", $html);
        $this->assertStringContainsString("'Akurasi: '", $html);
    }
}
