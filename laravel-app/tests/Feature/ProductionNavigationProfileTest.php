<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionNavigationProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_profile_shows_hr_managed_message_without_edit_profile_action(): void
    {
        $user = User::factory()->create(['role' => 'employee', 'is_active' => true]);
        Employee::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get('/my/profile')
            ->assertOk()
            ->assertSee('Profile updates are managed by HR')
            ->assertSee('Contact HR to update personal or payroll-related data')
            ->assertDontSee('Edit Profile');
    }

    public function test_profile_route_redirects_employee_to_my_profile(): void
    {
        $user = User::factory()->create(['role' => 'employee', 'is_active' => true]);
        Employee::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get('/profile')
            ->assertRedirect(route('my.profile'));
    }

    public function test_admin_hr_employee_edit_route_still_works(): void
    {
        $hrUser = User::factory()->create(['role' => 'admin_hr', 'is_active' => true]);
        $employee = Employee::factory()->create();

        $this->actingAs($hrUser)
            ->get(route('employees.edit', $employee))
            ->assertOk();
    }

    public function test_finance_dashboard_nav_does_not_render_forbidden_links(): void
    {
        $finance = User::factory()->create(['role' => 'finance', 'is_active' => true]);

        $this->actingAs($finance)
            ->get('/finance/dashboard')
            ->assertOk()
            ->assertDontSee('/attendance', false)
            ->assertDontSee('/hr/approval-queue', false)
            ->assertDontSee('/hr/employees', false);
    }

    public function test_finance_shared_pages_do_not_render_hr_or_attendance_nav_links(): void
    {
        $finance = User::factory()->create(['role' => 'finance', 'is_active' => true]);

        foreach (['/payroll/periods', '/reports', '/profile'] as $path) {
            $this->actingAs($finance)
                ->get($path)
                ->assertOk()
                ->assertDontSee('/attendance', false)
                ->assertDontSee('/hr/approval-queue', false)
                ->assertDontSee('/hr/employees', false);
        }
    }

    public function test_finance_direct_access_to_employee_and_hr_routes_still_returns_403(): void
    {
        $finance = User::factory()->create(['role' => 'finance', 'is_active' => true]);

        $this->actingAs($finance)->get('/attendance/checkin')->assertForbidden();
        $this->actingAs($finance)->get('/hr/approval-queue')->assertForbidden();
    }
}
