<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        $employee = auth()->user()->employee;

        abort_unless($employee !== null, 404);

        $employee->load(['user', 'department', 'position']);

        return view('pages.employee.my-profile', compact('employee'));
    }
}
