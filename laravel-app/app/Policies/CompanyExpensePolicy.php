<?php

namespace App\Policies;

use App\Models\CompanyExpense;
use App\Models\User;

class CompanyExpensePolicy
{
    private function isFinance(User $user): bool
    {
        return in_array($user->role, ['finance', 'super_admin'], true);
    }

    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['finance', 'super_admin', 'admin_hr'], true);
    }

    public function view(User $user, CompanyExpense $expense): bool
    {
        if ($this->isFinance($user)) {
            return true;
        }
        if ($user->role === 'admin_hr') {
            return true;
        }
        // Employee can view their own reimbursement requests
        return $user->employee?->id === $expense->employee_id
            && in_array($expense->category, CompanyExpense::EMPLOYEE_SUBMITTABLE_CATEGORIES, true);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['finance', 'super_admin', 'admin_hr'], true);
    }

    public function update(User $user, CompanyExpense $expense): bool
    {
        if (! in_array($expense->status, ['DRAFT', 'REJECTED'], true)) {
            return false;
        }
        if ($this->isFinance($user)) {
            return true;
        }
        // admin_hr can only edit their own submissions
        return $user->role === 'admin_hr' && $expense->created_by === $user->id;
    }

    public function submit(User $user, CompanyExpense $expense): bool
    {
        if (! in_array($expense->status, ['DRAFT', 'REJECTED'], true)) {
            return false;
        }
        if ($this->isFinance($user)) {
            return true;
        }
        return $user->role === 'admin_hr' && $expense->created_by === $user->id;
    }

    // Maker-checker: the creator may not approve their own expense.
    public function approve(User $user, CompanyExpense $expense): bool
    {
        return $this->isFinance($user)
            && $expense->status === 'SUBMITTED'
            && $user->id !== $expense->created_by;
    }

    // Maker-checker: the creator may not reject their own expense.
    public function reject(User $user, CompanyExpense $expense): bool
    {
        return $this->isFinance($user)
            && $expense->status === 'SUBMITTED'
            && $user->id !== $expense->created_by;
    }

    // Maker-checker: the original creator may never mark their own expense paid.
    public function markPaid(User $user, CompanyExpense $expense): bool
    {
        return $this->isFinance($user)
            && $expense->status === 'APPROVED'
            && $user->id !== $expense->created_by;
    }
}
