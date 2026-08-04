<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceCheckOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    // Phase 60A: trim before validation so a whitespace-only work result fails
    // `required`/`min:10` naturally, and the trimmed value is what gets stored.
    protected function prepareForValidation(): void
    {
        if ($this->has('check_out_work_result') && is_string($this->input('check_out_work_result'))) {
            $this->merge([
                'check_out_work_result' => trim($this->input('check_out_work_result')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'lat'      => ['required', 'numeric', 'between:-90,90'],
            'lng'      => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['required', 'numeric', 'min:0', 'max:100000'],
            // TODO (Phase 38): add 'photo' required selfie for checkout, mirroring check-in.
            // Phase 60A: required for every new checkout, regardless of inside/outside or approval status.
            'check_out_work_result' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'lat.required'      => 'Koordinat GPS diperlukan. Pastikan izin lokasi diaktifkan.',
            'lat.numeric'       => 'Koordinat latitude tidak valid.',
            'lat.between'       => 'Latitude harus antara -90 dan 90.',
            'lng.required'      => 'Koordinat GPS diperlukan. Pastikan izin lokasi diaktifkan.',
            'lng.numeric'       => 'Koordinat longitude tidak valid.',
            'lng.between'       => 'Longitude harus antara -180 dan 180.',
            'accuracy.required' => 'Akurasi GPS diperlukan. Pastikan izin lokasi diaktifkan.',
            'accuracy.numeric'  => 'Akurasi GPS tidak valid.',
            'accuracy.min'      => 'Akurasi GPS tidak valid.',
            'accuracy.max'      => 'Akurasi GPS tidak wajar.',
            'check_out_work_result.required' => 'Hasil pekerjaan hari ini wajib diisi.',
            'check_out_work_result.min'      => 'Hasil pekerjaan minimal 10 karakter.',
            'check_out_work_result.max'      => 'Hasil pekerjaan maksimal 2000 karakter.',
        ];
    }
}
