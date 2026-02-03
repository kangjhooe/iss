<?php

namespace App\Http\Requests;

use App\Models\AlumniDestination;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAlumniDestinationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'destination_type' => [
                'sometimes',
                'string',
                Rule::in(array_keys(AlumniDestination::DESTINATION_TYPES)),
            ],
            'destination_name' => 'sometimes|string|max:255',
            'program_or_position' => 'nullable|string|max:255',
            'year_entered' => 'nullable|integer|min:1990|max:' . (date('Y') + 2),
            'notes' => 'nullable|string|max:65535',
        ];
    }
}
