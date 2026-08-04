<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Phase 60B — the single, immutable description of "which attendance rows are
 * we looking at".
 *
 * The dashboard screen and the XLSX export are both built from one instance of
 * this object, so the file a super_admin downloads can never describe a
 * different slice of data than the page they were looking at.
 *
 * Nothing here comes from the request unfiltered: an instance is only ever
 * built from FormRequest-validated input (see AttendanceDisciplineFilterRequest),
 * and every field is either a bounded enum, an integer id, or a date.
 */
final class AttendanceDisciplineFilter
{
    public const RADIUS_ALL     = 'all';

    public const RADIUS_INSIDE  = 'inside';

    public const RADIUS_OUTSIDE = 'outside';

    public const CHECKOUT_ALL        = 'all';

    public const CHECKOUT_COMPLETE   = 'complete';

    public const CHECKOUT_INCOMPLETE = 'incomplete';

    /**
     * The attendance_records.status enum, verbatim from the create migration.
     * AttendanceRecord itself declares no status constants (existing code uses
     * string literals), so this list is the whitelist the filter validates
     * against — a status outside it can never reach a query.
     */
    public const STATUSES = ['APPROVED', 'PENDING_REVIEW', 'REJECTED'];

    private function __construct(
        public readonly CarbonImmutable $startDate,
        public readonly CarbonImmutable $endDate,
        public readonly ?int $employeeId,
        public readonly ?int $departmentId,
        public readonly ?string $status,
        public readonly string $radius,
        public readonly string $checkout,
    ) {}

    /**
     * Build from already-validated input. Absent dates fall back to the current
     * month in the application timezone (config('app.timezone')), never the
     * server's raw PHP default.
     *
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        $today = CarbonImmutable::now(config('app.timezone'));

        $start = ! empty($validated['start_date'])
            ? CarbonImmutable::parse($validated['start_date'], config('app.timezone'))->startOfDay()
            : $today->startOfMonth();

        $end = ! empty($validated['end_date'])
            ? CarbonImmutable::parse($validated['end_date'], config('app.timezone'))->startOfDay()
            : $today->endOfMonth()->startOfDay();

        return new self(
            startDate:    $start,
            endDate:      $end,
            employeeId:   isset($validated['employee_id']) && $validated['employee_id'] !== null
                              ? (int) $validated['employee_id']
                              : null,
            departmentId: isset($validated['department_id']) && $validated['department_id'] !== null
                              ? (int) $validated['department_id']
                              : null,
            status:       ! empty($validated['status']) ? (string) $validated['status'] : null,
            radius:       ! empty($validated['radius']) ? (string) $validated['radius'] : self::RADIUS_ALL,
            checkout:     ! empty($validated['checkout']) ? (string) $validated['checkout'] : self::CHECKOUT_ALL,
        );
    }

    /** Inclusive number of days covered, used to enforce the export period cap. */
    public function dayCount(): int
    {
        return $this->startDate->diffInDays($this->endDate) + 1;
    }

    /** Safe, deterministic filename stem — built from parsed dates, never raw input. */
    public function filenameStem(): string
    {
        return 'attendance-discipline-'
            .$this->startDate->format('Y-m-d')
            .'-to-'
            .$this->endDate->format('Y-m-d');
    }

    /** @return array<string, string|int|null> query-string representation, for pagination links */
    public function toQueryString(): array
    {
        return array_filter([
            'start_date'    => $this->startDate->format('Y-m-d'),
            'end_date'      => $this->endDate->format('Y-m-d'),
            'employee_id'   => $this->employeeId,
            'department_id' => $this->departmentId,
            'status'        => $this->status,
            'radius'        => $this->radius,
            'checkout'      => $this->checkout,
        ], static fn ($value) => $value !== null && $value !== '');
    }
}
