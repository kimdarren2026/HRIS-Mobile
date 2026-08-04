<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Support\AttendanceDisciplineFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\LazyCollection;

/**
 * Phase 60B — Dashboard Disiplin Kehadiran.
 *
 * This describes attendance *discipline and data completeness only*. It is not
 * a performance, productivity, or quality assessment, and it deliberately
 * produces no per-employee score and no ranking.
 *
 * Every read (summary, charts, table, export) starts from the same baseQuery(),
 * so the numbers on screen and the rows in the XLSX can never diverge.
 */
class AttendanceDisciplineService
{
    public const PER_PAGE = 20;

    /** Rows pulled per round-trip when accumulating averages / streaming the export. */
    public const CHUNK_SIZE = 500;

    /**
     * Historical radius classification (Phase 60B, approved decision).
     *
     * attendance_records stores neither office_location_id nor a snapshot of
     * radius_meters, and office_locations.radius_meters is editable (it was
     * changed during Phase 58E/58F). Re-deriving "inside" as
     * `distance_from_office <= current radius` would therefore silently
     * *reclassify history* every time HR edits the office — so we never do it.
     *
     * Instead we read the only persistent trace of the decision that was
     * actually made at check-in time (AttendanceController writes
     * out_of_radius_reason = null on the inside branch, and the employee's
     * required reason on the outside branch):
     *
     *   OUTSIDE  out_of_radius_reason IS NOT NULL
     *   INSIDE   out_of_radius_reason IS NULL AND distance_from_office IS NOT NULL
     *   UNKNOWN  out_of_radius_reason IS NULL AND distance_from_office IS NULL
     *
     * The UNKNOWN bucket exists because distance_from_office was only added in
     * migration 2026_06_27_000001; rows written before it carry no distance
     * evidence at all, so calling them "Dalam Radius" would be a guess. They are
     * surfaced as "Tidak diketahui" rather than being folded into either side.
     *
     * These are literal SQL fragments with no interpolated user input.
     */
    private const SQL_OUTSIDE = 'out_of_radius_reason IS NOT NULL';

    private const SQL_INSIDE = 'out_of_radius_reason IS NULL AND distance_from_office IS NOT NULL';

    private const SQL_UNKNOWN = 'out_of_radius_reason IS NULL AND distance_from_office IS NULL';

    public const RADIUS_INSIDE  = 'INSIDE';

    public const RADIUS_OUTSIDE = 'OUTSIDE';

    public const RADIUS_UNKNOWN = 'UNKNOWN';

    /** @var array<string, string> */
    public const STATUS_LABELS = [
        'APPROVED'       => 'Disetujui',
        'PENDING_REVIEW' => 'Menunggu Review',
        'REJECTED'       => 'Ditolak',
    ];

    /** @var array<string, string> */
    public const RADIUS_LABELS = [
        self::RADIUS_INSIDE  => 'Dalam Radius',
        self::RADIUS_OUTSIDE => 'Luar Radius',
        self::RADIUS_UNKNOWN => 'Tidak diketahui',
    ];

    /**
     * The one query every other method builds on.
     *
     * whereDate() is used rather than a plain where() on attendance_date: that
     * column is a 'date'-cast Eloquent attribute, and Eloquent serialises those
     * with the connection's full datetime format, so a raw string comparison
     * mis-sorts stored values like "2026-08-03 00:00:00". This is the same
     * class of bug fixed in LeaveService during Phase 59D.
     */
    public function baseQuery(AttendanceDisciplineFilter $filter): Builder
    {
        $query = AttendanceRecord::query()
            ->whereDate('attendance_date', '>=', $filter->startDate->format('Y-m-d'))
            ->whereDate('attendance_date', '<=', $filter->endDate->format('Y-m-d'));

        if ($filter->employeeId !== null) {
            $query->where('employee_id', $filter->employeeId);
        }

        if ($filter->departmentId !== null) {
            // Constrained through the relation rather than a join so the base
            // query stays composable and no column name comes from the request.
            $query->whereHas(
                'employee',
                fn (Builder $employee) => $employee->where('department_id', $filter->departmentId)
            );
        }

        if ($filter->status !== null) {
            $query->where('status', $filter->status);
        }

        match ($filter->radius) {
            AttendanceDisciplineFilter::RADIUS_INSIDE  => $query->whereRaw(self::SQL_INSIDE),
            AttendanceDisciplineFilter::RADIUS_OUTSIDE => $query->whereRaw(self::SQL_OUTSIDE),
            default                                    => null,
        };

        match ($filter->checkout) {
            AttendanceDisciplineFilter::CHECKOUT_COMPLETE   => $query->whereNotNull('check_out_time'),
            AttendanceDisciplineFilter::CHECKOUT_INCOMPLETE => $query->whereNull('check_out_time'),
            default                                        => null,
        };

        return $query;
    }

