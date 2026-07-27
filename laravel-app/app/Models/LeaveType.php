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

    // Phase 58C correction: Annual Leave and Personal Leave are one shared
    // 18-day/year pool, not 18 days each (they already share one monthly cap
    // in LeaveService — the balance pool must match). All leave_balances
    // reads/writes for any annual-entitlement type must key off this single
    // canonical type's id.
    //
    // leave_types has no stable identifier column (code/slug/category/
    // is_annual) — only `name`, which is unique but renamable by admin_hr via
    // Settings. Database id / insertion order is NOT used to pick the owner,
    // since it depends on seed/creation order and is not a business rule.
    //
    // Instead: resolve by explicit name priority — ANNUAL_ENTITLEMENT_TYPE_NAMES
    // is ordered ['annual leave', 'personal leave'], so "Annual Leave" /
    // "Cuti Tahunan" always wins as pool owner whenever it exists, regardless
    // of which type was created first or has the lower id. "Personal Leave"
    // is used as the pool owner ONLY as a documented fallback, for the case
    // where Annual Leave has been deleted/renamed away and is genuinely
    // unavailable — it still never gets its own separate 18-day pool.
    public static function canonicalAnnualEntitlementType(): ?self
    {
        $candidates = static::all()->filter(fn (self $type) => $type->isAnnualEntitlementType());

        foreach (self::ANNUAL_ENTITLEMENT_TYPE_NAMES as $preferredName) {
            $match = $candidates->first(fn (self $type) => strtolower($type->name) === $preferredName);

            if ($match !== null) {
                return $match;
            }
        }

        return null;
    }

    public static function annualEntitlementTypeIds(): array
    {
        return static::all()
            ->filter(fn (self $type) => $type->isAnnualEntitlementType())
            ->pluck('id')
            ->all();
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
