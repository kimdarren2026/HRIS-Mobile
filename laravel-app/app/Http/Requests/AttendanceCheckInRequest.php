<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceCheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
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
        ];
    }
}
