<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PayrollRecord extends Model
{
    protected $fillable = [
        'payroll_period_id',
        'employee_id',
        'basic_salary',
        'allowance',
        'bonus',
        'overtime',
        'deduction',
        'late_deduction',
        'attendance_deduction',
        'tax_bpjs',
        'net_salary',
        'attendance_days',
        'leave_days',
    ];

    protected function casts(): array
    {
        return [
            'basic_salary'         => 'decimal:2',
            'allowance'            => 'decimal:2',
            'bonus'                => 'decimal:2',
            'overtime'             => 'decimal:2',
            'deduction'            => 'decimal:2',
            'late_deduction'       => 'decimal:2',
            'attendance_deduction' => 'decimal:2',
            'tax_bpjs'             => 'decimal:2',
            'net_salary'           => 'decimal:2',
            'leave_days'           => 'decimal:2',
        ];
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payslip(): HasOne
    {
        return $this->hasOne(Payslip::class);
    }
}
