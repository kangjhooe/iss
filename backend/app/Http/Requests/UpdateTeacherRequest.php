<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeacherRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $teacherId = $this->route('teacher')->id ?? $this->route('id');
        
        return [
            'nik' => [
                'sometimes',
                'required',
                'string',
                'size:16',
                'regex:/^[0-9]{16}$/',
                Rule::unique('employee', 'nik')->ignore($teacherId),
            ],
            'type' => 'sometimes|required|in:Guru',
            'nip' => 'nullable|string|max:50',
            'nuptk' => [
                'nullable',
                'string',
                'max:16',
                Rule::unique('employee', 'nuptk')->ignore($teacherId),
            ],
            'name' => 'sometimes|required|string|max:255',
            'gender' => 'sometimes|required|in:L,P',
            'birth_date' => 'nullable|date',
            'birth_place' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'religion' => 'nullable|string|max:50',
            'employment_status' => 'nullable|in:PNS,CPNS,Guru Tetap Yayasan,Guru Honor Sekolah,Guru Kontrak,Pegawai Tetap Yayasan,Pegawai Honor,Pegawai Kontrak',
            'education_level' => 'nullable|in:SMA,D3,S1,S2,S3',
            'major' => 'nullable|string|max:255',
            'subject' => 'nullable|string|max:255',
            'status' => 'nullable|in:Aktif,Pensiun,Pindah,Tidak Aktif',
            'join_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'certification_status' => 'nullable|in:Sudah,Belum',
            'certification_date' => 'nullable|date',
            'teacher_registration_number' => 'nullable|string|max:50',
            'certification_number' => 'nullable|string|max:100',
            'certification_issuing_authority' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nik.required' => 'NIK wajib diisi',
            'nik.size' => 'NIK harus terdiri dari 16 digit',
            'nik.regex' => 'NIK harus berupa angka 16 digit',
            'nik.unique' => 'NIK sudah terdaftar',
            'name.required' => 'Nama guru wajib diisi',
            'gender.required' => 'Jenis kelamin wajib diisi',
            'gender.in' => 'Jenis kelamin harus L atau P',
            'nuptk.unique' => 'NUPTK sudah terdaftar',
            'email.email' => 'Format email tidak valid',
        ];
    }
}
