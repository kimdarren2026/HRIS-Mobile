<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthRoleRoutingTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('demoLoginCases')]
    public function test_demo_users_login_to_role_dashboards(string $email, string $redirect): void
    {
        $this->seed();

        $response = $this->post('/login', [
            'email' => $email,
            'password' => 'password',
        ]);

        $response->assertRedirect($redirect);
        $this->assertAuthenticated();
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'inactive@hris.local',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'is_active' => false,
        ]);

        $this->post('/login', [
            'email' => 'inactive@hris.local',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    #[DataProvider('allowedRouteCases')]
    public function test_authenticated_roles_can_open_allowed_static_pages(string $role, string $path): void
    {
        $user = User::factory()->create([
            'role' => $role,
            'is_active' => true,
        ]);

        if (str_starts_with($path, '/leave/') || $path === '/my/profile') {
            Employee::factory()->create(['user_id' => $user->id]);
        }

        $response = $this->actingAs($user)->get($path);

        $response->assertOk();
        $this->assertNotEmpty($response->getContent());
    }

    #[DataProvider('forbiddenRouteCases')]
    public function test_wrong_roles_are_forbidden(string $role, string $path): void
    {
        $user = User::factory()->create([
            'role' => $role,
            'is_active' => true,
        ]);

        $this->actingAs($user)->get($path)->assertForbidden();
    }

    public function test_login_page_redirects_authenticated_user_by_role(): void
    {
        $user = User::factory()->create([
            'role' => 'finance',
            'is_active' => true,
        ]);

        $this->actingAs($user)->get('/login')->assertRedirect('/finance/dashboard');
    }

    public function test_logout_invalidates_session(): void
    {
        $user = User::factory()->create([
            'role' => 'employee',
            'is_active' => true,
        ]);

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public static function demoLoginCases(): array
    {
        return [
            ['employee@hris.local', '/employee/dashboard'],
            ['admin.hr@hris.local', '/admin/dashboard'],
            ['finance@hris.local', '/finance/dashboard'],
            ['super.admin@hris.local', '/admin/dashboard'],
        ];
    }

    public static function allowedRouteCases(): array
    {
        return [
            ['employee', '/employee/dashboard'],
            ['employee', '/attendance/checkin'],
            ['employee', '/attendance/checkin-outside'],
            ['employee', '/attendance/history'],
            ['employee', '/leave/request'],
            ['employee', '/leave/history'],
            ['employee', '/payslip/detail'],
            ['employee', '/my/profile'],
            ['admin_hr', '/admin/dashboard'],
            ['admin_hr', '/hr/approval-queue'],
            ['admin_hr', '/hr/employees'],
            ['admin_hr', '/employees'],
            ['admin_hr', '/reports'],
            ['admin_hr', '/settings'],
            ['admin_hr', '/profile'],
            ['finance', '/finance/dashboard'],
            ['finance', '/payroll/periods'],
            ['finance', '/reports'],
            ['finance', '/profile'],
            ['super_admin', '/admin/dashboard'],
            ['super_admin', '/hr/approval-queue'],
            ['super_admin', '/hr/employees'],
            ['super_admin', '/employees'],
            ['super_admin', '/finance/dashboard'],
            ['super_admin', '/payroll/periods'],
            ['super_admin', '/reports'],
            ['super_admin', '/settings'],
            ['super_admin', '/profile'],
        ];
    }

    public static function forbiddenRouteCases(): array
    {
        return [
            ['employee', '/admin/dashboard'],
            ['employee', '/finance/dashboard'],
            ['employee', '/employees'],
            ['admin_hr', '/employee/dashboard'],
            ['admin_hr', '/finance/dashboard'],
            ['finance', '/employee/dashboard'],
            ['finance', '/hr/employees'],
            ['finance', '/employees'],
            ['super_admin', '/employee/dashboard'],
        ];
    }
}
