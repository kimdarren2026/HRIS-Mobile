<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Employee::class);

        $employees = Employee::with(['user', 'department', 'position'])
            ->when(request('search'), fn ($q, $s) => $q->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$s}%"))
                ->orWhere('nik', 'like', "%{$s}%"))
            ->when(request('department_id'), fn ($q, $d) => $q->where('department_id', $d))
            ->when(request('status'), fn ($q, $s) => $q->where('employment_status', $s))
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        $departments = Department::orderBy('name')->get();

        return view('pages.hr.employees-index', compact('employees', 'departments'));
    }

    public function show(Employee $employee): View
    {
        Gate::authorize('view', $employee);

        $employee->load(['user', 'department', 'position']);

        return view('pages.hr.employee-show', compact('employee'));
    }

    public function create(): View
    {
        Gate::authorize('create', Employee::class);

        $departments = Department::orderBy('name')->get();
        $positions   = Position::with('department')->orderBy('name')->get();

        return view('pages.hr.employee-form', compact('departments', 'positions'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Employee::class);

        $validated = $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            'email'               => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'            => ['required', 'string', 'min:8', 'max:255'],
            'nik'                 => ['required', 'string', 'max:50', 'unique:employees,nik'],
            'department_id'       => ['required', 'exists:departments,id'],
            'position_id'         => ['required', 'exists:positions,id'],
            'join_date'           => ['required', 'date'],
            'employment_status'   => ['required', Rule::in(['active', 'probation', 'resigned', 'terminated'])],
            'phone_number'        => ['required', 'string', 'max:20'],
            'address'             => ['nullable', 'string', 'max:500'],
            'bank_name'           => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
        ]);

        $user = User::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'password'  => Hash::make($validated['password']),
            'role'      => 'employee',
            'is_active' => true,
        ]);

        $employee = Employee::create([
            'user_id'             => $user->id,
            'nik'                 => $validated['nik'],
            'department_id'       => $validated['department_id'],
            'position_id'         => $validated['position_id'],
            'join_date'           => $validated['join_date'],
            'employment_status'   => $validated['employment_status'],
            'phone_number'        => $validated['phone_number'],
            'address'             => $validated['address'] ?? null,
            'bank_name'           => $validated['bank_name'] ?? null,
            'bank_account_number' => $validated['bank_account_number'] ?? null,
        ]);

        AuditLogService::log(
            auth()->user(),
            'create_employee',
            'employee',
            "Employee '{$employee->nik}' ({$user->name}) created by " . auth()->user()->name . '.'
        );

        return redirect()->route('employees.show', $employee)
            ->with('success', "Employee {$user->name} created successfully.");
    }

    public function edit(Employee $employee): View
    {
        Gate::authorize('update', $employee);

        $employee->load(['user', 'department', 'position']);
        $departments = Department::orderBy('name')->get();
        $positions   = Position::with('department')->orderBy('name')->get();

        return view('pages.hr.employee-form', compact('employee', 'departments', 'positions'));
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        Gate::authorize('update', $employee);

        $validated = $request->validate([
            'nik'                 => ['required', 'string', 'max:50', Rule::unique('employees', 'nik')->ignore($employee->id)],
            'department_id'       => ['required', 'exists:departments,id'],
            'position_id'         => ['required', 'exists:positions,id'],
            'join_date'           => ['required', 'date'],
            'employment_status'   => ['required', Rule::in(['active', 'probation', 'resigned', 'terminated'])],
            'phone_number'        => ['required', 'string', 'max:20'],
            'address'             => ['nullable', 'string', 'max:500'],
            'bank_name'           => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
        ]);

        $employee->update($validated);

        AuditLogService::log(
            auth()->user(),
            'update_employee',
            'employee',
            "Employee '{$employee->nik}' updated by " . auth()->user()->name . '.'
        );

        return redirect()->route('employees.show', $employee)
            ->with('success', 'Employee updated successfully.');
    }
}
