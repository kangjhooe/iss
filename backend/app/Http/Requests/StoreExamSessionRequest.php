<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExamSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        if ($this->has('scheduled_start_at') && $this->scheduled_start_at === '') {
            $merge['scheduled_start_at'] = null;
        }
        if ($this->has('scheduled_end_at') && $this->scheduled_end_at === '') {
            $merge['scheduled_end_at'] = null;
        }
        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'exam_id' => 'required|exists:exams,id',
            'name' => 'required|string|max:255',
            'scheduled_start_at' => 'nullable|date',
            'scheduled_end_at' => 'nullable|date|after_or_equal:scheduled_start_at',
        ];
    }

    public function messages(): array
    {
        return [
            'exam_id.required' => 'Ujian wajib dipilih.',
            'exam_id.exists' => 'Ujian tidak ditemukan.',
            'name.required' => 'Nama sesi wajib diisi.',
            'name.max' => 'Nama sesi maksimal 255 karakter.',
            'scheduled_end_at.after_or_equal' => 'Waktu selesai harus sama atau setelah waktu mulai.',
        ];
    }
}
