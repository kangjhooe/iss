<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeacherPointRewardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'point_min' => 'required|integer|min:0',
            'point_max' => 'required|integer|min:0|gte:point_min',
            'reward_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ];
    }
}
