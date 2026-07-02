<?php

// ⚠️  LOCAL / TESTING ONLY — do NOT run on production. Creates demo accounts with weak passwords.
// For production master data, use: php artisan db:seed --class=ProductionSeeder

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\OfficeLocation;
use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Models\Payslip;
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

        $employeeUser = User::updateOrCreate(
            ['email' => 'employee@hris.local'],
            ['name' => 'Demo Employee', 'password' => $password, 'role' => 'employee', 'is_active' => true]
        );

        $adminUser = User::updateOrCreate(
            ['email' => 'admin.hr@hris.local'],
            ['name' => 'Demo Admin HR', 'password' => $password, 'role' => 'admin_hr', 'is_active' => true]
        );

        $financeUser = User::updateOrCreate(
            ['email' => 'finance@hris.local'],
            ['name' => 'Demo Finance', 'password' => $password, 'role' => 'finance', 'is_active' => true]
        );

        $superAdminUser = User::updateOrCreate(
            ['email' => 'super.admin@hris.local'],
            ['name' => 'Demo Super Admin', 'password' => $password, 'role' => 'super_admin', 'is_active' => true]
        );

        $departments = [
            'Human Resources' => Department::updateOrCreate(
                ['name' => 'Human Resources'],
                ['description' => 'Demo department for HR operations.']
            ),
            'Finance' => Department::updateOrCreate(
                ['name' => 'Finance'],
                ['description' => 'Demo department for payroll and finance operations.']
            ),
            'Engineering' => Department::updateOrCreate(
                ['name' => 'Engineering'],
                ['description' => 'Demo department for product and technical teams.']
            ),
        ];

        $hrManager = Position::updateOrCreate(
            ['department_id' => $departments['Human Resources']->id, 'name' => 'HR Manager'],
            []
        );
        $hrOfficer = Position::updateOrCreate(
            ['department_id' => $departments['Human Resources']->id, 'name' => 'HR Officer'],
            []
        );
        $payrollSpecialist = Position::updateOrCreate(
            ['department_id' => $departments['Finance']->id, 'name' => 'Payroll Specialist'],
            []
        );
        $softwareEngineer = Position::updateOrCreate(
            ['department_id' => $departments['Engineering']->id, 'name' => 'Software Engineer'],
            []
        );

        OfficeLocation::updateOrCreate(
            ['name' => 'STIKes Advaita Tabanan'],
            [
                'latitude' => -8.5320882,
                'longitude' => 115.1248781,
                'radius_meters' => 150,
                'is_active' => true,
            ]
        );

        $leaveTypes = [
            LeaveType::updateOrCreate(['name' => 'Annual Leave'], ['deducts_balance' => true]),
            LeaveType::updateOrCreate(['name' => 'Sick Leave'], ['deducts_balance' => true]),
            LeaveType::updateOrCreate(['name' => 'Personal Leave'], ['deducts_balance' => true]),
            LeaveType::updateOrCreate(['name' => 'Special Leave'], ['deducts_balance' => false]),
        ];

        $employees = [
            Employee::updateOrCreate(
                ['nik' => 'DEMO-NIK-0001'],
                [
                    'user_id' => $employeeUser->id,
                    'department_id' => $departments['Engineering']->id,
                    'position_id' => $softwareEngineer->id,
                    'join_date' => '2026-01-15',
                    'employment_status' => 'active',
                    'phone_number' => '+620000000001',
                    'address' => 'Demo address only',
                    'bank_name' => 'Demo Bank',
                    'bank_account_number' => '0000000000',
                ]
            ),
            Employee::updateOrCreate(
                ['nik' => 'DEMO-NIK-0002'],
                [
                    'user_id' => $adminUser->id,
                    'department_id' => $departments['Human Resources']->id,
                    'position_id' => $hrManager->id,
                    'join_date' => '2025-11-01',
                    'employment_status' => 'active',
                    'phone_number' => '+620000000002',
                    'address' => 'Demo address only',
                    'bank_name' => 'Demo Bank',
                    'bank_account_number' => '0000000001',
                ]
            ),
            Employee::updateOrCreate(
                ['nik' => 'DEMO-NIK-0003'],
                [
                    'user_id' => $financeUser->id,
                    'department_id' => $departments['Finance']->id,
                    'position_id' => $payrollSpecialist->id,
                    'join_date' => '2025-12-01',
                    'employment_status' => 'active',
                    'phone_number' => '+620000000003',
                    'address' => 'Demo address only',
                    'bank_name' => 'Demo Bank',
                    'bank_account_number' => '0000000002',
                ]
            ),
            Employee::updateOrCreate(
                ['nik' => 'DEMO-NIK-0004'],
                [
                    'user_id' => $superAdminUser->id,
                    'department_id' => $departments['Human Resources']->id,
                    'position_id' => $hrOfficer->id,
                    'join_date' => '2025-10-01',
                    'employment_status' => 'active',
                    'phone_number' => '+620000000004',
                    'address' => 'Demo address only',
                    'bank_name' => 'Demo Bank',
                    'bank_account_number' => '0000000003',
                ]
            ),
        ];

        foreach ($employees as $employee) {
            foreach ($leaveTypes as $leaveType) {
                $used = $employee->is($employees[0]) && $leaveType->name === 'Annual Leave' ? 1 : 0;
                $quota = $leaveType->deducts_balance ? 12 : 0;

                LeaveBalance::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'leave_type_id' => $leaveType->id,
                        'year' => 2026,
                    ],
                    [
                        'total_quota' => $quota,
                        'used' => $used,
                        'remaining' => max($quota - $used, 0),
                    ]
                );
            }
        }

        $annualLeave = $leaveTypes[0];

        $attendanceRecord = AttendanceRecord::where('employee_id', $employees[0]->id)
            ->whereDate('attendance_date', '2026-06-18')
            ->first() ?? new AttendanceRecord([
                'employee_id' => $employees[0]->id,
                'attendance_date' => '2026-06-18',
            ]);

        $attendanceRecord->fill([
                'check_in_time' => '2026-06-18 09:12:00',
                'check_in_lat' => -6.2015000,
                'check_in_lng' => 106.8181000,
                'status' => 'PENDING_REVIEW',
                'out_of_radius_reason' => 'Demo check-in from client site for HR review.',
            ])->save();

        $approvedLeave = LeaveRequest::where('employee_id', $employees[0]->id)
            ->where('leave_type_id', $annualLeave->id)
            ->whereDate('start_date', '2026-06-12')
            ->whereDate('end_date', '2026-06-12')
            ->first() ?? new LeaveRequest([
                'employee_id' => $employees[0]->id,
                'leave_type_id' => $annualLeave->id,
                'start_date' => '2026-06-12',
                'end_date' => '2026-06-12',
            ]);

        $approvedLeave->fill([
                'total_days' => 1,
                'reason' => 'Demo approved annual leave.',
                'status' => 'APPROVED',
                'approved_by' => $adminUser->id,
                'approved_at' => '2026-06-10 10:00:00',
                'approval_note' => 'Demo approval.',
            ])->save();

        $pendingLeave = LeaveRequest::where('employee_id', $employees[0]->id)
            ->where('leave_type_id', $annualLeave->id)
            ->whereDate('start_date', '2026-07-08')
            ->whereDate('end_date', '2026-07-09')
            ->first() ?? new LeaveRequest([
                'employee_id' => $employees[0]->id,
                'leave_type_id' => $annualLeave->id,
                'start_date' => '2026-07-08',
                'end_date' => '2026-07-09',
            ]);

        $pendingLeave->fill([
                'total_days' => 2,
                'reason' => 'Demo pending family event leave.',
                'status' => 'PENDING_HR',
                'approved_by' => null,
                'approved_at' => null,
                'approval_note' => null,
            ])->save();

        $paidPeriod = PayrollPeriod::updateOrCreate(
            ['name' => 'Demo Payroll June 2026'],
            [
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-30',
                'pay_date' => '2026-06-30',
                'status' => 'PAID',
                'created_by' => $financeUser->id,
                'calculated_by' => $financeUser->id,
                'calculated_at' => '2026-06-25 09:00:00',
                'reviewed_by' => $adminUser->id,
                'reviewed_at' => '2026-06-26 10:00:00',
                'approved_by' => $financeUser->id,
                'approved_at' => '2026-06-27 11:00:00',
                'locked_by' => $financeUser->id,
                'locked_at' => '2026-06-28 12:00:00',
                'paid_by' => $financeUser->id,
                'paid_at' => '2026-06-30 15:00:00',
            ]
        );

        PayrollPeriod::updateOrCreate(
            ['name' => 'Demo Payroll July 2026 Draft'],
            [
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-31',
                'pay_date' => '2026-07-31',
                'status' => 'DRAFT',
                'created_by' => $financeUser->id,
                'calculated_by' => null,
                'calculated_at' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'approved_by' => null,
                'approved_at' => null,
                'locked_by' => null,
                'locked_at' => null,
                'paid_by' => null,
                'paid_at' => null,
            ]
        );

        $payrollRecord = PayrollRecord::updateOrCreate(
            ['payroll_period_id' => $paidPeriod->id, 'employee_id' => $employees[0]->id],
            [
                'basic_salary' => 5_000_000,
                'allowance' => 500_000,
                'bonus' => 250_000,
                'overtime' => 0,
                'deduction' => 0,
                'late_deduction' => 0,
                'attendance_deduction' => 0,
                'tax_bpjs' => 150_000,
                'net_salary' => 5_600_000,
                'attendance_days' => 21,
                'leave_days' => 1,
            ]
        );

        Payslip::updateOrCreate(
            ['payroll_record_id' => $payrollRecord->id],
            [
                'employee_id' => $employees[0]->id,
                'payroll_period_id' => $paidPeriod->id,
                'snapshot_data' => [
                    'basic_salary' => 5_000_000,
                    'allowance' => 500_000,
                    'bonus' => 250_000,
                    'tax_bpjs' => 150_000,
                    'net_salary' => 5_600_000,
                ],
                'payment_status' => 'PAID',
                'paid_at' => '2026-06-30 15:00:00',
            ]
        );
    }
}
