<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyExpense extends Model
{
    public const CATEGORIES = [
        'BUSINESS_TRAVEL',
        'TRANSPORT',
        'ACCOMMODATION',
        'MEALS',
        'OFFICE_SUPPLIES',
        'VENDOR_PAYMENT',
        'EMPLOYEE_REIMBURSEMENT',
        'OTHER',
    ];

    public const STATUSES = ['DRAFT', 'SUBMITTED', 'APPROVED', 'REJECTED', 'PAID'];

    public const EMPLOYEE_SUBMITTABLE_CATEGORIES = [
        'BUSINESS_TRAVEL',
        'EMPLOYEE_REIMBURSEMENT',
    ];

    protected $fillable = [
        'expense_number',
        'category',
        'title',
        'description',
        'amount',
        'expense_date',
        'recipient_name',
        'employee_id',
        'cost_center',
        'receipt_path',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'paid_by',
        'paid_at',
        'payment_reference',
        'rejection_note',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount'       => 'decimal:2',
            'approved_at'  => 'datetime',
            'paid_at'      => 'datetime',
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

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
