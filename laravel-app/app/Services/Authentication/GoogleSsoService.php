<?php

namespace App\Services\Authentication;

use App\Exceptions\Authentication\ExternalAuthenticationException;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;
use Throwable;

class GoogleSsoService
{
    public function __construct(
        private readonly UserAccessValidator $userAccessValidator,
    ) {}

    public function isConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }

    /**
     * @return array{user: User, linked: bool}
     */
    public function authenticate(): array
    {
        if (! $this->isConfigured()) {
            throw new ExternalAuthenticationException(
                'Konfigurasi login Google belum siap. Silakan hubungi Admin HR.',
                'google_config_incomplete',
            );
        }

        try {
            /** @var SocialiteUser $googleUser */
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException $exception) {
            throw new ExternalAuthenticationException(
                'Sesi login Google tidak valid atau sudah kedaluwarsa. Silakan coba lagi.',
                'google_invalid_state',
                previous: $exception,
            );
        } catch (Throwable $exception) {
            throw new ExternalAuthenticationException(
                'Login Google sedang tidak dapat digunakan. Silakan coba lagi beberapa saat lagi.',
                'google_callback_failed',
                previous: $exception,
            );
        }

        $googleId = trim((string) $googleUser->getId());

        if ($googleId === '') {
            throw new ExternalAuthenticationException(
                'Login Google gagal diproses. Silakan coba lagi.',
                'google_id_missing',
            );
        }

        $email = $this->normalizeEmail($googleUser->getEmail());

        if ($email === null) {
            throw new ExternalAuthenticationException(
                'Akun Google Anda tidak mengirimkan email yang dapat digunakan untuk login.',
                'google_email_missing',
                ['google_id' => $googleId],
            );
        }

        if ($this->isEmailVerificationAvailable($googleUser) && ! $this->isEmailVerified($googleUser)) {
            throw new ExternalAuthenticationException(
                'Email akun Google Anda belum terverifikasi.',
                'google_email_unverified',
                ['google_id' => $googleId],
            );
        }

        [$user, $linked] = DB::transaction(function () use ($googleId, $email, $googleUser): array {
            $linkedUser = User::with('employee')
                ->where('google_id', $googleId)
                ->first();

            if ($linkedUser) {
                return [$linkedUser, false];
            }

            /** @var User|null $emailUser */
            $emailUser = User::with('employee')
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();

            // TEMP diagnostic logging for Phase 52 SSO investigation — remove once root cause confirmed in production.
            Log::info('google_sso_lookup_debug', [
                'google_raw_email' => $googleUser->getEmail(),
                'google_id' => $googleId,
                'normalized_email' => $email,
                'user_found' => $emailUser !== null,
                'user_id' => $emailUser?->id,
            ]);

            if (! $emailUser) {
                throw new ExternalAuthenticationException(
                    'Akun Anda belum terdaftar di HRIS. Silakan hubungi Admin HR.',
                    'user_not_registered',
                    ['email' => $email],
                );
            }

            if ($emailUser->google_id && $emailUser->google_id !== $googleId) {
                throw new ExternalAuthenticationException(
                    'Akun Google Anda belum dapat digunakan untuk mengakses HRIS. Silakan hubungi Admin HR.',
                    'google_id_conflict',
                    ['user_id' => $emailUser->id],
                );
            }

            $emailUser->forceFill([
                'google_id' => $googleId,
                'google_linked_at' => $emailUser->google_linked_at ?? now(),
            ])->save();

            return [$emailUser->fresh('employee'), true];
        });

        $this->userAccessValidator->validate($user);

        return ['user' => $user, 'linked' => $linked];
    }

    public function normalizeEmail(?string $email): ?string
    {
        if (! is_string($email)) {
            return null;
        }

        $normalized = strtolower(trim($email));

        return filter_var($normalized, FILTER_VALIDATE_EMAIL) ? $normalized : null;
    }

    private function isEmailVerificationAvailable(SocialiteUser $googleUser): bool
    {
        return array_key_exists('email_verified', $googleUser->user);
    }

    private function isEmailVerified(SocialiteUser $googleUser): bool
    {
        return (bool) ($googleUser->user['email_verified'] ?? false);
    }
}
