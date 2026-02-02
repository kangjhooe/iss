<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $subject = $this->route('subject');
        $institutionId = $this->user()?->institution_id;
        return [
            'code' => [
                'sometimes',
                'string',
                'max:50',
                $subject && $institutionId
                    ? Rule::unique('subjects', 'code')->where('institution_id', $institutionId)->ignore($subject->id)
                    : 'unique:subjects,code',
            ],
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
