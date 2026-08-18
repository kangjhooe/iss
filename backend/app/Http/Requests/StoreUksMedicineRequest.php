<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUksMedicineRequest extends FormRequest
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
            'unit' => 'nullable|string|max:50',
            'quantity' => 'nullable|integer|min:0',
            'min_stock' => 'nullable|integer|min:0',
            'expiry_date' => 'nullable|date',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama obat wajib diisi.',
            'quantity.min' => 'Stok awal tidak boleh negatif.',
        ];
    }
}
