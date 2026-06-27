<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    private static array $validRoles = [
        User::ROLE_EMPLOYEE,
        User::ROLE_ADMIN_HR,
        User::ROLE_FINANCE,
        User::ROLE_SUPER_ADMIN,
    ];

    public function index(): View
    {
        $users = User::with('employee')->orderBy('name')->get();

        return view('pages.admin.users.index', compact('users'));
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', 'in:' . implode(',', self::$validRoles)],
        ]);

        if ($validated['role'] !== User::ROLE_SUPER_ADMIN && $this->isLastSuperAdmin($user)) {
            return back()->with('error', 'Cannot demote the last super_admin.');
        }

        $oldRole = $user->role;
        $user->update(['role' => $validated['role']]);

        AuditLogService::log(
            auth()->user(),
            'UPDATE_ROLE',
            'user_management',
            "Role changed for user [{$user->name}]: {$oldRole} → {$validated['role']}",
            null,
            User::class,
            $user->id,
            ['role' => $oldRole],
            ['role' => $validated['role']],
        );

        return back()->with('success', "Role for {$user->name} updated to {$validated['role']}.");
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        if (! $validated['is_active'] && $this->isLastSuperAdmin($user)) {
            return back()->with('error', 'Cannot deactivate the last super_admin.');
        }

        $oldStatus = $user->is_active;
        $user->update(['is_active' => $validated['is_active']]);

        $statusLabel = $validated['is_active'] ? 'activated' : 'deactivated';

        AuditLogService::log(
            auth()->user(),
            'UPDATE_STATUS',
            'user_management',
            "User [{$user->name}] {$statusLabel}",
            null,
            User::class,
            $user->id,
            ['is_active' => $oldStatus],
            ['is_active' => $validated['is_active']],
        );

        return back()->with('success', "User {$user->name} has been {$statusLabel}.");
    }

    private function isLastSuperAdmin(User $user): bool
    {
        return $user->role === User::ROLE_SUPER_ADMIN
            && User::where('role', User::ROLE_SUPER_ADMIN)
                ->where('is_active', true)
                ->count() <= 1;
    }
}
