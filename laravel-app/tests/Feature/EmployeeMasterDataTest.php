<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeMasterDataTest extends TestCase
{
    use RefreshDatabase;

    private Department $dept;
    private Position $position;
    private User $hrUser;
    private User $superAdmin;
    private User $financeUser;
    private User $employeeUser;
    private User $otherEmployeeUser;
    private Employee $employee;
    private Employee $otherEmployee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dept     = Department::create(['name' => 'Engineering', 'description' => '']);
        $this->position = Position::create(['name' => 'Developer', 'department_id' => $this->dept->id]);

        $this->hrUser            = User::factory()->create(['role' => 'admin_hr',    'is_active' => true]);
        $this->superAdmin        = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->financeUser       = User::factory()->create(['role' => 'finance',     'is_active' => true]);
        $this->employeeUser      = User::factory()->create(['role' => 'employee',    'is_active' => true]);
        $this->otherEmployeeUser = User::factory()->create(['role' => 'employee',    'is_active' => true]);

        $base = [
            'department_id'     => $this->dept->id,
            'position_id'       => $this->position->id,
            'join_date'         => '2026-01-01',
            'employment_status' => 'active',
            'phone_number'      => '+62812345678',
        ];

        $this->employee      = Employee::create(['user_id' => $this->employeeUser->id,      'nik' => 'EMP-001'] + $base);
        $this->otherEmployee = Employee::create(['user_id' => $this->otherEmployeeUser->id, 'nik' => 'EMP-002'] + $base);
    }

    // ── Employee Directory (index) ─────────────────────────────────────────────

    public function test_admin_hr_can_view_employee_directory(): void
    {
        $this->actingAs($this->hrUser)->get(route('employees.index'))->assertOk();
    }

    public function test_super_admin_can_view_employee_directory(): void
    {
        $this->actingAs($this->superAdmin)->get(route('employees.index'))->assertOk();
    }

    public function test_employee_cannot_access_employee_directory(): void
    {
        $this->actingAs($this->employeeUser)->get(route('employees.index'))->assertForbidden();
    }

    public function test_finance_cannot_access_employee_directory(): void
    {
        $this->actingAs($this->financeUser)->get(route('employees.index'))->assertForbidden();
    }

    public function test_guest_is_redirected_from_employee_directory(): void
    {
        $this->get(route('employees.index'))->assertRedirect('/login');
    }

    public function test_employee_directory_lists_employees(): void
    {
        $this->actingAs($this->hrUser)
            ->get(route('employees.index'))
            ->assertOk()
            ->assertSee('EMP-001')
            ->assertSee('EMP-002');
    }

    public function test_employee_directory_search_filters_by_nik(): void
    {
        $this->actingAs($this->hrUser)
            ->get(route('employees.index', ['search' => 'EMP-001']))
            ->assertOk()
            ->assertSee('EMP-001')
            ->assertDontSee('EMP-002');
    }

    // ── Employee Detail (show) ─────────────────────────────────────────────────

    public function test_admin_hr_can_view_employee_detail(): void
    {
        $this->actingAs($this->hrUser)
            ->get(route('employees.show', $this->employee))
            ->assertOk()
            ->assertSee($this->employee->nik);
    }

    public function test_super_admin_can_view_employee_detail(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('employees.show', $this->employee))
            ->assertOk();
    }

    public function test_employee_cannot_view_another_employee_detail(): void
    {
        $this->actingAs($this->employeeUser)
            ->get(route('employees.show', $this->otherEmployee))
            ->assertForbidden();
    }

    public function test_finance_cannot_view_employee_detail(): void
    {
        $this->actingAs($this->financeUser)
            ->get(route('employees.show', $this->employee))
            ->assertForbidden();
    }

    // ── Employee Create ────────────────────────────────────────────────────────

    public function test_admin_hr_can_access_create_form(): void
    {
        $this->actingAs($this->hrUser)
            ->get(route('employees.create'))
            ->assertOk();
    }

    public function test_employee_cannot_access_create_form(): void
    {
        $this->actingAs($this->employeeUser)
            ->get(route('employees.create'))
            ->assertForbidden();
    }

    public function test_admin_hr_can_create_employee(): void
    {
        $payload = [
            'name'              => 'New Employee',
            'email'             => 'newemployee@hris.local',
            'password'          => 'password123',
            'nik'               => 'EMP-NEW-001',
            'department_id'     => $this->dept->id,
            'position_id'       => $this->position->id,
            'join_date'         => '2026-01-15',
            'employment_status' => 'active',
            'phone_number'      => '+62811111111',
        ];

        $response = $this->actingAs($this->hrUser)->post(route('employees.store'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('employees', ['nik' => 'EMP-NEW-001']);
        $this->assertDatabaseHas('users', ['email' => 'newemployee@hris.local', 'role' => 'employee']);
    }

    public function test_employee_cannot_create_employee(): void
    {
        $this->actingAs($this->employeeUser)
            ->post(route('employees.store'), [])
            ->assertForbidden();
    }

    public function test_create_employee_validates_required_fields(): void
    {
        $this->actingAs($this->hrUser)
            ->post(route('employees.store'), [])
            ->assertSessionHasErrors(['name', 'email', 'password', 'nik', 'department_id', 'position_id', 'join_date', 'employment_status', 'phone_number']);
    }

    public function test_create_employee_validates_unique_email(): void
    {
        $this->actingAs($this->hrUser)->post(route('employees.store'), [
            'name'              => 'Duplicate',
            'email'             => $this->employeeUser->email,
            'password'          => 'password123',
            'nik'               => 'EMP-DUP-001',
            'department_id'     => $this->dept->id,
            'position_id'       => $this->position->id,
            'join_date'         => '2026-01-01',
            'employment_status' => 'active',
            'phone_number'      => '+62811111112',
        ])->assertSessionHasErrors('email');
    }

    public function test_create_employee_validates_unique_nik(): void
    {
        $this->actingAs($this->hrUser)->post(route('employees.store'), [
            'name'              => 'Duplicate NIK',
            'email'             => 'unique@hris.local',
            'password'          => 'password123',
            'nik'               => 'EMP-001',
            'department_id'     => $this->dept->id,
            'position_id'       => $this->position->id,
            'join_date'         => '2026-01-01',
            'employment_status' => 'active',
            'phone_number'      => '+62811111113',
        ])->assertSessionHasErrors('nik');
    }

    // ── Employee Edit/Update ───────────────────────────────────────────────────

    public function test_admin_hr_can_access_edit_form(): void
    {
        $this->actingAs($this->hrUser)
            ->get(route('employees.edit', $this->employee))
            ->assertOk();
    }

    public function test_employee_cannot_access_edit_form(): void
    {
        $this->actingAs($this->employeeUser)
            ->get(route('employees.edit', $this->employee))
            ->assertForbidden();
    }

    public function test_admin_hr_can_update_employee(): void
    {
        $response = $this->actingAs($this->hrUser)->put(route('employees.update', $this->employee), [
            'nik'               => 'EMP-001-UPDATED',
            'department_id'     => $this->dept->id,
            'position_id'       => $this->position->id,
            'join_date'         => '2026-02-01',
            'employment_status' => 'probation',
            'phone_number'      => '+62899999999',
        ]);

        $response->assertRedirect(route('employees.show', $this->employee));
        $this->assertDatabaseHas('employees', [
            'id'                => $this->employee->id,
            'nik'               => 'EMP-001-UPDATED',
            'employment_status' => 'probation',
        ]);
    }

    public function test_employee_cannot_update_employee(): void
    {
        $this->actingAs($this->employeeUser)
            ->put(route('employees.update', $this->employee), [])
            ->assertForbidden();
    }

    public function test_update_nik_unique_ignores_self(): void
    {
        $response = $this->actingAs($this->hrUser)->put(route('employees.update', $this->employee), [
            'nik'               => 'EMP-001',
            'department_id'     => $this->dept->id,
            'position_id'       => $this->position->id,
            'join_date'         => '2026-01-01',
            'employment_status' => 'active',
            'phone_number'      => '+62812345678',
        ]);

        $response->assertRedirect(route('employees.show', $this->employee));
        $this->assertDatabaseHas('employees', ['id' => $this->employee->id, 'nik' => 'EMP-001']);
    }

    // ── Employee Self Profile ──────────────────────────────────────────────────

    public function test_employee_can_view_own_profile(): void
    {
        $this->actingAs($this->employeeUser)
            ->get(route('my.profile'))
            ->assertOk()
            ->assertSee($this->employee->nik);
    }

    public function test_admin_hr_cannot_access_my_profile_route(): void
    {
        $this->actingAs($this->hrUser)
            ->get(route('my.profile'))
            ->assertForbidden();
    }

    public function test_my_profile_returns_403_if_no_employee_record(): void
    {
        $userWithoutEmployee = User::factory()->create(['role' => 'employee', 'is_active' => true]);

        $this->actingAs($userWithoutEmployee)
            ->get(route('my.profile'))
            ->assertForbidden();
    }

    // ── Policy: employee cannot view other employee via employees.show ─────────

    public function test_employee_cannot_view_other_via_employees_show(): void
    {
        // employees.show is under admin_hr,super_admin middleware — employee gets 403 from middleware
        $this->actingAs($this->employeeUser)
            ->get(route('employees.show', $this->otherEmployee))
            ->assertForbidden();
    }
}
