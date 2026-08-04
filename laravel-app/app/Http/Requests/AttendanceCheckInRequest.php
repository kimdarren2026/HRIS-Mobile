<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceCheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    // Phase 60A: trim before validation so a whitespace-only work plan fails
    // `required`/`min:10` naturally instead of passing as "10 spaces", and so
    // the trimmed value is what actually gets stored.
    protected function prepareForValidation(): void
    {
        if ($this->has('check_in_work_plan') && is_string($this->input('check_in_work_plan'))) {
            $this->merge([
                'check_in_work_plan' => trim($this->input('check_in_work_plan')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'lat'      => ['required', 'numeric', 'between:-90,90'],
            'lng'      => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['required', 'numeric', 'min:0', 'max:100000'],
            'photo'    => ['required', 'image', 'mimes:jpeg,png', 'max:5120'],
            // Conditional 'required if outside radius' is enforced in the controller,
            // since the radius decision itself depends on a server-side distance calc.
            'reason'   => ['nullable', 'string', 'max:500'],
            // Phase 60A: required for every new check-in, inside or outside radius.
            'check_in_work_plan' => ['required', 'string', 'min:10', 'max:1000'],
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
            'photo.required'    => 'Foto selfie wajib diambil sebelum check-in.',
            'photo.image'       => 'File yang diunggah harus berupa gambar.',
            'photo.mimes'       => 'Format foto harus JPEG atau PNG.',
            'photo.max'         => 'Ukuran foto tidak boleh melebihi 5 MB.',
            'check_in_work_plan.required' => 'Rencana kerja hari ini wajib diisi.',
            'check_in_work_plan.min'      => 'Rencana kerja minimal 10 karakter.',
            'check_in_work_plan.max'      => 'Rencana kerja maksimal 1000 karakter.',
        ];
    }
}
