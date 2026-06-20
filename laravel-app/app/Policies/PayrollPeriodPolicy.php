<?php

namespace App\Policies;

use App\Models\PayrollPeriod;
use App\Models\User;

class PayrollPeriodPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin_hr', 'finance', 'super_admin'], true);
    }

    public function view(User $user, PayrollPeriod $payrollPeriod): bool
    {
        return in_array($user->role, ['admin_hr', 'finance', 'super_admin'], true);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['finance', 'super_admin'], true);
    }

    public function calculate(User $user, PayrollPeriod $payrollPeriod): bool
    {
        return in_array($user->role, ['finance', 'super_admin'], true)
            && $payrollPeriod->status === 'DRAFT';
    }

    public function submitHrReview(User $user, PayrollPeriod $payrollPeriod): bool
    {
        return in_array($user->role, ['finance', 'super_admin'], true)
            && $payrollPeriod->status === 'CALCULATED';
    }
}
