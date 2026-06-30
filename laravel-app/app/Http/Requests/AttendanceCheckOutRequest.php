<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceCheckOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            // TODO (Phase 38): add 'photo' required selfie for checkout, mirroring check-in.
        ];
    }

    public function messages(): array
    {
        return [
            'lat.required' => 'Koordinat GPS diperlukan. Pastikan izin lokasi diaktifkan.',
            'lat.numeric'  => 'Koordinat latitude tidak valid.',
            'lat.between'  => 'Latitude harus antara -90 dan 90.',
            'lng.required' => 'Koordinat GPS diperlukan. Pastikan izin lokasi diaktifkan.',
            'lng.numeric'  => 'Koordinat longitude tidak valid.',
            'lng.between'  => 'Longitude harus antara -180 dan 180.',
        ];
    }
}
