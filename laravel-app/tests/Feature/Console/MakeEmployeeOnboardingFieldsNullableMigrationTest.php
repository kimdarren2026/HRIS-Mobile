<?php

namespace Tests\Feature\Console;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class MakeEmployeeOnboardingFieldsNullableMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function downMigration(): object
    {
        return require database_path('migrations/2026_07_28_000002_make_employee_onboarding_fields_nullable.php');
    }

    // Phase 58D correction #3: rollback refuses to run when NULL data would make it unsafe.
    public function test_migration_down_fails_safely_when_nullable_data_exists(): void
    {
        $department = Department::create(['name' => 'Synthetic Dept', 'description' => '']);
        $position = Position::create(['name' => 'Synthetic Role', 'department_id' => $department->id]);
        $user = User::factory()->create(['role' => 'employee']);

        $employee = Employee::create([
            'user_id' => $user->id,
            'nik' => null,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'join_date' => '2026-01-01',
            'employment_status' => 'active',
            'phone_number' => null,
        ]);

        $caught = null;

        try {
            $this->downMigration()->down();
        } catch (RuntimeException $e) {
            $caught = $e;
        }

        $this->assertNotNull($caught, 'Expected RuntimeException was not thrown.');
        $this->assertStringContainsString('NULL', $caught->getMessage());

        $fresh = $employee->fresh();
        $this->assertNotNull($fresh);
        $this->assertNull($fresh->nik);
        $this->assertNull($fresh->phone_number);
    }

    // Phase 58D correction #3: rollback succeeds and re-enforces NOT NULL when data is complete.
    public function test_migration_down_can_restore_not_null_when_all_data_complete(): void
    {
        $this->assertSame(0, Employee::query()
            ->where(function ($query) {
                $query->orWhereNull('nik')
                    ->orWhereNull('department_id')
                    ->orWhereNull('position_id')
                    ->orWhereNull('join_date')
                    ->orWhereNull('phone_number');
            })
            ->count());

        $this->downMigration()->down();

        $this->expectException(QueryException::class);

        $department = Department::create(['name' => 'Post-Rollback Dept', 'description' => '']);
        $position = Position::create(['name' => 'Post-Rollback Role', 'department_id' => $department->id]);
        $user = User::factory()->create(['role' => 'employee']);

        Employee::create([
            'user_id' => $user->id,
            'nik' => null,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'join_date' => '2026-01-01',
            'employment_status' => 'active',
            'phone_number' => null,
        ]);
    }
}
