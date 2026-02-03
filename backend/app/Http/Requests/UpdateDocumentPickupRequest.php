<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentPickupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pickup_date' => 'sometimes|date',
            'taken_ijazah' => 'nullable|boolean',
            'taken_raport' => 'nullable|boolean',
            'taken_skhun' => 'nullable|boolean',
            'nomor_ijazah' => 'nullable|string|max:64',
            'kode_blangko' => 'nullable|string|max:64',
            'dokumen_lainnya' => 'nullable|string|max:1000',
            'foto' => 'nullable|image|max:5120',
            'received_by' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:65535',
        ];
    }

    public function messages(): array
    {
        return [
            'foto.image' => 'Berkas harus berupa gambar (JPG, PNG, dll).',
            'foto.max' => 'Ukuran foto maksimal 5 MB.',
        ];
    }
}
