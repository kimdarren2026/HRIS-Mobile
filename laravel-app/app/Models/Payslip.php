<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payslip extends Model
{
    protected $fillable = [
        'payroll_record_id',
        'employee_id',
        'payroll_period_id',
        'snapshot_data',
        'payment_status',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_data' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function payrollRecord(): BelongsTo
    {
        return $this->belongsTo(PayrollRecord::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }
}
