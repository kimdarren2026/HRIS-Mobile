<?php

namespace App\Console\Commands;

use App\Exceptions\Authentication\ExternalAuthenticationException;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Authentication\GoogleSsoService;
use App\Services\Authentication\UserAccessValidator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProvisionGoogleWorkspaceUsers extends Command
{
    protected $signature = 'hris:provision-google-users
        {--dry-run : Preview the provisioning plan without writing to the database (default)}
        {--apply : Persist the provisioning plan to the database}
        {--file= : Path to a newline-delimited email list (defaults to the bundled seed list)}
        {--emails= : Comma-separated list of emails; overrides --file}';

    protected $description = 'Safely pre-register Google Workspace emails as HRIS users so employees can sign in with Google SSO.';

    public function __construct(
        private readonly GoogleSsoService $googleSsoService,
        private readonly UserAccessValidator $userAccessValidator,
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

        $emails = $this->collectEmails();

        if ($emails === null) {
            return self::FAILURE;
        }

        if ($emails === []) {
            $this->components->error('No email addresses to process.');

            return self::FAILURE;
        }

        $keywords = array_filter(array_map(
            static fn ($keyword): string => strtolower((string) $keyword),
            (array) config('google_workspace_provisioning.shared_unit_keywords', []),
        ));

        $created = [];
        $existing = [];
        $skippedSharedUnit = [];
        $invalid = [];
        $needsProfile = [];
        $readyForSso = [];

        foreach ($emails as $rawEmail) {
            $normalized = $this->googleSsoService->normalizeEmail($rawEmail);

            if ($normalized === null) {
                $invalid[] = ['email' => $rawEmail, 'reason' => 'invalid_format'];

                continue;
            }

            if (! $this->isAllowedWorkspaceDomain($normalized)) {
                $invalid[] = ['email' => $normalized, 'reason' => 'domain_not_allowed'];

                continue;
            }

            $user = User::query()->whereRaw('LOWER(email) = ?', [$normalized])->first();

            if ($user) {
                $ready = $this->isReadyForSso($user);

                $existing[] = [
                    'email' => $normalized,
                    'role' => $user->role,
                    'is_active' => $user->is_active ? 'yes' : 'no',
                    'ready_for_sso' => $ready ? 'yes' : 'no',
                ];

                if ($ready) {
                    $readyForSso[] = $normalized;
                } elseif ($user->requiresEmployeeRecord() && ! $user->employee) {
                    $needsProfile[] = $normalized;
                }

                continue;
            }

            $localPart = Str::before($normalized, '@');

            if ($this->looksLikeSharedUnitAccount($localPart, $keywords)) {
                $skippedSharedUnit[] = ['email' => $normalized, 'reason' => 'shared_or_unit_account_needs_manual_review'];

                continue;
            }

            if ($apply) {
                DB::transaction(function () use ($normalized, $localPart): void {
                    User::create([
                        'name' => $this->deriveDisplayName($localPart),
                        'email' => $normalized,
                        'password' => Hash::make(Str::random(48)),
                        'role' => User::ROLE_EMPLOYEE,
                        'is_active' => false,
                    ]);

                    AuditLogService::log(
                        null,
                        'google_workspace_user_provisioned',
                        'auth',
                        'Akun Google Workspace didaftarkan untuk SSO, menunggu kelengkapan profil pegawai.',
                        ['email' => $normalized, 'role' => User::ROLE_EMPLOYEE],
                    );
                });
            }

            $created[] = ['email' => $normalized, 'role' => User::ROLE_EMPLOYEE, 'is_active' => 'no'];
            $needsProfile[] = $normalized;
        }

        $this->printReport($apply, $created, $existing, $skippedSharedUnit, $invalid, $needsProfile, $readyForSso);

        return self::SUCCESS;
    }

    /**
     * @return list<string>|null
     */
    private function collectEmails(): ?array
    {
        if (filled($this->option('emails'))) {
            $lines = explode(',', (string) $this->option('emails'));
        } else {
            $path = $this->option('file') ?: config('google_workspace_provisioning.seed_list_path');

            if (! is_string($path) || ! File::exists($path)) {
                $this->components->error("Email list file not found: {$path}");

                return null;
            }

            $lines = preg_split('/\r\n|\r|\n/', File::get($path)) ?: [];
        }

        $emails = [];
        $seen = [];

        foreach ($lines as $line) {
            $line = trim((string) $line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $key = strtolower($line);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $emails[] = $line;
        }

        return $emails;
    }

    private function isAllowedWorkspaceDomain(string $email): bool
    {
        $allowedDomains = $this->allowedWorkspaceDomains();

        if ($allowedDomains === []) {
            return false;
        }

        $domain = $this->extractDomain($email);

        return $domain !== null && in_array($domain, $allowedDomains, true);
    }

    /**
     * @return list<string>
     */
    private function allowedWorkspaceDomains(): array
    {
        $domains = config('google_workspace_provisioning.allowed_domains', []);

        return is_array($domains) ? $domains : [];
    }

    private function extractDomain(string $email): ?string
    {
        if (! str_contains($email, '@')) {
            return null;
        }

        $domain = strtolower(substr(strrchr($email, '@'), 1));

        return $domain !== '' ? $domain : null;
    }

    /**
     * @param  list<string>  $keywords
     */
    private function looksLikeSharedUnitAccount(string $localPart, array $keywords): bool
    {
        $localPart = strtolower($localPart);

        foreach ($keywords as $keyword) {
            if ($keyword !== '' && str_contains($localPart, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function deriveDisplayName(string $localPart): string
    {
        $normalized = str_replace(['.', '_', '-'], ' ', $localPart);
        $normalized = trim((string) preg_replace('/\s+/', ' ', $normalized));

        return $normalized === '' ? $localPart : Str::title($normalized);
    }

    private function isReadyForSso(User $user): bool
    {
        try {
            $this->userAccessValidator->validate($user->loadMissing('employee'));

            return true;
        } catch (ExternalAuthenticationException) {
            return false;
        }
    }

    /**
     * @param  list<array<string, string>>  $created
     * @param  list<array<string, string>>  $existing
     * @param  list<array<string, string>>  $skippedSharedUnit
     * @param  list<array<string, string>>  $invalid
     * @param  list<string>  $needsProfile
     * @param  list<string>  $readyForSso
     */
    private function printReport(
        bool $apply,
        array $created,
        array $existing,
        array $skippedSharedUnit,
        array $invalid,
        array $needsProfile,
        array $readyForSso,
    ): void {
        $this->newLine();
        $this->components->twoColumnDetail('<fg=default>Mode</>', $apply ? 'APPLY (changes written)' : 'DRY-RUN (no changes written)');
        $this->newLine();

        $this->line(($apply ? 'Created' : 'Would create').' personal users ('.count($created).')');
        if ($created !== []) {
            $this->table(['Email', 'Role', 'is_active'], $created);
        }

        $this->line('Existing users, untouched ('.count($existing).')');
        if ($existing !== []) {
            $this->table(['Email', 'Role', 'is_active', 'Ready for SSO'], $existing);
        }

        $this->line('Skipped shared/unit accounts, needs manual review ('.count($skippedSharedUnit).')');
        if ($skippedSharedUnit !== []) {
            $this->table(['Email', 'Reason'], $skippedSharedUnit);
        }

        $this->line('Invalid / rejected emails ('.count($invalid).')');
        if ($invalid !== []) {
            $this->table(['Email', 'Reason'], $invalid);
        }

        $this->line('Users needing employee profile completion ('.count($needsProfile).')');
        foreach ($needsProfile as $email) {
            $this->line("  - {$email}");
        }

        $this->line('Users ready for Google SSO login now ('.count($readyForSso).')');
        foreach ($readyForSso as $email) {
            $this->line("  - {$email}");
        }

        $this->newLine();

        if (! $apply && $created !== []) {
            $this->components->warn('Dry-run only: no users were created. Re-run with --apply to persist.');
        }

        if ($apply && $needsProfile !== []) {
            $this->components->warn('Newly provisioned users are inactive until Admin HR completes their Employee profile and activates the account.');
        }
    }
}
