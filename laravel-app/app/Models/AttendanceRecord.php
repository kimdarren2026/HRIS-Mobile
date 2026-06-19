<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    protected $fillable = [
        'employee_id',
        'attendance_date',
        'check_in_time',
        'check_in_lat',
        'check_in_lng',
        'check_in_photo_path',
        'check_out_time',
        'check_out_lat',
        'check_out_lng',
        'check_out_photo_path',
        'status',
        'out_of_radius_reason',
        'approved_by',
        'approved_at',
        'approval_note',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'check_in_time' => 'datetime',
            'check_in_lat' => 'decimal:7',
            'check_in_lng' => 'decimal:7',
            'check_out_time' => 'datetime',
            'check_out_lat' => 'decimal:7',
            'check_out_lng' => 'decimal:7',
            'approved_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
