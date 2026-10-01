<?php

namespace App\Http\Requests;

use App\Models\SubjectCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubjectCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'code' => [
                'sometimes',
                'string',
                'size:4',
                'regex:/^\d{4}$/',
                Rule::unique('subject_catalog', 'code')->ignore($id),
            ],
            'name' => ['sometimes', 'string', 'max:255'],
            'jenjang' => ['sometimes', Rule::in(array_keys(SubjectCatalog::JENJANG_PREFIX))],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.size' => 'Kode harus tepat 4 digit',
            'code.regex' => 'Kode harus berupa 4 digit angka (contoh: 1001)',
            'code.unique' => 'Kode mata pelajaran ini sudah dipakai',
            'jenjang.in' => 'Jenjang tidak valid',
        ];
    }
}
