<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Contracts\Auth\Authenticatable;

class AuditLogService
{
    private static array $sensitiveKeys = [
        'password', 'bank_account_number', 'token', 'api_key', 'secret', 'card_number',
    ];

    public static function log(
        ?Authenticatable $user,
        string $action,
        string $module,
        string $description,
        ?array $changes = null,
        ?string $auditableType = null,
        ?int $auditableId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): void {
        AuditLog::create([
            'user_id'        => $user?->id,
            'action'         => $action,
            'module'         => $module,
            'description'    => $description,
            'changes'        => $changes,
            'auditable_type' => $auditableType,
            'auditable_id'   => $auditableId,
            'old_values'     => $oldValues !== null ? self::mask($oldValues) : null,
            'new_values'     => $newValues !== null ? self::mask($newValues) : null,
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
        ]);
    }

    public static function mask(array $data): array
    {
        foreach (self::$sensitiveKeys as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== null) {
                $val       = (string) $data[$key];
                $data[$key] = strlen($val) > 4
                    ? str_repeat('*', max(1, strlen($val) - 4)) . substr($val, -4)
                    : '****';
            }
        }

        return $data;
    }
}
