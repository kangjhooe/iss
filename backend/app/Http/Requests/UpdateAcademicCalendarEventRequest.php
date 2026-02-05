<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAcademicCalendarEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'academic_year_id' => 'sometimes|exists:academic_years,id',
            'semester_id' => [
                'nullable',
                Rule::exists('semesters', 'id')->when($this->filled('academic_year_id'), fn ($rule) => $rule->where('academic_year_id', $this->input('academic_year_id'))),
            ],
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'event_type' => 'sometimes|in:Ujian,Libur,Kegiatan,Other',
            'start_date' => 'sometimes|date',
            'end_date' => [
                'nullable',
                'date',
                Rule::when($this->filled('start_date'), 'after_or_equal:start_date'),
            ],
            'is_all_day' => 'sometimes|boolean',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => [
                'nullable',
                'date_format:H:i',
                Rule::when($this->filled('start_time'), 'after:start_time'),
            ],
            'reminder_days_before' => 'nullable|array',
            'reminder_days_before.*' => 'integer|min:1|max:365',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'status' => 'sometimes|in:Aktif,Dibatalkan,Draft',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'academic_year_id.exists' => 'Tahun ajaran tidak ditemukan',
            'semester_id.exists' => 'Semester tidak ditemukan atau tidak termasuk dalam tahun ajaran yang dipilih',
            'title.max' => 'Judul event maksimal 255 karakter',
            'event_type.in' => 'Jenis event harus salah satu dari: Ujian, Libur, Kegiatan, Other',
            'start_date.date' => 'Tanggal mulai harus berupa tanggal yang valid',
            'end_date.date' => 'Tanggal akhir harus berupa tanggal yang valid',
            'end_date.after_or_equal' => 'Tanggal akhir harus setelah atau sama dengan tanggal mulai',
            'start_time.date_format' => 'Format waktu mulai tidak valid (gunakan format HH:mm)',
            'end_time.date_format' => 'Format waktu akhir tidak valid (gunakan format HH:mm)',
            'end_time.after' => 'Waktu akhir harus setelah waktu mulai',
            'reminder_days_before.array' => 'Reminder days harus berupa array',
            'reminder_days_before.*.integer' => 'Setiap reminder day harus berupa angka',
            'reminder_days_before.*.min' => 'Reminder day minimal 1 hari',
            'reminder_days_before.*.max' => 'Reminder day maksimal 365 hari',
            'color.regex' => 'Format warna tidak valid (gunakan format hex: #RRGGBB)',
            'status.in' => 'Status harus salah satu dari: Aktif, Dibatalkan, Draft',
        ];
    }
}
