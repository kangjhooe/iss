<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCorrespondenceRequest extends FormRequest
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
        $rules = [
            'type' => 'required|in:masuk,keluar,internal',
            'letter_type_code' => 'required|string|size:2|in:01,02,03,04,05,06,07,08,09,10,11,12,13,14,15,16',
            'subject' => 'required|string|max:255',
            'date' => 'required|date',
            'priority' => 'nullable|in:biasa,penting,sangat_penting',
            'status' => 'nullable|in:draft,pending,approved,sent,archived',
            'category_id' => 'nullable|exists:correspondence_categories,id',
            'description' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf|max:5120', // Max 5MB
        ];

        // Rules for surat masuk
        if ($this->type === 'masuk') {
            $rules['reference_number'] = 'nullable|string|max:255';
            $rules['from'] = 'required|string|max:255';
            $rules['received_date'] = 'nullable|date';
            $rules['letter_number'] = 'nullable|string|max:255'; // Manual untuk surat masuk
        }

        // Rules for surat keluar
        if ($this->type === 'keluar') {
            $rules['to'] = 'required|string|max:255';
            $rules['letter_number'] = 'nullable|string|max:255'; // Auto-generate, bisa di-override manual
        }

        // Rules for surat internal
        if ($this->type === 'internal') {
            $rules['to'] = 'nullable|string|max:255';
            $rules['letter_number'] = 'nullable|string|max:255'; // Auto-generate seperti surat keluar
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Tipe surat wajib diisi',
            'type.in' => 'Tipe surat harus masuk, keluar, atau internal',
            'letter_type_code.required' => 'Jenis surat wajib diisi',
            'letter_type_code.size' => 'Kode jenis surat harus 2 digit',
            'letter_type_code.in' => 'Jenis surat tidak valid',
            'subject.required' => 'Perihal wajib diisi',
            'date.required' => 'Tanggal surat wajib diisi',
            'from.required' => 'Pengirim wajib diisi untuk surat masuk',
            'to.required' => 'Penerima wajib diisi untuk surat keluar',
            'file.mimes' => 'File harus berformat PDF',
            'file.max' => 'Ukuran file maksimal 5MB',
            'category_id.exists' => 'Kategori tidak ditemukan',
        ];
    }
}
