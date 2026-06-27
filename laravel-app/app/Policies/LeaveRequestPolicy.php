<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;

class LeaveRequestPolicy
{
    public function viewAttachment(User $user, LeaveRequest $leaveRequest): bool
    {
        if (in_array($user->role, [User::ROLE_ADMIN_HR, User::ROLE_SUPER_ADMIN], true)) {
            return true;
        }

        return $user->employee?->id === $leaveRequest->employee_id;
    }
}
