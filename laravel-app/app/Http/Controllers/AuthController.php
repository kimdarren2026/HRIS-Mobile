<?php

namespace App\Http\Controllers;

use App\Exceptions\Authentication\ExternalAuthenticationException;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Authentication\GoogleSsoService;
use App\Services\Authentication\UserAccessValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function __construct(
        private readonly GoogleSsoService $googleSsoService,
        private readonly UserAccessValidator $userAccessValidator,
    ) {}

    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect(Auth::user()->dashboardPath() ?? '/login');
        }

        return view('pages.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        if (Auth::check()) {
            return redirect(Auth::user()->dashboardPath() ?? '/login');
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');
        $email = strtolower(trim($credentials['email']));
        $matchedUser = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (! Auth::attempt([
            'email' => $matchedUser?->email ?? $email,
            'password' => $credentials['password'],
            'is_active' => true,
        ], $remember)) {
            throw ValidationException::withMessages([
                'email' => 'Email, password, atau status akun tidak valid.',
            ]);
        }

        try {
            $user = $request->user()->loadMissing('employee');
            $this->userAccessValidator->validate($user);
            $this->finalizeSuccessfulLogin($request, $user, 'password');
        } catch (ExternalAuthenticationException $exception) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => $exception->userMessage(),
            ]);
        }

        return redirect($request->user()->dashboardPath() ?? '/login');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function redirectToGoogle(): RedirectResponse
    {
        if (! $this->googleSsoService->isConfigured()) {
            return redirect('/login')->withErrors([
                'email' => 'Konfigurasi login Google belum siap. Silakan hubungi Admin HR.',
            ]);
        }

        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            ['user' => $user, 'linked' => $linked] = $this->googleSsoService->authenticate();
            Auth::login($user);
            $this->finalizeSuccessfulLogin($request, $user, 'google');

            if ($linked) {
                AuditLogService::log(
                    $user,
                    'google_account_linked',
                    'auth',
                    'Akun Google berhasil dihubungkan ke akun HRIS.',
                    ['provider' => 'google'],
                );
            }

            AuditLogService::log(
                $user,
                'google_login_success',
                'auth',
                'Login Google berhasil.',
                ['provider' => 'google'],
            );

            Log::info('google_sso_login_success', [
                'user_id' => $user->id,
                'provider' => 'google',
                'ip_address' => $request->ip(),
            ]);

            return redirect($user->dashboardPath() ?? '/login');
        } catch (ExternalAuthenticationException $exception) {
            Log::warning('google_sso_login_failed', [
                'reason' => $exception->reason(),
                'context' => $exception->context(),
                'ip_address' => $request->ip(),
            ]);

            AuditLogService::log(
                isset($user) && $user instanceof User ? $user : null,
                'google_login_failed',
                'auth',
                'Login Google gagal.',
                [
                    'provider' => 'google',
                    'reason' => $exception->reason(),
                ] + $exception->context(),
            );

            if ($exception->reason() === 'google_id_conflict') {
                AuditLogService::log(
                    isset($user) && $user instanceof User ? $user : null,
                    'google_account_link_rejected',
                    'auth',
                    'Penghubungan akun Google ditolak karena konflik identitas.',
                    ['provider' => 'google'],
                );
            }

            return redirect('/login')->withErrors([
                'email' => $exception->userMessage(),
            ]);
        }
    }

    private function finalizeSuccessfulLogin(Request $request, User $user, string $provider): void
    {
        $request->session()->regenerate();
        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'last_login_provider' => $provider,
        ])->save();
    }
}
