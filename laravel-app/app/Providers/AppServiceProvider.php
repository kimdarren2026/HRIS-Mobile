<?php

namespace App\Providers;

use App\Models\CompanyExpense;
use App\Models\Notification;
use App\Policies\CompanyExpensePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
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
