<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePointThresholdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'point_min' => 'required|integer',
            'point_max' => 'required|integer|gte:point_min',
            'action_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }
}
