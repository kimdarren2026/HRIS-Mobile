<?php

namespace App\Console\Commands;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Services\Authentication\GoogleSsoService;
use App\Services\AuditLogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

class EmployeeSsoImport extends Command
{
    protected $signature = 'employees:sync-sso
        {file : Path to the CSV file (email,name,nik,position,department,join_date,phone,role,action,allow_name_duplicate,notes)}
        {--dry-run : Preview the import plan without writing to the database (default)}
        {--apply : Persist the import plan to the database}';

    protected $description = 'Import/update employees for Google SSO onboarding from a CSV file that lives outside the repository.';

    private const REQUIRED_COLUMNS = ['email', 'name', 'nik', 'position', 'department', 'join_date', 'phone', 'role', 'action', 'allow_name_duplicate', 'notes'];

    private const ACTION_CREATE_OR_UPDATE = 'CREATE_OR_UPDATE';

    private const ACTION_UPDATE_ONLY = 'UPDATE_ONLY';

    private const ACTION_SKIP = 'SKIP';

    private const VALID_ACTIONS = [self::ACTION_CREATE_OR_UPDATE, self::ACTION_UPDATE_ONLY, self::ACTION_SKIP];

    /**
     * Roles this import may assign. finance and super_admin can never be set
     * by an import file — those are managed exclusively through /admin/users.
     */
    private const IMPORTABLE_ROLES = [User::ROLE_EMPLOYEE, User::ROLE_ADMIN_HR];

    /**
     * Conflict classification.
     *
     * BATCH-CRITICAL — any row carrying one of these flags aborts the entire
     * --apply run before a single row is written (see finalizePlans()). This
     * import processes an official employee roster: a typo in role/action, or
     * a bad email, must never cause one person to be silently skipped.
     *   - MISSING_REQUIRED_FIELD         Row is missing a mandatory email or name.
     *   - INVALID_EMAIL                  Email is present but not a valid address.
     *   - INVALID_ROLE                   role column is not "employee" or "admin_hr".
     *   - INVALID_ACTION                 action column is not one of the three valid values.
     *   - CONFLICT_NIK                   NIK already belongs to a different email.
     *   - MISSING_MASTER_DATA_DEPARTMENT Department name does not match master data.
     *   - MISSING_MASTER_DATA_POSITION   Position name does not match master data.
     *   - UPDATE_ONLY_NOT_FOUND          UPDATE_ONLY row has no existing user+employee.
     *   - DUPLICATE_EMAIL_IN_FILE        Same normalized email appears twice in the CSV.
     *   - DUPLICATE_NIK_IN_FILE          Same NIK appears twice in the CSV.
     *   - ROLE_CONFLICT_CRITICAL         Matched user is super_admin or finance; never
     *                                    touched automatically, held for manual review.
     * An uncaught exception during the apply transaction is also treated as
     * batch-critical: the transaction rolls back and the command exits non-zero
     * (see handle()).
     *
     * ROW-LEVEL — only this row is skipped, the rest of the batch still applies:
     *   - ROLE_CONFLICT       Matched user's role would have to be *downgraded*
     *                         to satisfy this row's target role; role is never changed.
     *   - POSSIBLE_DUPLICATE  Name matches an existing user under a different email.
     *                         Blocks create/update by default — same name plus a
     *                         different email plus no NIK match is not enough to
     *                         prove it's the same person. A row can only proceed
     *                         despite this flag when the CSV explicitly sets
     *                         allow_name_duplicate=1 for that row (an operator's
     *                         reviewed, one-off exception — never inferred).
     *   - explicit SKIP action.
     */
    private const CRITICAL_FLAGS = [
        'MISSING_REQUIRED_FIELD',
        'INVALID_EMAIL',
        'INVALID_ROLE',
        'INVALID_ACTION',
        'CONFLICT_NIK',
        'MISSING_MASTER_DATA_DEPARTMENT',
        'MISSING_MASTER_DATA_POSITION',
        'UPDATE_ONLY_NOT_FOUND',
        'DUPLICATE_EMAIL_IN_FILE',
        'DUPLICATE_NIK_IN_FILE',
        'ROLE_CONFLICT_CRITICAL',
    ];

