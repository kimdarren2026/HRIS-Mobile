<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PayrollPeriod;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardSummaryTest extends TestCase
{
    use RefreshDatabase;

    // ── HR / Admin dashboard ────────────────────────────────────────────────────

    public function test_admin_hr_dashboard_loads_with_summary_cards(): void
    {
        $hrUser = User::factory()->create(['role' => 'admin_hr', 'is_active' => true]);
        Employee::factory()->count(3)->create();

        $response = $this->actingAs($hrUser)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertViewHas('totalEmployees', 3);
        $response->assertViewHas('pendingLeave', 0);
        $response->assertViewHas('pendingAttendance', 0);
        $response->assertViewHas('latestPeriod', null);
    }

    public function test_super_admin_dashboard_loads_summary(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertViewHas('totalEmployees');
    }

    public function test_admin_dashboard_pending_counts_reflect_real_data(): void
    {
        $hrUser   = User::factory()->create(['role' => 'admin_hr', 'is_active' => true]);
        $empUser  = User::factory()->create(['role' => 'employee', 'is_active' => true]);
        $dept     = Department::create(['name' => 'Eng', 'description' => '']);
        $position = Position::create(['name' => 'Dev', 'department_id' => $dept->id]);
        $employee = Employee::create([
            'user_id'           => $empUser->id,
            'nik'               => 'EMP-001',
            'department_id'     => $dept->id,
            'position_id'       => $position->id,
            'join_date'         => '2026-01-01',
            'employment_status' => 'active',
            'phone_number'      => '+62812345678',
        ]);

        $leaveType = LeaveType::factory()->create();
        LeaveRequest::create([
            'employee_id'   => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date'    => today()->addDay(),
            'end_date'      => today()->addDay(),
            'total_days'    => 1,
            'reason'        => 'Test leave',
            'status'        => 'PENDING_HR',
        ]);

        AttendanceRecord::create([
            'employee_id'         => $employee->id,
            'attendance_date'     => today(),
            'check_in_time'       => now(),
            'check_in_lat'        => -6.2,
            'check_in_lng'        => 106.8,
            'check_in_photo_path' => 'photos/test.jpg',
            'status'              => 'PENDING_REVIEW',
        ]);

        $response = $this->actingAs($hrUser)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertViewHas('pendingLeave', 1);
        $response->assertViewHas('pendingAttendance', 1);
        $response->assertViewHas('totalEmployees', 1);
    }

    // ── Finance dashboard ───────────────────────────────────────────────────────

    public function test_finance_dashboard_loads_with_payroll_summary(): void
    {
        $financeUser = User::factory()->create(['role' => 'finance', 'is_active' => true]);

        $response = $this->actingAs($financeUser)->get('/finance/dashboard');

        $response->assertOk();
        $response->assertViewHas('statusCounts');
        $response->assertViewHas('latestPeriods');
        $response->assertViewHas('totalEmployees');
    }

    public function test_finance_dashboard_status_counts_reflect_real_data(): void
    {
        $financeUser = User::factory()->create(['role' => 'finance', 'is_active' => true]);

        PayrollPeriod::create([
            'name'       => 'June 2026',
            'start_date' => '2026-06-01',
            'end_date'   => '2026-06-30',
            'pay_date'   => '2026-06-30',
            'status'     => 'DRAFT',
            'created_by' => $financeUser->id,
        ]);

        PayrollPeriod::create([
            'name'       => 'May 2026',
            'start_date' => '2026-05-01',
            'end_date'   => '2026-05-31',
            'pay_date'   => '2026-05-31',
            'status'     => 'PAID',
            'created_by' => $financeUser->id,
        ]);

        $response = $this->actingAs($financeUser)->get('/finance/dashboard');

        $response->assertOk();
        $statusCounts = $response->viewData('statusCounts');
        $this->assertSame(1, $statusCounts['DRAFT']);
        $this->assertSame(1, $statusCounts['PAID']);
        $this->assertSame(0, $statusCounts['LOCKED']);
        $this->assertCount(2, $response->viewData('latestPeriods'));
    }

    // ── Employee dashboard ──────────────────────────────────────────────────────

    public function test_employee_dashboard_loads_own_summary_data(): void
    {
        $user     = User::factory()->create(['role' => 'employee', 'is_active' => true]);
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/employee/dashboard');

        $response->assertOk();
        $this->assertSame($employee->id, $response->viewData('employee')->id);
        $this->assertNull($response->viewData('todayRecord'));
        $this->assertNull($response->viewData('latestLeave'));
        $this->assertNull($response->viewData('latestPayroll'));
    }

    public function test_employee_dashboard_shows_today_attendance_when_checked_in(): void
    {
        $user     = User::factory()->create(['role' => 'employee', 'is_active' => true]);
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        $record = AttendanceRecord::create([
            'employee_id'         => $employee->id,
            'attendance_date'     => today(),
            'check_in_time'       => now(),
            'check_in_lat'        => -6.2,
            'check_in_lng'        => 106.8,
            'check_in_photo_path' => 'photos/test.jpg',
            'status'              => 'PENDING_REVIEW',
        ]);

        $response = $this->actingAs($user)->get('/employee/dashboard');

        $response->assertOk();
        $this->assertSame($record->id, $response->viewData('todayRecord')->id);
    }

    public function test_employee_dashboard_does_not_expose_other_employee_data(): void
    {
        $user     = User::factory()->create(['role' => 'employee', 'is_active' => true]);
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        $otherUser     = User::factory()->create(['role' => 'employee', 'is_active' => true]);
        $otherEmployee = Employee::factory()->create(['user_id' => $otherUser->id]);

        $leaveType = LeaveType::factory()->create();
        LeaveRequest::create([
            'employee_id'   => $otherEmployee->id,
            'leave_type_id' => $leaveType->id,
            'start_date'    => today()->addDay(),
            'end_date'      => today()->addDay(),
            'total_days'    => 1,
            'reason'        => 'Other employee leave',
            'status'        => 'PENDING_HR',
        ]);

        AttendanceRecord::create([
            'employee_id'         => $otherEmployee->id,
            'attendance_date'     => today(),
            'check_in_time'       => now(),
            'check_in_lat'        => -6.2,
            'check_in_lng'        => 106.8,
            'check_in_photo_path' => 'photos/other.jpg',
            'status'              => 'PENDING_REVIEW',
        ]);

        $response = $this->actingAs($user)->get('/employee/dashboard');

        $response->assertOk();
        $this->assertNull($response->viewData('latestLeave'));
        $this->assertNull($response->viewData('todayRecord'));
    }

    public function test_employee_dashboard_shows_own_latest_leave(): void
    {
        $user     = User::factory()->create(['role' => 'employee', 'is_active' => true]);
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        $leaveType = LeaveType::factory()->create();
        $leave = LeaveRequest::create([
            'employee_id'   => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date'    => today()->addDay(),
            'end_date'      => today()->addDay(),
            'total_days'    => 1,
            'reason'        => 'My leave',
            'status'        => 'PENDING_HR',
        ]);

        $response = $this->actingAs($user)->get('/employee/dashboard');

        $response->assertOk();
        $this->assertSame($leave->id, $response->viewData('latestLeave')->id);
    }

    // ── Authorization cross-checks ──────────────────────────────────────────────

    public function test_finance_cannot_access_employee_directory(): void
    {
        $financeUser = User::factory()->create(['role' => 'finance', 'is_active' => true]);
        $this->actingAs($financeUser)->get('/hr/employees')->assertForbidden();
    }

    public function test_employee_cannot_access_employee_directory(): void
    {
        $user = User::factory()->create(['role' => 'employee', 'is_active' => true]);
        $this->actingAs($user)->get('/hr/employees')->assertForbidden();
    }

    public function test_employee_can_access_own_profile(): void
    {
        $user = User::factory()->create(['role' => 'employee', 'is_active' => true]);
        Employee::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user)->get('/my/profile')->assertOk();
    }
}
