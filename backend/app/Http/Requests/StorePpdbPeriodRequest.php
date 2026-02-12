<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePpdbPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => 'required|exists:academic_years,id',
            'name' => 'required|string|max:255',
            'level' => 'nullable|string|max:20',
            'open_date' => 'required|date',
            'close_date' => 'required|date|after_or_equal:open_date',
            'status' => 'nullable|in:draft,open,closed,finished',
            'description' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'academic_year_id.required' => 'Tahun ajaran wajib dipilih.',
            'name.required' => 'Nama periode wajib diisi.',
            'open_date.required' => 'Tanggal buka wajib diisi.',
            'close_date.required' => 'Tanggal tutup wajib diisi.',
            'close_date.after_or_equal' => 'Tanggal tutup harus sama atau setelah tanggal buka.',
        ];
    }
}
