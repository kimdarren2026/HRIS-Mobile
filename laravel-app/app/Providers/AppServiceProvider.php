<?php

namespace App\Providers;

use App\Models\CompanyExpense;
use App\Models\Notification;
use App\Policies\CompanyExpensePolicy;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Drives ->translatedFormat() and ->diffForHumans() output (e.g. month
        // names, "X menit yang lalu") to match the app locale. Does not affect
        // raw ->format() tokens or stored date values.
        Carbon::setLocale(app()->getLocale());

        Gate::policy(CompanyExpense::class, CompanyExpensePolicy::class);

        // Compute once per request and reuse across sub-views.
        View::composer('*', function ($view): void {
            $request = request();

            if (! $request->attributes->has('unreadNotificationCount')) {
                $user  = auth()->user();
                $count = $user
                    ? Notification::where('user_id', $user->id)->where('is_read', false)->count()
                    : 0;

                $request->attributes->set('unreadNotificationCount', $count);
            }

            $view->with(
                'unreadNotificationCount',
                $request->attributes->get('unreadNotificationCount', 0)
            );
        });
    }
}
