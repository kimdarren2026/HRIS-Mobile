<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'action',
        'module',
        'description',
        'changes',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'changes'    => 'array',
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new RuntimeException('Audit logs are append-only.'));
        static::deleting(fn () => throw new RuntimeException('Audit logs are append-only.'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
