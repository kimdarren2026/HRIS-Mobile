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

    // HR reviews CALCULATED → HR_REVIEW
    public function submitHrReview(User $user, PayrollPeriod $payrollPeriod): bool
    {
        return in_array($user->role, ['admin_hr', 'super_admin'], true)
            && $payrollPeriod->status === 'CALCULATED';
    }

    // Finance approves HR_REVIEW → FINANCE_APPROVAL
    public function financeApprove(User $user, PayrollPeriod $payrollPeriod): bool
    {
        return in_array($user->role, ['finance', 'super_admin'], true)
            && $payrollPeriod->status === 'HR_REVIEW';
    }

    // Finance locks FINANCE_APPROVAL → LOCKED
    public function lock(User $user, PayrollPeriod $payrollPeriod): bool
    {
        return in_array($user->role, ['finance', 'super_admin'], true)
            && $payrollPeriod->status === 'FINANCE_APPROVAL';
    }

    // Finance marks LOCKED → PAID
    public function markPaid(User $user, PayrollPeriod $payrollPeriod): bool
    {
        return in_array($user->role, ['finance', 'super_admin'], true)
            && $payrollPeriod->status === 'LOCKED';
    }

    public function export(User $user, PayrollPeriod $payrollPeriod): bool
    {
        return in_array($user->role, ['finance', 'super_admin'], true);
    }
}
