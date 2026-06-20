<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use App\Models\OfficeLocation;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        $office     = OfficeLocation::where('is_active', true)->first();
        $leaveTypes = LeaveType::orderBy('name')->get();

        return view('pages.settings.index', compact('office', 'leaveTypes'));
    }
}
