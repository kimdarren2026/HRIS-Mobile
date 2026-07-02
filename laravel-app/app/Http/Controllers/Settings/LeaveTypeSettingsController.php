<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use App\Services\AuditLogService;
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
            'name'                  => ['required', 'string', 'max:100', 'unique:leave_types,name'],
            'deducts_balance'       => ['sometimes', 'boolean'],
            'counts_calendar_days'  => ['sometimes', 'boolean'],
        ]);

        $data['deducts_balance']      = $request->boolean('deducts_balance');
        $data['counts_calendar_days'] = $request->boolean('counts_calendar_days');
        $leaveType = LeaveType::create($data);

        AuditLogService::log(
            auth()->user(),
            'create_leave_type',
            'settings',
            "Leave type '{$leaveType->name}' created.",
            null,
            LeaveType::class,
            $leaveType->id,
            null,
            ['name' => $leaveType->name, 'deducts_balance' => $leaveType->deducts_balance, 'counts_calendar_days' => $leaveType->counts_calendar_days],
        );

        return redirect()->route('settings.leave-types.index')->with('success', 'Leave type created.');
    }

    public function edit(LeaveType $leaveType): View
    {
        return view('pages.settings.leave-types.edit', compact('leaveType'));
    }

    public function update(Request $request, LeaveType $leaveType): RedirectResponse
    {
        $data = $request->validate([
            'name'                  => ['required', 'string', 'max:100', 'unique:leave_types,name,' . $leaveType->id],
            'deducts_balance'       => ['sometimes', 'boolean'],
            'counts_calendar_days'  => ['sometimes', 'boolean'],
        ]);

        $old = $leaveType->only(['name', 'deducts_balance', 'counts_calendar_days']);
        $data['deducts_balance']      = $request->boolean('deducts_balance');
        $data['counts_calendar_days'] = $request->boolean('counts_calendar_days');
        $leaveType->update($data);

        AuditLogService::log(
            auth()->user(),
            'update_leave_type',
            'settings',
            "Leave type '{$leaveType->name}' updated.",
            null,
            LeaveType::class,
            $leaveType->id,
            $old,
            ['name' => $leaveType->name, 'deducts_balance' => $leaveType->deducts_balance, 'counts_calendar_days' => $leaveType->counts_calendar_days],
        );

        return redirect()->route('settings.leave-types.index')->with('success', 'Leave type updated.');
    }

    public function destroy(LeaveType $leaveType): RedirectResponse
    {
        if ($leaveType->leaveRequests()->exists() || $leaveType->leaveBalances()->exists()) {
            return back()->withErrors(['general' => 'Cannot delete: leave type has existing requests or balances.']);
        }

        $snapshot = $leaveType->only(['id', 'name', 'deducts_balance']);
        $leaveType->delete();

        AuditLogService::log(
            auth()->user(),
            'delete_leave_type',
            'settings',
            "Leave type '{$snapshot['name']}' deleted.",
            null,
            LeaveType::class,
            $snapshot['id'],
            $snapshot,
            null,
        );

        return redirect()->route('settings.leave-types.index')->with('success', 'Leave type deleted.');
    }
}
