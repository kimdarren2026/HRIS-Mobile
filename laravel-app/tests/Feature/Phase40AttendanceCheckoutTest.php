<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\OfficeLocation;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

class Phase40AttendanceCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private User $employeeUser;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);

        $dept     = Department::create(['name' => 'Engineering', 'description' => '']);
        $position = Position::create(['name' => 'Dev', 'department_id' => $dept->id]);

        $this->employeeUser = User::factory()->create([
            'role'      => 'employee',
            'is_active' => true,
        ]);

        $this->employee = Employee::create([
            'user_id'             => $this->employeeUser->id,
            'nik'                 => 'NIK-P40-001',
            'department_id'       => $dept->id,
            'position_id'         => $position->id,
            'join_date'           => '2026-01-01',
            'employment_status'   => 'active',
            'phone_number'        => '+62812345678',
            'address'             => 'Test Address',
            'bank_name'           => 'Test Bank',
            'bank_account_number' => '1234567890',
        ]);

        OfficeLocation::create([
            'name'          => 'Main Office',
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

    private function makeApprovedRecord(): AttendanceRecord
    {
        return AttendanceRecord::create([
            'employee_id'     => $this->employee->id,
            'attendance_date' => today(),
            'check_in_time'   => now()->subHours(8),
            'check_in_lat'    => -6.2001000,
            'check_in_lng'    => 106.8166660,
            'status'          => 'APPROVED',
        ]);
    }

    private function makePendingRecord(): AttendanceRecord
    {
        return AttendanceRecord::create([
            'employee_id'          => $this->employee->id,
            'attendance_date'      => today(),
            'check_in_time'        => now()->subHours(8),
            'check_in_lat'         => -6.2100000,
            'check_in_lng'         => 106.8166660,
            'status'               => 'PENDING_REVIEW',
            'out_of_radius_reason' => 'Working from client site today.',
        ]);
    }

    // ── Page state ────────────────────────────────────────────────────────────

    public function test_checkin_page_shows_checkin_form_when_no_record_today(): void
    {
        $this->actingAs($this->employeeUser)
            ->get('/attendance/checkin')
            ->assertOk()
            ->assertSee('id="checkin-form"', false);
    }

    public function test_checkin_page_shows_checkout_form_when_checked_in_not_yet_checked_out(): void
    {
        $this->makeApprovedRecord();

        $this->actingAs($this->employeeUser)
            ->get('/attendance/checkin')
            ->assertOk()
            // id="checkout-form" (double-quoted HTML attr) is distinct from JS getElementById('checkout-form')
            ->assertSee('id="checkout-form"', false)
            ->assertSee('Absen Pulang')
            ->assertDontSee('id="checkin-form"', false);
    }

    public function test_checkin_page_shows_checkout_form_for_pending_review_record(): void
    {
        $this->makePendingRecord();

        $this->actingAs($this->employeeUser)
            ->get('/attendance/checkin')
            ->assertOk()
            ->assertSee('id="checkout-form"', false)
            ->assertSee('Absen Pulang')
            ->assertSee('Menunggu Review HR');
    }

    public function test_checkin_page_shows_done_state_when_already_checked_out(): void
    {
        $record = $this->makeApprovedRecord();
        $record->update([
            'check_out_time' => now(),
            'check_out_lat'  => -6.2001000,
            'check_out_lng'  => 106.8166660,
        ]);

        $this->actingAs($this->employeeUser)
            ->get('/attendance/checkin')
            ->assertOk()
            ->assertSee('Presensi Selesai')
            ->assertDontSee('id="checkout-form"', false)
            ->assertDontSee('id="checkin-form"', false);
    }

    // ── Checkout POST success ─────────────────────────────────────────────────

    public function test_checkout_approved_record_succeeds_and_redirects(): void
    {
        $this->makeApprovedRecord();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', $this->coordsWithin())
            ->assertRedirect('/attendance/history');
    }

    public function test_checkout_approved_record_stores_coords(): void
    {
        $this->makeApprovedRecord();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', $this->coordsWithin());

        $record = AttendanceRecord::where('employee_id', $this->employee->id)->first();
        $this->assertNotNull($record->check_out_time);
        $this->assertNotNull($record->check_out_lat);
        $this->assertNotNull($record->check_out_lng);
    }

    public function test_checkout_pending_review_record_succeeds_and_status_unchanged(): void
    {
        $this->makePendingRecord();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', $this->coordsWithin())
            ->assertRedirect('/attendance/history');

        $this->assertDatabaseHas('attendance_records', [
            'employee_id' => $this->employee->id,
            'status'      => 'PENDING_REVIEW',
        ]);

        $record = AttendanceRecord::where('employee_id', $this->employee->id)->first();
        $this->assertNotNull($record->check_out_time);
    }

    public function test_checkout_coords_are_persisted_correctly(): void
    {
        $this->makeApprovedRecord();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', ['lat' => -6.2050000, 'lng' => 106.8200000]);

        $record = AttendanceRecord::where('employee_id', $this->employee->id)->first();
        $this->assertEqualsWithDelta(-6.2050000, (float) $record->check_out_lat, 0.00001);
        $this->assertEqualsWithDelta(106.8200000, (float) $record->check_out_lng, 0.00001);
    }

    // ── Checkout POST failure ─────────────────────────────────────────────────

    public function test_checkout_without_prior_checkin_fails(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', $this->coordsWithin())
            ->assertSessionHasErrors('general');
    }

    public function test_duplicate_checkout_is_rejected(): void
    {
        $record = $this->makeApprovedRecord();
        $record->update([
            'check_out_time' => now(),
            'check_out_lat'  => -6.2001000,
            'check_out_lng'  => 106.8166660,
        ]);

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', $this->coordsWithin())
            ->assertSessionHasErrors('general');
    }

    public function test_checkout_missing_lat_fails_validation(): void
    {
        $this->makeApprovedRecord();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', ['lng' => 106.8166660])
            ->assertSessionHasErrors('lat');
    }

    public function test_checkout_missing_lng_fails_validation(): void
    {
        $this->makeApprovedRecord();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', ['lat' => -6.2001000])
            ->assertSessionHasErrors('lng');
    }

    public function test_checkout_invalid_lat_fails_validation(): void
    {
        $this->makeApprovedRecord();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', ['lat' => 99.999, 'lng' => 106.8166660])
            ->assertSessionHasErrors('lat');
    }

    public function test_checkout_invalid_lng_fails_validation(): void
    {
        $this->makeApprovedRecord();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', ['lat' => -6.2001000, 'lng' => 200.0])
            ->assertSessionHasErrors('lng');
    }

    public function test_guest_cannot_checkout(): void
    {
        $this->post('/attendance/check-out', $this->coordsWithin())
            ->assertRedirect('/login');
    }

    // ── History checkout display ──────────────────────────────────────────────

    public function test_history_shows_checkout_time(): void
    {
        $record = $this->makeApprovedRecord();
        $record->update([
            'check_out_time' => now()->setTime(17, 30),
            'check_out_lat'  => -6.2001000,
            'check_out_lng'  => 106.8166660,
        ]);

        $this->actingAs($this->employeeUser)
            ->get('/attendance/history')
            ->assertOk()
            ->assertSee('17:30');
    }

    public function test_history_shows_checkout_coords_when_present(): void
    {
        $record = $this->makeApprovedRecord();
        $record->update([
            'check_out_time' => now(),
            'check_out_lat'  => -6.20010,
            'check_out_lng'  => 106.81667,
        ]);

        $this->actingAs($this->employeeUser)
            ->get('/attendance/history')
            ->assertOk()
            ->assertSee('-6.20010')
            ->assertSee('106.81667');
    }

    public function test_history_does_not_show_checkout_coords_when_not_checked_out(): void
    {
        $this->makeApprovedRecord();

        $html = $this->actingAs($this->employeeUser)
            ->get('/attendance/history')
            ->assertOk()
            ->getContent();

        // check_out_lat is null — coords section should not be rendered
        $this->assertStringNotContainsString('check_out_lat', $html);
    }
}
