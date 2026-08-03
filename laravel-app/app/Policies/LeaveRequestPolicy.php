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

    // Maker-checker (Phase 59B): an approver may never process their own leave request.
    public function approve(User $user, LeaveRequest $leaveRequest): bool
    {
        return $this->canManage($user, $leaveRequest);
    }

    // Maker-checker (Phase 59B): an approver may never process their own leave request.
    public function reject(User $user, LeaveRequest $leaveRequest): bool
    {
        return $this->canManage($user, $leaveRequest);
    }

    private function canManage(User $user, LeaveRequest $leaveRequest): bool
    {
        if (! in_array($user->role, [User::ROLE_ADMIN_HR, User::ROLE_SUPER_ADMIN], true)) {
            return false;
        }

        if (! $user->is_active) {
            return false;
        }

        if ($user->employee && $user->employee->id === $leaveRequest->employee_id) {
            return false;
        }

        return true;
    }
}
