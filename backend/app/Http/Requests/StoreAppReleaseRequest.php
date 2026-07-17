<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppReleaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:200',
            'version' => 'nullable|string|max:50',
            'released_at' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*' => 'required|string|max:500',
            'is_published' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul wajib diisi',
            'released_at.required' => 'Tanggal rilis wajib diisi',
            'released_at.date' => 'Tanggal rilis tidak valid',
            'items.required' => 'Minimal satu poin pembaruan',
            'items.min' => 'Minimal satu poin pembaruan',
            'items.*.required' => 'Poin pembaruan tidak boleh kosong',
        ];
    }
}
