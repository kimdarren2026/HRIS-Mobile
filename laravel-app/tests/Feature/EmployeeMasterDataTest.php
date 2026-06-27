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

    // ── Search by name ─────────────────────────────────────────────────────────

    public function test_search_by_name_returns_matching_employee(): void
    {
        $user = User::factory()->create(['name' => 'ZZSEARCHNAME_Alpha', 'role' => 'employee', 'is_active' => true]);
        $emp  = Employee::create([
            'user_id' => $user->id, 'nik' => 'EMP-SRCHNAME-01',
            'department_id' => $this->dept->id, 'position_id' => $this->position->id,
            'join_date' => '2026-01-01', 'employment_status' => 'active', 'phone_number' => '+62800000001',
        ]);

        $this->actingAs($this->hrUser)
            ->get(route('employees.index', ['search' => 'ZZSEARCHNAME_Alpha']))
            ->assertOk()
            ->assertSee('EMP-SRCHNAME-01')
            ->assertDontSee('EMP-001')
            ->assertDontSee('EMP-002');
    }

    // ── Search by email ────────────────────────────────────────────────────────

    public function test_search_by_email_returns_matching_employee(): void
    {
        $user = User::factory()->create([
            'name' => 'ZZSRCHEMAIL User', 'email' => 'zzsrchemail_unique@hris.local',
            'role' => 'employee', 'is_active' => true,
        ]);
        Employee::create([
            'user_id' => $user->id, 'nik' => 'EMP-SRCHEMAIL-01',
            'department_id' => $this->dept->id, 'position_id' => $this->position->id,
            'join_date' => '2026-01-01', 'employment_status' => 'active', 'phone_number' => '+62800000002',
        ]);

        $this->actingAs($this->hrUser)
            ->get(route('employees.index', ['search' => 'zzsrchemail_unique']))
            ->assertOk()
            ->assertSee('EMP-SRCHEMAIL-01')
            ->assertDontSee('EMP-001')
            ->assertDontSee('EMP-002');
    }

    // ── Search by position ─────────────────────────────────────────────────────

    public function test_search_by_position_returns_matching_employee(): void
    {
        $posManager = Position::create(['name' => 'ZZSRCHPOS_Manager', 'department_id' => $this->dept->id]);
        $user       = User::factory()->create(['name' => 'Position Search User', 'role' => 'employee', 'is_active' => true]);
        Employee::create([
            'user_id' => $user->id, 'nik' => 'EMP-SRCHPOS-01',
            'department_id' => $this->dept->id, 'position_id' => $posManager->id,
            'join_date' => '2026-01-01', 'employment_status' => 'active', 'phone_number' => '+62800000003',
        ]);

        $this->actingAs($this->hrUser)
            ->get(route('employees.index', ['search' => 'ZZSRCHPOS_Manager']))
            ->assertOk()
            ->assertSee('EMP-SRCHPOS-01')
            ->assertDontSee('EMP-001')
            ->assertDontSee('EMP-002');
    }

    // ── Department filter restricts all search results ─────────────────────────

    public function test_department_filter_restricts_all_search_results(): void
    {
        $dept2 = Department::create(['name' => 'ZZDEPT2_Test', 'description' => '']);

        $u1 = User::factory()->create(['role' => 'employee', 'is_active' => true]);
        $u2 = User::factory()->create(['role' => 'employee', 'is_active' => true]);

        Employee::create([
            'user_id' => $u1->id, 'nik' => 'EMP-DEPTF-DEPT1',
            'department_id' => $this->dept->id, 'position_id' => $this->position->id,
            'join_date' => '2026-01-01', 'employment_status' => 'active', 'phone_number' => '+62800000010',
        ]);
        Employee::create([
            'user_id' => $u2->id, 'nik' => 'EMP-DEPTF-DEPT2',
            'department_id' => $dept2->id, 'position_id' => $this->position->id,
            'join_date' => '2026-01-01', 'employment_status' => 'active', 'phone_number' => '+62800000011',
        ]);

        // Both NIKs start with EMP-DEPTF; department filter must exclude the dept2 employee
        $this->actingAs($this->hrUser)
            ->get(route('employees.index', ['search' => 'EMP-DEPTF', 'department_id' => $this->dept->id]))
            ->assertOk()
            ->assertSee('EMP-DEPTF-DEPT1')
            ->assertDontSee('EMP-DEPTF-DEPT2');
    }

    // ── Status filter restricts all search results ─────────────────────────────

    public function test_status_filter_restricts_all_search_results(): void
    {
        $u1 = User::factory()->create(['name' => 'ZZSTATUSF Alpha', 'role' => 'employee', 'is_active' => true]);
        $u2 = User::factory()->create(['name' => 'ZZSTATUSF Beta',  'role' => 'employee', 'is_active' => true]);

        Employee::create([
            'user_id' => $u1->id, 'nik' => 'EMP-STATUSF-ACTIVE',
            'department_id' => $this->dept->id, 'position_id' => $this->position->id,
            'join_date' => '2026-01-01', 'employment_status' => 'active', 'phone_number' => '+62800000020',
        ]);
        Employee::create([
            'user_id' => $u2->id, 'nik' => 'EMP-STATUSF-RESIGNED',
            'department_id' => $this->dept->id, 'position_id' => $this->position->id,
            'join_date' => '2026-01-01', 'employment_status' => 'resigned', 'phone_number' => '+62800000021',
        ]);

        // Both names contain ZZSTATUSF; status filter must exclude the resigned employee
        $this->actingAs($this->hrUser)
            ->get(route('employees.index', ['search' => 'ZZSTATUSF', 'status' => 'active']))
            ->assertOk()
            ->assertSee('EMP-STATUSF-ACTIVE')
            ->assertDontSee('EMP-STATUSF-RESIGNED');
    }

    // ── Index contains View Detail and Edit links ──────────────────────────────

    public function test_index_shows_view_detail_and_edit_links_for_each_employee(): void
    {
        $this->actingAs($this->hrUser)
            ->get(route('employees.index'))
            ->assertOk()
            ->assertSee(route('employees.show', $this->employee), false)
            ->assertSee(route('employees.edit', $this->employee), false)
            ->assertSee(route('employees.show', $this->otherEmployee), false)
            ->assertSee(route('employees.edit', $this->otherEmployee), false);
    }

    // ── Finance 403 on all employee management write routes ────────────────────

    public function test_finance_cannot_access_employee_edit_form(): void
    {
        $this->actingAs($this->financeUser)
            ->get(route('employees.edit', $this->employee))
            ->assertForbidden();
    }

    public function test_finance_cannot_update_employee(): void
    {
        $this->actingAs($this->financeUser)
            ->put(route('employees.update', $this->employee), [])
            ->assertForbidden();
    }

    public function test_finance_cannot_access_employee_create_form(): void
    {
        $this->actingAs($this->financeUser)
            ->get(route('employees.create'))
            ->assertForbidden();
    }

    public function test_finance_cannot_store_employee(): void
    {
        $this->actingAs($this->financeUser)
            ->post(route('employees.store'), [])
            ->assertForbidden();
    }

    // ── Employee 403 on all employee management write routes ───────────────────

    public function test_employee_cannot_access_employee_create_form_via_direct_route(): void
    {
        $this->actingAs($this->employeeUser)
            ->get(route('employees.create'))
            ->assertForbidden();
    }
}
