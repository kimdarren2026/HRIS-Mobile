<?php

namespace App\Policies;

use App\Models\AttendanceRecord;
use App\Models\User;

class AttendanceRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [User::ROLE_ADMIN_HR, User::ROLE_SUPER_ADMIN], true);
    }

    public function view(User $user, AttendanceRecord $record): bool
    {
        return $user->employee?->id === $record->employee_id
            || in_array($user->role, [User::ROLE_ADMIN_HR, User::ROLE_SUPER_ADMIN], true);
    }

    public function approve(User $user, AttendanceRecord $record): bool
    {
        return in_array($user->role, [User::ROLE_ADMIN_HR, User::ROLE_SUPER_ADMIN], true)
            && $record->status === 'PENDING_REVIEW';
    }
}
