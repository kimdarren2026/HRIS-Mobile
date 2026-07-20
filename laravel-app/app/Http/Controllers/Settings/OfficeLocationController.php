<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\OfficeLocation;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OfficeLocationController extends Controller
{
    // 50m keeps a small single-building office usable; 10000m (10km) caps
    // obviously mistaken entries (e.g. a wrong decimal place) without ruling
    // out a legitimately large campus radius.
    private const RULES = [
        'name'          => ['required', 'string', 'max:100'],
        'latitude'      => ['required', 'numeric', 'between:-90,90'],
        'longitude'     => ['required', 'numeric', 'between:-180,180'],
        'radius_meters' => ['required', 'integer', 'min:50', 'max:10000'],
    ];

    public function create(): View
    {
        $officeLocation = new OfficeLocation(['is_active' => true]);

        return view('pages.settings.locations.create', compact('officeLocation'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);

        $officeLocation = DB::transaction(function () use ($data) {
            if ($data['is_active']) {
                OfficeLocation::where('is_active', true)->update(['is_active' => false]);
            }

            return OfficeLocation::create($data);
        });

        AuditLogService::log(
            auth()->user(),
            'create_office_location',
            'settings',
            "Office location '{$officeLocation->name}' created (radius: {$officeLocation->radius_meters}m, active: "
                .($officeLocation->is_active ? 'yes' : 'no').').'
        );

        return redirect()->route('settings.index')->with('success', 'Office location created successfully.');
    }

    public function edit(OfficeLocation $officeLocation): View
    {
        return view('pages.settings.locations.edit', compact('officeLocation'));
    }

    public function update(Request $request, OfficeLocation $officeLocation): RedirectResponse
    {
        $data = $this->validatedData($request, $officeLocation);

        DB::transaction(function () use ($data, $officeLocation) {
            if ($data['is_active']) {
                OfficeLocation::where('is_active', true)
                    ->where('id', '!=', $officeLocation->id)
                    ->update(['is_active' => false]);
            }

            $officeLocation->update($data);
        });

        AuditLogService::log(
            auth()->user(),
            'update_office_location',
            'settings',
            "Office location '{$officeLocation->name}' updated (radius: {$officeLocation->radius_meters}m, active: "
                .($officeLocation->is_active ? 'yes' : 'no').').'
        );

        return redirect()->route('settings.index')->with('success', 'Office location updated successfully.');
    }

    private function validatedData(Request $request, ?OfficeLocation $officeLocation = null): array
    {
        $data = $request->validate(self::RULES);

        // Checkboxes are absent from the request payload entirely when unchecked,
        // so this is validated and defaulted separately from the rules above.
        $data['is_active'] = $request->boolean('is_active', $officeLocation?->is_active ?? true);

        return $data;
    }
}
