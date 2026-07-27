<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Services\AnnualLeaveEntitlementService;
use App\Services\AuditLogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class InitializeAnnualLeaveYear extends Command
{
    protected $signature = 'leave:initialize-year
        {year? : Balance year to initialize (defaults to the current year)}
        {--dry-run : Preview what would be created without writing to the database}';

    protected $description = 'Create annual leave entitlement balances (full 18 days or prorated) for eligible active employees for a given year. Idempotent — never overwrites or duplicates existing rows, never carries over prior-year balance.';

    public function __construct(
        private readonly AnnualLeaveEntitlementService $entitlementService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $year   = (int) ($this->argument('year') ?: now()->year);
        $dryRun = (bool) $this->option('dry-run');

        // Phase 58C correction: Annual Leave and Personal Leave share ONE
        // 18-day/year pool (they already share one monthly cap), so exactly
        // one leave_balances row per employee per year is created here,
        // keyed by the canonical annual-entitlement type — never one row per
        // matching leave type, which would create an additive 18-day pool
        // for each type instead of one shared pool.
        $canonicalType = LeaveType::canonicalAnnualEntitlementType();

        if ($canonicalType === null) {
            // Fail clearly rather than silently skipping or picking an
            // arbitrary leave type to build balances on.
            $this->error('Jenis cuti tahunan kanonik (Annual Leave/Cuti Tahunan) tidak ditemukan. Tidak ada saldo yang diproses.');

            return self::FAILURE;
        }

        $summary = [
            'created'           => 0,
            'skipped_existing'  => 0,
            'not_eligible'      => 0,
            'invalid_join_date' => 0,
            'errors'            => 0,
        ];

        $employees = Employee::where('employment_status', 'active')->get();

        foreach ($employees as $employee) {
            try {
                $this->processOne($employee, $canonicalType, $year, $dryRun, $summary);
            } catch (Throwable $e) {
                $summary['errors']++;
                $this->error("Employee #{$employee->id}: {$e->getMessage()}");
            }
        }

        $this->table(['Ringkasan', 'Jumlah'], [
            ['Dibuat', $summary['created']],
            ['Sudah tersedia (dilewati)', $summary['skipped_existing']],
            ['Tidak eligible', $summary['not_eligible']],
            ['Join_date tidak valid', $summary['invalid_join_date']],
            ['Error', $summary['errors']],
        ]);

        if ($dryRun) {
            $this->info("Dry-run untuk tahun {$year}: tidak ada perubahan yang ditulis ke database.");
        } else {
            $this->info("Selesai memproses tahun {$year}.");
        }

        return self::SUCCESS;
    }

    /** @param array{created:int,skipped_existing:int,not_eligible:int,invalid_join_date:int,errors:int} $summary */
    private function processOne(Employee $employee, LeaveType $leaveType, int $year, bool $dryRun, array &$summary): void
    {
        $entitlement = $this->entitlementService->calculate($employee, $year);

        if ($entitlement['needs_hr_correction']) {
            $summary['invalid_join_date']++;

            return;
        }

        if (! $entitlement['eligible']) {
            $summary['not_eligible']++;

            return;
        }

        $exists = LeaveBalance::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $year)
            ->exists();

        if ($exists) {
            $summary['skipped_existing']++;

            return;
        }

        if ($dryRun) {
            $summary['created']++;

            return;
        }

        DB::transaction(function () use ($employee, $leaveType, $year, $entitlement): void {
            $days = $entitlement['final_entitlement'];

            $balance = LeaveBalance::create([
                'employee_id'   => $employee->id,
                'leave_type_id' => $leaveType->id,
                'year'          => $year,
                'total_quota'   => $days,
                'used'          => 0,
                'remaining'     => $days,
            ]);

            AuditLogService::log(
                null,
                'initialize_annual_leave_balance',
                'leave',
                "Saldo cuti tahunan {$leaveType->name} tahun {$year} untuk employee #{$employee->id} dibuat via leave:initialize-year: {$entitlement['reason']}",
                null,
                LeaveBalance::class,
                $balance->id,
                null,
                ['total_quota' => $days, 'used' => 0, 'remaining' => $days],
            );
        });

        $summary['created']++;
    }
}
