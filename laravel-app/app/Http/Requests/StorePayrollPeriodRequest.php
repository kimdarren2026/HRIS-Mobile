<?php

namespace App\Http\Requests;

use App\Models\PayrollPeriod;
use Illuminate\Foundation\Http\FormRequest;

class StorePayrollPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', PayrollPeriod::class);
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
            'pay_date'   => ['nullable', 'date', 'after_or_equal:end_date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $start = $this->input('start_date');
            $end   = $this->input('end_date');

            if (! $start || ! $end) {
                return;
            }

            $overlap = PayrollPeriod::where('start_date', '<=', $end)
                ->where('end_date', '>=', $start)
                ->exists();

            if ($overlap) {
                $validator->errors()->add('start_date', 'A payroll period already exists for this date range.');
            }
        });
    }
}
