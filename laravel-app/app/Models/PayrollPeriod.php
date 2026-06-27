<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollPeriod extends Model
{
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'pay_date',
        'status',
        'created_by',
        'calculated_by',
        'calculated_at',
        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',
        'locked_by',
        'locked_at',
        'paid_by',
        'paid_at',
        'payment_reference',
        'payment_date',
    ];

    protected function casts(): array
    {
        return [
            'start_date'    => 'date',
            'end_date'      => 'date',
            'pay_date'      => 'date',
            'calculated_at' => 'datetime',
            'reviewed_at'   => 'datetime',
            'approved_at'   => 'datetime',
            'locked_at'     => 'datetime',
            'paid_at'        => 'datetime',
            'payment_date'   => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payrollRecords(): HasMany
    {
        return $this->hasMany(PayrollRecord::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }
}
