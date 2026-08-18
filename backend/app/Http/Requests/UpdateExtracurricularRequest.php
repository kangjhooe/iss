<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExtracurricularRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'supervisor_employee_id' => 'nullable|exists:employee,id',
            'capacity' => 'nullable|integer|min:1',
            'kkm' => 'nullable|numeric|min:0|max:100',
            'status' => 'sometimes|string|in:Aktif,Nonaktif',
            'is_pramuka' => 'nullable|boolean',
            'days_of_week' => 'nullable|array',
            'days_of_week.*' => 'integer|min:1|max:6',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'room_id' => 'nullable|exists:room,id',
            'is_outdoor' => 'nullable|boolean',
            'location_note' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'end_time.after' => 'Jam selesai harus setelah jam mulai.',
            'days_of_week.*.min' => 'Hari tidak valid.',
            'days_of_week.*.max' => 'Hari tidak valid.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        foreach (['start_time', 'end_time'] as $key) {
            if ($this->filled($key)) {
                $merge[$key] = substr((string) $this->input($key), 0, 5);
            }
        }
        if ($this->has('is_outdoor') && $this->boolean('is_outdoor')) {
            $merge['room_id'] = null;
            $merge['is_outdoor'] = true;
        } elseif ($this->filled('room_id')) {
            $merge['is_outdoor'] = false;
            $merge['location_note'] = null;
        }
        if ($merge) {
            $this->merge($merge);
        }
    }
}
