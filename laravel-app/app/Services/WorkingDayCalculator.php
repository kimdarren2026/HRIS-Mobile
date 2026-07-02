<?php

namespace App\Services;

use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Support\Collection;

// STIKES Advaita leave policy points 2 & 3: working days are Monday-Friday
// only, and national holidays are not counted against annual leave balance.
// Internal campus holidays are not regulated yet (policy point 4) and are
// intentionally NOT excluded here.
class WorkingDayCalculator
{
    public function countChargeableDays(Carbon $start, Carbon $end, bool $excludeWeekendsAndHolidays = true): int
    {
        if (! $excludeWeekendsAndHolidays) {
            return $this->countCalendarDays($start, $end);
        }

        return $this->countWorkingDays($start, $end);
    }

    public function countCalendarDays(Carbon $start, Carbon $end): int
    {
        return (int) $start->diffInDays($end) + 1;
    }

    public function countWorkingDays(Carbon $start, Carbon $end): int
    {
        $holidays = $this->holidayDatesBetween($start, $end);

        $count = 0;
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            if (! $cursor->isWeekend() && ! $holidays->contains($cursor->toDateString())) {
                $count++;
            }
            $cursor->addDay();
        }

        return $count;
    }

    /**
     * Working-day count per calendar month touched by the range, keyed by "Y-m".
     * Used to enforce the monthly chargeable-day cap even when a request spans
     * multiple months.
     *
     * @return array<string, int>
     */
    public function workingDaysByMonth(Carbon $start, Carbon $end): array
    {
        $holidays = $this->holidayDatesBetween($start, $end);
        $byMonth = [];

        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            if (! $cursor->isWeekend() && ! $holidays->contains($cursor->toDateString())) {
                $key = $cursor->format('Y-m');
                $byMonth[$key] = ($byMonth[$key] ?? 0) + 1;
            }
            $cursor->addDay();
        }

        return $byMonth;
    }

    private function holidayDatesBetween(Carbon $start, Carbon $end): Collection
    {
        // whereDate() (not whereBetween) because date-cast columns are stored
        // with a "00:00:00" time suffix, which would break exact-boundary
        // string comparisons against plain Y-m-d values.
        return Holiday::whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->get()
            ->map(fn (Holiday $holiday) => $holiday->date->toDateString());
    }
}
