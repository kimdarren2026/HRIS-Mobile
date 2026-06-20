<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin_hr', 'super_admin'], true);
    }

    public function view(User $user, Employee $employee): bool
    {
        if (in_array($user->role, ['admin_hr', 'super_admin'], true)) {
            return true;
        }

        return $user->role === 'employee' && $user->employee?->id === $employee->id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin_hr', 'super_admin'], true);
    }

    public function update(User $user, Employee $employee): bool
    {
        return in_array($user->role, ['admin_hr', 'super_admin'], true);
    }
}
