<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        // Phase 58C: create the new calendar year's annual leave balances
        // (full or prorated per join_date, no carry-over) once, on Jan 1.
        // Idempotent — safe if it fires more than once or is re-run manually.
        $schedule->command('leave:initialize-year')
            ->yearlyOn(1, 1, '01:00')
            ->timezone('Asia/Makassar')
            ->withoutOverlapping();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );

        $middleware->redirectGuestsTo('/login');
        $middleware->alias([
            'role'         => RoleMiddleware::class,
            'has_employee' => \App\Http\Middleware\HasEmployee::class,
        ]);

        // Phase 59C: centrally enforce that every authenticated web request
        // still belongs to an active user, on top of (not instead of) the
        // per-route 'auth'/'role'/'has_employee' middleware and Policies.
        // Appended to the 'web' group (not the outer global stack) so it
        // runs after StartSession, once auth()->user() is resolvable.
        $middleware->web(append: [EnsureUserIsActive::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