    /**
     * All count metrics in a single aggregate round-trip — the database does the
     * counting, no attendance rows are pulled into memory.
     *
     * COUNT(CASE WHEN ... THEN 1 END) is portable across MySQL (production) and
     * SQLite (test suite); every CASE below is a literal, no user input.
     *
     * @return array<string, int|string|null>
     */
    public function summary(AttendanceDisciplineFilter $filter): array
    {
        $row = $this->baseQuery($filter)
            ->selectRaw('COUNT(*) as total_records')
            ->selectRaw("COUNT(CASE WHEN status = 'APPROVED' THEN 1 END) as total_approved")
            ->selectRaw("COUNT(CASE WHEN status = 'PENDING_REVIEW' THEN 1 END) as total_pending")
            ->selectRaw("COUNT(CASE WHEN status = 'REJECTED' THEN 1 END) as total_rejected")
            ->selectRaw('COUNT(CASE WHEN '.self::SQL_INSIDE.' THEN 1 END) as total_inside')
            ->selectRaw('COUNT(CASE WHEN '.self::SQL_OUTSIDE.' THEN 1 END) as total_outside')
            ->selectRaw('COUNT(CASE WHEN '.self::SQL_UNKNOWN.' THEN 1 END) as total_unknown_radius')
            ->selectRaw('COUNT(CASE WHEN check_out_time IS NOT NULL THEN 1 END) as total_checked_out')
            ->selectRaw('COUNT(CASE WHEN check_out_time IS NULL THEN 1 END) as total_not_checked_out')
            ->selectRaw('COUNT(CASE WHEN check_in_work_plan IS NOT NULL THEN 1 END) as total_with_work_plan')
            ->selectRaw('COUNT(CASE WHEN check_out_work_result IS NOT NULL THEN 1 END) as total_with_work_result')
            ->toBase()
            ->first();

        $averages = $this->averageTimes($filter);

        return [
            'total_records'          => (int) ($row->total_records ?? 0),
            'total_approved'         => (int) ($row->total_approved ?? 0),
            'total_pending'          => (int) ($row->total_pending ?? 0),
            'total_rejected'         => (int) ($row->total_rejected ?? 0),
            'total_inside'           => (int) ($row->total_inside ?? 0),
            'total_outside'          => (int) ($row->total_outside ?? 0),
            'total_unknown_radius'   => (int) ($row->total_unknown_radius ?? 0),
            'total_checked_out'      => (int) ($row->total_checked_out ?? 0),
            'total_not_checked_out'  => (int) ($row->total_not_checked_out ?? 0),
            'total_with_work_plan'   => (int) ($row->total_with_work_plan ?? 0),
            'total_with_work_result' => (int) ($row->total_with_work_result ?? 0),
            'avg_check_in'           => $averages['check_in'],
            'avg_check_out'          => $averages['check_out'],
        ];
    }

    /**
     * Mean check-in / check-out clock time, as HH:mm.
     *
     * Deliberately *not* an AVG() of the raw column: averaging a datetime as a
     * decimal produces nonsense, and the SQL needed to extract seconds-since-
     * midnight is dialect-specific (TIME_TO_SEC on MySQL vs strftime on SQLite),
     * which would mean branching on the driver. Instead we read only the two
     * time columns, in bounded id-ordered chunks, and accumulate
     * seconds-from-start-of-day in PHP.
     *
     * NULLs are skipped entirely — a record with no check-out is *not* counted
     * as 00:00, it simply does not participate in the check-out average. When
     * nothing qualifies, null is returned and the UI renders an em dash.
     *
     * Values come back through the model's 'datetime' cast, so Carbon presents
     * them in config('app.timezone') (Asia/Makassar).
     *
     * @return array{check_in: ?string, check_out: ?string}
     */
    private function averageTimes(AttendanceDisciplineFilter $filter): array
    {
        $checkInSeconds = 0;
        $checkInCount   = 0;
        $checkOutSeconds = 0;
        $checkOutCount   = 0;

        $this->baseQuery($filter)
            ->select(['id', 'check_in_time', 'check_out_time'])
            ->chunkById(self::CHUNK_SIZE, function ($records) use (
                &$checkInSeconds, &$checkInCount, &$checkOutSeconds, &$checkOutCount
            ): void {
                foreach ($records as $record) {
                    if ($record->check_in_time !== null) {
                        $checkInSeconds += $this->secondsFromStartOfDay($record->check_in_time);
                        $checkInCount++;
                    }

                    if ($record->check_out_time !== null) {
                        $checkOutSeconds += $this->secondsFromStartOfDay($record->check_out_time);
                        $checkOutCount++;
                    }
                }
            });

        return [
            'check_in'  => $checkInCount > 0 ? $this->formatSeconds(intdiv($checkInSeconds, $checkInCount)) : null,
            'check_out' => $checkOutCount > 0 ? $this->formatSeconds(intdiv($checkOutSeconds, $checkOutCount)) : null,
        ];
    }

