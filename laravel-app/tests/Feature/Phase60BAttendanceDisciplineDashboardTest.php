<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\AttendanceDisciplineController;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\OfficeLocation;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

/**
 * Phase 60B — Dashboard Disiplin Kehadiran + XLSX export.
 *
 * Covers the 104 scenarios from the Phase 60B spec: authorization, filtering,
 * summary maths, charts, the detail table, the real XLSX export (including
 * spreadsheet formula-injection protection), and regression over Phases 58G,
 * 59B, 59C, 59D and 60A.
 */
class Phase60BAttendanceDisciplineDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $adminHr;
    private User $employeeUser;
    private User $financeUser;

    private Employee $employeeA;
    private Employee $employeeB;

    private Department $deptA;
    private Department $deptB;

    private OfficeLocation $office;

    /** @var list<string> */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
        Storage::fake('local');

        // Freeze time so "current month" defaults are deterministic.
        Carbon::setTestNow(Carbon::parse('2026-08-15 09:00:00', config('app.timezone')));

        $this->deptA = Department::create(['name' => 'Engineering', 'description' => '']);
        $this->deptB = Department::create(['name' => 'Keuangan', 'description' => '']);

        $posA = Position::create(['name' => 'Developer', 'department_id' => $this->deptA->id]);
        $posB = Position::create(['name' => 'Analis', 'department_id' => $this->deptB->id]);

        $this->superAdmin   = User::factory()->create(['role' => 'super_admin', 'is_active' => true, 'name' => 'Super Admin Satu']);
        $this->adminHr      = User::factory()->create(['role' => 'admin_hr', 'is_active' => true, 'name' => 'HR Satu']);
        $this->employeeUser = User::factory()->create(['role' => 'employee', 'is_active' => true, 'name' => 'Pegawai Alpha']);
        $this->financeUser  = User::factory()->create(['role' => 'finance', 'is_active' => true, 'name' => 'Finance Satu']);

        $this->employeeA = $this->makeEmployee($this->employeeUser, $this->deptA, $posA, 'P60B-EMP-001');

        $employeeUserB = User::factory()->create(['role' => 'employee', 'is_active' => true, 'name' => 'Pegawai Beta']);
        $this->employeeB = $this->makeEmployee($employeeUserB, $this->deptB, $posB, 'P60B-EMP-002');

        // admin_hr needs its own employee profile for the attendance maker-checker regression.
        $this->makeEmployee($this->adminHr, $this->deptA, $posA, 'P60B-HR-001');

        $this->office = OfficeLocation::create([
            'name'          => 'Main Office',
            'latitude'      => -6.2000000,
            'longitude'     => 106.8166660,
            'radius_meters' => 100,
            'is_active'     => true,
        ]);
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        $this->tempFiles = [];

        Carbon::setTestNow();

        parent::tearDown();
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeEmployee(User $user, Department $dept, Position $position, string $nik): Employee
    {
        return Employee::create([
            'user_id'           => $user->id,
            'nik'               => $nik,
            'department_id'     => $dept->id,
            'position_id'       => $position->id,
            'join_date'         => '2026-01-01',
            'employment_status' => 'active',
            'phone_number'      => '+62812345678',
        ]);
    }

    /** Inside-radius record: distance recorded, no out-of-radius reason. */
    private function record(Employee $employee, string $date, array $overrides = []): AttendanceRecord
    {
        return AttendanceRecord::create(array_merge([
            'employee_id'          => $employee->id,
            'attendance_date'      => $date,
            'check_in_time'        => $date.' 08:00:00',
            'check_in_lat'         => -6.2000000,
            'check_in_lng'         => 106.8166660,
            'distance_from_office' => 25.50,
            'check_in_accuracy'    => 10.00,
            'check_in_photo_path'  => 'attendance/selfie-secret-'.$employee->id.'-'.$date.'.jpg',
            'check_in_work_plan'   => 'Menyelesaikan laporan mingguan dan rapat tim.',
            'check_out_time'       => $date.' 17:00:00',
            'check_out_accuracy'   => 12.00,
            'check_out_work_result'=> 'Laporan mingguan selesai dan rapat tim terlaksana.',
            'status'               => 'APPROVED',
        ], $overrides));
    }

    private function outsideRecord(Employee $employee, string $date, array $overrides = []): AttendanceRecord
    {
        return $this->record($employee, $date, array_merge([
            'distance_from_office' => 850.00,
            'status'               => 'PENDING_REVIEW',
            'out_of_radius_reason' => 'Kunjungan klien di luar kantor.',
        ], $overrides));
    }

    /** Pre-Phase-60A / pre-distance-column shape: everything optional is NULL. */
    private function legacyRecord(Employee $employee, string $date): AttendanceRecord
    {
        return AttendanceRecord::create([
            'employee_id'          => $employee->id,
            'attendance_date'      => $date,
            'check_in_time'        => $date.' 08:30:00',
            'distance_from_office' => null,
            'check_in_accuracy'    => null,
            'check_in_work_plan'   => null,
            'check_out_time'       => null,
            'check_out_accuracy'   => null,
            'check_out_work_result'=> null,
            'out_of_radius_reason' => null,
            'approved_by'          => null,
            'approved_at'          => null,
            'status'               => 'APPROVED',
        ]);
    }

    private function dashboard(array $query = [])
    {
        return $this->actingAs($this->superAdmin)
            ->get(route('admin.attendance-discipline.index', $query));
    }

    private function exportResponse(array $query = [])
    {
        return $this->actingAs($this->superAdmin)
            ->get(route('admin.attendance-discipline.export', $query));
    }

    private function exportSpreadsheet(array $query = []): Spreadsheet
    {
        $response = $this->exportResponse($query);
        $response->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'p60b').'.xlsx';
        file_put_contents($path, $response->streamedContent());
        $this->tempFiles[] = $path;

        return IOFactory::load($path);
    }

    /** @return list<list<string|null>> */
    private function exportRows(array $query = []): array
    {
        $spreadsheet = $this->exportSpreadsheet($query);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, false, false);
        $spreadsheet->disconnectWorksheets();

        return $rows;
    }

    private function photo(): UploadedFile
    {
        return UploadedFile::fake()->image('selfie.jpg', 200, 200)->size(100);
    }

    // ══════════════════════════════════════════════════════════════════════
    // AUTHORIZATION (1–10)
    // ══════════════════════════════════════════════════════════════════════

    public function test_01_super_admin_can_open_dashboard(): void
    {
        $this->record($this->employeeA, '2026-08-10');

        $this->dashboard()
            ->assertOk()
            ->assertSee('Dashboard Disiplin Kehadiran')
            ->assertSee('bukan penilaian performa karyawan');
    }

    public function test_02_super_admin_can_download_xlsx(): void
    {
        $this->record($this->employeeA, '2026-08-10');

        $this->exportResponse()->assertOk();
    }

    public function test_03_admin_hr_gets_403_on_dashboard(): void
    {
        $this->actingAs($this->adminHr)
            ->get(route('admin.attendance-discipline.index'))
            ->assertForbidden();
    }

    public function test_04_admin_hr_gets_403_on_export(): void
    {
        $this->actingAs($this->adminHr)
            ->get(route('admin.attendance-discipline.export'))
            ->assertForbidden();
    }

    public function test_05_employee_gets_403_on_dashboard(): void
    {
        $this->actingAs($this->employeeUser)
            ->get(route('admin.attendance-discipline.index'))
            ->assertForbidden();
    }

    public function test_06_employee_gets_403_on_export(): void
    {
        $this->actingAs($this->employeeUser)
            ->get(route('admin.attendance-discipline.export'))
            ->assertForbidden();
    }

    public function test_07_finance_gets_403_on_dashboard(): void
    {
        $this->actingAs($this->financeUser)
            ->get(route('admin.attendance-discipline.index'))
            ->assertForbidden();
    }

    public function test_08_finance_gets_403_on_export(): void
    {
        $this->actingAs($this->financeUser)
            ->get(route('admin.attendance-discipline.export'))
            ->assertForbidden();
    }

    /**
     * Scenario 9 — genuine active → inactive transition, not a user that started
     * inactive: the super_admin opens the dashboard successfully first, is then
     * deactivated, and is thrown out of the *next* request by Phase 59C.
     */
    public function test_09_deactivated_super_admin_is_blocked_by_phase_59c(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('admin.attendance-discipline.index'))
            ->assertOk();

        $this->superAdmin->forceFill(['is_active' => false])->save();

        $this->get(route('admin.attendance-discipline.index'))
            ->assertRedirect('/login');
        $this->assertGuest();

        $this->actingAs($this->superAdmin)
            ->get(route('admin.attendance-discipline.export'))
            ->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_10_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.attendance-discipline.index'))->assertRedirect('/login');
        $this->get(route('admin.attendance-discipline.export'))->assertRedirect('/login');
    }

    // ══════════════════════════════════════════════════════════════════════
    // FILTER (11–28)
    // ══════════════════════════════════════════════════════════════════════

    public function test_11_default_filter_uses_current_month(): void
    {
        $this->record($this->employeeA, '2026-08-10');          // in current month
        $this->record($this->employeeA, '2026-07-10');          // previous month

        $this->dashboard()
            ->assertOk()
            ->assertViewHas('summary', fn (array $summary) => $summary['total_records'] === 1)
            ->assertViewHas('filter', fn ($filter) => $filter->startDate->format('Y-m-d') === '2026-08-01'
                && $filter->endDate->format('Y-m-d') === '2026-08-31');
    }

    public function test_12_start_date_filter_works(): void
    {
        $this->record($this->employeeA, '2026-08-05');
        $this->record($this->employeeA, '2026-08-20');

        $this->dashboard(['start_date' => '2026-08-10', 'end_date' => '2026-08-31'])
            ->assertViewHas('summary', fn (array $s) => $s['total_records'] === 1);
    }

    public function test_13_end_date_filter_works(): void
    {
        $this->record($this->employeeA, '2026-08-05');
        $this->record($this->employeeA, '2026-08-20');

        $this->dashboard(['start_date' => '2026-08-01', 'end_date' => '2026-08-10'])
            ->assertViewHas('summary', fn (array $s) => $s['total_records'] === 1);
    }

    public function test_14_end_date_before_start_date_is_rejected(): void
    {
        $this->dashboard(['start_date' => '2026-08-20', 'end_date' => '2026-08-01'])
            ->assertSessionHasErrors(['end_date']);
    }

    public function test_15_employee_filter_works(): void
    {
        $this->record($this->employeeA, '2026-08-10');
        $this->record($this->employeeB, '2026-08-10');

        $this->dashboard(['employee_id' => $this->employeeA->id])
            ->assertViewHas('summary', fn (array $s) => $s['total_records'] === 1);
    }

    public function test_16_department_filter_works(): void
    {
        $this->record($this->employeeA, '2026-08-10');   // Engineering
        $this->record($this->employeeB, '2026-08-10');   // Keuangan

        $this->dashboard(['department_id' => $this->deptB->id])
            ->assertViewHas('summary', fn (array $s) => $s['total_records'] === 1);
    }

    public function test_17_status_filter_works(): void
    {
        $this->record($this->employeeA, '2026-08-10');                                  // APPROVED
        $this->outsideRecord($this->employeeA, '2026-08-11');                           // PENDING_REVIEW
        $this->record($this->employeeA, '2026-08-12', ['status' => 'REJECTED']);

        $this->dashboard(['status' => 'PENDING_REVIEW'])
            ->assertViewHas('summary', fn (array $s) => $s['total_records'] === 1);

        $this->dashboard(['status' => 'REJECTED'])
            ->assertViewHas('summary', fn (array $s) => $s['total_records'] === 1);
    }

    public function test_18_inside_radius_filter_works(): void
    {
        $this->record($this->employeeA, '2026-08-10');            // inside
        $this->outsideRecord($this->employeeA, '2026-08-11');     // outside
        $this->legacyRecord($this->employeeA, '2026-08-12');      // unknown

        $this->dashboard(['radius' => 'inside'])
            ->assertViewHas('summary', fn (array $s) => $s['total_records'] === 1
                && $s['total_inside'] === 1);
    }

    public function test_19_outside_radius_filter_works(): void
    {
        $this->record($this->employeeA, '2026-08-10');
        $this->outsideRecord($this->employeeA, '2026-08-11');
        $this->legacyRecord($this->employeeA, '2026-08-12');

        $this->dashboard(['radius' => 'outside'])
            ->assertViewHas('summary', fn (array $s) => $s['total_records'] === 1
                && $s['total_outside'] === 1);
    }

    public function test_20_checkout_complete_filter_works(): void
    {
        $this->record($this->employeeA, '2026-08-10');                             // checked out
        $this->record($this->employeeA, '2026-08-11', ['check_out_time' => null]); // not checked out

        $this->dashboard(['checkout' => 'complete'])
            ->assertViewHas('summary', fn (array $s) => $s['total_records'] === 1
                && $s['total_checked_out'] === 1);
    }

    public function test_21_checkout_incomplete_filter_works(): void
    {
        $this->record($this->employeeA, '2026-08-10');
        $this->record($this->employeeA, '2026-08-11', ['check_out_time' => null]);

        $this->dashboard(['checkout' => 'incomplete'])
            ->assertViewHas('summary', fn (array $s) => $s['total_records'] === 1
                && $s['total_not_checked_out'] === 1);
    }

    public function test_22_combined_filters_return_correct_data(): void
    {
        // Target row: employeeA, Engineering, outside radius, not checked out.
        $this->outsideRecord($this->employeeA, '2026-08-10', ['check_out_time' => null]);
        // Decoys, each differing in exactly one dimension.
        $this->outsideRecord($this->employeeB, '2026-08-10', ['check_out_time' => null]);
        $this->record($this->employeeA, '2026-08-11', ['check_out_time' => null]);
        $this->outsideRecord($this->employeeA, '2026-08-12');
        $this->outsideRecord($this->employeeA, '2026-07-10', ['check_out_time' => null]);

        $this->dashboard([
            'start_date'    => '2026-08-01',
            'end_date'      => '2026-08-31',
            'employee_id'   => $this->employeeA->id,
            'department_id' => $this->deptA->id,
            'status'        => 'PENDING_REVIEW',
            'radius'        => 'outside',
            'checkout'      => 'incomplete',
        ])->assertViewHas('summary', fn (array $s) => $s['total_records'] === 1);
    }

    public function test_23_invalid_employee_id_is_rejected(): void
    {
        $this->dashboard(['employee_id' => 999999])->assertSessionHasErrors(['employee_id']);
        $this->dashboard(['employee_id' => 'abc'])->assertSessionHasErrors(['employee_id']);
    }

    public function test_24_invalid_department_id_is_rejected(): void
    {
        $this->dashboard(['department_id' => 999999])->assertSessionHasErrors(['department_id']);
    }

    public function test_25_invalid_status_is_rejected(): void
    {
        $this->dashboard(['status' => 'DELETED'])->assertSessionHasErrors(['status']);
    }

    public function test_26_invalid_radius_filter_is_rejected(): void
    {
        $this->dashboard(['radius' => 'somewhere'])->assertSessionHasErrors(['radius']);
    }

    public function test_27_invalid_checkout_filter_is_rejected(): void
    {
        $this->dashboard(['checkout' => 'maybe'])->assertSessionHasErrors(['checkout']);
    }

    public function test_28_pagination_preserves_query_string(): void
    {
        for ($day = 1; $day <= 25; $day++) {
            $this->record($this->employeeA, sprintf('2026-08-%02d', $day));
        }

        $response = $this->dashboard([
            'employee_id' => $this->employeeA->id,
            'radius'      => 'inside',
        ])->assertOk();

        $records = $response->viewData('records');

        $this->assertSame(20, $records->perPage());
        $this->assertTrue($records->hasPages());
        $this->assertStringContainsString('employee_id='.$this->employeeA->id, $records->nextPageUrl());
        $this->assertStringContainsString('radius=inside', $records->nextPageUrl());
    }

    // ══════════════════════════════════════════════════════════════════════
    // SUMMARY (29–42)
    // ══════════════════════════════════════════════════════════════════════

    public function test_29_to_38_summary_totals_are_correct(): void
    {
        $this->record($this->employeeA, '2026-08-01');                                    // APPROVED, inside, checked out
        $this->record($this->employeeA, '2026-08-02', ['check_out_time' => null,
            'check_out_work_result' => null]);                                            // APPROVED, inside, no checkout
        $this->outsideRecord($this->employeeA, '2026-08-03');                             // PENDING_REVIEW, outside
        $this->record($this->employeeA, '2026-08-04', ['status' => 'REJECTED']);          // REJECTED, inside
        $this->legacyRecord($this->employeeA, '2026-08-05');                              // unknown radius, all NULL

        $summary = $this->dashboard()->assertOk()->viewData('summary');

        $this->assertSame(5, $summary['total_records']);          // 29
        $this->assertSame(3, $summary['total_approved']);         // 30
        $this->assertSame(1, $summary['total_pending']);          // 31
        $this->assertSame(1, $summary['total_rejected']);         // 32
        $this->assertSame(3, $summary['total_inside']);           // 33
        $this->assertSame(1, $summary['total_outside']);          // 34
        $this->assertSame(1, $summary['total_unknown_radius']);
        $this->assertSame(3, $summary['total_checked_out']);      // 35
        $this->assertSame(2, $summary['total_not_checked_out']);  // 36
        $this->assertSame(4, $summary['total_with_work_plan']);   // 37
        $this->assertSame(3, $summary['total_with_work_result']); // 38
    }

    public function test_39_average_check_in_ignores_null(): void
    {
        $this->record($this->employeeA, '2026-08-01', ['check_in_time' => '2026-08-01 08:00:00']);
        $this->record($this->employeeA, '2026-08-02', ['check_in_time' => '2026-08-02 10:00:00']);
        // NULL check-in must not be averaged in as 00:00.
        $this->record($this->employeeA, '2026-08-03', ['check_in_time' => null]);

        $summary = $this->dashboard()->viewData('summary');

        $this->assertSame('09:00', $summary['avg_check_in']);
    }

    public function test_40_average_checkout_ignores_null(): void
    {
        $this->record($this->employeeA, '2026-08-01', ['check_out_time' => '2026-08-01 17:00:00']);
        $this->record($this->employeeA, '2026-08-02', ['check_out_time' => '2026-08-02 19:00:00']);
        $this->record($this->employeeA, '2026-08-03', ['check_out_time' => null]);

        $summary = $this->dashboard()->viewData('summary');

        // Mean of 17:00 and 19:00 — NOT (17+19+0)/3.
        $this->assertSame('18:00', $summary['avg_check_out']);
    }

    public function test_41_empty_dataset_does_not_error(): void
    {
        $response = $this->dashboard()->assertOk();
        $summary  = $response->viewData('summary');

        $this->assertSame(0, $summary['total_records']);
        $this->assertNull($summary['avg_check_in']);
        $this->assertNull($summary['avg_check_out']);
        // Em dash rather than 00:00 when there is nothing to average.
        $response->assertSee('—');
        $response->assertSee('Tidak ada data kehadiran');
    }

    public function test_42_records_outside_period_are_excluded_from_summary(): void
    {
        $this->record($this->employeeA, '2026-08-10');
        $this->record($this->employeeA, '2026-07-31');
        $this->record($this->employeeA, '2026-09-01');

        $summary = $this->dashboard(['start_date' => '2026-08-01', 'end_date' => '2026-08-31'])
            ->viewData('summary');

        $this->assertSame(1, $summary['total_records']);
    }

    // ══════════════════════════════════════════════════════════════════════
    // GRAFIK (43–50)
    // ══════════════════════════════════════════════════════════════════════

    public function test_43_trend_per_date_follows_filter(): void
    {
        $this->record($this->employeeA, '2026-08-10');
        $this->record($this->employeeB, '2026-08-10');
        $this->record($this->employeeA, '2026-08-11');
        $this->record($this->employeeA, '2026-07-10');   // outside period

        $trend = $this->dashboard()->viewData('trend');

        $this->assertCount(2, $trend);
        $this->assertSame('2026-08-10', substr((string) $trend[0]['date'], 0, 10));
        $this->assertSame(2, $trend[0]['total']);
        $this->assertSame(1, $trend[1]['total']);
    }

    public function test_44_status_distribution_follows_filter(): void
    {
        $this->record($this->employeeA, '2026-08-10');
        $this->outsideRecord($this->employeeA, '2026-08-11');

        $charts = $this->dashboard()->viewData('charts');

        $this->assertSame(['Disetujui', 'Menunggu Review', 'Ditolak'], array_column($charts['status'], 'label'));
        $this->assertSame([1, 1, 0], array_column($charts['status'], 'value'));
    }

    public function test_45_inside_outside_chart_follows_filter(): void
    {
        $this->record($this->employeeA, '2026-08-10');
        $this->outsideRecord($this->employeeA, '2026-08-11');
        $this->legacyRecord($this->employeeA, '2026-08-12');

        $charts = $this->dashboard()->viewData('charts');

        $this->assertSame(['Dalam Radius', 'Luar Radius', 'Tidak diketahui'], array_column($charts['radius'], 'label'));
        $this->assertSame([1, 1, 1], array_column($charts['radius'], 'value'));
    }

    public function test_46_checkout_completeness_chart_follows_filter(): void
    {
        $this->record($this->employeeA, '2026-08-10');
        $this->record($this->employeeA, '2026-08-11', ['check_out_time' => null]);

        $charts = $this->dashboard()->viewData('charts');

        $this->assertSame(['Sudah Checkout', 'Belum Checkout'], array_column($charts['checkout'], 'label'));
        $this->assertSame([1, 1], array_column($charts['checkout'], 'value'));
    }

    public function test_47_charts_render_safely_when_all_values_are_zero(): void
    {
        $response = $this->dashboard()->assertOk();

        $charts = $response->viewData('charts');
        foreach ($charts as $dataset) {
            foreach ($dataset as $slice) {
                $this->assertSame(0, $slice['value']);
            }
        }

        // Every category still labelled, and no division-by-zero blow-up.
        $response->assertSee('Dalam Radius')
            ->assertSee('Luar Radius')
            ->assertSee('Sudah Checkout')
            ->assertSee('Belum Checkout')
            ->assertSee('Tidak ada data kehadiran pada periode ini.');
    }

    public function test_48_chart_data_is_not_emitted_into_raw_javascript(): void
    {
        $this->record($this->employeeA, '2026-08-10');

        $html = $this->dashboard()->assertOk()->getContent();

        // Charts are server-rendered HTML/CSS bars: no chart library, no CDN
        // chart script, and no dataset handed to JavaScript at all.
        $this->assertStringNotContainsString('chart.js', strtolower($html));
        $this->assertStringNotContainsString('apexcharts', strtolower($html));
        $this->assertStringNotContainsString('new Chart(', $html);
        $this->assertStringNotContainsString('cdn.jsdelivr.net', $html);
        $this->assertStringNotContainsString('unpkg.com', $html);
    }

    public function test_49_user_supplied_html_is_escaped_not_executed(): void
    {
        $payload = '<script>alert("xss")</script>';

        $this->record($this->employeeA, '2026-08-10', [
            'check_in_work_plan'    => $payload,
            'check_out_work_result' => $payload,
            'out_of_radius_reason'  => null,
            'approval_note'         => $payload,
        ]);

        $html = $this->dashboard()->assertOk()->getContent();

        $this->assertStringNotContainsString($payload, $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_50_dashboard_has_no_employee_ranking_or_score(): void
    {
        $this->record($this->employeeA, '2026-08-10');
        $this->record($this->employeeB, '2026-08-10');

        $html = strtolower($this->dashboard()->assertOk()->getContent());

        foreach (['ranking', 'peringkat', 'skor kinerja', 'performance score', 'produktivitas', 'penilaian karyawan'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $html);
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // TABEL (51–63)
    // ══════════════════════════════════════════════════════════════════════

    public function test_51_to_56_table_shows_core_columns(): void
    {
        $this->outsideRecord($this->employeeA, '2026-08-10', [
            'check_in_work_plan'    => 'Rencana kerja audit internal.',
            'check_out_work_result' => 'Audit internal selesai dikerjakan.',
        ]);

        $this->dashboard()->assertOk()
            ->assertSee('Pegawai Alpha')                       // 51
            ->assertSee('Engineering')                         // 52
            ->assertSee('Menunggu Review')                     // 53
            ->assertSee('Luar Radius')                         // 54
            ->assertSee('Rencana kerja audit internal.')       // 55
            ->assertSee('Audit internal selesai dikerjakan.'); // 56
    }

    public function test_57_table_separates_out_of_radius_reason_from_work_plan(): void
    {
        $this->outsideRecord($this->employeeA, '2026-08-10', [
            'out_of_radius_reason' => 'Alasan luar radius yang unik.',
            'check_in_work_plan'   => 'Rencana kerja yang unik.',
        ]);

        $response = $this->dashboard()->assertOk();

        $response->assertSee('Alasan Luar Radius')
            ->assertSee('Rencana Kerja')
            ->assertSee('Alasan luar radius yang unik.')
            ->assertSee('Rencana kerja yang unik.');

        $record = AttendanceRecord::first();
        $this->assertSame('Alasan luar radius yang unik.', $record->out_of_radius_reason);
        $this->assertSame('Rencana kerja yang unik.', $record->check_in_work_plan);
    }

    public function test_58_legacy_null_record_renders(): void
    {
        $this->legacyRecord($this->employeeA, '2026-08-10');

        $this->dashboard()->assertOk()
            ->assertSee('Pegawai Alpha')
            ->assertSee('Tidak diketahui')   // radius classification, not "Dalam Radius"
            ->assertSee('—');
    }

    public function test_59_60_61_exact_coordinates_and_selfie_path_are_not_displayed(): void
    {
        $record = $this->record($this->employeeA, '2026-08-10', [
            'check_in_lat'  => -6.1234567,
            'check_in_lng'  => 106.7654321,
            'check_out_lat' => -6.1111111,
            'check_out_lng' => 106.2222222,
        ]);

        $html = $this->dashboard()->assertOk()->getContent();

        $this->assertStringNotContainsString('-6.1234567', $html);   // 59
        $this->assertStringNotContainsString('106.7654321', $html);  // 60
        $this->assertStringNotContainsString('-6.1111111', $html);
        $this->assertStringNotContainsString('106.2222222', $html);
        $this->assertStringNotContainsString($record->check_in_photo_path, $html); // 61
        $this->assertStringNotContainsString('selfie-secret', $html);
    }

    public function test_62_long_text_is_escaped_and_truncated_safely(): void
    {
        $long = str_repeat('A', 120).'<b>bold</b>';

        $this->record($this->employeeA, '2026-08-10', ['check_in_work_plan' => $long]);

        $html = $this->dashboard()->assertOk()->getContent();

        $this->assertStringNotContainsString('<b>bold</b>', $html);
        $this->assertStringContainsString('&lt;b&gt;bold&lt;/b&gt;', $html);
        $this->assertStringContainsString('Selengkapnya', $html);
    }

    public function test_63_newest_records_appear_first(): void
    {
        $this->record($this->employeeA, '2026-08-05');
        $this->record($this->employeeA, '2026-08-20');
        $this->record($this->employeeA, '2026-08-12');

        $records = $this->dashboard()->viewData('records');

        $dates = collect($records->items())
            ->map(fn (AttendanceRecord $r) => $r->attendance_date->format('Y-m-d'))
            ->all();

        $this->assertSame(['2026-08-20', '2026-08-12', '2026-08-05'], $dates);
    }

    // ══════════════════════════════════════════════════════════════════════
    // EXPORT (64–90)
    // ══════════════════════════════════════════════════════════════════════

    public function test_64_export_has_correct_xlsx_content_type(): void
    {
        $this->record($this->employeeA, '2026-08-10');

        $response = $this->exportResponse()->assertOk();

        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type'),
        );

        // A real XLSX is a ZIP container — proves this is not a renamed CSV.
        $this->assertStringStartsWith('PK', $response->streamedContent());
    }

    public function test_65_filename_is_safe(): void
    {
        $this->record($this->employeeA, '2026-08-10');

        $disposition = $this->exportResponse(['start_date' => '2026-08-01', 'end_date' => '2026-08-31'])
            ->headers->get('Content-Disposition');

        $this->assertStringContainsString('attendance-discipline-2026-08-01-to-2026-08-31.xlsx', $disposition);
    }

    public function test_66_sheet_has_indonesian_headings(): void
    {
        $spreadsheet = $this->exportSpreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        $this->assertSame('Disiplin Kehadiran', $sheet->getTitle());

        $headings = $sheet->rangeToArray('A1:U1', null, true, false, false)[0];

        $this->assertSame([
            'Nomor', 'Tanggal', 'NIK', 'Nama Pegawai', 'Departemen', 'Posisi',
            'Jam Check-in', 'Jam Checkout', 'Status Persetujuan', 'Klasifikasi Radius',
            'Jarak dari Kantor (meter)', 'Akurasi GPS Check-in (meter)', 'Akurasi GPS Checkout (meter)',
            'Alasan Luar Radius', 'Rencana Kerja', 'Hasil Pekerjaan', 'Penyetuju',
            'Waktu Persetujuan', 'Catatan Persetujuan', 'Check-in Lengkap', 'Checkout Lengkap',
        ], $headings);

        $spreadsheet->disconnectWorksheets();
    }

    public function test_67_export_follows_date_filter(): void
    {
        $this->record($this->employeeA, '2026-08-10');
        $this->record($this->employeeA, '2026-07-10');

        $rows = $this->exportRows(['start_date' => '2026-08-01', 'end_date' => '2026-08-31']);

        $this->assertCount(2, $rows);                 // header + 1 data row
        $this->assertSame('2026-08-10', $rows[1][1]);
    }

    public function test_68_export_follows_employee_filter(): void
    {
        $this->record($this->employeeA, '2026-08-10');
        $this->record($this->employeeB, '2026-08-10');

        $rows = $this->exportRows(['employee_id' => $this->employeeB->id]);

        $this->assertCount(2, $rows);
        $this->assertSame('Pegawai Beta', $rows[1][3]);
    }

    public function test_69_export_follows_department_filter(): void
    {
        $this->record($this->employeeA, '2026-08-10');
        $this->record($this->employeeB, '2026-08-10');

        $rows = $this->exportRows(['department_id' => $this->deptA->id]);

        $this->assertCount(2, $rows);
        $this->assertSame('Engineering', $rows[1][4]);
    }

    public function test_70_export_follows_status_filter(): void
    {
        $this->record($this->employeeA, '2026-08-10');
        $this->outsideRecord($this->employeeA, '2026-08-11');

        $rows = $this->exportRows(['status' => 'PENDING_REVIEW']);

        $this->assertCount(2, $rows);
        $this->assertSame('Menunggu Review', $rows[1][8]);
    }

    public function test_71_export_follows_radius_filter(): void
    {
        $this->record($this->employeeA, '2026-08-10');
        $this->outsideRecord($this->employeeA, '2026-08-11');

        $rows = $this->exportRows(['radius' => 'outside']);

        $this->assertCount(2, $rows);
        $this->assertSame('Luar Radius', $rows[1][9]);
    }

    public function test_72_export_follows_checkout_filter(): void
    {
        $this->record($this->employeeA, '2026-08-10');
        $this->record($this->employeeA, '2026-08-11', ['check_out_time' => null]);

        $rows = $this->exportRows(['checkout' => 'incomplete']);

        $this->assertCount(2, $rows);
        $this->assertSame('Tidak', $rows[1][20]);   // Checkout Lengkap
    }

    public function test_73_export_excludes_records_outside_the_filter(): void
    {
        $this->record($this->employeeA, '2026-08-10', ['check_in_work_plan' => 'Termasuk dalam filter.']);
        $this->record($this->employeeB, '2026-08-10', ['check_in_work_plan' => 'Tidak boleh ikut.']);

        $rows = $this->exportRows(['employee_id' => $this->employeeA->id]);
        $flat = json_encode($rows);

        $this->assertStringContainsString('Termasuk dalam filter.', $flat);
        $this->assertStringNotContainsString('Tidak boleh ikut.', $flat);
    }

    public function test_74_75_76_77_export_contains_work_notes_reason_and_approver(): void
    {
        $this->outsideRecord($this->employeeA, '2026-08-10', [
            'check_in_work_plan'    => 'Rencana kerja diexport.',
            'check_out_work_result' => 'Hasil pekerjaan diexport.',
            'out_of_radius_reason'  => 'Alasan luar radius diexport.',
            'approved_by'           => $this->superAdmin->id,
            'approved_at'           => '2026-08-10 18:00:00',
            'approval_note'         => 'Catatan persetujuan diexport.',
        ]);

        $row = $this->exportRows()[1];

        $this->assertSame('Alasan luar radius diexport.', $row[13]);   // 76 — separate column
        $this->assertSame('Rencana kerja diexport.', $row[14]);        // 74
        $this->assertSame('Hasil pekerjaan diexport.', $row[15]);      // 75
        $this->assertSame('Super Admin Satu', $row[16]);               // 77
        $this->assertSame('2026-08-10 18:00', $row[17]);
        $this->assertSame('Catatan persetujuan diexport.', $row[18]);
    }

    public function test_78_79_80_81_export_omits_coordinates_selfie_and_credentials(): void
    {
        $record = $this->record($this->employeeA, '2026-08-10', [
            'check_in_lat'  => -6.1234567,
            'check_in_lng'  => 106.7654321,
            'check_out_lat' => -6.1111111,
            'check_out_lng' => 106.2222222,
        ]);

        $flat = json_encode($this->exportRows());

        $this->assertStringNotContainsString('-6.1234567', $flat);      // 78
        $this->assertStringNotContainsString('106.7654321', $flat);     // 79
        $this->assertStringNotContainsString('-6.1111111', $flat);
        $this->assertStringNotContainsString('106.2222222', $flat);
        $this->assertStringNotContainsString($record->check_in_photo_path, $flat);  // 80
        $this->assertStringNotContainsString('selfie-secret', $flat);
        $this->assertStringNotContainsString($this->employeeUser->password, $flat); // 81
        $this->assertStringNotContainsString('remember_token', $flat);

        // Column set is closed: nothing beyond the 21 declared headings exists.
        $this->assertCount(21, $this->exportRows()[0]);
    }

    public function test_82_legacy_null_record_can_be_exported(): void
    {
        $this->legacyRecord($this->employeeA, '2026-08-10');

        $row = $this->exportRows()[1];

        $this->assertSame('Tidak diketahui', $row[9]);   // radius classification, never assumed inside
        $this->assertBlankCell($row[10], 'Jarak dari Kantor');
        $this->assertBlankCell($row[11], 'Akurasi GPS Check-in');
        $this->assertBlankCell($row[12], 'Akurasi GPS Checkout');
        $this->assertBlankCell($row[13], 'Alasan Luar Radius');
        $this->assertBlankCell($row[14], 'Rencana Kerja');
        $this->assertBlankCell($row[15], 'Hasil Pekerjaan');
        $this->assertBlankCell($row[16], 'Penyetuju');
        $this->assertSame('Tidak', $row[20]);            // checkout not complete
    }

    /**
     * A missing value must export as a genuinely blank cell — never as 0, and
     * never as the "—" placeholder used for on-screen display. PhpSpreadsheet
     * round-trips an explicitly-written empty string as NULL, which is exactly
     * what an empty XLSX cell is.
     */
    private function assertBlankCell(mixed $value, string $column): void
    {
        $this->assertTrue(
            $value === null || $value === '',
            "Column [{$column}] should be blank for a legacy record, got: ".var_export($value, true),
        );
        $this->assertNotSame(0, $value, "Column [{$column}] must not treat NULL as zero.");
        $this->assertNotSame('0', $value, "Column [{$column}] must not treat NULL as zero.");
        $this->assertNotSame(0.0, $value, "Column [{$column}] must not treat NULL as zero.");
        $this->assertNotSame('—', $value, "Column [{$column}] must not carry the display placeholder into XLSX.");
    }

    public function test_83_empty_export_is_safe(): void
    {
        // Documented UX decision: an empty result still produces a valid file
        // containing the header row only, rather than an error.
        $rows = $this->exportRows(['start_date' => '2026-08-01', 'end_date' => '2026-08-02']);

        $this->assertCount(1, $rows);
        $this->assertSame('Nomor', $rows[0][0]);
        $this->assertSame('Checkout Lengkap', $rows[0][20]);
    }

    /**
     * Scenarios 84–88 — spreadsheet formula injection.
     *
     * Each risky prefix must survive as literal text. The proof is read back
     * from the generated file: the cell must be an explicit string cell, must
     * not be a formula, and must still hold the original characters.
     */
    public function test_84_to_88_formula_payloads_are_written_as_literal_text(): void
    {
        $payloads = [
            '=SUM(1+1)',                        // 84
            '+1+1',                             // 85
            '-1-1',                             // 86
            '@SUM(A1:A9)',                      // 87
            "=cmd|'/c calc'!A0",                // 88 — classic DDE payload
            "\tLeading tab",
            "\rLeading carriage return",
        ];

        $day = 1;
        foreach ($payloads as $payload) {
            $this->record($this->employeeA, sprintf('2026-08-%02d', $day), [
                'check_in_work_plan'    => $payload,
                'check_out_work_result' => $payload,
                'out_of_radius_reason'  => null,
                'approval_note'         => $payload,
            ]);
            $day++;
        }

        $spreadsheet = $this->exportSpreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        $found = [];
        foreach ($sheet->getRowIterator(2) as $row) {
            foreach (['O', 'P', 'S'] as $column) {   // Rencana Kerja, Hasil Pekerjaan, Catatan Persetujuan
                $cell  = $sheet->getCell($column.$row->getRowIndex());
                $value = $cell->getValue();

                $this->assertFalse(
                    $cell->isFormula(),
                    "Cell {$column}{$row->getRowIndex()} was stored as a formula: ".var_export($value, true),
                );
                $this->assertSame(
                    DataType::TYPE_STRING,
                    $cell->getDataType(),
                    "Cell {$column}{$row->getRowIndex()} is not an explicit string cell.",
                );

                $found[] = $value;
            }
        }

        $spreadsheet->disconnectWorksheets();

        // Values round-trip unchanged — not mangled by an injected quote either.
        foreach (['=SUM(1+1)', '+1+1', '-1-1', '@SUM(A1:A9)', "=cmd|'/c calc'!A0"] as $expected) {
            $this->assertContains($expected, $found, "Payload {$expected} did not survive as literal text.");
        }
    }

    public function test_89_filter_input_never_reaches_the_filename(): void
    {
        $this->record($this->employeeA, '2026-08-10');

        $disposition = $this->exportResponse([
            'start_date' => '2026-08-01',
            'end_date'   => '2026-08-31',
            'radius'     => 'inside',
        ])->headers->get('Content-Disposition');

        // Filename is rebuilt from parsed dates only.
        $this->assertStringContainsString('attendance-discipline-2026-08-01-to-2026-08-31.xlsx', $disposition);
        $this->assertStringNotContainsString('inside', $disposition);
        $this->assertStringNotContainsString('..', $disposition);
        $this->assertStringNotContainsString('/', str_replace('filename', '', $disposition));

        // A hostile value in a validated field is rejected outright, so it can
        // never reach filename construction.
        $this->exportResponse(['status' => '../../etc/passwd'])
            ->assertSessionHasErrors(['status']);
    }

    /**
     * Scenario 90 — verified by source review plus a behavioural guard, since
     * "did not materialise the whole result set" is not directly observable
     * from the HTTP response.
     */
    public function test_90_export_streams_the_query_instead_of_loading_everything(): void
    {
        $service = file_get_contents(app_path('Services/AttendanceDisciplineService.php'));
        $export  = file_get_contents(app_path('Exports/AttendanceDisciplineExport.php'));

        // Query side is chunked via lazy(), and the writer consumes it as a stream.
        $this->assertStringContainsString('->lazy(self::CHUNK_SIZE)', $service);
        $this->assertStringContainsString('foreach ($this->service->exportRows($filter) as $record)', $export);
        $this->assertStringNotContainsString('->get()', $export);

        // Export period is capped so a single file stays bounded.
        $this->assertSame(366, AttendanceDisciplineController::MAX_EXPORT_DAYS);

        $this->exportResponse(['start_date' => '2020-01-01', 'end_date' => '2026-12-31'])
            ->assertRedirect()
            ->assertSessionHasErrors(['start_date']);
    }

    // ══════════════════════════════════════════════════════════════════════
    // REGRESSION (91–104)
    // ══════════════════════════════════════════════════════════════════════

    public function test_91_inside_radius_check_in_is_still_approved(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', [
                'lat' => -6.2000000, 'lng' => 106.8166660,
                'accuracy' => 5, 'photo' => $this->photo(),
                'check_in_work_plan' => 'Rencana kerja regresi Phase 58G.',
            ])
            ->assertRedirect('/attendance/history');

        $this->assertDatabaseHas('attendance_records', [
            'employee_id' => $this->employeeA->id,
            'status'      => 'APPROVED',
        ]);
    }

    public function test_92_outside_radius_check_in_is_still_pending_review(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', [
                'lat' => -6.2500000, 'lng' => 106.8166660,
                'accuracy' => 5, 'photo' => $this->photo(),
                'reason' => 'Kunjungan klien di luar kantor.',
                'check_in_work_plan' => 'Rencana kerja regresi luar radius.',
            ])
            ->assertRedirect('/attendance/history');

        $this->assertDatabaseHas('attendance_records', [
            'employee_id'          => $this->employeeA->id,
            'status'               => 'PENDING_REVIEW',
            'out_of_radius_reason' => 'Kunjungan klien di luar kantor.',
        ]);
    }

    public function test_93_gps_accuracy_is_still_not_a_blocker(): void
    {
        // Very poor accuracy, but well inside the radius by distance.
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', [
                'lat' => -6.2000000, 'lng' => 106.8166660,
                'accuracy' => 9000, 'photo' => $this->photo(),
                'check_in_work_plan' => 'Rencana kerja akurasi buruk.',
            ])
            ->assertRedirect('/attendance/history');

        $this->assertDatabaseHas('attendance_records', [
            'employee_id' => $this->employeeA->id,
            'status'      => 'APPROVED',
        ]);
    }

    public function test_94_95_attendance_approval_and_maker_checker_still_work(): void
    {
        $record = $this->outsideRecord($this->employeeA, '2026-08-10');

        // 94 — admin_hr can still approve someone else's attendance.
        $this->actingAs($this->adminHr)
            ->post("/hr/attendance/{$record->id}/approve", ['approval_note' => 'Disetujui.'])
            ->assertRedirect();

        $this->assertSame('APPROVED', $record->fresh()->status);

        // 95 — but never their own.
        $ownRecord = $this->outsideRecord($this->adminHr->employee, '2026-08-11');

        $this->actingAs($this->adminHr)
            ->post("/hr/attendance/{$ownRecord->id}/approve", ['approval_note' => 'Sendiri.'])
            ->assertForbidden();

        $this->assertSame('PENDING_REVIEW', $ownRecord->fresh()->status);
    }

    public function test_96_phase_60a_work_plan_is_still_required(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', [
                'lat' => -6.2000000, 'lng' => 106.8166660,
                'accuracy' => 5, 'photo' => $this->photo(),
            ])
            ->assertSessionHasErrors(['check_in_work_plan']);

        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_97_phase_60a_work_result_is_still_required(): void
    {
        $this->record($this->employeeA, '2026-08-15', [
            'check_out_time'        => null,
            'check_out_work_result' => null,
        ]);

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', [
                'lat' => -6.2000000, 'lng' => 106.8166660,
                'accuracy' => 5, 'photo' => $this->photo(),
            ])
            ->assertSessionHasErrors(['check_out_work_result']);
    }

    public function test_98_phase_59b_leave_maker_checker_still_works(): void
    {
        $leave = LeaveRequest::create([
            'employee_id'   => $this->adminHr->employee->id,
            'leave_type_id' => \App\Models\LeaveType::factory()->create()->id,
            'start_date'    => '2026-08-20',
            'end_date'      => '2026-08-20',
            'total_days'    => 1,
            'reason'        => 'Keperluan pribadi.',
            'status'        => 'PENDING_HR',
        ]);

        $this->actingAs($this->adminHr)
            ->post("/hr/leave/{$leave->id}/approve")
            ->assertForbidden();

        $this->assertSame('PENDING_HR', $leave->fresh()->status);
    }

    public function test_99_phase_59c_active_session_enforcement_still_works(): void
    {
        $this->actingAs($this->employeeUser)->get('/attendance/history')->assertOk();

        $this->employeeUser->forceFill(['is_active' => false])->save();

        $this->get('/attendance/history')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_100_phase_59d_leave_concurrency_hardening_is_intact(): void
    {
        // Source review: SQLite cannot exercise real row locks, so the guarantee
        // is asserted at the source level rather than claimed as proven at runtime.
        $leaveService = file_get_contents(app_path('Services/LeaveService.php'));

        $this->assertStringContainsString('DB::transaction', $leaveService);
        $this->assertStringContainsString('lockForUpdate', $leaveService);
    }

    public function test_101_102_payroll_and_leave_modules_are_unchanged(): void
    {
        $this->actingAs($this->financeUser)->get('/payroll/periods')->assertOk();
        $this->actingAs($this->employeeUser)->get('/leave/request')->assertOk();
    }

    public function test_103_phase_60b_added_no_migration(): void
    {
        $migrations = glob(database_path('migrations/*.php'));

        $this->assertCount(36, $migrations, 'Phase 60B must not add a migration.');

        foreach ($migrations as $migration) {
            foreach (['discipline', 'dashboard_summary', 'report_cache', 'export_queue', 'ranking', 'score'] as $forbidden) {
                $this->assertStringNotContainsString($forbidden, basename($migration));
            }
        }
    }

    public function test_104_no_performance_or_ranking_feature_was_created(): void
    {
        foreach (glob(app_path('**/*.php')) as $file) {
            foreach (['Performance', 'Ranking', 'ProductivityScore'] as $forbidden) {
                $this->assertStringNotContainsString($forbidden, basename($file));
            }
        }

        $this->assertFileDoesNotExist(app_path('Services/EmployeePerformanceService.php'));
        $this->assertFileDoesNotExist(app_path('Services/EmployeeRankingService.php'));
    }

    // ══════════════════════════════════════════════════════════════════════
    // QUERY EFFICIENCY (Section F — no N+1)
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Proves eager loading actually holds: the query count for a page of 5 rows
     * and a page of 20 rows must be identical. If any relation (employee, user,
     * department, position, approver) were lazily loaded, the count would grow
     * with the number of rows.
     */
    public function test_dashboard_query_count_does_not_grow_with_row_count(): void
    {
        for ($day = 1; $day <= 5; $day++) {
            $this->record($this->employeeA, sprintf('2026-08-%02d', $day), [
                'approved_by' => $this->superAdmin->id,
                'approved_at' => sprintf('2026-08-%02d 18:00:00', $day),
            ]);
        }

        $smallCount = $this->countQueries(fn () => $this->dashboard()->assertOk());

        for ($day = 6; $day <= 20; $day++) {
            $this->record($this->employeeA, sprintf('2026-08-%02d', $day), [
                'approved_by' => $this->superAdmin->id,
                'approved_at' => sprintf('2026-08-%02d 18:00:00', $day),
            ]);
        }

        $largeCount = $this->countQueries(fn () => $this->dashboard()->assertOk());

        $this->assertSame(
            $smallCount,
            $largeCount,
            "Query count grew from {$smallCount} to {$largeCount} when rows went 5 → 20; that is an N+1.",
        );
    }

    private function countQueries(callable $callback): int
    {
        $count = 0;
        \Illuminate\Support\Facades\DB::listen(function () use (&$count): void {
            $count++;
        });

        $callback();

        return $count;
    }

    // ══════════════════════════════════════════════════════════════════════
    // NAVIGATION (Section N)
    // ══════════════════════════════════════════════════════════════════════

    public function test_navigation_entry_is_visible_only_to_super_admin(): void
    {
        $this->actingAs($this->superAdmin)->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Disiplin Kehadiran');

        $this->actingAs($this->adminHr)->get('/admin/dashboard')
            ->assertOk()
            ->assertDontSee('Disiplin Kehadiran');

        $this->actingAs($this->financeUser)->get('/finance/dashboard')
            ->assertOk()
            ->assertDontSee('Disiplin Kehadiran');

        $this->actingAs($this->employeeUser)->get('/employee/dashboard')
            ->assertOk()
            ->assertDontSee('Disiplin Kehadiran');
    }
}
