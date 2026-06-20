<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\OfficeLocation;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OfficeLocationController extends Controller
{
    public function edit(OfficeLocation $officeLocation): View
    {
        return view('pages.settings.locations.edit', compact('officeLocation'));
    }

    public function update(Request $request, OfficeLocation $officeLocation): RedirectResponse
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'latitude'      => ['required', 'numeric', 'between:-90,90'],
            'longitude'     => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'min:50', 'max:10000'],
        ]);

        $officeLocation->update($data);

        AuditLogService::log(
            auth()->user(),
            'update_office_location',
            'settings',
            "Office location '{$officeLocation->name}' updated (radius: {$officeLocation->radius_meters}m)."
        );

        return redirect()->route('settings.index')->with('success', 'Office location updated successfully.');
    }
}
