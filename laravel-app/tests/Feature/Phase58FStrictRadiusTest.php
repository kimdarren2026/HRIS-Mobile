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
 * Phase 58F introduced a strict radius policy that rejected OUTSIDE_RADIUS
 * check-ins/check-outs outright and added an accuracy-margin LOCATION_UNCERTAIN
 * classification. Phase 58G corrects this: the radius decision is distance-only
 * (INSIDE_RADIUS vs OUTSIDE_RADIUS, no uncertain state), OUTSIDE_RADIUS check-in
 * is allowed with a mandatory reason and lands in PENDING_REVIEW with an HR
 * notification, and check-out is never blocked by radius or accuracy. This file
 * now documents and asserts that corrected behavior.
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

    private function validReason(): string
    {
        return 'Kunjungan klien di luar kantor hari ini.';
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

    // Phase 60A: included on every helper below (harmless no-op extra field
    // for whichever of check-in/check-out doesn't define it) so these shared
    // coord helpers keep working for both endpoints without duplicating them.
    private const WORK_NOTES = [
        'check_in_work_plan'    => 'Menyelesaikan laporan mingguan dan rapat tim.',
        'check_out_work_result' => 'Pekerjaan hari ini selesai dengan baik.',
    ];

    // Well inside the 100m radius, tight accuracy.
    private function insideCoords(): array
    {
        return ['lat' => $this->latAtDistance(10), 'lng' => $this->officeLng(), 'accuracy' => 5] + self::WORK_NOTES;
    }

    // 200m out with tight accuracy — unambiguously OUTSIDE_RADIUS.
    private function outsideCoords(): array
    {
        return ['lat' => $this->latAtDistance(200), 'lng' => $this->officeLng(), 'accuracy' => 5] + self::WORK_NOTES;
    }

    // Exactly at the radius boundary with large accuracy — accuracy must not
    // affect the decision: distance <= radius is still INSIDE_RADIUS.
    private function boundaryCoordsWithLargeAccuracy(): array
    {
        return ['lat' => $this->latAtDistance(100), 'lng' => $this->officeLng(), 'accuracy' => 30] + self::WORK_NOTES;
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

    // ── 2. Check-in outside radius tanpa alasan ditolak ──────────────────────

    public function test_checkin_outside_radius_without_reason_is_rejected(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->outsideCoords(), ['photo' => $this->photo()]))
            ->assertSessionHasErrors('reason');

        $this->assertDatabaseMissing('attendance_records', ['employee_id' => $this->employee->id]);
    }

    // ── 3. Distance persis di radius, accuracy besar → tetap INSIDE_RADIUS ──

    public function test_distance_at_radius_boundary_with_large_accuracy_is_inside_and_approved(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->boundaryCoordsWithLargeAccuracy(), ['photo' => $this->photo()]))
            ->assertRedirect('/attendance/history');

        $this->assertDatabaseHas('attendance_records', [
            'employee_id' => $this->employee->id,
            'status'      => 'APPROVED',
        ]);
    }

    // ── 4. Outside dengan alasan valid membuat PENDING_REVIEW ────────────────

    public function test_outside_radius_with_reason_creates_pending_review_record(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->outsideCoords(), [
                'photo'  => $this->photo(),
                'reason' => $this->validReason(),
            ]))
            ->assertRedirect('/attendance/history');

        $this->assertDatabaseHas('attendance_records', [
            'employee_id'          => $this->employee->id,
            'status'               => 'PENDING_REVIEW',
            'out_of_radius_reason' => $this->validReason(),
        ]);
    }

    // ── 5. Outside tanpa alasan tidak menyimpan selfie ───────────────────────

    public function test_outside_radius_without_reason_does_not_store_selfie(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->outsideCoords(), ['photo' => $this->photo()]));

        $this->assertEmpty(Storage::disk('local')->allFiles('attendance'));
    }

    // ── 6. Outside dengan alasan valid menyimpan selfie ──────────────────────

    public function test_outside_radius_with_reason_stores_selfie(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->outsideCoords(), [
                'photo'  => $this->photo(),
                'reason' => $this->validReason(),
            ]));

        $record = AttendanceRecord::where('employee_id', $this->employee->id)->first();
        Storage::disk('local')->assertExists($record->check_in_photo_path);
    }

    // ── 7. Outside dengan alasan valid membuat notification HR ───────────────

    public function test_outside_radius_with_reason_creates_hr_notification(): void
    {
        User::factory()->create(['role' => 'admin_hr', 'is_active' => true]);
        User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->outsideCoords(), [
                'photo'  => $this->photo(),
                'reason' => $this->validReason(),
            ]));

        $this->assertSame(2, Notification::count());
    }

    // ── 8. Inside radius tidak membuat notification review ───────────────────

    public function test_inside_radius_does_not_create_notification(): void
    {
        User::factory()->create(['role' => 'admin_hr', 'is_active' => true]);
        User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->insideCoords(), ['photo' => $this->photo()]));

        $this->assertSame(0, Notification::count());
    }

    // ── 9. Outside tanpa alasan tidak membuat PENDING_REVIEW ─────────────────

    public function test_outside_radius_without_reason_does_not_create_any_record(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->outsideCoords(), ['photo' => $this->photo()]));

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
            ->assertSessionHasErrors('reason');

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

    // ── 12. Check-out outside radius tetap berhasil (Phase 58G) ─────────────

    public function test_checkout_outside_radius_still_succeeds(): void
    {
        $this->makeApprovedRecordToday();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', $this->outsideCoords())
            ->assertRedirect('/attendance/history');

        $record = AttendanceRecord::where('employee_id', $this->employee->id)->first();
        $this->assertNotNull($record->check_out_time);
    }

    // ── 13. Check-out dengan accuracy besar tetap berhasil ───────────────────

    public function test_checkout_with_large_accuracy_still_succeeds(): void
    {
        $this->makeApprovedRecordToday();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', $this->boundaryCoordsWithLargeAccuracy())
            ->assertRedirect('/attendance/history');
    }

    // ── 14. Check-out outside radius tidak membuat notification ──────────────

    public function test_checkout_outside_radius_does_not_create_notification(): void
    {
        User::factory()->create(['role' => 'admin_hr', 'is_active' => true]);
        $this->makeApprovedRecordToday();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', $this->outsideCoords());

        $this->assertSame(0, Notification::count());
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
            'out_of_radius_reason' => 'Kunjungan klien (data legacy).',
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
            'out_of_radius_reason' => 'Legacy record.',
        ]);

        $this->actingAs($hrUser)->get('/hr/approval-queue')->assertOk();

        $this->actingAs($hrUser)
            ->post("/hr/attendance/{$legacyRecord->id}/approve", ['approval_note' => 'Verified legacy record'])
            ->assertRedirect();

        $this->assertSame('APPROVED', $legacyRecord->fresh()->status);
    }

    // ── 17. Field alasan luar radius tampil kembali pada UI ──────────────────

    public function test_out_of_radius_reason_field_is_present_in_checkin_ui(): void
    {
        $html = $this->actingAs($this->employeeUser)
            ->get('/attendance/checkin')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('name="reason"', $html);
        $this->assertStringContainsString('id="reason-section"', $html);
        $this->assertStringContainsString('Kirim untuk Review HR', $html);
    }

    // ── 18. Reason field hidden by default (JS toggles it once distance is known) ──

    public function test_reason_section_hidden_by_default(): void
    {
        $html = $this->actingAs($this->employeeUser)
            ->get('/attendance/checkin')
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/id="reason-section"[^>]*hidden/s', $html);
    }

    // ── 19. Submit button tidak lagi diblokir oleh klasifikasi OUTSIDE_RADIUS ──

    public function test_submit_button_js_no_longer_blocks_outside_radius(): void
    {
        $html = $this->actingAs($this->employeeUser)
            ->get('/attendance/checkin')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString("classification === 'OUTSIDE_RADIUS'", $html);
        $this->assertStringNotContainsString("classification === 'LOCATION_UNCERTAIN'", $html);
        $this->assertStringNotContainsString('locationBlocked', $html);
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
