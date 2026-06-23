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

        View::composer('*', function ($view): void {
            $user = auth()->user();
            $view->with('unreadNotificationCount', $user
                ? Notification::where('user_id', $user->id)->where('is_read', false)->count()
                : 0);
        });
    }
}
