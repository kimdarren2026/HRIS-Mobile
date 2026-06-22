<?php

namespace App\Providers;

use App\Models\CompanyExpense;
use App\Policies\CompanyExpensePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(CompanyExpense::class, CompanyExpensePolicy::class);
    }
}
