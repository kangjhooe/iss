<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePiketScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|integer|exists:employee,id',
            'day_of_week' => 'nullable|integer|between:1,6',
            'days_of_week' => 'nullable|array|min:1',
            'days_of_week.*' => 'integer|between:1,6|distinct',
            'shift' => 'required|in:pagi,siang,full',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'academic_year_id' => 'nullable|integer|exists:academic_years,id',
            'semester_id' => 'nullable|integer|exists:semesters,id',
            'notes' => 'nullable|string|max:2000',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $hasSingle = $this->filled('day_of_week');
            $hasMulti = is_array($this->input('days_of_week')) && count($this->input('days_of_week')) > 0;
            if (!$hasSingle && !$hasMulti) {
                $validator->errors()->add('days_of_week', 'Pilih minimal satu hari piket.');
            }
        });
    }
}
