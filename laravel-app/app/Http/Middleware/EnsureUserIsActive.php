<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Force out any authenticated user whose account has been deactivated
     * since their session was created. Runs on every web request (guest
     * requests pass straight through) so that a stale session can never be
     * used to read or mutate data once is_active flips to false.
     *
     * Re-fetches the user by primary key rather than trusting the guard's
     * already-resolved model, so the check always reflects the current
     * database state for this request, not a value cached earlier.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $current = $user->fresh();

        if ($current && $current->is_active) {
            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Akun Anda telah dinonaktifkan.',
            ], 403);
        }

        return redirect('/login')->withErrors([
            'email' => 'Akun Anda telah dinonaktifkan. Hubungi administrator.',
        ]);
    }
}
