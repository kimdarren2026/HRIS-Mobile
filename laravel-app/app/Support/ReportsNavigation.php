<?php

namespace App\Support;

use App\Models\User;

/**
 * Phase 60C — the single decision point for where the "Laporan" bottom-nav
 * entry points, per role.
 *
 * super_admin goes straight to the Phase 60B Dashboard Disiplin Kehadiran;
 * every other role keeps the existing /reports landing. This lives in one class
 * because the bottom navigation is duplicated per page in this codebase — one
 * decision point keeps every copy honest instead of six near-identical @if
 * blocks drifting apart.
 *
 * Presentation only. Access is enforced by the route middleware
 * (role:super_admin on the dashboard and its export, role:admin_hr,finance,
 * super_admin on /reports) plus the controller check, so a wrong link here
 * could never hand anyone a page they may not open.
 */
final class ReportsNavigation
{
    /**
     * Destination of the "Laporan" menu item for the given (or current) user.
     */
    public static function url(?User $user = null): string
    {
        $user ??= auth()->user();

        return self::pointsToDisciplineDashboard($user)
            ? route('admin.attendance-discipline.index')
            : route('reports.index');
    }

    /**
     * Whether this user's "Laporan" entry resolves to the Phase 60B dashboard.
     *
     * Exposed so active-state logic and tests read the same rule the link does.
     */
    public static function pointsToDisciplineDashboard(?User $user = null): bool
    {
        $user ??= auth()->user();

        return $user?->role === User::ROLE_SUPER_ADMIN;
    }
}
