<?php

namespace App\Http\Requests;

use App\Support\InstitutionContext;
use Illuminate\Foundation\Http\FormRequest;

class StoreSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();
        $institutionId = $user
            ? InstitutionContext::resolveForUser($user, $this, $this->get('institution_id'))
            : null;
        return [
            'subject_catalog_id' => 'nullable|integer|exists:subject_catalog,id',
            'code' => [
                'required_without:subject_catalog_id',
                'nullable',
                'string',
                'max:50',
                $institutionId ? 'unique:subjects,code,NULL,id,institution_id,' . $institutionId : 'unique:subjects,code',
            ],
            'name' => 'required_without:subject_catalog_id|nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required_without' => 'Kode mata pelajaran wajib diisi jika tidak memilih dari katalog.',
            'code.unique' => 'Kode mata pelajaran sudah digunakan di institusi ini.',
            'name.required_without' => 'Nama mata pelajaran wajib diisi jika tidak memilih dari katalog.',
            'subject_catalog_id.exists' => 'Mapel katalog tidak ditemukan.',
        ];
    }
}
