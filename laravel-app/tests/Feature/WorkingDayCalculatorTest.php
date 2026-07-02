<?php

namespace Tests\Feature;

use App\Models\Holiday;
use App\Services\WorkingDayCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkingDayCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private WorkingDayCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new WorkingDayCalculator();
    }

    public function test_counts_only_monday_to_friday(): void
    {
        // 2026-06-29 is a Monday, 2026-07-05 is the following Sunday.
        $start = Carbon::parse('2026-06-29');
        $end   = Carbon::parse('2026-07-05');

        $this->assertSame(5, $this->calculator->countWorkingDays($start, $end));
    }

    public function test_weekend_only_range_counts_zero(): void
    {
        // 2026-07-04 (Sat) - 2026-07-05 (Sun)
        $start = Carbon::parse('2026-07-04');
        $end   = Carbon::parse('2026-07-05');

        $this->assertSame(0, $this->calculator->countWorkingDays($start, $end));
    }

    public function test_national_holiday_is_excluded_from_working_days(): void
    {
        // 2026-06-29 (Mon) - 2026-07-03 (Fri): 5 working days, minus 1 holiday.
        Holiday::create(['date' => '2026-07-01', 'name' => 'Test National Holiday']);

        $start = Carbon::parse('2026-06-29');
        $end   = Carbon::parse('2026-07-03');

        $this->assertSame(4, $this->calculator->countWorkingDays($start, $end));
    }

    public function test_calendar_days_mode_counts_every_day_including_weekend(): void
    {
        Holiday::create(['date' => '2026-07-01', 'name' => 'Test National Holiday']);

        $start = Carbon::parse('2026-06-29'); // Mon
        $end   = Carbon::parse('2026-07-05'); // Sun

        $this->assertSame(7, $this->calculator->countChargeableDays($start, $end, false));
    }

    public function test_working_days_by_month_splits_range_spanning_two_months(): void
    {
        // 2026-06-29 (Mon) - 2026-07-02 (Thu): 2 working days in June, 2 in July.
        $start = Carbon::parse('2026-06-29');
        $end   = Carbon::parse('2026-07-02');

        $byMonth = $this->calculator->workingDaysByMonth($start, $end);

        $this->assertSame(2, $byMonth['2026-06']);
        $this->assertSame(2, $byMonth['2026-07']);
    }
}
