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
            'lat'    => ['required', 'numeric', 'between:-90,90'],
            'lng'    => ['required', 'numeric', 'between:-180,180'],
            'photo'  => ['required', 'image', 'mimes:jpeg,png', 'max:5120'],
            // reason conditional validation is handled in the controller after radius check
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'lat.required'   => 'Koordinat GPS diperlukan. Pastikan izin lokasi diaktifkan.',
            'lat.numeric'    => 'Koordinat latitude tidak valid.',
            'lat.between'    => 'Latitude harus antara -90 dan 90.',
            'lng.required'   => 'Koordinat GPS diperlukan. Pastikan izin lokasi diaktifkan.',
            'lng.numeric'    => 'Koordinat longitude tidak valid.',
            'lng.between'    => 'Longitude harus antara -180 dan 180.',
            'photo.required' => 'Foto selfie wajib diambil sebelum check-in.',
            'photo.image'    => 'File yang diunggah harus berupa gambar.',
            'photo.mimes'    => 'Format foto harus JPEG atau PNG.',
            'photo.max'      => 'Ukuran foto tidak boleh melebihi 5 MB.',
        ];
    }
}
