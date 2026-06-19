<?php

namespace App\Policies;

use App\Models\AttendanceRecord;
use App\Models\User;

class AttendanceRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin_hr', 'super_admin'], true);
    }

    public function view(User $user, AttendanceRecord $record): bool
    {
        return $user->employee?->id === $record->employee_id
            || in_array($user->role, ['admin_hr', 'super_admin'], true);
    }

    public function approve(User $user, AttendanceRecord $record): bool
    {
        return in_array($user->role, ['admin_hr', 'super_admin'], true)
            && $record->status === 'PENDING_REVIEW';
    }
}
