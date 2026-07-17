<?php

namespace Tests\Feature\Console;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MakeSuperAdminTest extends TestCase
{
    use RefreshDatabase;

    private const STRONG_PASSWORD = 'SuperSecure!Pass2026';

    private const PASSWORD_PROMPT = 'Password (minimal 12 karakter, kombinasi huruf besar, huruf kecil, angka, dan simbol)';

    public function test_creates_super_admin_successfully(): void
    {
        $this->artisan('hris:make-super-admin')
            ->expectsQuestion('Nama lengkap', 'Darren Kim')
            ->expectsQuestion('Email', 'admin@hris.example')
            ->expectsQuestion(self::PASSWORD_PROMPT, self::STRONG_PASSWORD)
            ->expectsQuestion('Konfirmasi password', self::STRONG_PASSWORD)
            ->expectsConfirmation('Lanjutkan membuat super admin ini?', 'yes')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@hris.example',
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
        ]);
    }

    public function test_email_is_normalized_to_lowercase(): void
    {
        $this->artisan('hris:make-super-admin')
            ->expectsQuestion('Nama lengkap', 'Darren Kim')
            ->expectsQuestion('Email', '  Admin@HRIS.example  ')
            ->expectsQuestion(self::PASSWORD_PROMPT, self::STRONG_PASSWORD)
            ->expectsQuestion('Konfirmasi password', self::STRONG_PASSWORD)
            ->expectsConfirmation('Lanjutkan membuat super admin ini?', 'yes')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', ['email' => 'admin@hris.example']);
        $this->assertDatabaseMissing('users', ['email' => '  Admin@HRIS.example  ']);
    }

    public function test_password_is_stored_hashed(): void
    {
        $this->artisan('hris:make-super-admin')
            ->expectsQuestion('Nama lengkap', 'Darren Kim')
            ->expectsQuestion('Email', 'admin@hris.example')
            ->expectsQuestion(self::PASSWORD_PROMPT, self::STRONG_PASSWORD)
            ->expectsQuestion('Konfirmasi password', self::STRONG_PASSWORD)
            ->expectsConfirmation('Lanjutkan membuat super admin ini?', 'yes')
            ->assertExitCode(0);

        $user = User::where('email', 'admin@hris.example')->firstOrFail();

        $this->assertTrue(Hash::isHashed($user->password));
        $this->assertNotSame(self::STRONG_PASSWORD, $user->password);
        $this->assertTrue(Hash::check(self::STRONG_PASSWORD, $user->password));
    }

    public function test_google_id_remains_null(): void
    {
        $this->artisan('hris:make-super-admin')
            ->expectsQuestion('Nama lengkap', 'Darren Kim')
            ->expectsQuestion('Email', 'admin@hris.example')
            ->expectsQuestion(self::PASSWORD_PROMPT, self::STRONG_PASSWORD)
            ->expectsQuestion('Konfirmasi password', self::STRONG_PASSWORD)
            ->expectsConfirmation('Lanjutkan membuat super admin ini?', 'yes')
            ->assertExitCode(0);

        $user = User::where('email', 'admin@hris.example')->firstOrFail();

        $this->assertNull($user->google_id);
        $this->assertNull($user->google_linked_at);
    }

    public function test_does_not_create_employee_record(): void
    {
        $this->artisan('hris:make-super-admin')
            ->expectsQuestion('Nama lengkap', 'Darren Kim')
            ->expectsQuestion('Email', 'admin@hris.example')
            ->expectsQuestion(self::PASSWORD_PROMPT, self::STRONG_PASSWORD)
            ->expectsQuestion('Konfirmasi password', self::STRONG_PASSWORD)
            ->expectsConfirmation('Lanjutkan membuat super admin ini?', 'yes')
            ->assertExitCode(0);

        $user = User::where('email', 'admin@hris.example')->firstOrFail();

        $this->assertSame(0, Employee::where('user_id', $user->id)->count());
        $this->assertNull($user->employee);
    }

    public function test_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'admin@hris.example']);

        $this->artisan('hris:make-super-admin')
            ->expectsQuestion('Nama lengkap', 'Darren Kim')
            ->expectsQuestion('Email', 'admin@hris.example')
            ->assertExitCode(1);

        $this->assertSame(1, User::where('email', 'admin@hris.example')->count());
    }

    public function test_rejects_weak_password(): void
    {
        $this->artisan('hris:make-super-admin')
            ->expectsQuestion('Nama lengkap', 'Darren Kim')
            ->expectsQuestion('Email', 'admin@hris.example')
            ->expectsQuestion(self::PASSWORD_PROMPT, 'weakpassword')
            ->expectsQuestion('Konfirmasi password', 'weakpassword')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['email' => 'admin@hris.example']);
    }

    public function test_cancelling_command_does_not_create_user(): void
    {
        $this->artisan('hris:make-super-admin')
            ->expectsQuestion('Nama lengkap', 'Darren Kim')
            ->expectsQuestion('Email', 'admin@hris.example')
            ->expectsQuestion(self::PASSWORD_PROMPT, self::STRONG_PASSWORD)
            ->expectsQuestion('Konfirmasi password', self::STRONG_PASSWORD)
            ->expectsConfirmation('Lanjutkan membuat super admin ini?', 'no')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('users', ['email' => 'admin@hris.example']);
    }

    public function test_password_never_appears_in_output(): void
    {
        $this->artisan('hris:make-super-admin')
            ->expectsQuestion('Nama lengkap', 'Darren Kim')
            ->expectsQuestion('Email', 'admin@hris.example')
            ->expectsQuestion(self::PASSWORD_PROMPT, self::STRONG_PASSWORD)
            ->expectsQuestion('Konfirmasi password', self::STRONG_PASSWORD)
            ->expectsConfirmation('Lanjutkan membuat super admin ini?', 'yes')
            ->doesntExpectOutputToContain(self::STRONG_PASSWORD)
            ->assertExitCode(0);
    }

    public function test_rejects_creating_second_super_admin_with_different_email(): void
    {
        User::factory()->create([
            'email' => 'existing-admin@hris.example',
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $this->artisan('hris:make-super-admin')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['email' => 'admin@hris.example']);
    }

    public function test_does_not_prompt_for_password_when_super_admin_already_exists(): void
    {
        User::factory()->create([
            'email' => 'existing-admin@hris.example',
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $this->artisan('hris:make-super-admin')
            ->doesntExpectOutputToContain(self::PASSWORD_PROMPT)
            ->assertExitCode(1);
    }

    public function test_super_admin_count_remains_one_after_guard_rejects(): void
    {
        User::factory()->create([
            'email' => 'existing-admin@hris.example',
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $this->artisan('hris:make-super-admin')
            ->assertExitCode(1);

        $this->assertSame(1, User::where('role', User::ROLE_SUPER_ADMIN)->count());
    }

    public function test_does_not_create_any_user_or_employee_when_guard_rejects(): void
    {
        User::factory()->create([
            'email' => 'existing-admin@hris.example',
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $userCountBefore = User::count();

        $this->artisan('hris:make-super-admin')
            ->assertExitCode(1);

        $this->assertSame($userCountBefore, User::count());
        $this->assertSame(0, Employee::count());
    }
}
