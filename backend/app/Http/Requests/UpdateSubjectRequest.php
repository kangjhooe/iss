<?php

namespace App\Http\Requests;

use App\Support\InstitutionContext;
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
        $user = $this->user();
        $institutionId = $subject?->institution_id
            ?? ($user ? InstitutionContext::resolveForUser($user, $this, $this->get('institution_id')) : null);
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
