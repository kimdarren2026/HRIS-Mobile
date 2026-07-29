<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\OfficeLocation;
use App\Models\Position;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase58ERadiusAccuracyTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
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

        $this->superAdmin   = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->employeeUser = User::factory()->create(['role' => 'employee', 'is_active' => true]);

        $this->employee = Employee::create([
            'user_id'             => $this->employeeUser->id,
            'nik'                 => 'P58E-EMP-001',
            'department_id'       => $dept->id,
            'position_id'         => $position->id,
            'join_date'           => '2026-01-01',
            'employment_status'   => 'active',
            'phone_number'        => '+62812345678',
            'address'             => 'Test Address',
            'bank_name'           => 'Test Bank',
            'bank_account_number' => '1234567890',
        ]);

        // Radius held at the new minimum (20m) for every classification test below.
        $this->office = OfficeLocation::create([
            'name'          => 'Main Office',
            'latitude'      => -6.2000000,
            'longitude'     => 106.8166660,
            'radius_meters' => 20,
            'is_active'     => true,
        ]);
    }

    private function photo(): UploadedFile
    {
        return UploadedFile::fake()->image('selfie.jpg', 200, 200)->size(100);
    }

    /**
     * Latitude that is a pure north/south offset of the office by the given
     * distance in metres, using the same spherical-earth radius (6371000m)
     * as AttendanceService::calculateDistance() — for a pure latitude
     * offset the haversine formula reduces to distance = R * dLat(radians),
     * so this is accurate to sub-centimetre precision.
     */
    private function latAtDistance(float $meters): float
    {
        return round((float) $this->office->latitude - ($meters * 180 / (M_PI * 6371000)), 7);
    }

    private function officeLng(): float
    {
        return (float) $this->office->longitude;
    }

    // ── 1-3: radius_meters minimum is 20, not 50 ─────────────────────────────

    public function test_radius_19_is_rejected(): void
    {
        $this->actingAs($this->superAdmin)
            ->post('/settings/locations', $this->officePayload(['radius_meters' => 19]))
            ->assertSessionHasErrors('radius_meters');

        $this->assertDatabaseMissing('office_locations', ['radius_meters' => 19]);
    }

    public function test_radius_20_is_accepted(): void
    {
        $this->actingAs($this->superAdmin)
            ->post('/settings/locations', $this->officePayload(['radius_meters' => 20]))
            ->assertRedirect(route('settings.index'));

        $this->assertDatabaseHas('office_locations', ['name' => 'Branch Office', 'radius_meters' => 20]);
    }

    public function test_radius_50_is_still_accepted(): void
    {
        $this->actingAs($this->superAdmin)
            ->post('/settings/locations', $this->officePayload(['radius_meters' => 50]))
            ->assertRedirect(route('settings.index'));

        $this->assertDatabaseHas('office_locations', ['name' => 'Branch Office', 'radius_meters' => 50]);
    }

    private function officePayload(array $overrides = []): array
    {
        return array_merge([
            'name'          => 'Branch Office',
            'latitude'      => -6.3000000,
            'longitude'     => 106.9000000,
            'radius_meters' => 100,
            'is_active'     => '0',
        ], $overrides);
    }

    // ── 4-5: only an authorized role may change the radius ───────────────────

    public function test_super_admin_can_update_radius_to_20(): void
    {
        $office = OfficeLocation::create([
            'name' => 'Old Radius Office', 'latitude' => -6.3, 'longitude' => 106.9,
            'radius_meters' => 100, 'is_active' => false,
        ]);

        $this->actingAs($this->superAdmin)
            ->put("/settings/locations/{$office->id}", [
                'name' => 'Old Radius Office', 'latitude' => -6.3, 'longitude' => 106.9,
                'radius_meters' => 20, 'is_active' => '0',
            ])
            ->assertRedirect(route('settings.index'));

        $this->assertSame(20, $office->fresh()->radius_meters);
    }

    public function test_employee_without_permission_cannot_change_radius(): void
    {
        $this->actingAs($this->employeeUser)
            ->put("/settings/locations/{$this->office->id}", [
                'name' => $this->office->name, 'latitude' => (float) $this->office->latitude,
                'longitude' => (float) $this->office->longitude, 'radius_meters' => 500, 'is_active' => '1',
            ])
            ->assertForbidden();

        $this->assertSame(20, $this->office->fresh()->radius_meters);
    }

    // ── 6-8: classifyLocation() three-way decision (unit-level, exact inputs) ─

    public function test_classify_distance5_accuracy5_radius20_is_inside(): void
    {
        $service = app(AttendanceService::class);
        $office  = new OfficeLocation(['radius_meters' => 20]);

        $this->assertSame(AttendanceService::INSIDE_RADIUS, $service->classifyLocation(5, 5, $office));
    }

    public function test_classify_distance15_accuracy10_radius20_is_uncertain(): void
    {
        $service = app(AttendanceService::class);
        $office  = new OfficeLocation(['radius_meters' => 20]);

        $this->assertSame(AttendanceService::LOCATION_UNCERTAIN, $service->classifyLocation(15, 10, $office));
    }

    public function test_classify_distance40_accuracy10_radius20_is_outside(): void
    {
        $service = app(AttendanceService::class);
        $office  = new OfficeLocation(['radius_meters' => 20]);

        $this->assertSame(AttendanceService::OUTSIDE_RADIUS, $service->classifyLocation(40, 10, $office));
    }

    // ── 9-11: LOCATION_UNCERTAIN blocks everything ──────────────────────────

    public function test_uncertain_location_does_not_create_attendance_record(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', [
                'lat'      => $this->latAtDistance(15),
                'lng'      => $this->officeLng(),
                'accuracy' => 10,
                'photo'    => $this->photo(),
            ])
            ->assertSessionHasErrors('general');

        $this->assertDatabaseMissing('attendance_records', ['employee_id' => $this->employee->id]);
    }

    public function test_uncertain_location_does_not_store_selfie(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', [
                'lat'      => $this->latAtDistance(15),
                'lng'      => $this->officeLng(),
                'accuracy' => 10,
                'photo'    => $this->photo(),
            ]);

        $this->assertEmpty(Storage::disk('local')->allFiles('attendance'));
    }

    public function test_uncertain_location_does_not_create_notification(): void
    {
        User::factory()->create(['role' => 'admin_hr', 'is_active' => true]);
        User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', [
                'lat'      => $this->latAtDistance(15),
                'lng'      => $this->officeLng(),
                'accuracy' => 10,
                'photo'    => $this->photo(),
            ]);

        $this->assertSame(0, Notification::count());
    }

    // ── 12-13: OUTSIDE → PENDING_REVIEW, INSIDE → existing normal flow ───────

    public function test_outside_radius_results_in_pending_review(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', [
                'lat'      => $this->latAtDistance(40),
                'lng'      => $this->officeLng(),
                'accuracy' => 10,
                'reason'   => 'Bertemu klien di luar kantor hari ini.',
                'photo'    => $this->photo(),
            ])
            ->assertRedirect('/attendance/history');

        $this->assertDatabaseHas('attendance_records', [
            'employee_id' => $this->employee->id,
            'status'      => 'PENDING_REVIEW',
        ]);
    }

    public function test_inside_radius_results_in_normal_approved_status(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', [
                'lat'      => $this->latAtDistance(5),
                'lng'      => $this->officeLng(),
                'accuracy' => 5,
                'photo'    => $this->photo(),
            ])
            ->assertRedirect('/attendance/history');

        $this->assertDatabaseHas('attendance_records', [
            'employee_id' => $this->employee->id,
            'status'      => 'APPROVED',
        ]);
    }

    // ── 14: backend never trusts a client-supplied status/classification ────

    public function test_backend_ignores_client_supplied_status_field(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', [
                'lat'      => $this->latAtDistance(40),
                'lng'      => $this->officeLng(),
                'accuracy' => 10,
                'reason'   => 'Bertemu klien di luar kantor hari ini.',
                'photo'    => $this->photo(),
                // Attacker-controlled fields the request never expects — must be ignored.
                'status'         => 'APPROVED',
                'classification' => 'INSIDE_RADIUS',
            ])
            ->assertRedirect('/attendance/history');

        $this->assertDatabaseHas('attendance_records', [
            'employee_id' => $this->employee->id,
            'status'      => 'PENDING_REVIEW',
        ]);
    }

    // ── 15-16: request validation ────────────────────────────────────────────

    public function test_accuracy_is_required(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', [
                'lat'   => $this->latAtDistance(5),
                'lng'   => $this->officeLng(),
                'photo' => $this->photo(),
            ])
            ->assertSessionHasErrors('accuracy');

        $this->assertDatabaseMissing('attendance_records', ['employee_id' => $this->employee->id]);
    }

    public function test_invalid_latitude_longitude_is_rejected(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', [
                'lat'      => 999.0,
                'lng'      => 999.0,
                'accuracy' => 5,
                'photo'    => $this->photo(),
            ])
            ->assertSessionHasErrors(['lat', 'lng']);

        $this->assertDatabaseMissing('attendance_records', ['employee_id' => $this->employee->id]);
    }

    // ── 17: check-out applies the same classification rule ──────────────────

    public function test_checkout_with_uncertain_location_is_rejected_and_not_recorded(): void
    {
        AttendanceRecord::create([
            'employee_id'     => $this->employee->id,
            'attendance_date' => today(),
            'check_in_time'   => now()->subHours(4),
            'status'          => 'APPROVED',
        ]);

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', [
                'lat'      => $this->latAtDistance(15),
                'lng'      => $this->officeLng(),
                'accuracy' => 10,
            ])
            ->assertSessionHasErrors('general');

        $record = AttendanceRecord::where('employee_id', $this->employee->id)->first();
        $this->assertNull($record->check_out_time);
    }

    // ── 18: check-in and check-out accuracy are both persisted ──────────────

    public function test_record_stores_both_check_in_and_check_out_accuracy(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', [
                'lat'      => $this->latAtDistance(5),
                'lng'      => $this->officeLng(),
                'accuracy' => 5,
                'photo'    => $this->photo(),
            ])
            ->assertRedirect('/attendance/history');

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', [
                'lat'      => $this->latAtDistance(5),
                'lng'      => $this->officeLng(),
                'accuracy' => 7,
            ])
            ->assertRedirect('/attendance/history');

        $record = AttendanceRecord::where('employee_id', $this->employee->id)->first();
        $this->assertEqualsWithDelta(5.0, (float) $record->check_in_accuracy, 0.01);
        $this->assertEqualsWithDelta(7.0, (float) $record->check_out_accuracy, 0.01);
    }

    // ── 19: old attendance rows with NULL accuracy remain readable ──────────

    public function test_old_attendance_record_with_null_accuracy_is_still_readable(): void
    {
        AttendanceRecord::create([
            'employee_id'     => $this->employee->id,
            'attendance_date' => today()->subDay(),
            'check_in_time'   => now()->subDay(),
            'check_in_lat'    => -6.2001000,
            'check_in_lng'    => 106.8166660,
            'status'          => 'APPROVED',
            // check_in_accuracy / check_out_accuracy intentionally omitted (legacy row).
        ]);

        $record = AttendanceRecord::where('employee_id', $this->employee->id)->first();
        $this->assertNull($record->check_in_accuracy);
        $this->assertNull($record->check_out_accuracy);

        $this->actingAs($this->employeeUser)
            ->get('/attendance/history')
            ->assertOk();
    }

    // ── 20: mobile Super Admin form exposes min=20 on the radius input ──────

    public function test_create_location_form_radius_input_has_min_20(): void
    {
        $html = $this->actingAs($this->superAdmin)
            ->get('/settings/locations/create')
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/id="radius_meters"[^>]*min="20"/s', $html);
    }

    public function test_edit_location_form_radius_input_has_min_20(): void
    {
        $html = $this->actingAs($this->superAdmin)
            ->get("/settings/locations/{$this->office->id}/edit")
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/id="radius_meters"[^>]*min="20"/s', $html);
    }
}
