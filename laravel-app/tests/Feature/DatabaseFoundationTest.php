<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Notification;
use App\Models\OfficeLocation;
use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Models\Payslip;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class DatabaseFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_foundation_models_and_seed_data_are_valid(): void
    {
        $this->seed();

        $this->assertSame(4, User::count());
        $this->assertSame(3, Department::count());
        $this->assertGreaterThanOrEqual(4, Position::count());
        $this->assertSame(1, OfficeLocation::count());
        $this->assertSame(4, LeaveType::count());

        $employee = Employee::with(['user', 'department', 'position', 'leaveBalances'])->firstOrFail();
        $admin = User::where('role', 'admin_hr')->firstOrFail();
        $annualLeave = LeaveType::where('name', 'Annual Leave')->firstOrFail();

        $attendance = AttendanceRecord::create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-06-19',
            'check_in_time' => '2026-06-19 09:00:00',
            'status' => 'APPROVED',
            'approved_by' => $admin->id,
            'approved_at' => '2026-06-19 09:05:00',
        ]);

        $leaveRequest = LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $annualLeave->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-02',
            'total_days' => 2,
            'reason' => 'Demo local testing request.',
            'status' => 'PENDING_HR',
        ]);

        $period = PayrollPeriod::create([
            'name' => 'Payroll June 2026',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'status' => 'DRAFT',
            'created_by' => $admin->id,
        ]);

        $record = PayrollRecord::create([
            'payroll_period_id' => $period->id,
            'employee_id' => $employee->id,
            'basic_salary' => 1000000,
            'allowance' => 100000,
            'bonus' => 0,
            'overtime' => 0,
            'deduction' => 0,
            'late_deduction' => 0,
            'attendance_deduction' => 0,
            'tax_bpjs' => 50000,
            'net_salary' => 1050000,
        ]);

        $payslip = Payslip::create([
            'payroll_record_id' => $record->id,
            'employee_id' => $employee->id,
            'payroll_period_id' => $period->id,
            'snapshot_data' => ['net_salary' => 1050000],
            'payment_status' => 'UNPAID',
        ]);

        Notification::create([
            'user_id' => $employee->user_id,
            'title' => 'Demo notification',
            'message' => 'Local testing only.',
            'type' => 'general',
            'reference_type' => Payslip::class,
            'reference_id' => $payslip->id,
        ]);

        $auditLog = AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'demo_seed_check',
            'module' => 'database',
            'description' => 'Local test audit log.',
            'changes' => ['status' => 'created'],
            'ip_address' => '127.0.0.1',
        ]);

        $this->assertTrue($employee->user->is($employee->user));
        $this->assertTrue($attendance->approver->is($admin));
        $this->assertTrue($leaveRequest->employee->is($employee));
        $this->assertTrue($record->payslip->is($payslip));
        $this->assertCount(4, $employee->leaveBalances);

        $this->expectException(RuntimeException::class);
        $auditLog->update(['description' => 'Should not update.']);
    }

    public function test_database_seeder_is_idempotent_for_demo_data(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(4, User::count());
        $this->assertSame(4, Employee::count());
        $this->assertSame(3, Department::count());
        $this->assertSame(1, OfficeLocation::count());
        $this->assertSame(4, LeaveType::count());
        $this->assertSame(16, \App\Models\LeaveBalance::count());
        $this->assertSame(1, AttendanceRecord::count());
        $this->assertSame(2, LeaveRequest::count());
        $this->assertSame(2, PayrollPeriod::count());
        $this->assertSame(1, PayrollRecord::count());
        $this->assertSame(1, Payslip::count());

        $this->assertDatabaseHas('payroll_periods', [
            'name' => 'Demo Payroll June 2026',
            'status' => 'PAID',
        ]);
        $this->assertDatabaseHas('leave_requests', [
            'reason' => 'Demo pending family event leave.',
            'status' => 'PENDING_HR',
        ]);
    }
}
