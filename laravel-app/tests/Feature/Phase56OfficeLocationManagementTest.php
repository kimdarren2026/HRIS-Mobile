<?php

namespace Tests\Feature;

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

class Phase56OfficeLocationManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $adminHr;
    private User $financeUser;
    private User $employeeUser;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
        Storage::fake('local');

        $dept     = Department::create(['name' => 'Engineering', 'description' => '']);
        $position = Position::create(['name' => 'Dev', 'department_id' => $dept->id]);

        $this->superAdmin   = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->adminHr      = User::factory()->create(['role' => 'admin_hr',    'is_active' => true]);
        $this->financeUser  = User::factory()->create(['role' => 'finance',     'is_active' => true]);
        $this->employeeUser = User::factory()->create(['role' => 'employee',    'is_active' => true]);

        $this->employee = Employee::create([
            'user_id'             => $this->employeeUser->id,
            'nik'                 => 'P56-EMP-001',
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

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name'          => 'Main Office',
            'latitude'      => -6.2000000,
            'longitude'     => 106.8166660,
            'radius_meters' => 150,
            'is_active'     => '1',
        ], $overrides);
    }

    // ── Authorization: form access ──────────────────────────────────────────

    public function test_super_admin_can_open_create_form(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/settings/locations/create')
            ->assertOk();
    }

    public function test_admin_hr_can_open_create_form(): void
    {
        $this->actingAs($this->adminHr)
            ->get('/settings/locations/create')
            ->assertOk();
    }

    public function test_employee_gets_403_on_create_form(): void
    {
        $this->actingAs($this->employeeUser)
            ->get('/settings/locations/create')
            ->assertForbidden();
    }

    public function test_finance_gets_403_on_create_form(): void
    {
        $this->actingAs($this->financeUser)
            ->get('/settings/locations/create')
            ->assertForbidden();
    }

    // ── Authorization: store/update protected, not just hidden buttons ─────

    public function test_employee_gets_403_on_store(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/settings/locations', $this->validPayload())
            ->assertForbidden();

        $this->assertDatabaseCount('office_locations', 0);
    }

    public function test_finance_gets_403_on_store(): void
    {
        $this->actingAs($this->financeUser)
            ->post('/settings/locations', $this->validPayload())
            ->assertForbidden();

        $this->assertDatabaseCount('office_locations', 0);
    }

    public function test_employee_gets_403_on_update(): void
    {
        $office = OfficeLocation::create($this->activeOfficeAttributes());

        $this->actingAs($this->employeeUser)
            ->put("/settings/locations/{$office->id}", $this->validPayload(['name' => 'Hacked']))
            ->assertForbidden();

        $this->assertDatabaseHas('office_locations', ['id' => $office->id, 'name' => 'Main Office']);
    }

    // ── Create ───────────────────────────────────────────────────────────────

    public function test_super_admin_can_create_office_location(): void
    {
        $this->actingAs($this->superAdmin)
            ->post('/settings/locations', $this->validPayload())
            ->assertRedirect(route('settings.index'));

        $this->assertDatabaseHas('office_locations', [
            'name'          => 'Main Office',
            'radius_meters' => 150,
            'is_active'     => 1,
        ]);
    }

    // ── Update ───────────────────────────────────────────────────────────────

    public function test_admin_hr_can_update_office_location(): void
    {
        $office = OfficeLocation::create($this->activeOfficeAttributes());

        $this->actingAs($this->adminHr)
            ->put("/settings/locations/{$office->id}", $this->validPayload([
                'name'          => 'Updated Office',
                'radius_meters' => 200,
            ]))
            ->assertRedirect(route('settings.index'));

        $office->refresh();
        $this->assertSame('Updated Office', $office->name);
        $this->assertSame(200, $office->radius_meters);
    }

    // ── Validation ───────────────────────────────────────────────────────────

    public function test_store_requires_name(): void
    {
        $this->actingAs($this->superAdmin)
            ->post('/settings/locations', $this->validPayload(['name' => '']))
            ->assertSessionHasErrors('name');
    }

    public function test_store_rejects_latitude_out_of_range(): void
    {
        $this->actingAs($this->superAdmin)
            ->post('/settings/locations', $this->validPayload(['latitude' => 91]))
            ->assertSessionHasErrors('latitude');
    }

    public function test_store_rejects_longitude_out_of_range(): void
    {
        $this->actingAs($this->superAdmin)
            ->post('/settings/locations', $this->validPayload(['longitude' => -181]))
            ->assertSessionHasErrors('longitude');
    }

    public function test_store_rejects_non_positive_radius(): void
    {
        $this->actingAs($this->superAdmin)
            ->post('/settings/locations', $this->validPayload(['radius_meters' => 0]))
            ->assertSessionHasErrors('radius_meters');
    }

    public function test_store_rejects_radius_above_maximum(): void
    {
        $this->actingAs($this->superAdmin)
            ->post('/settings/locations', $this->validPayload(['radius_meters' => 10001]))
            ->assertSessionHasErrors('radius_meters');
    }

    // ── Only one active location at a time ──────────────────────────────────

    public function test_creating_active_location_deactivates_previous_active_location(): void
    {
        $first = OfficeLocation::create($this->activeOfficeAttributes(['name' => 'Old Office']));

        $this->actingAs($this->superAdmin)
            ->post('/settings/locations', $this->validPayload(['name' => 'New Office', 'is_active' => '1']))
            ->assertRedirect(route('settings.index'));

        $first->refresh();
        $this->assertFalse($first->is_active);
        $this->assertSame(1, OfficeLocation::where('is_active', true)->count());
        $this->assertSame('New Office', OfficeLocation::where('is_active', true)->first()->name);
    }

    public function test_activating_office_via_update_deactivates_other_active_location(): void
    {
        $active   = OfficeLocation::create($this->activeOfficeAttributes(['name' => 'Currently Active']));
        $inactive = OfficeLocation::create($this->activeOfficeAttributes(['name' => 'Was Inactive', 'is_active' => false]));

        $this->actingAs($this->superAdmin)
            ->put("/settings/locations/{$inactive->id}", $this->validPayload(['name' => 'Was Inactive', 'is_active' => '1']))
            ->assertRedirect(route('settings.index'));

        $active->refresh();
        $inactive->refresh();
        $this->assertFalse($active->is_active);
        $this->assertTrue($inactive->is_active);
        $this->assertSame(1, OfficeLocation::where('is_active', true)->count());
    }

    public function test_old_locations_are_not_deleted_when_a_new_one_is_activated(): void
    {
        $first = OfficeLocation::create($this->activeOfficeAttributes(['name' => 'Old Office']));

        $this->actingAs($this->superAdmin)
            ->post('/settings/locations', $this->validPayload(['name' => 'New Office']));

        $this->assertDatabaseCount('office_locations', 2);
        $this->assertDatabaseHas('office_locations', ['id' => $first->id, 'name' => 'Old Office']);
    }

    // ── Attendance integration ──────────────────────────────────────────────

    public function test_checkin_is_blocked_with_friendly_message_when_no_active_office_location(): void
    {
        $photo = UploadedFile::fake()->image('selfie.jpg', 200, 200)->size(100);

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', [
                'lat'   => -6.2000000,
                'lng'   => 106.8166660,
                'photo' => $photo,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('general');

        $this->assertDatabaseMissing('attendance_records', ['employee_id' => $this->employee->id]);
    }

    public function test_checkin_uses_the_currently_active_office_location(): void
    {
        // Far from the employee's coordinates — check-in against this one would be out of radius.
        OfficeLocation::create($this->activeOfficeAttributes([
            'name'      => 'Wrong Office',
            'latitude'  => 10.0,
            'longitude' => 10.0,
            'is_active' => false,
        ]));

        // Close to the employee's coordinates and active — check-in should be approved against this one.
        OfficeLocation::create($this->activeOfficeAttributes([
            'name'      => 'Right Office',
            'latitude'  => -6.2000000,
            'longitude' => 106.8166660,
            'is_active' => true,
        ]));

        $photo = UploadedFile::fake()->image('selfie.jpg', 200, 200)->size(100);

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', [
                'lat'   => -6.2001000,
                'lng'   => 106.8166660,
                'photo' => $photo,
            ])
            ->assertRedirect('/attendance/history');

        $this->assertDatabaseHas('attendance_records', [
            'employee_id' => $this->employee->id,
            'status'      => 'APPROVED',
        ]);
    }

    private function activeOfficeAttributes(array $overrides = []): array
    {
        return array_merge([
            'name'          => 'Main Office',
            'latitude'      => -6.2000000,
            'longitude'     => 106.8166660,
            'radius_meters' => 100,
            'is_active'     => true,
        ], $overrides);
    }
}
