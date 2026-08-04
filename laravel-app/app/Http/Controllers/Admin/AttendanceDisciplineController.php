<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AttendanceDisciplineExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceDisciplineFilterRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Services\AttendanceDisciplineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Phase 60B — Dashboard Disiplin Kehadiran (super_admin only).
 *
 * Reports attendance discipline and data completeness. It produces no employee
 * score, no ranking, and no automated judgement about work quality.
 *
 * Authorization is enforced in three independent places, because the frontend is
 * never the source of truth: the route group's `role:super_admin` middleware,
 * the abort_unless below (mirroring the existing AuditLogController pattern),
 * and AttendanceDisciplineFilterRequest::authorize(). Hiding the nav entry is
 * cosmetic only — a direct URL or a direct GET on the export still 403s.
 */
class AttendanceDisciplineController extends Controller
{
    /**
     * Upper bound on how wide a single export may be.
     *
     * The database side of the export streams in chunks, but PhpSpreadsheet
     * assembles the worksheet in memory before writing (that is how the library
     * works — there is no true streaming XLSX writer), so an unbounded range is
     * a memory risk. A year plus one day covers any realistic reporting period
     * while keeping a single file bounded. The dashboard itself is paginated and
     * aggregate-driven, so it is not capped.
     */
    public const MAX_EXPORT_DAYS = 366;

    public function __construct(private readonly AttendanceDisciplineService $service) {}

    public function index(AttendanceDisciplineFilterRequest $request): View
    {
        $this->authorizeSuperAdmin();

        $filter  = $request->toFilter();
        $summary = $this->service->summary($filter);

        return view('pages.admin.attendance-discipline', [
            'filter'      => $filter,
            'summary'     => $summary,
            'charts'      => $this->service->charts($summary),
            'trend'       => $this->service->trend($filter),
            'records'     => $this->service->table($filter),
            'employees'   => $this->employeeOptions(),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'statuses'    => AttendanceDisciplineService::STATUS_LABELS,
            'service'     => $this->service,
        ]);
    }

    public function export(
        AttendanceDisciplineFilterRequest $request,
        AttendanceDisciplineExport $export,
    ): StreamedResponse|RedirectResponse {
        $this->authorizeSuperAdmin();

        $filter = $request->toFilter();

        if ($filter->dayCount() > self::MAX_EXPORT_DAYS) {
            // Controlled message, not an exception — an over-wide range must not
            // surface a stack trace or a memory-exhaustion error page.
            return redirect()
                ->route('admin.attendance-discipline.index', $filter->toQueryString())
                ->withErrors([
                    'start_date' => 'Periode export maksimal '.self::MAX_EXPORT_DAYS.' hari. Persempit rentang tanggal terlebih dahulu.',
                ]);
        }

        return $export->download($filter);
    }

    /**
     * No download audit log is written here. The one existing export in the app
     * (PayrollPeriodController::export) does not log either, so there is no
     * established export-audit pattern to follow, and Phase 60B is not the place
     * to invent one.
     */
    private function authorizeSuperAdmin(): void
    {
        abort_unless(auth()->user()?->role === User::ROLE_SUPER_ADMIN, 403);
    }

    /**
     * Filter dropdown options. Eager-loads the user relation so building the
     * list costs two queries rather than one per employee.
     */
    private function employeeOptions()
    {
        return Employee::with('user:id,name')
            ->get(['id', 'user_id', 'nik'])
            ->sortBy(fn (Employee $employee) => $employee->user?->name ?? '')
            ->values();
    }
}
