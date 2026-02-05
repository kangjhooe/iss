<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAcademicCalendarEventRequest extends FormRequest
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
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester_id' => [
                'nullable',
                Rule::exists('semesters', 'id')->where('academic_year_id', $this->input('academic_year_id')),
            ],
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_type' => 'required|in:Ujian,Libur,Kegiatan,Other',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_all_day' => 'boolean',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'reminder_days_before' => 'nullable|array',
            'reminder_days_before.*' => 'integer|min:1|max:365',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'status' => 'nullable|in:Aktif,Dibatalkan,Draft',
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
            'academic_year_id.required' => 'Tahun ajaran wajib diisi',
            'academic_year_id.exists' => 'Tahun ajaran tidak ditemukan',
            'semester_id.exists' => 'Semester tidak ditemukan atau tidak termasuk dalam tahun ajaran yang dipilih',
            'title.required' => 'Judul event wajib diisi',
            'title.max' => 'Judul event maksimal 255 karakter',
            'event_type.required' => 'Jenis event wajib diisi',
            'event_type.in' => 'Jenis event harus salah satu dari: Ujian, Libur, Kegiatan, Other',
            'start_date.required' => 'Tanggal mulai wajib diisi',
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

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set institution_id from authenticated user
        if ($this->user() && $this->user()->institution_id) {
            $this->merge([
                'institution_id' => $this->user()->institution_id,
                'created_by' => $this->user()->id,
            ]);
        }

        // Set default is_all_day if not provided
        if (!$this->has('is_all_day')) {
            $this->merge(['is_all_day' => true]);
        }

        // Set default end_date to start_date if not provided
        if ($this->has('start_date') && !$this->has('end_date')) {
            $this->merge(['end_date' => $this->start_date]);
        }
    }
}
