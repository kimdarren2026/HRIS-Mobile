<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect($this->redirectPathForRole(Auth::user()->role));
        }

        return view('pages.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        if (Auth::check()) {
            return redirect($this->redirectPathForRole(Auth::user()->role));
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'is_active' => true,
        ], $remember)) {
            throw ValidationException::withMessages([
                'email' => 'Email, password, atau status akun tidak valid.',
            ]);
        }

        $request->session()->regenerate();
        $request->user()->forceFill(['last_login_at' => now()])->save();

        return redirect($this->redirectPathForRole($request->user()->role));
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
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            /** @var SocialiteUser $googleUser */
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException) {
            return redirect('/login')->withErrors([
                'email' => 'Sesi login Google tidak valid atau kedaluwarsa. Silakan coba lagi.',
            ]);
        }

        $email = $googleUser->getEmail();

        if (! $email || ! $this->isAllowedGoogleDomain($email)) {
            return redirect('/login')->withErrors([
                'email' => 'Domain email Google Anda tidak diizinkan untuk masuk ke HRIS.',
            ]);
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return redirect('/login')->withErrors([
                'email' => 'Akun Google Anda belum terdaftar di HRIS. Silakan hubungi HR/Admin.',
            ]);
        }

        if (! $user->is_active) {
            return redirect('/login')->withErrors([
                'email' => 'Akun Anda tidak aktif. Silakan hubungi HR/Admin.',
            ]);
        }

        if (! $user->google_id) {
            $user->forceFill(['google_id' => $googleUser->getId()])->save();
        }

        Auth::login($user);

        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect($this->redirectPathForRole($user->role));
    }

    private function isAllowedGoogleDomain(string $email): bool
    {
        $allowedDomains = array_filter(array_map(
            'trim',
            explode(',', (string) config('services.google.allowed_domains'))
        ));

        if (empty($allowedDomains)) {
            return false;
        }

        $emailDomain = Str::lower(Str::after($email, '@'));

        foreach ($allowedDomains as $domain) {
            if (Str::lower($domain) === $emailDomain) {
                return true;
            }
        }

        return false;
    }

    private function redirectPathForRole(string $role): string
    {
        return match ($role) {
            'employee' => '/employee/dashboard',
            'finance' => '/finance/dashboard',
            'admin_hr', 'super_admin' => '/admin/dashboard',
            default => '/login',
        };
    }
}