    private function secondsFromStartOfDay(\DateTimeInterface $moment): int
    {
        return ((int) $moment->format('H')) * 3600
            + ((int) $moment->format('i')) * 60
            + (int) $moment->format('s');
    }

    private function formatSeconds(int $seconds): string
    {
        return sprintf('%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60));
    }

    /**
     * Attendance count per calendar day, for the trend chart.
     *
     * DATE(...) is understood by both MySQL and SQLite, and normalises the
     * 'date'-cast storage difference between them (SQLite keeps a full
     * "Y-m-d H:i:s" string, MySQL truncates to a DATE).
     *
     * @return list<array{date: string, total: int}>
     */
    public function trend(AttendanceDisciplineFilter $filter): array
    {
        return $this->baseQuery($filter)
            ->selectRaw('DATE(attendance_date) as day')
            ->selectRaw('COUNT(*) as total')
            ->groupByRaw('DATE(attendance_date)')
            ->orderByRaw('DATE(attendance_date)')
            ->toBase()
            ->get()
            ->map(fn ($row) => [
                'date'  => (string) $row->day,
                'total' => (int) $row->total,
            ])
            ->all();
    }

    /**
     * Chart datasets, all derived from the same summary aggregate so a chart can
     * never contradict its summary card. Categories are always present even when
     * their value is zero, so a chart with one empty category still renders.
     *
     * @param  array<string, int|string|null>  $summary
     * @return array<string, list<array{label: string, value: int}>>
     */
    public function charts(array $summary): array
    {
        return [
            'status' => [
                ['label' => 'Disetujui',       'value' => (int) $summary['total_approved']],
                ['label' => 'Menunggu Review', 'value' => (int) $summary['total_pending']],
                ['label' => 'Ditolak',         'value' => (int) $summary['total_rejected']],
            ],
            'radius' => [
                ['label' => 'Dalam Radius',    'value' => (int) $summary['total_inside']],
                ['label' => 'Luar Radius',     'value' => (int) $summary['total_outside']],
                ['label' => 'Tidak diketahui', 'value' => (int) $summary['total_unknown_radius']],
            ],
            'checkout' => [
                ['label' => 'Sudah Checkout',  'value' => (int) $summary['total_checked_out']],
                ['label' => 'Belum Checkout',  'value' => (int) $summary['total_not_checked_out']],
            ],
        ];
    }

    /**
     * Paginated detail table. Eager-loads every relation the view touches
     * (employee → user / department / position, and the approver) so rendering
     * a page of rows costs a fixed number of queries rather than one per row.
     */
    public function table(AttendanceDisciplineFilter $filter): LengthAwarePaginator
    {
        return $this->ordered($this->baseQuery($filter))
            ->with([
                'employee.user',
                'employee.department',
                'employee.position',
                'approver',
            ])
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * Export feed. Same base query, same ordering, same eager loading as the
     * table — only the pagination differs.
     *
     * lazy() keeps the required ordering while reading in bounded chunks, so the
     * database side of the export never materialises the whole result set.
     */
    public function exportRows(AttendanceDisciplineFilter $filter): LazyCollection
    {
        return $this->ordered($this->baseQuery($filter))
            ->with([
                'employee.user',
                'employee.department',
                'employee.position',
                'approver',
            ])
            ->lazy(self::CHUNK_SIZE);
    }

    /** Newest first; id as a stable tie-breaker. Fixed here — never client-supplied. */
    private function ordered(Builder $query): Builder
    {
        return $query
            ->orderByDesc('attendance_date')
            ->orderByDesc('check_in_time')
            ->orderByDesc('id');
    }

    /**
     * Per-record counterpart of the SQL classification above. Kept in one place
     * so the table, the export, and the summary can never disagree.
     */
    public function classify(AttendanceRecord $record): string
    {
        if ($record->out_of_radius_reason !== null) {
            return self::RADIUS_OUTSIDE;
        }

        return $record->distance_from_office !== null
            ? self::RADIUS_INSIDE
            : self::RADIUS_UNKNOWN;
    }

    public function radiusLabel(AttendanceRecord $record): string
    {
        return self::RADIUS_LABELS[$this->classify($record)];
    }

    public function statusLabel(?string $status): string
    {
        return self::STATUS_LABELS[$status] ?? 'Tidak diketahui';
    }
}
