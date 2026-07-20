<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CompanyExpense;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

class Phase57ModuleLocalizationTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $adminHr;
    private User $financeUser;
    private User $employeeUser;
    private Employee $employee;
    private Department $dept;
    private Position $position;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);

        $this->dept     = Department::create(['name' => 'Engineering', 'description' => '']);
        $this->position = Position::create(['name' => 'Dev', 'department_id' => $this->dept->id]);

        $this->superAdmin   = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->adminHr      = User::factory()->create(['role' => 'admin_hr',    'is_active' => true]);
        $this->financeUser  = User::factory()->create(['role' => 'finance',     'is_active' => true]);
        $this->employeeUser = User::factory()->create(['role' => 'employee',    'is_active' => true]);

        $this->employee = Employee::create([
            'user_id'             => $this->employeeUser->id,
            'nik'                 => 'P57M-EMP-001',
            'department_id'       => $this->dept->id,
            'position_id'         => $this->position->id,
            'join_date'           => '2026-01-01',
            'employment_status'   => 'active',
            'phone_number'        => '+62812345678',
            'address'             => 'Test Address',
            'bank_name'           => 'Test Bank',
            'bank_account_number' => '1234567890',
        ]);
    }

    // ── 1: HR employee management berbahasa Indonesia ─────────────────────────

    public function test_hr_employee_index_has_no_english_labels(): void
    {
        $this->actingAs($this->adminHr)
            ->get('/hr/employees')
            ->assertOk()
            ->assertSee('Direktori Pegawai')
            ->assertDontSee('Employee Directory')
            ->assertDontSee('All Departments')
            ->assertDontSee('All Status');
    }

    public function test_hr_employee_create_form_is_indonesian(): void
    {
        $this->actingAs($this->adminHr)
            ->get('/hr/employees/create')
            ->assertOk()
            ->assertSee('Tambah Pegawai')
            ->assertDontSee('Add Employee')
            ->assertDontSee('Employment Details');
    }

    public function test_hr_employee_show_is_indonesian(): void
    {
        $this->actingAs($this->adminHr)
            ->get("/hr/employees/{$this->employee->id}")
            ->assertOk()
            ->assertSee('Detail Pegawai')
            ->assertDontSee('Employee Detail')
            ->assertDontSee('Join Date');
    }

    // ── 2: Finance expense create/edit/show berbahasa Indonesia ───────────────

    public function test_finance_expense_create_is_indonesian(): void
    {
        $this->actingAs($this->financeUser)
            ->get('/finance/expenses/create')
            ->assertOk()
            ->assertSee('Pengeluaran Baru')
            ->assertDontSee('New Expense')
            ->assertDontSee('Recipient / Vendor');
    }

    public function test_finance_expense_show_is_indonesian(): void
    {
        $expense = CompanyExpense::create([
            'expense_number' => 'EXP-P57-001',
            'category'       => 'OFFICE_SUPPLIES',
            'title'          => 'Test Expense',
            'amount'         => 100000,
            'expense_date'   => '2026-07-01',
            'recipient_name' => 'Toko Test',
            'status'         => 'DRAFT',
            'created_by'     => $this->financeUser->id,
        ]);

        $this->actingAs($this->financeUser)
            ->get("/finance/expenses/{$expense->id}")
            ->assertOk()
            ->assertSee('Detail Pengeluaran')
            ->assertSee('Draf')
            ->assertDontSee('Expense Detail')
            ->assertDontSee('Created By');
    }

    public function test_finance_expense_edit_is_indonesian(): void
    {
        $expense = CompanyExpense::create([
            'expense_number' => 'EXP-P57-002',
            'category'       => 'OFFICE_SUPPLIES',
            'title'          => 'Test Expense 2',
            'amount'         => 100000,
            'expense_date'   => '2026-07-01',
            'recipient_name' => 'Toko Test',
            'status'         => 'DRAFT',
            'created_by'     => $this->financeUser->id,
        ]);

        $this->actingAs($this->financeUser)
            ->get("/finance/expenses/{$expense->id}/edit")
            ->assertOk()
            ->assertSee('Ubah Pengeluaran')
            ->assertDontSee('Edit Expense');
    }

    // ── 3: Admin users berbahasa Indonesia ─────────────────────────────────────

    public function test_admin_users_index_is_indonesian(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('Manajemen Pengguna')
            ->assertDontSee('User Management')
            ->assertDontSee('No linked employee');
    }

    // ── 4: Audit log berbahasa Indonesia ───────────────────────────────────────

    public function test_audit_log_index_is_indonesian(): void
    {
        AuditLog::create([
            'user_id'     => $this->superAdmin->id,
            'action'      => 'test_action',
            'module'      => 'test',
            'description' => 'Aksi uji coba',
        ]);

        $this->actingAs($this->superAdmin)
            ->get('/audit-logs')
            ->assertOk()
            ->assertSee('Log Audit')
            ->assertDontSee('Audit Logs')
            ->assertDontSee('All Modules');
    }

    public function test_audit_log_show_is_indonesian(): void
    {
        $log = AuditLog::create([
            'user_id'     => $this->superAdmin->id,
            'action'      => 'test_action',
            'module'      => 'test',
            'description' => 'Aksi uji coba',
        ]);

        $this->actingAs($this->superAdmin)
            ->get("/audit-logs/{$log->id}")
            ->assertOk()
            ->assertSee('Detail Audit')
            ->assertSee('Pelaku')
            ->assertDontSee('Audit Log Detail');
    }

    // ── 5: Payroll periods & /my/payroll berbahasa Indonesia ──────────────────

    public function test_payroll_periods_index_is_indonesian(): void
    {
        $this->actingAs($this->financeUser)
            ->get('/payroll/periods')
            ->assertOk()
            ->assertSee('Periode Penggajian')
            ->assertDontSee('Payroll Periods')
            ->assertDontSee('Create New Period');
    }

    public function test_my_payroll_index_is_indonesian(): void
    {
        $period = PayrollPeriod::create([
            'name'       => 'Juli 2026',
            'start_date' => '2026-07-01',
            'end_date'   => '2026-07-31',
            'status'     => 'PAID',
            'created_by' => $this->financeUser->id,
        ]);
        PayrollRecord::create([
            'payroll_period_id'    => $period->id,
            'employee_id'          => $this->employee->id,
            'basic_salary'         => '5000000',
            'allowance'            => '0',
            'bonus'                => '0',
            'overtime'             => '0',
            'deduction'            => '0',
            'late_deduction'       => '0',
            'attendance_deduction' => '0',
            'tax_bpjs'             => '0',
            'net_salary'           => '5000000',
            'attendance_days'      => 22,
            'leave_days'           => '0',
        ]);

        $this->actingAs($this->employeeUser)
            ->get('/my/payroll')
            ->assertOk()
            ->assertSee('Gaji Bersih')
            ->assertDontSee('Net Pay');
    }

    // ── 6 & 7: Dashboard tidak menampilkan data contoh statis + empty state ───

    public function test_admin_dashboard_has_no_static_example_names(): void
    {
        $response = $this->actingAs($this->adminHr)
            ->get('/admin/dashboard')
            ->assertOk();

        $response->assertDontSee('Alex Rivers');
        $response->assertDontSee('Sarah Chen');
        $response->assertDontSee('John Doe');
    }

    public function test_admin_dashboard_shows_empty_state_for_recent_activity(): void
    {
        $this->actingAs($this->adminHr)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Belum ada aktivitas terbaru');
    }

    // ── 10: Tidak ada perubahan data payroll/payslip ───────────────────────────

    public function test_payslip_maintenance_page_does_not_alter_payroll_data(): void
    {
        $period = PayrollPeriod::create([
            'name'       => 'Agustus 2026',
            'start_date' => '2026-08-01',
            'end_date'   => '2026-08-31',
            'status'     => 'PAID',
            'created_by' => $this->financeUser->id,
        ]);
        $record = PayrollRecord::create([
            'payroll_period_id'    => $period->id,
            'employee_id'          => $this->employee->id,
            'basic_salary'         => '5000000',
            'allowance'            => '0',
            'bonus'                => '0',
            'overtime'             => '0',
            'deduction'            => '0',
            'late_deduction'       => '0',
            'attendance_deduction' => '0',
            'tax_bpjs'             => '0',
            'net_salary'           => '5000000',
            'attendance_days'      => 22,
            'leave_days'           => '0',
        ]);

        $beforePeriodCount = PayrollPeriod::count();
        $beforeRecordCount = PayrollRecord::count();
        $beforeNetSalary   = $record->net_salary;

        $this->actingAs($this->employeeUser)->get('/payslip/detail')->assertOk();

        $this->assertSame($beforePeriodCount, PayrollPeriod::count());
        $this->assertSame($beforeRecordCount, PayrollRecord::count());
        $this->assertSame($beforeNetSalary, $record->fresh()->net_salary);
    }

    // ── 12: Animasi maintenance & class animasi utama tetap ada ───────────────

    public function test_payslip_maintenance_page_retains_animation_classes(): void
    {
        $response = $this->actingAs($this->employeeUser)
            ->get('/payslip/detail')
            ->assertOk();

        $response->assertSee('payslip-maintenance-float', false);
        $response->assertSee('payslip-maintenance-gear', false);
        $response->assertSee('payslip-maintenance-gear-2', false);
        $response->assertSee('payslip-maintenance-dot', false);
        $response->assertSee('payslip-maintenance-badge', false);
        $response->assertSee('payslip-maintenance-page', false);
        $response->assertSee('prefers-reduced-motion', false);
    }

    public function test_checkin_page_retains_lottie_and_pin_pulse_animation(): void
    {
        $response = $this->actingAs($this->employeeUser)
            ->get('/attendance/checkin')
            ->assertOk();

        $response->assertSee('mountLottie', false);
        $response->assertSee('animate-pin-pulse', false);
    }
}
