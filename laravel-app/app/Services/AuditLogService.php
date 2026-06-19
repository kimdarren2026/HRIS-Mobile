<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Contracts\Auth\Authenticatable;

class AuditLogService
{
    public static function log(
        ?Authenticatable $user,
        string $action,
        string $module,
        string $description,
        ?array $changes = null
    ): void {
        AuditLog::create([
            'user_id'     => $user?->id,
            'action'      => $action,
            'module'      => $module,
            'description' => $description,
            'changes'     => $changes,
            'ip_address'  => request()->ip(),
        ]);
    }
}
