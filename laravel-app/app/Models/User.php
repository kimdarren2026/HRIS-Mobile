<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_EMPLOYEE = 'employee';

    public const ROLE_ADMIN_HR = 'admin_hr';

    public const ROLE_FINANCE = 'finance';

    public const ROLE_SUPER_ADMIN = 'super_admin';

    private const DASHBOARD_PATHS = [
        self::ROLE_EMPLOYEE => '/employee/dashboard',
        self::ROLE_FINANCE => '/finance/dashboard',
        self::ROLE_ADMIN_HR => '/admin/dashboard',
        self::ROLE_SUPER_ADMIN => '/admin/dashboard',
    ];

    protected $fillable = [
        'name',
        'email',
        'google_id',
        'google_linked_at',
        'password',
        'role',
        'is_active',
        'last_login_at',
        'last_login_ip',
        'last_login_provider',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public static function supportedRoles(): array
    {
        return array_keys(self::DASHBOARD_PATHS);
    }

    public static function isSupportedRole(?string $role): bool
    {
        return $role !== null && array_key_exists($role, self::DASHBOARD_PATHS);
    }

    public static function dashboardPathForRole(string $role): ?string
    {
        return self::DASHBOARD_PATHS[$role] ?? null;
    }

    public function dashboardPath(): ?string
    {
        return self::dashboardPathForRole($this->role);
    }

    public function requiresEmployeeRecord(): bool
    {
        return $this->role === self::ROLE_EMPLOYEE;
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function approvedAttendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'approved_by');
    }

    public function approvedLeaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'approved_by');
    }

    public function createdPayrollPeriods(): HasMany
    {
        return $this->hasMany(PayrollPeriod::class, 'created_by');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'google_linked_at' => 'datetime',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected function email(): Attribute
    {
        return Attribute::make(
            set: static fn (?string $value): ?string => $value === null ? null : strtolower(trim($value)),
        );
    }
}