    public function __construct(
        private readonly GoogleSsoService $googleSsoService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->option('apply') && $this->option('dry-run')) {
            $this->components->error('Use either --dry-run or --apply, not both.');

            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');

        if (! $apply) {
            $this->components->info('Running in dry-run mode. No changes will be written. Pass --apply to persist changes.');
        }

        $path = (string) $this->argument('file');

        if (! File::exists($path)) {
            $this->components->error("CSV file not found: {$path}");

            return self::FAILURE;
        }

        $rows = $this->readCsv($path);

        if ($rows === null) {
            return self::FAILURE;
        }

        // ── TAHAP 1: PREFLIGHT ──────────────────────────────────────────────
        // Parse, normalize, and evaluate every row against the database and
        // against every other row in this file. Nothing is written here.
        $plans = array_map(fn (array $row) => $this->planRow($row), $rows);
        $plans = $this->finalizePlans($plans);

        $batchCritical = array_values(array_filter($plans, fn (array $plan) => $plan['tier'] === 'critical'));

        if ($apply && $batchCritical !== []) {
            $this->printReport($plans, $apply);
            $this->newLine();
            $this->components->error('Apply aborted: one or more rows have a critical conflict ('.implode(', ', self::CRITICAL_FLAGS).'). No changes were written.');

            return self::FAILURE;
        }

        // ── TAHAP 2: APPLY ──────────────────────────────────────────────────
        // Only reached when preflight found zero batch-critical conflicts.
        if ($apply) {
            try {
                DB::transaction(function () use ($plans): void {
                    foreach ($plans as $plan) {
                        $this->applyPlan($plan);
                    }
                });
            } catch (Throwable) {
                $this->printReport($plans, $apply);
                $this->newLine();
                $this->components->error('Apply aborted due to an unexpected error during the transaction. It was rolled back — no changes were written.');

                return self::FAILURE;
            }
        }

        $this->printReport($plans, $apply);

        return self::SUCCESS;
    }

    /**
     * @return list<array<string, string>>|null
     */
    private function readCsv(string $path): ?array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            $this->components->error("Unable to open CSV file: {$path}");

