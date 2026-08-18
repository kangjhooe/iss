<?php

namespace App\Http\Requests;

use App\Models\EmployeeDecree;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeDecreeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employee,id'],
            'decree_type' => ['required', 'string', Rule::in(array_keys(EmployeeDecree::TYPES))],
            'number' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'decree_date' => ['required', 'date'],
            'effective_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:effective_date'],
            'description' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'file' => ['nullable', 'file', 'mimes:pdf', 'max:2048'],
        ];
    }
}
