<?php

namespace App\Services;

use App\Models\Employee;
use Carbon\Carbon;

/**
 * Single source of truth for annual leave entitlement (Phase 58C).
 *
 * Policy (STIKES Advaita, official decision):
 * - 18 working days/year, granted only after 12 consecutive months of service.
 * - eligibility_date = join_date + 12 months (leap-year/end-of-month safe).
 * - Employees eligible before or on 1 January of the balance year get the full
 *   18 days for that year.
 * - Employees who become eligible mid-year get a prorated entitlement: the
 *   month the 12-month anniversary falls in still counts as a full month.
 *   Rate is 18 / 12 = 1.5 days/month, floored to a whole number of days.
 * - Entitlement is never based on when the HRIS application itself started
 *   being used — only on join_date and the calendar year being calculated.
 */
class AnnualLeaveEntitlementService
{
    public const FULL_YEAR_ENTITLEMENT = 18;
    public const MONTHLY_RATE = 1.5;
    private const MIN_SERVICE_MONTHS = 12;

    public function calculate(Employee $employee, int $year): array
    {
        $joinDate = $employee->join_date;

        if ($joinDate === null) {
            return [
                'eligibility_date'    => null,
                'eligible'            => false,
                'months_remaining'    => 0,
                'raw_entitlement'     => 0.0,
                'final_entitlement'   => 0,
                'needs_hr_correction' => true,
                'reason'              => 'join_date tidak tersedia atau tidak valid — data pegawai memerlukan koreksi HR.',
            ];
        }

        $eligibilityDate = $joinDate->copy()->addMonthsNoOverflow(self::MIN_SERVICE_MONTHS);
        $yearStart = Carbon::create($year, 1, 1)->startOfDay();
        $yearEnd = Carbon::create($year, 12, 31)->endOfDay();

        if ($eligibilityDate->gt($yearEnd)) {
            return [
                'eligibility_date'    => $eligibilityDate,
                'eligible'            => false,
                'months_remaining'    => 0,
                'raw_entitlement'     => 0.0,
                'final_entitlement'   => 0,
                'needs_hr_correction' => false,
                'reason'              => "Belum memenuhi masa kerja 12 bulan pada tahun {$year}; hak cuti dimulai {$eligibilityDate->toDateString()}.",
            ];
        }

        if ($eligibilityDate->lte($yearStart)) {
            return [
                'eligibility_date'    => $eligibilityDate,
                'eligible'            => true,
                'months_remaining'    => 12,
                'raw_entitlement'     => (float) self::FULL_YEAR_ENTITLEMENT,
                'final_entitlement'   => self::FULL_YEAR_ENTITLEMENT,
                'needs_hr_correction' => false,
                'reason'              => "Sudah memenuhi masa kerja 12 bulan sebelum atau pada 1 Januari {$year} — hak penuh 18 hari.",
            ];
        }

        // eligibility_date falls within the balance year: prorate from the
        // anniversary month (inclusive) through December.
        $monthsRemaining = 12 - $eligibilityDate->month + 1;
        $rawEntitlement = $monthsRemaining * self::MONTHLY_RATE;
        $finalEntitlement = max(0, min(self::FULL_YEAR_ENTITLEMENT, (int) floor($rawEntitlement)));

        return [
            'eligibility_date'    => $eligibilityDate,
            'eligible'            => true,
            'months_remaining'    => $monthsRemaining,
            'raw_entitlement'     => $rawEntitlement,
            'final_entitlement'   => $finalEntitlement,
            'needs_hr_correction' => false,
            'reason'              => "Hak prorata: {$monthsRemaining} bulan x 1,5 hari = {$rawEntitlement} hari, dibulatkan ke bawah menjadi {$finalEntitlement} hari.",
        ];
    }
}
