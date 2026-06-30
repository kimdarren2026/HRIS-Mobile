<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    // TODO: replace with leave balance module config once implemented
    public const DEFAULT_ANNUAL_QUOTA = 12;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'total_quota',
        'used',
        'remaining',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'total_quota' => 'decimal:2',
            'used' => 'decimal:2',
            'remaining' => 'decimal:2',
        ];
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
