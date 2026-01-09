<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
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
        $studentId = $this->route('student')->id ?? $this->route('id');
        
        return [
            'nik' => [
                'sometimes',
                'required',
                'string',
                'max:16',
                Rule::unique('student', 'nik')->ignore($studentId),
            ],
            'nis' => 'nullable|string|max:50',
            'nisn' => [
                'nullable',
                'string',
                'max:10',
                Rule::unique('student', 'nisn')->ignore($studentId),
            ],
            'name' => 'sometimes|required|string|max:255',
            'gender' => 'sometimes|required|in:L,P',
            'birth_date' => 'sometimes|required|date',
            'birth_place' => 'sometimes|required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'religion' => 'nullable|string|max:50',
            'no_kk' => 'nullable|string|max:16',
            'aspiration' => 'nullable|string|max:255',
            'hobby' => 'nullable|string|max:255',
            'disability' => 'nullable|string|max:255',
            'height' => 'nullable|integer|min:0|max:300',
            'weight' => 'nullable|integer|min:0|max:500',
            'previous_school' => 'nullable|string|max:255',
            'residence_type' => 'nullable|in:asrama,kost_kontrak,tinggal_dengan_orang_tua,lainnya',
            'class' => 'nullable|string|max:50',
            'academic_year' => 'nullable|string|max:10',
            'status' => 'nullable|in:Aktif,Lulus,Pindah,Drop Out,Tidak Aktif',
            'father_name' => 'nullable|string|max:255',
            'father_status' => 'nullable|in:masih_hidup,meninggal_dunia,tidak_diketahui',
            'father_nik' => 'nullable|string|max:16',
            'father_birth_place' => 'nullable|string|max:255',
            'father_birth_date' => 'nullable|date',
            'father_education' => 'nullable|string|max:255',
            'father_occupation' => 'nullable|string|max:255',
            'father_income' => 'nullable|numeric|min:0',
            'mother_name' => 'nullable|string|max:255',
            'mother_status' => 'nullable|in:masih_hidup,meninggal_dunia,tidak_diketahui',
            'mother_nik' => 'nullable|string|max:16',
            'mother_birth_place' => 'nullable|string|max:255',
            'mother_birth_date' => 'nullable|date',
            'mother_education' => 'nullable|string|max:255',
            'mother_occupation' => 'nullable|string|max:255',
            'mother_income' => 'nullable|numeric|min:0',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_phone' => 'nullable|string|max:20',
            'guardian_type' => 'nullable|in:sama_dengan_ayah,sama_dengan_ibu,lainnya',
            'guardian_status' => 'nullable|in:masih_hidup,meninggal_dunia,tidak_diketahui',
            'guardian_nik' => 'nullable|string|max:16',
            'guardian_birth_place' => 'nullable|string|max:255',
            'guardian_birth_date' => 'nullable|date',
            'guardian_education' => 'nullable|string|max:255',
            'guardian_occupation' => 'nullable|string|max:255',
            'guardian_income' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
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
            'nik.unique' => 'NIK sudah terdaftar',
            'name.required' => 'Nama siswa wajib diisi',
            'gender.required' => 'Jenis kelamin wajib diisi',
            'gender.in' => 'Jenis kelamin harus L atau P',
            'birth_date.required' => 'Tanggal lahir wajib diisi',
            'birth_place.required' => 'Tempat lahir wajib diisi',
            'nisn.unique' => 'NISN sudah terdaftar',
            'email.email' => 'Format email tidak valid',
        ];
    }
}
