<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\LeaveType;
use App\Models\Position;
use Illuminate\Database\Seeder;

/**
 * ProductionSeeder: safe for production — no demo users, no demo passwords, no hardcoded credentials.
 * Seeds only essential master data (departments, positions, leave types).
 * Run with: php artisan db:seed --class=ProductionSeeder
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            'Human Resources' => 'Core HR operations',
            'Finance'         => 'Finance, payroll, and accounting',
            'IT'              => 'Information technology',
            'General'         => 'General administration',
        ];

        $created = [];
        foreach ($departments as $name => $description) {
            $created[$name] = Department::firstOrCreate(
                ['name' => $name],
                ['description' => $description],
            );
        }

        $positions = [
            'Human Resources' => ['HR Admin', 'HR Officer', 'HR Manager'],
            'Finance'         => ['Finance Staff', 'Finance Officer', 'Finance Manager'],
            'IT'              => ['IT Staff', 'IT Officer', 'IT Manager'],
            'General'         => ['Staff', 'Officer', 'Manager'],
        ];

        foreach ($positions as $deptName => $titles) {
            $dept = $created[$deptName] ?? null;
            if (! $dept) {
                continue;
            }
            foreach ($titles as $title) {
                Position::firstOrCreate([
                    'department_id' => $dept->id,
                    'name'          => $title,
                ]);
            }
        }

        $leaveTypes = [
            ['name' => 'Annual Leave',  'deducts_balance' => true],
            ['name' => 'Sick Leave',    'deducts_balance' => true],
            ['name' => 'Permission',    'deducts_balance' => true],
            ['name' => 'Special Leave', 'deducts_balance' => false],
        ];

        foreach ($leaveTypes as $lt) {
            LeaveType::firstOrCreate(
                ['name' => $lt['name']],
                ['deducts_balance' => $lt['deducts_balance']],
            );
        }
    }
}
