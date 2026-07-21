<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date'    => ['required', 'date', 'after_or_equal:today'],
            'end_date'      => ['required', 'date', 'after_or_equal:start_date'],
            'duration_type' => ['nullable', 'in:FULL_DAY,HALF_DAY'],
            'reason'        => ['required', 'string', 'min:10', 'max:1000'],
            'attachment'    => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'leave_type_id.required' => 'Please select a leave type.',
            'start_date.after_or_equal' => 'Start date cannot be in the past.',
            'end_date.after_or_equal'   => 'End date must be on or after start date.',
            'reason.min'                => 'Reason must be at least 10 characters.',
            'attachment.mimes'          => 'Attachment must be PDF, JPG, or PNG.',
            'attachment.max'            => 'Attachment must be under 5MB.',
        ];
    }
}
