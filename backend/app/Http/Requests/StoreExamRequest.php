<?php

namespace App\Http\Requests;

use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExamRequest extends FormRequest
{
    use ResolvesInstitution;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $institutionId = $this->resolveInstitutionId($this);
        return [
            'code' => [
                'required',
                'string',
                'max:64',
                Rule::unique('exams', 'code')->where('institution_id', $institutionId),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'subject_id' => 'nullable|exists:subjects,id',
            'duration_minutes' => 'nullable|integer|min:1|max:600',
            'shuffle_questions' => 'boolean',
            'shuffle_options' => 'boolean',
            'start_type' => 'required|in:scheduled,manual',
            'scheduled_start_at' => 'nullable|date',
            'scheduled_end_at' => 'nullable|date|after_or_equal:scheduled_start_at',
        ];
    }
}
