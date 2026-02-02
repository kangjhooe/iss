<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateViolationTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'code' => 'nullable|string|max:50',
            'category' => 'sometimes|in:ringan,sedang,berat',
            'point_weight' => 'nullable|integer|min:0|max:100',
            'default_sanction' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
