<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\AttendanceDisciplineFilter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Phase 60B — filter validation for the Dashboard Disiplin Kehadiran and its
 * XLSX export. Both endpoints use this same request, so the screen and the file
 * are validated identically.
 *
 * Only whitelisted keys are ever read. No table name, column name, or sort
 * expression is accepted from the client — ordering is fixed in the service.
 */
class AttendanceDisciplineFilterRequest extends FormRequest
{
    /**
     * Route middleware (`role:super_admin`) plus an explicit abort_unless in the
     * controller are the enforcement points; this mirrors the same rule so the
     * request can never be authorized for a non-super_admin even if a future
     * route registration forgets the middleware.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === User::ROLE_SUPER_ADMIN
            && (bool) $this->user()?->is_active;
    }

    /**
     * Blank query-string values ("?status=") arrive as empty strings, which
     * would fail `in:` rules even though they mean "no filter". Normalise them
     * to null up front so an unset dropdown behaves as "Semua".
     */
    protected function prepareForValidation(): void
    {
        $normalised = [];

        foreach (['start_date', 'end_date', 'employee_id', 'department_id', 'status', 'radius', 'checkout'] as $key) {
            if ($this->has($key) && $this->input($key) === '') {
                $normalised[$key] = null;
            }
        }

        if ($normalised !== []) {
            $this->merge($normalised);
        }
    }

    public function rules(): array
    {
        return [
            'start_date'    => ['nullable', 'date'],
            'end_date'      => ['nullable', 'date', 'after_or_equal:start_date'],
            'employee_id'   => ['nullable', 'integer', 'exists:employees,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'status'        => ['nullable', Rule::in(AttendanceDisciplineFilter::STATUSES)],
            'radius'        => ['nullable', Rule::in([
                AttendanceDisciplineFilter::RADIUS_ALL,
                AttendanceDisciplineFilter::RADIUS_INSIDE,
                AttendanceDisciplineFilter::RADIUS_OUTSIDE,
            ])],
            'checkout'      => ['nullable', Rule::in([
                AttendanceDisciplineFilter::CHECKOUT_ALL,
                AttendanceDisciplineFilter::CHECKOUT_COMPLETE,
                AttendanceDisciplineFilter::CHECKOUT_INCOMPLETE,
            ])],
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.date'          => 'Tanggal mulai tidak valid.',
            'end_date.date'            => 'Tanggal selesai tidak valid.',
            'end_date.after_or_equal'  => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'employee_id.integer'      => 'Pegawai yang dipilih tidak valid.',
            'employee_id.exists'       => 'Pegawai yang dipilih tidak ditemukan.',
            'department_id.integer'    => 'Departemen yang dipilih tidak valid.',
            'department_id.exists'     => 'Departemen yang dipilih tidak ditemukan.',
            'status.in'                => 'Status kehadiran yang dipilih tidak valid.',
            'radius.in'                => 'Klasifikasi radius yang dipilih tidak valid.',
            'checkout.in'              => 'Kelengkapan checkout yang dipilih tidak valid.',
        ];
    }

    public function toFilter(): AttendanceDisciplineFilter
    {
        return AttendanceDisciplineFilter::fromValidated($this->validated());
    }
}
