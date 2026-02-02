<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $institutionId = $this->user()?->institution_id;
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                $institutionId ? 'unique:subjects,code,NULL,id,institution_id,' . $institutionId : 'unique:subjects,code',
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kode mata pelajaran wajib diisi.',
            'code.unique' => 'Kode mata pelajaran sudah digunakan di institusi ini.',
            'name.required' => 'Nama mata pelajaran wajib diisi.',
        ];
    }
}
