<?php

namespace App\Http\Requests;

use App\Models\SubjectCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubjectCatalogRequest extends FormRequest
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
        return [
            'code' => ['required', 'string', 'size:4', 'regex:/^\d{4}$/', 'unique:subject_catalog,code'],
            'name' => ['required', 'string', 'max:255'],
            'jenjang' => ['required', Rule::in(array_keys(SubjectCatalog::JENJANG_PREFIX))],
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
            'code.required' => 'Kode mata pelajaran wajib diisi',
            'code.size' => 'Kode harus tepat 4 digit',
            'code.regex' => 'Kode harus berupa 4 digit angka (contoh: 1001)',
            'code.unique' => 'Kode mata pelajaran ini sudah dipakai',
            'name.required' => 'Nama mata pelajaran wajib diisi',
            'jenjang.required' => 'Jenjang wajib dipilih',
            'jenjang.in' => 'Jenjang tidak valid',
        ];
    }
}
