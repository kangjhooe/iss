<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeacherRequest extends FormRequest
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
        return [
            'institution_id' => 'sometimes|exists:institution,id',
            'nip' => 'nullable|string|max:50',
            'nuptk' => 'nullable|string|max:16|unique:teacher,nuptk',
            'name' => 'required|string|max:255',
            'gender' => 'required|in:L,P',
            'birth_date' => 'nullable|date',
            'birth_place' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'religion' => 'nullable|string|max:50',
            'employment_status' => 'nullable|in:PNS,CPNS,Guru Tetap Yayasan,Guru Honor Sekolah,Guru Kontrak',
            'education_level' => 'nullable|in:SMA,D3,S1,S2,S3',
            'major' => 'nullable|string|max:255',
            'subject' => 'nullable|string|max:255',
            'status' => 'nullable|in:Aktif,Pensiun,Pindah,Tidak Aktif',
            'join_date' => 'nullable|date',
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
            'name.required' => 'Nama guru wajib diisi',
            'gender.required' => 'Jenis kelamin wajib diisi',
            'gender.in' => 'Jenis kelamin harus L atau P',
            'nuptk.unique' => 'NUPTK sudah terdaftar',
            'email.email' => 'Format email tidak valid',
        ];
    }
}
