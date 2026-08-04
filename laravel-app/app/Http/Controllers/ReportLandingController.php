<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Phase 60C — role-aware landing for /reports.
 *
 * super_admin is sent to the only report module that actually exists, the
 * Phase 60B Dashboard Disiplin Kehadiran. admin_hr and finance keep the
 * existing placeholder because their report modules are not built yet.
 *
 * This grants no access: /reports keeps its role:admin_hr,finance,super_admin
 * middleware and the discipline dashboard keeps its own role:super_admin guard
 * plus the controller-level check. The redirect only picks a destination.
 */
class ReportLandingController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        if (auth()->user()?->role === User::ROLE_SUPER_ADMIN) {
            return redirect()->route('admin.attendance-discipline.index');
        }

        return view('pages.reports.index');
    }
}
