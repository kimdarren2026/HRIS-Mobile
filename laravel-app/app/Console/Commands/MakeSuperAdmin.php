<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password as PasswordRule;

class MakeSuperAdmin extends Command
{
    protected $signature = 'hris:make-super-admin';

    protected $description = 'Interactively create the first super admin user for a new production database.';

    private const PASSWORD_PROMPT = 'Password (minimal 12 karakter, kombinasi huruf besar, huruf kecil, angka, dan simbol)';

    public function handle(): int
    {
        if (User::query()->where('role', User::ROLE_SUPER_ADMIN)->exists()) {
            $this->components->error('Bootstrap dibatalkan: super admin sudah tersedia. Gunakan command recovery terpisah untuk menambah atau memulihkan super admin.');

            return self::FAILURE;
        }

        $name = trim((string) $this->ask('Nama lengkap'));

        if ($name === '') {
            $this->components->error('Nama tidak boleh kosong.');

            return self::FAILURE;
        }

        $email = $this->normalizeEmail((string) $this->ask('Email'));

        if (Validator::make(['email' => $email], ['email' => ['required', 'email']])->fails()) {
            $this->components->error('Format email tidak valid.');

            return self::FAILURE;
        }

        if (User::query()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
            $this->components->error("Email {$email} sudah digunakan oleh user lain.");

            return self::FAILURE;
        }

        $password = (string) $this->secret(self::PASSWORD_PROMPT);
        $passwordConfirmation = (string) $this->secret('Konfirmasi password');

        $passwordValidator = Validator::make(
            [
                'password' => $password,
                'password_confirmation' => $passwordConfirmation,
            ],
            [
                'password' => [
                    'required',
                    'confirmed',
                    PasswordRule::min(12)->mixedCase()->numbers()->symbols(),
                ],
            ],
        );

        if ($passwordValidator->fails()) {
            foreach ($passwordValidator->errors()->get('password') as $message) {
                $this->components->error($message);
            }

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info('Ringkasan super admin yang akan dibuat:');
        $this->components->twoColumnDetail('Nama', $name);
        $this->components->twoColumnDetail('Email', $email);
        $this->components->twoColumnDetail('Role', User::ROLE_SUPER_ADMIN);
        $this->newLine();

        if (! $this->confirm('Lanjutkan membuat super admin ini?', false)) {
            $this->components->warn('Dibatalkan. Tidak ada perubahan pada database.');

            return self::SUCCESS;
        }

        $user = DB::transaction(function () use ($name, $email, $password): User {
            return User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'role' => User::ROLE_SUPER_ADMIN,
                'is_active' => true,
                'google_id' => null,
                'google_linked_at' => null,
            ]);
        });

        $this->components->info("Super admin berhasil dibuat. ID: {$user->id}, Email: {$user->email}");

        return self::SUCCESS;
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }
}