            return null;
        }

        $header = fgetcsv($handle);

        if ($header === false) {
            fclose($handle);
            $this->components->error('CSV file is empty.');

            return null;
        }

        $header = array_map(static fn ($col) => strtolower(trim((string) $col)), $header);

        if ($header !== self::REQUIRED_COLUMNS) {
            fclose($handle);
            $this->components->error('CSV header must be exactly: '.implode(',', self::REQUIRED_COLUMNS));

            return null;
        }

        $rows = [];

        while (($line = fgetcsv($handle)) !== false) {
            if ($line === [null] || $line === ['']) {
                continue;
            }

            $rows[] = array_combine($header, array_map(
                static fn ($value) => trim((string) $value),
                $line,
            ));
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, mixed>
     */
    private function planRow(array $row): array
    {
        $emailRaw = $row['email'];
        $name = $row['name'];
        $action = strtoupper(trim($row['action']));
        $normalizedEmail = $this->googleSsoService->normalizeEmail($emailRaw);

        $plan = [
            'email_raw' => $emailRaw,
            'email' => $normalizedEmail,
            'name' => $name,
            'action' => $action,
            'notes' => $row['notes'],
            'flags' => [],
            'tier' => 'none',
            'operation' => 'skip',
            'reason' => null,
            'existing_user_id' => null,
            'existing_employee_id' => null,
            'role_change' => false,
        ];

        if (trim($emailRaw) === '' || $name === '') {
            $plan['flags'][] = 'MISSING_REQUIRED_FIELD';
            $plan['operation'] = 'skip';
            $plan['reason'] = 'missing_required_field';

            return $plan;
        }

        if ($normalizedEmail === null) {
            $plan['flags'][] = 'INVALID_EMAIL';
            $plan['operation'] = 'skip';
            $plan['reason'] = 'invalid_email';

            return $plan;
        }

        if (! in_array($action, self::VALID_ACTIONS, true)) {
            $plan['flags'][] = 'INVALID_ACTION';
            $plan['operation'] = 'skip';
            $plan['reason'] = 'invalid_action';

            return $plan;
        }

        if ($action === self::ACTION_SKIP) {
            $plan['operation'] = 'skip';
            $plan['reason'] = 'explicit_skip';

            return $plan;
        }

        $targetRole = strtolower(trim($row['role']));
        $targetRole = $targetRole === '' ? User::ROLE_EMPLOYEE : $targetRole;

        if (! in_array($targetRole, self::IMPORTABLE_ROLES, true)) {
            $plan['flags'][] = 'INVALID_ROLE';
            $plan['operation'] = 'skip';
            $plan['reason'] = 'invalid_role';

            return $plan;
        }

        $plan['target_role'] = $targetRole;
        $allowNameDuplicate = trim($row['allow_name_duplicate'] ?? '') === '1';

        $userByEmail = User::query()->whereRaw('LOWER(email) = ?', [$normalizedEmail])->first();
        $plan['existing_user_id'] = $userByEmail?->id;
        $plan['existing_employee_id'] = $userByEmail?->employee?->id;

        $nik = trim($row['nik']);
        $nik = $nik === '' ? null : $nik;

        if ($nik !== null) {
            $employeeByNik = Employee::query()->with('user')->where('nik', $nik)->first();

            if ($employeeByNik && $employeeByNik->user?->email !== $normalizedEmail) {
                $plan['flags'][] = 'CONFLICT_NIK';
            }
        }

        // Blocks new-account creation by default — see CRITICAL_FLAGS docblock
        // for the allow_name_duplicate=1 override and why it must be explicit.
        $normalizedName = $this->normalizeText($name);
        $nameMatch = User::query()->whereRaw('LOWER(TRIM(name)) = ?', [$normalizedName])->first();

        if ($nameMatch && $nameMatch->id !== $userByEmail?->id && strtolower($nameMatch->email) !== $normalizedEmail) {
            $plan['flags'][] = 'POSSIBLE_DUPLICATE';
        }

        [$departmentId, $departmentMissing] = $this->resolveDepartment(trim($row['department']));
        [$positionId, $positionMissing] = $this->resolvePosition(trim($row['position']), $departmentId);

        if ($departmentMissing) {
            $plan['flags'][] = 'MISSING_MASTER_DATA_DEPARTMENT';
        }

        if ($positionMissing) {
            $plan['flags'][] = 'MISSING_MASTER_DATA_POSITION';
        }

        [$joinDate, $joinDateIncomplete] = $this->resolveJoinDate(trim($row['join_date']));

        if ($joinDateIncomplete) {
            $plan['flags'][] = 'JOIN_DATE_INCOMPLETE';
        }

        $phone = trim($row['phone']);
        $phone = $phone === '' ? null : $phone;

        $plan['nik'] = $nik;
        $plan['department_id'] = $departmentId;
        $plan['position_id'] = $positionId;
        $plan['join_date'] = $joinDate;
        $plan['phone'] = $phone;

        if ($nik === null || $departmentId === null || $positionId === null || $joinDate === null || $phone === null) {
            $plan['flags'][] = 'PROFILE_INCOMPLETE';
        }

        if ($action === self::ACTION_UPDATE_ONLY) {
            if (! $userByEmail || ! $userByEmail->employee) {
                // UPDATE_ONLY must resolve to an existing user+employee; it
                // never falls back to CREATE_OR_UPDATE — see finalizePlans()
                // for the batch-wide abort this triggers.
                $plan['flags'][] = 'UPDATE_ONLY_NOT_FOUND';
                $plan['operation'] = 'skip';
                $plan['reason'] = 'update_only_not_found';

                return $plan;
            }

            $plan['operation'] = 'update_employee';
            $plan['existing_employee_id'] = $userByEmail->employee->id;

            return $plan;
        }

        // ACTION_CREATE_OR_UPDATE from here on.
        if ($userByEmail) {
            $existingRole = $userByEmail->role;

            if (in_array($existingRole, [User::ROLE_SUPER_ADMIN, User::ROLE_FINANCE], true)) {
                $plan['flags'][] = 'ROLE_CONFLICT_CRITICAL';
                $plan['operation'] = 'skip';
                $plan['reason'] = 'role_conflict_critical';

                return $plan;
            }

            if ($existingRole === $targetRole) {
                // No role change needed.
            } elseif ($existingRole === User::ROLE_EMPLOYEE && $targetRole === User::ROLE_ADMIN_HR) {
                // The only automatic role transition this command performs:
                // a plain employee being promoted to admin_hr by the import
                // file itself (an explicit, reviewable decision in the CSV).
                $plan['role_change'] = true;
            } else {
                // Any other mismatch (e.g. admin_hr -> employee downgrade)
                // is never applied automatically.
                $plan['flags'][] = 'ROLE_CONFLICT';
                $plan['operation'] = 'skip';
                $plan['reason'] = 'role_conflict';

                return $plan;
            }

            if ($userByEmail->employee) {
                $plan['operation'] = 'update_employee';

                return $plan;
            }

            $plan['operation'] = 'create_employee_for_existing_user';

            return $plan;
        }

        if (in_array('POSSIBLE_DUPLICATE', $plan['flags'], true) && ! $allowNameDuplicate) {
            $plan['operation'] = 'skip';
            $plan['reason'] = 'possible_duplicate_manual_review';

            return $plan;
        }

        $plan['operation'] = 'create_user_and_employee';

        return $plan;
    }

    /**
     * Cross-row checks the database alone can't see (duplicates within this
     * CSV), then the single source of truth for which rows are batch-critical:
     * any row whose flags intersect CRITICAL_FLAGS is forced to 'skip' and its
     * tier is set to 'critical', which aborts the whole --apply in handle().
     *
     * @param  list<array<string, mixed>>  $plans
     * @return list<array<string, mixed>>
     */
    private function finalizePlans(array $plans): array
    {
        $emailGroups = [];
        $nikGroups = [];

        foreach ($plans as $i => $plan) {
            if ($plan['email'] !== null && $plan['action'] !== self::ACTION_SKIP) {
                $emailGroups[$plan['email']][] = $i;
            }

            if (($plan['nik'] ?? null) !== null) {
                $nikGroups[$plan['nik']][] = $i;
            }
        }

        foreach ($emailGroups as $indexes) {
            if (count($indexes) > 1) {
                foreach ($indexes as $i) {
                    $plans[$i]['flags'][] = 'DUPLICATE_EMAIL_IN_FILE';
                }
            }
        }

        foreach ($nikGroups as $indexes) {
            if (count($indexes) > 1) {
                foreach ($indexes as $i) {
                    $plans[$i]['flags'][] = 'DUPLICATE_NIK_IN_FILE';
                }
            }
        }

        foreach ($plans as $i => $plan) {
            if (array_intersect($plan['flags'], self::CRITICAL_FLAGS) !== []) {
                $plans[$i]['tier'] = 'critical';
                $plans[$i]['operation'] = 'skip';
                $plans[$i]['reason'] = 'critical_conflict';
            }
        }

        return $plans;
    }

    /**
     * @param  array<string, mixed>  $plan
     */
    private function applyPlan(array $plan): void
    {
        match ($plan['operation']) {
            'create_user_and_employee' => $this->createUserAndEmployee($plan),
            'create_employee_for_existing_user' => $this->createEmployeeForExistingUser($plan),
            'update_employee' => $this->updateEmployee($plan),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $plan
     */
    private function createUserAndEmployee(array $plan): void
    {
        $user = User::create([
            'name' => $plan['name'],
            'email' => $plan['email'],
            'password' => Hash::make(Str::random(48)),
            'role' => $plan['target_role'],
            'is_active' => true,
        ]);

        AuditLogService::log(
            null,
            'sso_import_create_user',
            'employee',
            "User '{$plan['email']}' created via employees:sync-sso import.",
            null,
            User::class,
            $user->id,
            null,
            ['email' => $plan['email'], 'role' => $plan['target_role']],
        );

        $this->createEmployeeRecord($plan, $user->id);
    }

    /**
     * @param  array<string, mixed>  $plan
     */
    private function createEmployeeForExistingUser(array $plan): void
    {
        $this->applyRoleChangeIfNeeded($plan);
        $this->createEmployeeRecord($plan, $plan['existing_user_id']);
    }

    /**
     * @param  array<string, mixed>  $plan
     */
    private function createEmployeeRecord(array $plan, int $userId): void
    {
        $employee = Employee::create([
            'user_id' => $userId,
            'nik' => $plan['nik'],
            'department_id' => $plan['department_id'],
            'position_id' => $plan['position_id'],
            'join_date' => $plan['join_date'],
            'employment_status' => 'active',
            'phone_number' => $plan['phone'],
        ]);

        AuditLogService::log(
            null,
            'sso_import_create_employee',
            'employee',
            "Employee linked to user #{$userId} created via employees:sync-sso import.",
            null,
            Employee::class,
            $employee->id,
            null,
            $employee->only(['nik', 'department_id', 'position_id', 'join_date', 'phone_number']),
        );
    }

    /**
     * @param  array<string, mixed>  $plan
     */
    private function updateEmployee(array $plan): void
    {
        $this->applyRoleChangeIfNeeded($plan);

        $employee = Employee::findOrFail($plan['existing_employee_id']);
        $old = $employee->only(['nik', 'department_id', 'position_id', 'join_date', 'phone_number']);

        $changes = [];

        if ($plan['nik'] !== null) {
            $changes['nik'] = $plan['nik'];
        }

        if ($plan['department_id'] !== null) {
            $changes['department_id'] = $plan['department_id'];
        }

        if ($plan['position_id'] !== null) {
            $changes['position_id'] = $plan['position_id'];
        }

        if ($plan['join_date'] !== null) {
            $changes['join_date'] = $plan['join_date'];
        }

        if ($plan['phone'] !== null) {
            $changes['phone_number'] = $plan['phone'];
        }

        if ($changes === []) {
            return;
        }

        $employee->update($changes);

        AuditLogService::log(
            null,
            'sso_import_update_employee',
            'employee',
            "Employee #{$employee->id} updated via employees:sync-sso import.",
            null,
            Employee::class,
            $employee->id,
            $old,
            $employee->only(['nik', 'department_id', 'position_id', 'join_date', 'phone_number']),
        );
    }

    /**
     * Applies the single approved automatic role transition (employee ->
     * admin_hr) for an existing user, with an audit trail. No-op otherwise —
     * see planRow() for why every other mismatch is refused before this runs.
     *
     * @param  array<string, mixed>  $plan
     */
    private function applyRoleChangeIfNeeded(array $plan): void
    {
        if (! ($plan['role_change'] ?? false)) {
            return;
        }

        $user = User::findOrFail($plan['existing_user_id']);
        $oldRole = $user->role;
        $user->forceFill(['role' => $plan['target_role']])->save();

        AuditLogService::log(
            null,
            'sso_import_update_role',
            'employee',
            "User #{$user->id} role changed via employees:sync-sso import.",
            null,
            User::class,
            $user->id,
            ['role' => $oldRole],
            ['role' => $plan['target_role']],
        );
    }

    /**
     * @return array{0: ?int, 1: bool} [resolved department id, missing flag]
     */
    private function resolveDepartment(string $name): array
    {
        if ($name === '') {
            return [null, false];
        }

        $department = Department::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [$this->normalizeText($name)])
            ->first();

        return $department ? [$department->id, false] : [null, true];
    }

    /**
     * @return array{0: ?int, 1: bool} [resolved position id, missing flag]
     */
    private function resolvePosition(string $name, ?int $departmentId): array
    {
        if ($name === '') {
            return [null, false];
        }

        $query = Position::query()->whereRaw('LOWER(TRIM(name)) = ?', [$this->normalizeText($name)]);

        $query = $departmentId !== null
            ? $query->where('department_id', $departmentId)
            : $query->whereNull('department_id');

        $position = $query->first();

        return $position ? [$position->id, false] : [null, true];
    }

    /**
     * @return array{0: ?string, 1: bool} [resolved Y-m-d date, incomplete flag]
     */
    private function resolveJoinDate(string $raw): array
    {
        if ($raw === '') {
            return [null, false];
        }

        $date = \DateTime::createFromFormat('Y-m-d', $raw);
        $errors = \DateTime::getLastErrors();
        $hasErrors = $errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0);

        if ($date === false || $hasErrors || $date->format('Y-m-d') !== $raw) {
            return [null, true];
        }

        return [$raw, false];
    }

    private function normalizeText(string $value): string
    {
        return strtolower(trim((string) preg_replace('/\s+/', ' ', $value)));
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $visible = substr($local, 0, 2);

        return $visible.str_repeat('*', max(1, strlen($local) - strlen($visible))).'@'.$domain;
    }

    /**
     * @param  list<array<string, mixed>>  $plans
     */
    private function printReport(array $plans, bool $apply): void
    {
        $this->newLine();
        $this->components->twoColumnDetail('<fg=default>Mode</>', $apply ? 'APPLY (changes written)' : 'DRY-RUN (no changes written)');
        $this->newLine();

        $rows = array_map(function (array $plan) {
            return [
                $this->maskEmail($plan['email'] ?? $plan['email_raw']),
                $plan['name'],
                $plan['action'],
                $plan['target_role'] ?? '',
                $plan['operation'],
                $plan['existing_user_id'] ? 'yes' : 'no',
                $plan['existing_employee_id'] ? 'yes' : 'no',
                implode(',', $plan['flags']),
                $plan['reason'] ?? '',
            ];
        }, $plans);

        $this->table(
            ['Email', 'Name', 'Action', 'Target Role', 'Plan', 'Existing User', 'Existing Employee', 'Flags', 'Reason'],
            $rows,
        );

        $counts = [
            'create_user_and_employee' => 0,
            'create_employee_for_existing_user' => 0,
            'update_employee' => 0,
            'skip' => 0,
        ];

        foreach ($plans as $plan) {
            $counts[$plan['operation']]++;
        }

        $this->newLine();
        $this->line("Planned create (new user + employee): {$counts['create_user_and_employee']}");
        $this->line("Planned create (employee for existing user): {$counts['create_employee_for_existing_user']}");
        $this->line("Planned update: {$counts['update_employee']}");
        $this->line("Skipped: {$counts['skip']}");
    }
}
