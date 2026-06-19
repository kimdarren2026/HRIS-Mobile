<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\OfficeLocation;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $password = Hash::make('password'); // Local demo only.

        $employeeUser = User::create([
            'name' => 'Demo Employee',
            'email' => 'employee@hris.local',
            'password' => $password,
            'role' => 'employee',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Demo Admin HR',
            'email' => 'admin.hr@hris.local',
            'password' => $password,
            'role' => 'admin_hr',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Demo Finance',
            'email' => 'finance@hris.local',
            'password' => $password,
            'role' => 'finance',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Demo Super Admin',
            'email' => 'super.admin@hris.local',
            'password' => $password,
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $departments = [
            'Human Resources' => Department::create([
                'name' => 'Human Resources',
                'description' => 'Demo department for HR operations.',
            ]),
            'Finance' => Department::create([
                'name' => 'Finance',
                'description' => 'Demo department for payroll and finance operations.',
            ]),
            'Engineering' => Department::create([
                'name' => 'Engineering',
                'description' => 'Demo department for product and technical teams.',
            ]),
        ];

        Position::create(['department_id' => $departments['Human Resources']->id, 'name' => 'HR Manager']);
        Position::create(['department_id' => $departments['Human Resources']->id, 'name' => 'HR Officer']);
        Position::create(['department_id' => $departments['Finance']->id, 'name' => 'Payroll Specialist']);
        $softwareEngineer = Position::create([
            'department_id' => $departments['Engineering']->id,
            'name' => 'Software Engineer',
        ]);

        OfficeLocation::create([
            'name' => 'Main Office',
            'latitude' => -6.2000000,
            'longitude' => 106.8166660,
            'radius_meters' => 100,
            'is_active' => true,
        ]);

        $leaveTypes = [
            LeaveType::create(['name' => 'Annual Leave', 'deducts_balance' => true]),
            LeaveType::create(['name' => 'Sick Leave', 'deducts_balance' => true]),
            LeaveType::create(['name' => 'Personal Leave', 'deducts_balance' => true]),
            LeaveType::create(['name' => 'Special Leave', 'deducts_balance' => false]),
        ];

        $employee = Employee::create([
            'user_id' => $employeeUser->id,
            'nik' => 'DEMO-NIK-0001',
            'department_id' => $departments['Engineering']->id,
            'position_id' => $softwareEngineer->id,
            'join_date' => '2026-01-15',
            'employment_status' => 'active',
            'phone_number' => '+620000000001',
            'address' => 'Demo address only',
            'bank_name' => 'Demo Bank',
            'bank_account_number' => '0000000000',
        ]);

        foreach ($leaveTypes as $leaveType) {
            $quota = $leaveType->deducts_balance ? 12 : 0;

            LeaveBalance::create([
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'year' => 2026,
                'total_quota' => $quota,
                'used' => 0,
                'remaining' => $quota,
            ]);
        }
    }
}
