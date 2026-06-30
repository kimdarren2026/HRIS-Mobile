<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    private const CATEGORY_LABELS = [
        'attendance' => 'Attendance',
        'leave'      => 'Leave',
        'payroll'    => 'Payroll',
        'expense'    => 'Expense',
        'general'    => 'General',
    ];

    public function create(
        User $user,
        string $title,
        string $message,
        string $type,
        ?string $actionUrl = null,
        mixed $reference = null,
    ): Notification {
        return Notification::create([
            'user_id'        => $user->id,
            'title'          => $title,
            'message'        => $message,
            'type'           => $type,
            'category'       => self::CATEGORY_LABELS[$type] ?? ucfirst($type),
            'action_url'     => $this->safeActionUrl($actionUrl),
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id'   => $reference?->getKey(),
        ]);
    }

    public function notifyRoles(
        array $roles,
        string $title,
        string $message,
        string $type,
        ?string $actionUrl = null,
        mixed $reference = null,
    ): void {
        User::where('is_active', true)
            ->whereIn('role', $roles)
            ->each(fn (User $user) => $this->create($user, $title, $message, $type, $actionUrl, $reference));
    }

    public function safeActionUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        // Reject control characters
        if (preg_match('/[\x00-\x1f\x7f]/', $url)) {
            return null;
        }

        // Reject absolute and protocol-relative URLs
        if (str_contains($url, '://') || str_starts_with($url, '//')) {
            return null;
        }

        // Only relative internal paths are allowed
        if (! str_starts_with($url, '/')) {
            return null;
        }

        // Reject private file-serving paths
        foreach (['/attendance/photo', '/leave/attachment', '/storage/'] as $blocked) {
            if (str_starts_with($url, $blocked)) {
                return null;
            }
        }

        return $url;
    }
}
