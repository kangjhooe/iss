<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePpdbChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'quota' => 'nullable|integer|min:0',
            'requirements' => 'nullable|string',
            'required_documents' => 'nullable|array|max:12',
            'required_documents.*.key' => 'nullable|string|max:40',
            'required_documents.*.label' => 'required|string|max:80',
            'required_documents.*.required' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kode jalur wajib diisi.',
            'name.required' => 'Nama jalur wajib diisi.',
        ];
    }
}
