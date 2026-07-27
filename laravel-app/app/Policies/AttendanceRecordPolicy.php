<?php

namespace App\Policies;

use App\Models\AttendanceRecord;
use App\Models\User;

class AttendanceRecordPolicy
{
    /**
     * Mirrors UserAccessValidator::ACTIVE_EMPLOYMENT_STATUSES — the existing,
     * single definition of "active employee" used at SSO-login time. Reused
     * here so an admin_hr approver's own standing is judged the same way
     * everywhere in the app, not by a second, drifting definition.
     */
    private const ACTIVE_EMPLOYMENT_STATUSES = ['active', 'probation'];

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
        return $this->canManage($user, $record);
    }

    public function reject(User $user, AttendanceRecord $record): bool
    {
        return $this->canManage($user, $record);
    }

    /**
     * Role gate for approve/reject, plus two guards:
     *
     *  - admin_hr must be an active user with an active (non-deleted) employee
     *    profile of their own to approve/reject *anyone's* attendance. Without
     *    one they are refused entirely — not just on the self-approval check.
     *    super_admin is exempt from this (see class docblock on Phase 58D:
     *    it must always retain override capability, e.g. before Rama/Joshua's
     *    admin_hr employee profiles exist, the system cannot be left with zero
     *    working approvers).
     *  - Regardless of role, an approver's own linked employee record can
     *    never be the subject of their own approval/rejection decision.
     *
     * `$user->employee` already excludes soft-deleted records (Employee uses
     * SoftDeletes with Eloquent's default global scope), so "not deleted" is
     * satisfied by that relation alone — no separate check needed.
     *
     * Record-status checks (e.g. already processed) stay in the controller so
     * they keep returning 422 rather than 403.
     */
    private function canManage(User $user, AttendanceRecord $record): bool
    {
        if (! in_array($user->role, [User::ROLE_ADMIN_HR, User::ROLE_SUPER_ADMIN], true)) {
            return false;
        }

        if (! $user->is_active) {
            return false;
        }

        if ($user->role === User::ROLE_ADMIN_HR) {
            $employee = $user->employee;

            if (! $employee || ! in_array($employee->employment_status, self::ACTIVE_EMPLOYMENT_STATUSES, true)) {
                return false;
            }
        }

        if ($user->employee && $user->employee->id === $record->employee_id) {
            return false;
        }

        return true;
    }
}
