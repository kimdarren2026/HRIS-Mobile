<?php

namespace Tests\Feature\Console;

use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class EmployeeSsoImportTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private Position $position;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::create(['name' => 'Synthetic Dept', 'description' => '']);
        $this->position = Position::create(['name' => 'Synthetic Role', 'department_id' => $this->department->id]);
    }

    /**
     * @param  list<array<string, string>>  $rows
     */
    private function writeCsv(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sso_import_test_').'.csv';
        $handle = fopen($path, 'w');

        fputcsv($handle, ['email', 'name', 'nik', 'position', 'department', 'join_date', 'phone', 'role', 'action', 'allow_name_duplicate', 'notes']);

        foreach ($rows as $row) {
            fputcsv($handle, [
                $row['email'] ?? '',
                $row['name'] ?? '',
                $row['nik'] ?? '',
                $row['position'] ?? '',
                $row['department'] ?? '',
                $row['join_date'] ?? '',
                $row['phone'] ?? '',
                $row['role'] ?? '',
                $row['action'] ?? 'CREATE_OR_UPDATE',
                $row['allow_name_duplicate'] ?? '',
                $row['notes'] ?? '',
            ]);
        }

        fclose($handle);

        $this->beforeApplicationDestroyed(function () use ($path) {
            if (file_exists($path)) {
                unlink($path);
            }
        });

        return $path;
    }

    private function baseRow(array $overrides = []): array
    {
        return array_merge([
            'email' => 'synthetic.employee@example.test',
            'name' => 'Synthetic Employee',
            'nik' => 'SYN-NIK-0001',
            'position' => 'Synthetic Role',
            'department' => 'Synthetic Dept',
            'join_date' => '2026-01-15',
            'phone' => '081200000001',
            'action' => 'CREATE_OR_UPDATE',
            'notes' => '',
        ], $overrides);
    }

    // 1. Dry-run tidak menulis database.
    public function test_dry_run_does_not_write_to_database(): void
    {
        $path = $this->writeCsv([$this->baseRow()]);

        Artisan::call('employees:sync-sso', ['file' => $path, '--dry-run' => true]);

        $this->assertDatabaseMissing('users', ['email' => 'synthetic.employee@example.test']);
        $this->assertSame(0, Employee::count());
    }

    // 2. Apply membuat satu user dan satu employee.
    public function test_apply_creates_one_user_and_one_employee(): void
    {
        $path = $this->writeCsv([$this->baseRow()]);

        Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);

        $this->assertSame(1, User::where('email', 'synthetic.employee@example.test')->count());
        $this->assertSame(1, Employee::count());
        $this->assertDatabaseHas('employees', ['nik' => 'SYN-NIK-0001']);
    }

    // 3. Command idempotent (mixed create + update run twice).
    public function test_command_is_idempotent_across_repeated_runs(): void
    {
        $path = $this->writeCsv([$this->baseRow()]);

        Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);
        Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);
        Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);

        $this->assertSame(1, User::where('email', 'synthetic.employee@example.test')->count());
        $this->assertSame(1, Employee::count());
    }

    // 4. Email dinormalisasi lowercase.
    public function test_email_is_normalized_to_lowercase(): void
    {
        $path = $this->writeCsv([$this->baseRow(['email' => 'Synthetic.Employee@EXAMPLE.test'])]);

        Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);

        $this->assertDatabaseHas('users', ['email' => 'synthetic.employee@example.test']);
    }

    // 5. Duplicate email tidak membuat user baru.
    public function test_duplicate_email_does_not_create_new_user(): void
    {
        $existing = User::factory()->create(['email' => 'synthetic.employee@example.test', 'role' => 'employee']);

        $path = $this->writeCsv([$this->baseRow()]);

        Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);

        $this->assertSame(1, User::where('email', 'synthetic.employee@example.test')->count());
        $this->assertSame($existing->id, User::where('email', 'synthetic.employee@example.test')->first()->id);
        $this->assertSame(1, Employee::count());
    }

    // 6. Duplicate NIK dengan email berbeda membatalkan apply.
    public function test_duplicate_nik_with_different_email_cancels_apply(): void
    {
        $otherUser = User::factory()->create(['email' => 'other.person@example.test', 'role' => 'employee']);
        Employee::factory()->create([
            'user_id' => $otherUser->id,
            'nik' => 'SYN-NIK-0001',
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        $path = $this->writeCsv([$this->baseRow()]);

        $exitCode = Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);

        $this->assertNotSame(0, $exitCode);
        $this->assertDatabaseMissing('users', ['email' => 'synthetic.employee@example.test']);
        $this->assertSame(1, Employee::count());
    }

    // 7. Existing employee di-update, bukan dibuat ulang.
    public function test_existing_employee_is_updated_not_recreated(): void
    {
        $user = User::factory()->create(['email' => 'synthetic.employee@example.test', 'role' => 'employee']);
        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'nik' => 'SYN-NIK-0001',
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'phone_number' => '080000000000',
        ]);

        $path = $this->writeCsv([$this->baseRow(['phone' => '081299999999'])]);

        Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);

        $this->assertSame(1, Employee::count());
        $this->assertSame($employee->id, $employee->fresh()->id);
        $this->assertSame('081299999999', $employee->fresh()->phone_number);
    }

    // 8. UPDATE_ONLY gagal aman jika record tidak ada.
    public function test_update_only_fails_safe_when_record_not_found(): void
    {
        $path = $this->writeCsv([$this->baseRow(['action' => 'UPDATE_ONLY'])]);

        Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);

        $this->assertDatabaseMissing('users', ['email' => 'synthetic.employee@example.test']);
        $this->assertSame(0, Employee::count());
    }

    // 9. Field kosong disimpan NULL pada create.
    public function test_empty_optional_fields_are_stored_as_null_on_create(): void
    {
        $path = $this->writeCsv([$this->baseRow(['nik' => '', 'position' => '', 'department' => '', 'join_date' => '', 'phone' => ''])]);

        Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);

        $employee = Employee::first();

        $this->assertNotNull($employee);
        $this->assertNull($employee->nik);
        $this->assertNull($employee->department_id);
        $this->assertNull($employee->position_id);
        $this->assertNull($employee->join_date);
        $this->assertNull($employee->phone_number);
    }

    // 10. Field kosong tidak menghapus data existing pada update.
    public function test_empty_fields_do_not_clear_existing_data_on_update(): void
    {
        $user = User::factory()->create(['email' => 'synthetic.employee@example.test', 'role' => 'employee']);
        Employee::factory()->create([
            'user_id' => $user->id,
            'nik' => 'SYN-NIK-0001',
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'phone_number' => '080000000000',
        ]);

        $path = $this->writeCsv([$this->baseRow(['phone' => '', 'nik' => ''])]);

        Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);

        $fresh = Employee::first();
        $this->assertSame('080000000000', $fresh->phone_number);
        $this->assertSame('SYN-NIK-0001', $fresh->nik);
    }

    // 11. Join date bulan-tahun ditolak sebagai tanggal.
    public function test_month_year_join_date_is_rejected_and_stored_null(): void
    {
        $path = $this->writeCsv([$this->baseRow(['join_date' => 'Juli 2026', 'notes' => 'tanggal belum lengkap'])]);

        Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);

        $employee = Employee::first();
        $this->assertNotNull($employee);
        $this->assertNull($employee->join_date);
    }

    // 12. Missing department ditampilkan sebagai konflik (and blocks the batch).
    public function test_missing_department_is_reported_as_conflict_and_cancels_apply(): void
    {
        $path = $this->writeCsv([$this->baseRow(['department' => 'Nonexistent Dept'])]);

        $exitCode = Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);

        $this->assertNotSame(0, $exitCode);
        $this->assertDatabaseMissing('users', ['email' => 'synthetic.employee@example.test']);
    }

    // 13. Missing position ditampilkan sebagai konflik (and blocks the batch).
    public function test_missing_position_is_reported_as_conflict_and_cancels_apply(): void
    {
        $path = $this->writeCsv([$this->baseRow(['position' => 'Nonexistent Role'])]);

        $exitCode = Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);

        $this->assertNotSame(0, $exitCode);
        $this->assertDatabaseMissing('users', ['email' => 'synthetic.employee@example.test']);
    }

    // 14. Role super_admin/finance tidak pernah diubah otomatis — kritis, batalkan seluruh batch.
    public function test_super_admin_role_is_never_auto_changed_and_apply_is_aborted(): void
    {
        $admin = User::factory()->create(['email' => 'synthetic.employee@example.test', 'role' => 'super_admin']);

        $rows = [
            $this->baseRow(),
            $this->baseRow(['email' => 'unrelated.coworker@example.test', 'nik' => 'SYN-NIK-UNRELATED']),
        ];

        $path = $this->writeCsv($rows);

        $exitCode = Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);

        $this->assertNotSame(0, $exitCode);
        $this->assertSame('super_admin', $admin->fresh()->role);
        $this->assertSame(0, Employee::count());
        $this->assertDatabaseMissing('users', ['email' => 'unrelated.coworker@example.test']);
    }

    // 15. Possible duplicate berdasarkan nama, tanpa override, tidak dibuat sama sekali (default aman).
    public function test_possible_duplicate_by_name_is_blocked_by_default(): void
    {
        $existing = User::factory()->create(['name' => 'Synthetic Employee', 'email' => 'different.email@example.test', 'role' => 'employee']);

        $path = $this->writeCsv([$this->baseRow()]);

        Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);

        $this->assertDatabaseMissing('users', ['email' => 'synthetic.employee@example.test']);
        $this->assertSame(1, User::count());
        $this->assertSame('different.email@example.test', $existing->fresh()->email);
    }

    // allow_name_duplicate=1 is the explicit, reviewed override that lets a name
    // collision through as a separate account — never inferred automatically.
    public function test_possible_duplicate_with_allow_name_duplicate_override_creates_separate_account(): void
    {
        $existing = User::factory()->create(['name' => 'Synthetic Employee', 'email' => 'different.email@example.test', 'role' => 'employee']);

        $path = $this->writeCsv([$this->baseRow(['allow_name_duplicate' => '1'])]);

        Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);

        $this->assertDatabaseHas('users', ['email' => 'synthetic.employee@example.test']);
        $this->assertSame(2, User::count());

        $newUser = User::where('email', 'synthetic.employee@example.test')->firstOrFail();
        $this->assertNotSame($existing->id, $newUser->id);
        $this->assertSame('different.email@example.test', $existing->fresh()->email);
        $this->assertSame('employee', $existing->fresh()->role);
    }

    // allow_name_duplicate=1 does not bypass a real NIK conflict, which stays critical.
    public function test_allow_name_duplicate_override_does_not_bypass_nik_conflict(): void
    {
        $otherUser = User::factory()->create(['name' => 'Synthetic Employee', 'email' => 'other.person@example.test', 'role' => 'employee']);
        Employee::factory()->create([
            'user_id' => $otherUser->id,
            'nik' => 'SYN-NIK-0001',
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        $path = $this->writeCsv([$this->baseRow(['allow_name_duplicate' => '1'])]);

        $exitCode = Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);

        $this->assertNotSame(0, $exitCode);
        $this->assertDatabaseMissing('users', ['email' => 'synthetic.employee@example.test']);
    }

    // 16. User terhubung ke employee profile.
    public function test_created_user_is_linked_to_employee_profile(): void
    {
        $path = $this->writeCsv([$this->baseRow()]);

        Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);

        $user = User::where('email', 'synthetic.employee@example.test')->firstOrFail();
        $this->assertNotNull($user->employee);
        $this->assertSame('SYN-NIK-0001', $user->employee->nik);
    }

    // 17 & 18. User employee melewati HasEmployee middleware dan dapat membuka attendance.
    public function test_imported_employee_user_can_open_attendance_checkin(): void
    {
        $path = $this->writeCsv([$this->baseRow()]);
        Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);

        $user = User::where('email', 'synthetic.employee@example.test')->firstOrFail();
        $user->forceFill(['is_active' => true])->save();

        $this->actingAs($user)->get('/attendance/checkin')->assertOk();
    }

    // 19. Import tidak membuat attendance record.
    public function test_import_does_not_create_attendance_record(): void
    {
        $path = $this->writeCsv([$this->baseRow()]);

        Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);

        $this->assertSame(0, AttendanceRecord::count());
    }

    // 20. Konflik satu row membatalkan seluruh batch.
    public function test_conflict_in_one_row_cancels_entire_batch(): void
    {
        $otherUser = User::factory()->create(['email' => 'other.person@example.test', 'role' => 'employee']);
        Employee::factory()->create([
            'user_id' => $otherUser->id,
            'nik' => 'CONFLICTING-NIK',
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        $rows = [
            $this->baseRow(['email' => 'good.row@example.test', 'nik' => 'SYN-NIK-GOOD']),
            $this->baseRow(['email' => 'bad.row@example.test', 'nik' => 'CONFLICTING-NIK']),
        ];

        $path = $this->writeCsv($rows);

        $exitCode = Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);

        $this->assertNotSame(0, $exitCode);
        $this->assertDatabaseMissing('users', ['email' => 'good.row@example.test']);
        $this->assertDatabaseMissing('users', ['email' => 'bad.row@example.test']);
    }

    // 21. Audit log dibuat saat apply.
    public function test_audit_log_created_on_apply(): void
    {
        $path = $this->writeCsv([$this->baseRow()]);

        Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);

        $this->assertTrue(AuditLog::where('action', 'sso_import_create_user')->exists());
        $this->assertTrue(AuditLog::where('action', 'sso_import_create_employee')->exists());
    }

    // 22. Dry-run tidak membuat audit log.
    public function test_dry_run_does_not_create_audit_log(): void
    {
        $path = $this->writeCsv([$this->baseRow()]);

        Artisan::call('employees:sync-sso', ['file' => $path, '--dry-run' => true]);

        $this->assertSame(0, AuditLog::count());
    }

    // 23. Output command tidak mencetak nomor telepon penuh.
    public function test_output_does_not_print_full_phone_number(): void
    {
        $path = $this->writeCsv([$this->baseRow(['phone' => '081234567890'])]);

        Artisan::call('employees:sync-sso', ['file' => $path, '--dry-run' => true]);

        $this->assertStringNotContainsString('081234567890', Artisan::output());
    }

    // 24. Menjalankan apply dua kali tidak membuat duplikasi (single-row focus).
    public function test_running_apply_twice_does_not_duplicate_records(): void
    {
        $path = $this->writeCsv([$this->baseRow()]);

        Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);
        Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);

        $this->assertSame(1, User::where('email', 'synthetic.employee@example.test')->count());
        $this->assertSame(1, Employee::count());
    }

    // Phase 58D correction #1: UPDATE_ONLY_NOT_FOUND aborts the entire --apply.
    public function test_update_only_not_found_aborts_entire_apply(): void
    {
        $rows = [
            $this->baseRow(['email' => 'valid.new.hire@example.test', 'nik' => 'SYN-NIK-VALID']),
            $this->baseRow(['email' => 'ghost.update.only@example.test', 'nik' => 'SYN-NIK-GHOST', 'action' => 'UPDATE_ONLY']),
        ];

        $path = $this->writeCsv($rows);

        $exitCode = Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);

        $this->assertNotSame(0, $exitCode);
        $this->assertSame(0, User::where('email', 'valid.new.hire@example.test')->count());
        $this->assertSame(0, User::where('email', 'ghost.update.only@example.test')->count());
        $this->assertSame(0, Employee::count());
        $this->assertSame(0, AuditLog::count());
    }

    // Phase 58D correction #2: preflight evaluates the whole file before any write happens.
    public function test_apply_performs_full_preflight_before_writing(): void
    {
        $otherUser = User::factory()->create(['email' => 'nik.owner@example.test', 'role' => 'employee']);
        Employee::factory()->create([
            'user_id' => $otherUser->id,
            'nik' => 'ALREADY-TAKEN-NIK',
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        $rows = [
            $this->baseRow(['email' => 'first.row.valid@example.test', 'nik' => 'SYN-NIK-FIRST']),
            $this->baseRow(['email' => 'last.row.conflict@example.test', 'nik' => 'ALREADY-TAKEN-NIK']),
        ];

        $path = $this->writeCsv($rows);

        $exitCode = Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);

        $this->assertNotSame(0, $exitCode);
        $this->assertSame(0, User::where('email', 'first.row.valid@example.test')->count());
        $this->assertSame(0, User::where('email', 'last.row.conflict@example.test')->count());
        $this->assertSame(1, Employee::count());
    }

    // Phase 58D correction (row-level tier): a downgrade attempt (admin_hr -> employee)
    // is an ordinary ROLE_CONFLICT that skips only that row; the rest of the batch applies.
    public function test_role_downgrade_attempt_skips_only_conflicting_row(): void
    {
        $manager = User::factory()->create(['email' => 'existing.manager@example.test', 'role' => 'admin_hr']);

        $rows = [
            // No 'role' column value => target role defaults to 'employee',
            // which would downgrade the existing admin_hr user.
            $this->baseRow(['email' => 'existing.manager@example.test', 'nik' => 'SYN-NIK-MANAGER']),
            $this->baseRow(['email' => 'valid.coworker@example.test', 'nik' => 'SYN-NIK-COWORKER']),
        ];

        $path = $this->writeCsv($rows);

        $exitCode = Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);

        $this->assertSame(0, $exitCode);
        $this->assertSame('admin_hr', $manager->fresh()->role);
        $this->assertSame(0, Employee::where('user_id', $manager->id)->count());
        $this->assertSame(1, User::where('email', 'valid.coworker@example.test')->count());
        $this->assertSame(1, Employee::count());
    }

    // Phase 58D integrated correction — role classification.

    // 3. New admin_hr row creates a user with role admin_hr and an employee profile.
    public function test_new_admin_hr_row_creates_user_with_employee_profile(): void
    {
        $path = $this->writeCsv([$this->baseRow(['email' => 'new.manager.one@example.test', 'nik' => 'SYN-NIK-MGR1', 'role' => 'admin_hr'])]);

        Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);

        $user = User::where('email', 'new.manager.one@example.test')->firstOrFail();
        $this->assertSame('admin_hr', $user->role);
        $this->assertNotNull($user->employee);
        $this->assertSame('SYN-NIK-MGR1', $user->employee->nik);
    }

    // 4. A second, independent admin_hr row also gets its own employee profile.
    public function test_second_new_admin_hr_row_creates_independent_employee_profile(): void
    {
        $rows = [
            $this->baseRow(['email' => 'new.manager.one@example.test', 'nik' => 'SYN-NIK-MGR1', 'role' => 'admin_hr']),
            $this->baseRow(['email' => 'new.manager.two@example.test', 'nik' => 'SYN-NIK-MGR2', 'role' => 'admin_hr']),
        ];

        $path = $this->writeCsv($rows);

        Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);

        $userOne = User::where('email', 'new.manager.one@example.test')->firstOrFail();
        $userTwo = User::where('email', 'new.manager.two@example.test')->firstOrFail();

        $this->assertSame('admin_hr', $userOne->role);
        $this->assertSame('admin_hr', $userTwo->role);
        $this->assertNotSame($userOne->employee->id, $userTwo->employee->id);
        $this->assertSame(2, Employee::count());
    }

    // 5. Existing employee-role user can be upgraded to admin_hr when the CSV requests it.
    public function test_existing_employee_role_upgrades_to_admin_hr_when_requested(): void
    {
        $user = User::factory()->create(['email' => 'synthetic.employee@example.test', 'role' => 'employee']);
        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'nik' => 'SYN-NIK-0001',
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        $path = $this->writeCsv([$this->baseRow(['role' => 'admin_hr'])]);

        Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);

        $this->assertSame('admin_hr', $user->fresh()->role);
        $this->assertSame($employee->id, $employee->fresh()->id);
        $this->assertSame(1, Employee::count());
    }

    // 6. The employee -> admin_hr role upgrade produces an audit log with old/new values.
    public function test_role_upgrade_to_admin_hr_creates_audit_log(): void
    {
        $user = User::factory()->create(['email' => 'synthetic.employee@example.test', 'role' => 'employee']);
        Employee::factory()->create([
            'user_id' => $user->id,
            'nik' => 'SYN-NIK-0001',
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        $path = $this->writeCsv([$this->baseRow(['role' => 'admin_hr'])]);

        Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);

        $log = AuditLog::where('action', 'sso_import_update_role')->first();
        $this->assertNotNull($log);
        $this->assertSame('employee', $log->old_values['role'] ?? null);
        $this->assertSame('admin_hr', $log->new_values['role'] ?? null);
    }

    // 7. admin_hr with an employee profile can still open attendance (has_employee, not role, gates access).
    public function test_admin_hr_user_with_employee_profile_can_open_attendance(): void
    {
        $path = $this->writeCsv([$this->baseRow(['role' => 'admin_hr'])]);
        Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);

        $user = User::where('email', 'synthetic.employee@example.test')->firstOrFail();

        $this->actingAs($user)->get('/attendance/checkin')->assertOk();
    }

    // 8. admin_hr can open the existing approval queue.
    public function test_admin_hr_user_can_open_approval_queue(): void
    {
        $path = $this->writeCsv([$this->baseRow(['role' => 'admin_hr'])]);
        Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);

        $user = User::where('email', 'synthetic.employee@example.test')->firstOrFail();

        $this->actingAs($user)->get('/hr/approval-queue')->assertOk();
    }

    // 9. admin_hr without an employee profile is not treated as staff for attendance access.
    public function test_admin_hr_without_employee_profile_cannot_open_attendance(): void
    {
        $user = User::factory()->create(['role' => 'admin_hr', 'is_active' => true]);

        $this->actingAs($user)->get('/attendance/checkin')->assertForbidden();
    }

    // 10-12. A name collision with a super_admin account under a different email is not a
    // blocker: a separate user+employee is created, the super_admin account is left
    // untouched, and the two accounts keep distinct user IDs.
    public function test_new_account_created_despite_name_match_with_super_admin_account(): void
    {
        $superAdmin = User::factory()->create([
            'name' => 'Synthetic Employee',
            'email' => 'super.admin.account@example.test',
            'role' => 'super_admin',
        ]);

        // Explicit, reviewed override: the CSV operator has already confirmed
        // this name collision is two distinct people, not one account.
        $path = $this->writeCsv([$this->baseRow(['allow_name_duplicate' => '1'])]);

        $exitCode = Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);

        $this->assertSame(0, $exitCode);

        $newUser = User::where('email', 'synthetic.employee@example.test')->firstOrFail();
        $this->assertNotSame($superAdmin->id, $newUser->id);
        $this->assertSame('employee', $newUser->role);
        $this->assertNotNull($newUser->employee);

        $freshSuperAdmin = $superAdmin->fresh();
        $this->assertSame('super_admin', $freshSuperAdmin->role);
        $this->assertSame('super.admin.account@example.test', $freshSuperAdmin->email);
    }

    // 13. UPDATE_ONLY preserves the existing user ID and employee ID (no new records).
    public function test_update_only_preserves_existing_user_and_employee_ids(): void
    {
        $user = User::factory()->create(['email' => 'synthetic.employee@example.test', 'role' => 'employee']);
        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'nik' => 'SYN-NIK-0001',
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'phone_number' => '080000000000',
        ]);

        $path = $this->writeCsv([$this->baseRow(['action' => 'UPDATE_ONLY', 'phone' => '081277778888'])]);

        Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);

        $this->assertSame($user->id, User::where('email', 'synthetic.employee@example.test')->firstOrFail()->id);
        $this->assertSame($employee->id, Employee::where('user_id', $user->id)->firstOrFail()->id);
        $this->assertSame('081277778888', $employee->fresh()->phone_number);
        $this->assertSame(1, User::count());
        $this->assertSame(1, Employee::count());
    }

    // Batch-critical: an existing super_admin/finance account is never auto-changed and
    // aborts the whole apply, even when other rows in the same file are otherwise valid.
    public function test_role_conflict_critical_on_super_admin_aborts_entire_batch(): void
    {
        User::factory()->create(['email' => 'super.admin.account@example.test', 'role' => 'super_admin']);

        $rows = [
            $this->baseRow(['email' => 'super.admin.account@example.test', 'nik' => 'SYN-NIK-SA']),
            $this->baseRow(['email' => 'valid.coworker@example.test', 'nik' => 'SYN-NIK-COWORKER']),
        ];

        $path = $this->writeCsv($rows);

        $exitCode = Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);

        $this->assertNotSame(0, $exitCode);
        $this->assertDatabaseMissing('users', ['email' => 'valid.coworker@example.test']);
        $this->assertSame(0, Employee::count());
    }

    // INVALID_ROLE is batch-critical: a typo'd role must never silently skip just that row.
    public function test_invalid_role_aborts_entire_batch(): void
    {
        $rows = [
            $this->baseRow(['email' => 'valid.row@example.test', 'nik' => 'SYN-NIK-VALID']),
            $this->baseRow(['email' => 'typo.role@example.test', 'nik' => 'SYN-NIK-TYPO', 'role' => 'suprAdmin']),
        ];

        $path = $this->writeCsv($rows);

        $exitCode = Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);

        $this->assertNotSame(0, $exitCode);
        $this->assertDatabaseMissing('users', ['email' => 'valid.row@example.test']);
        $this->assertDatabaseMissing('users', ['email' => 'typo.role@example.test']);
        $this->assertSame(0, AuditLog::count());
    }

    // INVALID_ACTION is batch-critical: a typo'd action must never silently skip just that row.
    public function test_invalid_action_aborts_entire_batch(): void
    {
        $rows = [
            $this->baseRow(['email' => 'valid.row@example.test', 'nik' => 'SYN-NIK-VALID']),
            $this->baseRow(['email' => 'typo.action@example.test', 'nik' => 'SYN-NIK-TYPO', 'action' => 'CRAETE_OR_UPDATE']),
        ];

        $path = $this->writeCsv($rows);

        $exitCode = Artisan::call('employees:sync-sso', ['file' => $path, '--apply' => true]);

        $this->assertNotSame(0, $exitCode);
        $this->assertDatabaseMissing('users', ['email' => 'valid.row@example.test']);
        $this->assertDatabaseMissing('users', ['email' => 'typo.action@example.test']);
        $this->assertSame(0, AuditLog::count());
    }

    // Dry-run still surfaces INVALID_ROLE/INVALID_ACTION without writing anything.
    public function test_dry_run_surfaces_invalid_role_and_action_without_writing(): void
    {
        $rows = [
            $this->baseRow(['email' => 'typo.role@example.test', 'nik' => 'SYN-NIK-TYPO1', 'role' => 'manager']),
            $this->baseRow(['email' => 'typo.action@example.test', 'nik' => 'SYN-NIK-TYPO2', 'action' => 'DELETE']),
        ];

        $path = $this->writeCsv($rows);

        Artisan::call('employees:sync-sso', ['file' => $path, '--dry-run' => true]);

        $output = Artisan::output();
        $this->assertStringContainsString('INVALID_ROLE', $output);
        $this->assertStringContainsString('INVALID_ACTION', $output);
        $this->assertSame(0, User::count());
    }

    // 25. Nilai NULL tampil sebagai "-" pada UI employee.
    public function test_null_fields_render_as_dash_on_employee_show_page(): void
    {
        $hrUser = User::factory()->create(['role' => 'admin_hr', 'is_active' => true]);
        $employeeUser = User::factory()->create(['role' => 'employee', 'is_active' => true]);
        $employee = Employee::create([
            'user_id' => $employeeUser->id,
            'nik' => null,
            'department_id' => null,
            'position_id' => null,
            'join_date' => null,
            'employment_status' => 'active',
            'phone_number' => null,
        ]);

        $this->actingAs($hrUser)
            ->get(route('employees.show', $employee))
            ->assertOk()
            ->assertSee('—');
    }
}
