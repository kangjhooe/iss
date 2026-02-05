<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScanQrAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'qr_data' => ['required', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'attendance_type' => ['required', 'in:student,employee'],
            'teaching_journal_id' => ['required_if:attendance_type,student', 'integer', 'exists:teaching_journals,id'],
            'date' => ['required_if:attendance_type,employee', 'date'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'qr_data.required' => 'Data QR code harus diisi.',
            'latitude.between' => 'Latitude harus antara -90 dan 90.',
            'longitude.between' => 'Longitude harus antara -180 dan 180.',
            'attendance_type.required' => 'Tipe absensi harus diisi.',
            'attendance_type.in' => 'Tipe absensi harus student atau employee.',
            'teaching_journal_id.required_if' => 'ID jurnal mengajar harus diisi untuk absensi siswa.',
            'teaching_journal_id.exists' => 'Jurnal mengajar tidak ditemukan.',
            'date.required_if' => 'Tanggal harus diisi untuk absensi pegawai.',
        ];
    }
}
