<?php

namespace Tests\Feature;

use App\Http\Requests\AttendanceCheckInRequest;
use App\Http\Requests\AttendanceCheckOutRequest;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\User;
use App\Support\ReportsNavigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Phase 60C — report navigation integration.
 *
 * Phase 60B shipped a working Dashboard Disiplin Kehadiran, but the "Laporan"
 * menu still pointed at the old /reports placeholder and the dashboard's bottom
 * nav highlighted "Audit". This suite pins the wiring: where /reports sends each
 * role, which menu entry lights up on which page, and — most importantly — that
 * none of it hands out access. Authorization is asserted independently of the
 * links, because the navigation is cosmetic and the middleware is the real guard.
 */
class Phase60CReportNavigationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private const PLACEHOLDER = 'Modul laporan sedang dikembangkan';

    private User $superAdmin;

    private User $adminHr;

    private User $finance;

    private User $employeeUser;

    private Employee $superAdminEmployee;

    private Employee $employee;

    private LeaveType $leaveType;

    protected function setUp(): void
    {
        parent::setUp();

        // Freeze time so the dashboard's default "current month" filter is
        // deterministic and the seeded attendance row falls inside it.
        Carbon::setTestNow(Carbon::parse('2026-08-15 09:00:00', config('app.timezone')));

        $department = Department::create(['name' => 'Phase60C Dept', 'description' => '']);
        $position   = Position::create(['name' => 'Phase60C Role', 'department_id' => $department->id]);

        $this->superAdmin   = User::factory()->create(['role' => 'super_admin', 'is_active' => true, 'name' => 'Super Admin 60C']);
        $this->adminHr      = User::factory()->create(['role' => 'admin_hr', 'is_active' => true, 'name' => 'HR 60C']);
        $this->finance      = User::factory()->create(['role' => 'finance', 'is_active' => true, 'name' => 'Finance 60C']);
        $this->employeeUser = User::factory()->create(['role' => 'employee', 'is_active' => true, 'name' => 'Pegawai 60C']);

        $this->employee = Employee::factory()->create([
            'user_id'           => $this->employeeUser->id,
            'department_id'     => $department->id,
            'position_id'       => $position->id,
            'employment_status' => 'active',
        ]);

        // super_admin needs its own employee profile for the Phase 59B
        // maker-checker regression below.
        $this->superAdminEmployee = Employee::factory()->create([
            'user_id'           => $this->superAdmin->id,
            'department_id'     => $department->id,
            'position_id'       => $position->id,
            'employment_status' => 'active',
        ]);

        $this->leaveType = LeaveType::create(['name' => 'Annual Leave 60C', 'deducts_balance' => true]);

        $this->seedAttendance('2026-08-10');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function seedAttendance(string $date): AttendanceRecord
    {
        return AttendanceRecord::create([
            'employee_id'           => $this->employee->id,
            'attendance_date'       => $date,
            'check_in_time'         => $date.' 08:00:00',
            'check_in_lat'          => -6.2000000,
            'check_in_lng'          => 106.8166660,
            'distance_from_office'  => 25.50,
            'check_in_accuracy'     => 10.00,
            'check_in_work_plan'    => 'Menyusun laporan navigasi dan pengujian.',
            'check_out_time'        => $date.' 17:00:00',
            'check_out_accuracy'    => 12.00,
            'check_out_work_result' => 'Laporan navigasi selesai dan diuji.',
            'status'                => 'APPROVED',
        ]);
    }

    private function deactivate(User $user): void
    {
        // Separate write, decoupled from the instance passed to actingAs(), so a
        // real active -> inactive transition is proven (same approach as 59C).
        User::where('id', $user->id)->update(['is_active' => false]);
    }

    private function disciplineUrl(): string
    {
        return route('admin.attendance-discipline.index');
    }

    /** The bottom navigation is the last <nav> element on these pages. */
    private function bottomNav(string $html): string
    {
        preg_match_all('/<nav\b[^>]*>.*?<\/nav>/s', $html, $matches);

        $this->assertNotEmpty($matches[0], 'Page renders no <nav> element.');

        return (string) end($matches[0]);
    }

    /**
     * Every clickable entry in a bottom nav, whether it is an <a href> or a
     * <button onclick="window.location.href=...">, both of which this codebase uses.
     *
     * @return list<array{label: string, href: string, active: bool, html: string}>
     */
    private function navItems(string $navHtml): array
    {
        preg_match_all('/<(a|button)\b[^>]*>.*?<\/\1>/s', $navHtml, $matches);

        $items = [];

        foreach ($matches[0] as $element) {
            $href = '';

            if (preg_match('/href="([^"]*)"/', $element, $found)) {
                $href = $found[1];
            } elseif (preg_match('/window\.location\.href=\'([^\']*)\'/', $element, $found)) {
                $href = $found[1];
            }

            preg_match_all('/<span[^>]*>([^<]*)<\/span>/s', $element, $spans);

            $items[] = [
                'label'  => trim((string) end($spans[1])),
                'href'   => $href,
                'active' => str_contains($element, 'aria-current="page"'),
                'html'   => $element,
            ];
        }

        return $items;
    }

    /**
     * Path a nav entry resolves to. Compared as a path because this codebase
     * mixes relative hrefs ("/reports") with route() output
     * ("http://localhost/reports") — the destination is what matters.
     */
    private function navPath(array $item): string
    {
        return (string) parse_url($item['href'], PHP_URL_PATH);
    }

    /** @return array{label: string, href: string, active: bool, html: string} */
    private function navItem(string $navHtml, string $label): array
    {
        foreach ($this->navItems($navHtml) as $item) {
            if ($item['label'] === $label) {
                return $item;
            }
        }

        $this->fail("Bottom navigation has no \"{$label}\" item.");
    }

    private function navFor(User $user, string $path): string
    {
        $response = $this->actingAs($user)->get($path);
        $response->assertOk();

        return $this->bottomNav($response->getContent());
    }

    // ══════════════════════════════════════════════════════════════════════
    // ROUTE /REPORTS (spec 1-12)
    // ══════════════════════════════════════════════════════════════════════

    // 1
    public function test_guest_opening_reports_is_redirected_to_login(): void
    {
        $this->get('/reports')->assertRedirect('/login');
        $this->assertGuest();
    }

    // 2 — Phase 59C still owns deactivation; the session dies before the
    // role-aware redirect or the dashboard can run.
    public function test_deactivated_super_admin_with_stale_session_is_blocked_before_any_report_page(): void
    {
        $this->deactivate($this->superAdmin);

        $this->actingAs($this->superAdmin)->get('/reports')->assertRedirect('/login');
        $this->assertGuest();

        $this->actingAs($this->superAdmin)->get($this->disciplineUrl())->assertRedirect('/login');
        $this->assertGuest();
    }

    // 3
    public function test_super_admin_opening_reports_is_redirected_to_discipline_dashboard(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/reports')
            ->assertRedirect(route('admin.attendance-discipline.index'));
    }

    // 4 — the destination must answer 200, not bounce back to /reports.
    public function test_super_admin_report_redirect_does_not_loop(): void
    {
        $first = $this->actingAs($this->superAdmin)->get('/reports');
        $first->assertRedirect($this->disciplineUrl());

        $second = $this->actingAs($this->superAdmin)->get($first->headers->get('Location'));
        $second->assertOk();
        $this->assertSame(200, $second->getStatusCode());
    }

    // 5, 6, 7
    public function test_admin_hr_keeps_the_existing_reports_placeholder(): void
    {
        $response = $this->actingAs($this->adminHr)->get('/reports');

        $response->assertOk()
            ->assertSee(self::PLACEHOLDER)
            ->assertDontSee($this->disciplineUrl(), false)
            ->assertDontSee('/admin/attendance-discipline', false);
    }

    // 8, 9, 10
    public function test_finance_keeps_the_existing_reports_placeholder(): void
    {
        $response = $this->actingAs($this->finance)->get('/reports');

        $response->assertOk()
            ->assertSee(self::PLACEHOLDER)
            ->assertDontSee($this->disciplineUrl(), false)
            ->assertDontSee('/admin/attendance-discipline', false);
    }

    // 11 — employee had no access before Phase 60C and gains none.
    public function test_employee_gains_no_access_to_reports(): void
    {
        $this->actingAs($this->employeeUser)->get('/reports')->assertForbidden();
    }

    // 12
    public function test_reports_route_is_named_and_still_serves_the_same_path(): void
    {
        $this->assertSame('/reports', parse_url(route('reports.index'), PHP_URL_PATH));
    }

    // ══════════════════════════════════════════════════════════════════════
    // DASHBOARD ACCESS (spec 13-20)
    // ══════════════════════════════════════════════════════════════════════

    // 13
    public function test_super_admin_can_open_the_discipline_dashboard_directly(): void
    {
        $this->actingAs($this->superAdmin)->get($this->disciplineUrl())->assertOk();
    }

    // 14, 15, 16
    public function test_other_roles_are_forbidden_from_the_discipline_dashboard(): void
    {
        foreach ([$this->adminHr, $this->finance, $this->employeeUser] as $user) {
            $this->actingAs($user)->get($this->disciplineUrl())->assertForbidden();
        }
    }

    // 17
    public function test_super_admin_export_still_works(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.attendance-discipline.export'));

        $response->assertOk();
    }

    // 18, 19, 20
    public function test_other_roles_are_forbidden_from_the_export(): void
    {
        foreach ([$this->adminHr, $this->finance, $this->employeeUser] as $user) {
            $this->actingAs($user)
                ->get(route('admin.attendance-discipline.export'))
                ->assertForbidden();
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // BOTTOM NAV — SUPER_ADMIN (spec 21-30)
    // ══════════════════════════════════════════════════════════════════════

    // 21, 22
    public function test_super_admin_reports_menu_points_at_the_discipline_dashboard(): void
    {
        foreach (['/admin/dashboard', $this->disciplineUrl(), route('audit-logs.index')] as $path) {
            $item = $this->navItem($this->navFor($this->superAdmin, $path), 'Laporan');

            $this->assertSame('/admin/attendance-discipline', $this->navPath($item), "Wrong Laporan target on {$path}.");
            $this->assertNotSame('/reports', $this->navPath($item), "Laporan still points at the placeholder on {$path}.");
        }
    }

    // 23, 24, 27
    public function test_discipline_dashboard_marks_only_laporan_as_active(): void
    {
        $nav = $this->navFor($this->superAdmin, $this->disciplineUrl());

        $this->assertTrue($this->navItem($nav, 'Laporan')['active'], 'Laporan must be active here.');
        $this->assertFalse($this->navItem($nav, 'Audit')['active'], 'Audit must not be active here.');

        $active = array_filter($this->navItems($nav), fn (array $item): bool => $item['active']);
        $this->assertCount(1, $active, 'Exactly one bottom-nav item may be active.');
    }

    // 25, 26
    public function test_audit_page_marks_only_audit_as_active(): void
    {
        $nav = $this->navFor($this->superAdmin, route('audit-logs.index'));

        $this->assertTrue($this->navItem($nav, 'Audit')['active'], 'Audit must be active here.');
        $this->assertFalse($this->navItem($nav, 'Laporan')['active'], 'Laporan must not be active here.');

        $active = array_filter($this->navItems($nav), fn (array $item): bool => $item['active']);
        $this->assertCount(1, $active, 'Exactly one bottom-nav item may be active.');
    }

    // Section I.3 — the main admin dashboard still highlights Beranda only.
    public function test_main_admin_dashboard_still_highlights_beranda(): void
    {
        $nav = $this->navFor($this->superAdmin, '/admin/dashboard');

        $this->assertStringContainsString('text-primary', $this->navItem($nav, 'Beranda')['html']);
        $this->assertStringNotContainsString('text-primary', $this->navItem($nav, 'Laporan')['html']);
    }

    // 28
    public function test_reports_link_is_present_in_the_rendered_mobile_navigation(): void
    {
        $nav = $this->navFor($this->superAdmin, $this->disciplineUrl());

        $this->assertStringContainsString('Laporan', $nav);
        $this->assertStringContainsString($this->disciplineUrl(), $nav);
    }

    // 29, 30
    public function test_discipline_dashboard_back_button_uses_named_admin_dashboard_route(): void
    {
        $response = $this->actingAs($this->superAdmin)->get($this->disciplineUrl());

        $response->assertOk()
            ->assertSee('href="'.route('admin.dashboard').'"', false)
            ->assertDontSee('history.back', false)
            ->assertDontSee('history.go', false)
            ->assertDontSee('javascript:', false);

        // And it must not point back at /reports, which would bounce straight here.
        $this->assertStringNotContainsString(
            'aria-label="Kembali ke dashboard admin" href="'.route('reports.index').'"',
            $response->getContent(),
        );
    }

    // ══════════════════════════════════════════════════════════════════════
    // NAVIGATION — OTHER ROLES (spec 31-35)
    // ══════════════════════════════════════════════════════════════════════

    // 31, 34
    public function test_admin_hr_navigation_keeps_reports_and_never_exposes_the_dashboard(): void
    {
        foreach (['/admin/dashboard', '/reports', '/hr/employees', '/hr/approval-queue', '/settings'] as $path) {
            $nav = $this->navFor($this->adminHr, $path);

            $this->assertStringNotContainsString('/admin/attendance-discipline', $nav, "Leaked on {$path}.");
            $this->assertSame(
                '/reports',
                $this->navPath($this->navItem($nav, 'Laporan')),
                "admin_hr Laporan target changed on {$path}.",
            );
        }
    }

    // 32, 35
    public function test_finance_navigation_keeps_reports_and_never_exposes_the_dashboard(): void
    {
        foreach (['/finance/dashboard', '/reports', '/payroll/periods'] as $path) {
            $nav = $this->navFor($this->finance, $path);

            $this->assertStringNotContainsString('/admin/attendance-discipline', $nav, "Leaked on {$path}.");
            $this->assertSame(
                '/reports',
                $this->navPath($this->navItem($nav, 'Laporan')),
                "finance Laporan target changed on {$path}.",
            );
        }
    }

    // 33
    public function test_employee_navigation_never_exposes_the_discipline_dashboard(): void
    {
        $response = $this->actingAs($this->employeeUser)->get('/employee/dashboard');

        $response->assertOk()->assertDontSee('/admin/attendance-discipline', false);
    }

    // ══════════════════════════════════════════════════════════════════════
    // DASHBOARD CARD (spec 36-37)
    // ══════════════════════════════════════════════════════════════════════

    // 36
    public function test_super_admin_dashboard_card_targets_the_named_discipline_route(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Disiplin Kehadiran')
            ->assertSee($this->disciplineUrl(), false);
    }

    // 37 — exactly two entry points are intended: the quick-action card and the
    // bottom-nav item. A third occurrence means a duplicate card slipped in.
    public function test_super_admin_dashboard_has_no_duplicate_discipline_entry(): void
    {
        $html = $this->actingAs($this->superAdmin)->get('/admin/dashboard')->getContent();

        $this->assertSame(2, substr_count($html, $this->disciplineUrl()));
    }

    // ══════════════════════════════════════════════════════════════════════
    // REGRESSION (spec 38-54)
    // ══════════════════════════════════════════════════════════════════════

    // 38, 39, 40, 41
    public function test_phase_60b_dashboard_still_renders_filters_summary_charts_and_table(): void
    {
        $response = $this->actingAs($this->superAdmin)->get($this->disciplineUrl());

        $response->assertOk()
            ->assertSee('Dashboard Disiplin Kehadiran')
            ->assertSee('Filter')
            ->assertSee('Terapkan Filter')
            ->assertSee('Total Presensi')
            ->assertSee('Dalam Radius')
            ->assertSee('Luar Radius')
            ->assertSee('Tren Kehadiran per Tanggal')
            ->assertSee('Distribusi Status Persetujuan')
            ->assertSee('Pegawai 60C')
            ->assertSee('bukan penilaian performa karyawan');
    }

    // 42
    public function test_phase_60b_export_still_returns_a_real_xlsx(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.attendance-discipline.export'));

        $response->assertOk();

        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type'),
        );
        $this->assertStringStartsWith('PK', $response->streamedContent());
    }

    // 43
    public function test_phase_60b_export_still_honours_the_date_filter(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route(
            'admin.attendance-discipline.export',
            ['start_date' => '2026-08-01', 'end_date' => '2026-08-31'],
        ));

        $response->assertOk();
        $this->assertStringContainsString(
            'attendance-discipline-2026-08-01-to-2026-08-31.xlsx',
            (string) $response->headers->get('Content-Disposition'),
        );
    }

    // 44 — Phase 60A work notes remain mandatory.
    public function test_phase_60a_work_note_rules_are_unchanged(): void
    {
        $checkIn  = (new AttendanceCheckInRequest)->rules();
        $checkOut = (new AttendanceCheckOutRequest)->rules();

        $this->assertContains('required', $checkIn['check_in_work_plan']);
        $this->assertContains('required', $checkOut['check_out_work_result']);
    }

    // 45 — Phase 59B maker-checker: super_admin still cannot approve own leave.
    public function test_phase_59b_maker_checker_still_blocks_self_approval(): void
    {
        $leave = LeaveRequest::create([
            'employee_id'     => $this->superAdminEmployee->id,
            'leave_type_id'   => $this->leaveType->id,
            'start_date'      => '2026-09-01',
            'end_date'        => '2026-09-01',
            'total_days'      => 1,
            'chargeable_days' => 1,
            'reason'          => 'Regression check for Phase 60C navigation work.',
            'status'          => 'PENDING_HR',
        ]);

        $this->actingAs($this->superAdmin)
            ->post("/hr/leave/{$leave->id}/approve", ['approval_note' => 'Self approve attempt'])
            ->assertForbidden();

        $this->assertSame('PENDING_HR', $leave->fresh()->status);
    }

    // 46 — Phase 59C enforcement still applies to the pages this phase touched.
    public function test_phase_59c_enforcement_still_applies_to_admin_dashboard(): void
    {
        $this->deactivate($this->adminHr);

        $this->actingAs($this->adminHr)->get('/admin/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    // 47 — Phase 59D locking is untouched by this phase.
    public function test_phase_59d_leave_locking_is_still_in_place(): void
    {
        $source = file_get_contents(app_path('Services/LeaveService.php'));

        $this->assertStringContainsString('DB::transaction', $source);
        $this->assertStringContainsString('lockForUpdate', $source);
    }

    // 48
    public function test_payroll_pages_are_unaffected(): void
    {
        $this->actingAs($this->finance)->get('/payroll/periods')->assertOk();
        $this->actingAs($this->adminHr)->get('/payroll/periods')->assertOk();
    }

    // 49 — Phase 60C adds no migration.
    public function test_phase_60c_adds_no_migration(): void
    {
        $this->assertSame([], glob(database_path('migrations/*report*')));
        $this->assertSame([], glob(database_path('migrations/*navigation*')));
        $this->assertSame([], glob(database_path('migrations/*60c*')));
    }

    // 50 — Phase 60B's only dependency stays pinned; nothing new is added.
    public function test_phase_60c_adds_no_dependency(): void
    {
        $composer = json_decode((string) file_get_contents(base_path('composer.json')), true);
        $lock     = json_decode((string) file_get_contents(base_path('composer.lock')), true);

        $this->assertSame('^5.9', $composer['require']['phpoffice/phpspreadsheet']);

        $locked = array_column($lock['packages'], 'version', 'name');
        $this->assertSame('5.9.0', $locked['phpoffice/phpspreadsheet']);
    }

    // 51 — the dashboard this phase links to still loads nothing from a CDN.
    public function test_discipline_dashboard_still_loads_no_external_asset(): void
    {
        $html = $this->actingAs($this->superAdmin)->get($this->disciplineUrl())->getContent();

        $this->assertStringNotContainsString('cdn.tailwindcss.com', $html);
        $this->assertStringNotContainsString('fonts.googleapis.com', $html);
        $this->assertStringNotContainsString('<script src="http', $html);
    }

    // 52 — navigating reports writes nothing.
    public function test_report_navigation_performs_no_writes(): void
    {
        $attendanceBefore = AttendanceRecord::count();
        $auditBefore      = AuditLog::count();
        $userBefore       = User::count();

        $this->actingAs($this->superAdmin)->get('/reports')->assertRedirect($this->disciplineUrl());
        $this->actingAs($this->superAdmin)->get($this->disciplineUrl())->assertOk();
        $this->actingAs($this->adminHr)->get('/reports')->assertOk();

        $this->assertSame($attendanceBefore, AttendanceRecord::count());
        $this->assertSame($auditBefore, AuditLog::count());
        $this->assertSame($userBefore, User::count());
    }

    // 53
    public function test_placeholder_view_is_still_reachable_for_admin_hr_and_finance(): void
    {
        foreach ([$this->adminHr, $this->finance] as $user) {
            $this->actingAs($user)->get('/reports')->assertOk()->assertSee(self::PLACEHOLDER);
        }
    }

    // 54
    public function test_super_admin_never_lands_on_the_placeholder_again(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/reports')
            ->assertRedirect($this->disciplineUrl());

        $this->actingAs($this->superAdmin)
            ->get($this->disciplineUrl())
            ->assertOk()
            ->assertDontSee(self::PLACEHOLDER);
    }

    // The helper itself is the single decision point, so pin its contract.
    public function test_reports_navigation_helper_resolves_per_role(): void
    {
        $this->assertSame($this->disciplineUrl(), ReportsNavigation::url($this->superAdmin));
        $this->assertTrue(ReportsNavigation::pointsToDisciplineDashboard($this->superAdmin));

        foreach ([$this->adminHr, $this->finance, $this->employeeUser] as $user) {
            $this->assertSame(route('reports.index'), ReportsNavigation::url($user));
            $this->assertFalse(ReportsNavigation::pointsToDisciplineDashboard($user));
        }
    }
}
