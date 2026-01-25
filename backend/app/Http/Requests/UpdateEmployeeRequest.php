<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
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
        $employeeId = $this->route('employee')->id ?? $this->route('id');
        
        return [
            'nik' => [
                'sometimes',
                'required',
                'string',
                'size:16',
                'regex:/^[0-9]{16}$/',
                Rule::unique('employee', 'nik')->ignore($employeeId),
            ],
            'type' => 'sometimes|required|in:Guru,Staff,Tenaga Administrasi,Tenaga Kebersihan,Tenaga Keamanan,Lainnya',
            'nip' => 'nullable|string|max:50',
            'nuptk' => [
                'nullable',
                'string',
                'max:16',
                Rule::unique('employee', 'nuptk')->ignore($employeeId),
            ],
            'name' => 'sometimes|required|string|max:255',
            'gender' => 'sometimes|required|in:L,P',
            'birth_date' => 'nullable|date',
            'birth_place' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::requiredIf($this->input('type') === 'Guru'),
            ],
            'religion' => 'nullable|string|max:50',
            'employment_status' => 'nullable|in:PNS,CPNS,Guru Tetap Yayasan,Guru Honor Sekolah,Guru Kontrak,Pegawai Tetap Yayasan,Pegawai Honor,Pegawai Kontrak',
            'education_level' => 'nullable|in:SMA,D3,S1,S2,S3',
            'major' => 'nullable|string|max:255',
            'subject' => 'nullable|string|max:255',
            'status' => 'nullable|in:Aktif,Pensiun,Pindah,Tidak Aktif',
            'join_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'educations' => 'nullable|array',
            'educations.*.level' => 'nullable|in:SD,SMP,SMA,SMK,D1,D2,D3,D4,S1,S2,S3',
            'educations.*.school_name' => 'nullable|string|max:255',
            'educations.*.major' => 'nullable|string|max:255',
            'educations.*.graduation_year' => 'nullable|integer|min:1900|max:' . (date('Y') + 10),
            'educations.*.certificate_number' => 'nullable|string|max:255',
            'educations.*.city' => 'nullable|string|max:255',
            'educations.*.notes' => 'nullable|string',
            'permission_keys' => 'nullable|array',
            'permission_keys.*' => 'string|exists:permissions,key',
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
            'type.required' => 'Tipe pegawai wajib diisi',
            'type.in' => 'Tipe pegawai tidak valid',
            'name.required' => 'Nama pegawai wajib diisi',
            'gender.required' => 'Jenis kelamin wajib diisi',
            'gender.in' => 'Jenis kelamin harus L atau P',
            'nuptk.unique' => 'NUPTK sudah terdaftar',
            'email.email' => 'Format email tidak valid',
        ];
    }
}
