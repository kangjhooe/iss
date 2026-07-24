<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePpdbPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => 'sometimes|exists:academic_years,id',
            'name' => 'sometimes|string|max:255',
            'level' => 'nullable|string|max:20',
            'open_date' => 'sometimes|date',
            'close_date' => 'sometimes|date|after_or_equal:open_date',
            're_registration_deadline' => 'nullable|date',
            'status' => 'nullable|in:draft,open,closed,finished',
            'description' => 'nullable|string',
            'registration_fee' => 'nullable|numeric|min:0',
            're_registration_fee' => 'nullable|numeric|min:0',
        ];
    }
}
