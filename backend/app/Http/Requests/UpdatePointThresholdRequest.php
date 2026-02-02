<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePointThresholdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'point_min' => 'sometimes|integer',
            'point_max' => 'sometimes|integer|gte:point_min',
            'action_name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
