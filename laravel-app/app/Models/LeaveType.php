<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'deducts_balance',
        'counts_calendar_days',
    ];

    // Phase 47: stored `name` values stay English (production data, uniqueness,
    // and LeaveService's case-insensitive policy matching all key off them).
    // This is a display-only translation layer for employee-facing pages —
    // it never touches the database. Unmapped names (e.g. custom types an HR
    // admin creates later) fall back to the raw name unchanged.
    private const INDONESIAN_LABELS = [
        'annual leave'   => 'Cuti Tahunan',
        'sick leave'     => 'Cuti Sakit',
        'permission'     => 'Izin',
        'special leave'  => 'Cuti Khusus',
        'personal leave' => 'Cuti Tahunan',
    ];

    // Matches LeaveService::ANNUAL_ENTITLEMENT_TYPE_NAMES — kept here as the
    // single source of truth so both the policy engine and the balance-preview
    // UI agree on which leave types draw from the annual entitlement pool.
    private const ANNUAL_ENTITLEMENT_TYPE_NAMES = ['annual leave', 'personal leave'];

    protected function casts(): array
    {
        return [
            'deducts_balance'       => 'boolean',
            'counts_calendar_days'  => 'boolean',
        ];
    }

    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: fn () => self::INDONESIAN_LABELS[strtolower($this->name)] ?? $this->name,
        );
    }

    public function isAnnualEntitlementType(): bool
    {
        return in_array(strtolower($this->name), self::ANNUAL_ENTITLEMENT_TYPE_NAMES, true);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }
}
