<?php

namespace App\Services\Authentication;

use App\Exceptions\Authentication\ExternalAuthenticationException;
use App\Models\User;

class UserAccessValidator
{
    private const ACTIVE_EMPLOYMENT_STATUSES = ['active', 'probation'];

    public function validate(User $user): void
    {
        if (! $user->is_active) {
            throw new ExternalAuthenticationException(
                'Akun Anda belum dapat digunakan untuk mengakses HRIS. Silakan hubungi Admin HR.',
                'user_inactive',
                ['user_id' => $user->id, 'role' => $user->role],
            );
        }

        if (! User::isSupportedRole($user->role)) {
            throw new ExternalAuthenticationException(
                'Akun Anda belum dapat digunakan untuk mengakses HRIS. Silakan hubungi Admin HR.',
                'invalid_role',
                ['user_id' => $user->id, 'role' => $user->role],
            );
        }

        $employee = $user->employee;

        if ($user->requiresEmployeeRecord() && ! $employee) {
            throw new ExternalAuthenticationException(
                'Akun Anda belum dapat digunakan untuk mengakses HRIS. Silakan hubungi Admin HR.',
                'employee_record_missing',
                ['user_id' => $user->id, 'role' => $user->role],
            );
        }

        if ($employee && ! in_array($employee->employment_status, self::ACTIVE_EMPLOYMENT_STATUSES, true)) {
            throw new ExternalAuthenticationException(
                'Akun Anda belum dapat digunakan untuk mengakses HRIS. Silakan hubungi Admin HR.',
                'employee_inactive',
                ['user_id' => $user->id, 'role' => $user->role, 'employment_status' => $employee->employment_status],
            );
        }
    }
}
