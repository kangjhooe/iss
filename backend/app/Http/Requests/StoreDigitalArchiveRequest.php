<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDigitalArchiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:65535',
            'digital_archive_category_id' => 'nullable|exists:digital_archive_categories,id',
            'document_date' => 'nullable|date',
            'reference_number' => 'nullable|string|max:80',
            'file' => 'required|file|max:51200', // 50MB
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul dokumen wajib diisi.',
            'title.max' => 'Judul maksimal 255 karakter.',
            'file.required' => 'File dokumen wajib diunggah.',
            'file.file' => 'Berkas tidak valid.',
            'file.max' => 'Ukuran file maksimal 50 MB.',
        ];
    }
}
