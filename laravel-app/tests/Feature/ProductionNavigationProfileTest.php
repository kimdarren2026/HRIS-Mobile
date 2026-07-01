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
            ->assertSee('Profil dikelola oleh HR')
            ->assertSee('Hubungi HR untuk memperbarui data pribadi atau data terkait penggajian')
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

    public function test_finance_dashboard_shows_attendance_link_when_employee_is_linked(): void
    {
        $finance = User::factory()->create(['role' => 'finance', 'is_active' => true]);
        Employee::factory()->create(['user_id' => $finance->id]);

        $this->actingAs($finance)
            ->get('/finance/dashboard')
            ->assertOk()
            ->assertSee('/attendance/checkin', false)
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

    public function test_finance_without_employee_cannot_access_attendance_self_service(): void
    {
        $finance = User::factory()->create(['role' => 'finance', 'is_active' => true]);

        $this->actingAs($finance)->get('/attendance/checkin')->assertForbidden();
    }

    public function test_finance_with_employee_can_access_attendance_but_not_hr_routes(): void
    {
        $finance = User::factory()->create(['role' => 'finance', 'is_active' => true]);
        Employee::factory()->create(['user_id' => $finance->id]);

        $this->actingAs($finance)->get('/attendance/checkin')->assertOk();
        $this->actingAs($finance)->get('/hr/approval-queue')->assertForbidden();
    }
}
