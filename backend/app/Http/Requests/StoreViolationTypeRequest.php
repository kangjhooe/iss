<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreViolationTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'category' => 'required|in:ringan,sedang,berat',
            'point_weight' => 'nullable|integer|min:0|max:100',
            'default_sanction' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama jenis pelanggaran wajib diisi.',
            'category.required' => 'Kategori wajib dipilih.',
        ];
    }
}
