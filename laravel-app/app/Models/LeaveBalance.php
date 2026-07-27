<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    // STIKES Advaita policy: annual leave entitlement is 18 working days/year
    // (policy point 1), applied 12 months after join_date. This constant is
    // also used as the first-time default quota for any other balance-deducting
    // leave type, matching the pre-existing generic behavior of this module —
    // that is a known limitation, not new in this change.
    public const DEFAULT_ANNUAL_QUOTA = 18;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'total_quota',
        'used',
        'remaining',
        'pre_system_used_days',
        'opening_adjustment',
        'effective_date',
        'reason',
        'created_by',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'total_quota' => 'decimal:2',
            'used' => 'decimal:2',
            'remaining' => 'decimal:2',
            'pre_system_used_days' => 'decimal:2',
            'opening_adjustment' => 'decimal:2',
            'effective_date' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }
}
