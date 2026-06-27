<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase29RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $target;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'role'      => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
        ]);

        $this->target = User::factory()->create([
            'role'      => User::ROLE_EMPLOYEE,
            'is_active' => true,
        ]);
    }

    // ── Access control ────────────────────────────────────────────────────────

    public function test_super_admin_can_access_user_management(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('admin.users.index'))
            ->assertOk();
    }

    public function test_employee_cannot_access_user_management(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_EMPLOYEE, 'is_active' => true]);
        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_admin_hr_cannot_access_user_management(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN_HR, 'is_active' => true]);
        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_finance_cannot_access_user_management(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_FINANCE, 'is_active' => true]);
        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
    }

    // ── Role update ───────────────────────────────────────────────────────────

    public function test_super_admin_can_change_employee_to_finance(): void
    {
        $this->actingAs($this->superAdmin)
            ->patch(route('admin.users.update-role', $this->target), ['role' => User::ROLE_FINANCE])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id'   => $this->target->id,
            'role' => User::ROLE_FINANCE,
        ]);
    }

    public function test_super_admin_can_change_finance_to_admin_hr(): void
    {
        $this->target->update(['role' => User::ROLE_FINANCE]);

        $this->actingAs($this->superAdmin)
            ->patch(route('admin.users.update-role', $this->target), ['role' => User::ROLE_ADMIN_HR])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id'   => $this->target->id,
            'role' => User::ROLE_ADMIN_HR,
        ]);
    }

    public function test_invalid_role_is_rejected(): void
    {
        $this->actingAs($this->superAdmin)
            ->patch(route('admin.users.update-role', $this->target), ['role' => 'god_mode'])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseHas('users', ['id' => $this->target->id, 'role' => User::ROLE_EMPLOYEE]);
    }

    // ── Last super_admin guard ────────────────────────────────────────────────

    public function test_cannot_demote_last_super_admin(): void
    {
        // Only one super_admin (superAdmin itself)
        $this->actingAs($this->superAdmin)
            ->patch(route('admin.users.update-role', $this->superAdmin), ['role' => User::ROLE_EMPLOYEE])
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $this->superAdmin->id, 'role' => User::ROLE_SUPER_ADMIN]);
        $this->assertSessionHasError('Cannot demote the last super_admin.');
    }

    public function test_cannot_deactivate_last_super_admin(): void
    {
        $this->actingAs($this->superAdmin)
            ->patch(route('admin.users.update-status', $this->superAdmin), ['is_active' => '0'])
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $this->superAdmin->id, 'is_active' => true]);
        $this->assertSessionHasError('Cannot deactivate the last super_admin.');
    }

    public function test_can_demote_when_another_super_admin_exists(): void
    {
        User::factory()->create(['role' => User::ROLE_SUPER_ADMIN, 'is_active' => true]);

        $this->actingAs($this->superAdmin)
            ->patch(route('admin.users.update-role', $this->superAdmin), ['role' => User::ROLE_EMPLOYEE])
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $this->superAdmin->id, 'role' => User::ROLE_EMPLOYEE]);
    }

    // ── Status update ─────────────────────────────────────────────────────────

    public function test_super_admin_can_deactivate_employee(): void
    {
        $this->actingAs($this->superAdmin)
            ->patch(route('admin.users.update-status', $this->target), ['is_active' => '0'])
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $this->target->id, 'is_active' => false]);
    }

    public function test_super_admin_can_activate_inactive_user(): void
    {
        $this->target->update(['is_active' => false]);

        $this->actingAs($this->superAdmin)
            ->patch(route('admin.users.update-status', $this->target), ['is_active' => '1'])
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $this->target->id, 'is_active' => true]);
    }

    // ── Audit log ─────────────────────────────────────────────────────────────

    public function test_audit_log_recorded_on_role_change(): void
    {
        $this->actingAs($this->superAdmin)
            ->patch(route('admin.users.update-role', $this->target), ['role' => User::ROLE_FINANCE]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id'        => $this->superAdmin->id,
            'action'         => 'UPDATE_ROLE',
            'module'         => 'user_management',
            'auditable_type' => User::class,
            'auditable_id'   => $this->target->id,
        ]);
    }

    public function test_audit_log_recorded_on_status_change(): void
    {
        $this->actingAs($this->superAdmin)
            ->patch(route('admin.users.update-status', $this->target), ['is_active' => '0']);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->superAdmin->id,
            'action'  => 'UPDATE_STATUS',
            'module'  => 'user_management',
        ]);
    }

    // ── Unauthorized role update attempt ─────────────────────────────────────

    public function test_employee_cannot_update_role(): void
    {
        $employee = User::factory()->create(['role' => User::ROLE_EMPLOYEE, 'is_active' => true]);

        $this->actingAs($employee)
            ->patch(route('admin.users.update-role', $this->target), ['role' => User::ROLE_SUPER_ADMIN])
            ->assertForbidden();
    }

    // ── ProductionSeeder ──────────────────────────────────────────────────────

    public function test_production_seeder_does_not_create_demo_users(): void
    {
        $this->seed(ProductionSeeder::class);

        $this->assertDatabaseMissing('users', ['email' => 'employee@hris.local']);
        $this->assertDatabaseMissing('users', ['email' => 'admin.hr@hris.local']);
        $this->assertDatabaseMissing('users', ['email' => 'finance@hris.local']);
        $this->assertDatabaseMissing('users', ['email' => 'super.admin@hris.local']);
    }

    public function test_production_seeder_creates_departments(): void
    {
        $this->seed(ProductionSeeder::class);

        foreach (['Human Resources', 'Finance', 'IT', 'General'] as $name) {
            $this->assertDatabaseHas('departments', ['name' => $name]);
        }
    }

    public function test_production_seeder_creates_leave_types(): void
    {
        $this->seed(ProductionSeeder::class);

        foreach (['Annual Leave', 'Sick Leave', 'Permission', 'Special Leave'] as $name) {
            $this->assertDatabaseHas('leave_types', ['name' => $name]);
        }
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function assertSessionHasError(string $message): void
    {
        $this->assertStringContainsString(
            $message,
            session('error') ?? '',
        );
    }
}
