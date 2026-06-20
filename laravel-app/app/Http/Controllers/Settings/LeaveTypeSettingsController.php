<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveTypeSettingsController extends Controller
{
    public function index(): View
    {
        $leaveTypes = LeaveType::orderBy('name')->get();

        return view('pages.settings.leave-types.index', compact('leaveTypes'));
    }

    public function create(): View
    {
        return view('pages.settings.leave-types.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'            => ['required', 'string', 'max:100', 'unique:leave_types,name'],
            'deducts_balance' => ['sometimes', 'boolean'],
        ]);

        $data['deducts_balance'] = $request->boolean('deducts_balance');
        LeaveType::create($data);

        return redirect()->route('settings.leave-types.index')->with('success', 'Leave type created.');
    }

    public function edit(LeaveType $leaveType): View
    {
        return view('pages.settings.leave-types.edit', compact('leaveType'));
    }

    public function update(Request $request, LeaveType $leaveType): RedirectResponse
    {
        $data = $request->validate([
            'name'            => ['required', 'string', 'max:100', 'unique:leave_types,name,' . $leaveType->id],
            'deducts_balance' => ['sometimes', 'boolean'],
        ]);

        $data['deducts_balance'] = $request->boolean('deducts_balance');
        $leaveType->update($data);

        return redirect()->route('settings.leave-types.index')->with('success', 'Leave type updated.');
    }

    public function destroy(LeaveType $leaveType): RedirectResponse
    {
        if ($leaveType->leaveRequests()->exists() || $leaveType->leaveBalances()->exists()) {
            return back()->withErrors(['general' => 'Cannot delete: leave type has existing requests or balances.']);
        }

        $leaveType->delete();

        return redirect()->route('settings.leave-types.index')->with('success', 'Leave type deleted.');
    }
}
