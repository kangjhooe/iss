<?php

namespace App\Http\Requests;

use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExamRequest extends FormRequest
{
    use ResolvesInstitution;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $institutionId = $this->resolveInstitutionId($this);
        $exam = $this->route('exam');
        return [
            'code' => [
                'sometimes',
                'string',
                'max:64',
                Rule::unique('exams', 'code')->where('institution_id', $institutionId)->ignore($exam?->id),
            ],
            'subject_id' => 'sometimes|exists:subjects,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:5000',
            'duration_minutes' => 'sometimes|integer|min:1|max:600',
            'shuffle_questions' => 'boolean',
            'shuffle_options' => 'boolean',
            'start_type' => 'sometimes|in:scheduled,manual',
            'scheduled_start_at' => 'nullable|date',
            'scheduled_end_at' => 'nullable|date|after_or_equal:scheduled_start_at',
        ];
    }
}
