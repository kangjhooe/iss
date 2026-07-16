<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExtracurricularRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'institution_id' => 'sometimes|exists:institution,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'supervisor_employee_id' => 'nullable|exists:employee,id',
            'capacity' => 'nullable|integer|min:1',
            'status' => 'sometimes|string|in:Aktif,Nonaktif',
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
            'name.required' => 'Nama ekstrakurikuler wajib diisi.',
            'supervisor_employee_id.exists' => 'Guru penanggung jawab tidak ditemukan.',
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
        if ($this->boolean('is_outdoor')) {
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
