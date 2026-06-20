<?php

namespace App\Policies;

use App\Models\PayrollRecord;
use App\Models\User;

class PayrollRecordPolicy
{
    private const VISIBLE_STATUSES = ['CALCULATED', 'HR_REVIEW', 'FINANCE_APPROVAL', 'LOCKED', 'PAID'];

    public function view(User $user, PayrollRecord $record): bool
    {
        $employee = $user->employee;

        if (! $employee || $employee->id !== $record->employee_id) {
            return false;
        }

        return in_array($record->payrollPeriod->status, self::VISIBLE_STATUSES, true);
    }

    public function print(User $user, PayrollRecord $record): bool
    {
        return $this->view($user, $record);
    }
}
