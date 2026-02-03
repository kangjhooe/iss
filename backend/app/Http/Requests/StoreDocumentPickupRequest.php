<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentPickupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => 'required|integer|exists:student,id',
            'pickup_date' => 'required|date',
            'taken_ijazah' => 'nullable|boolean',
            'taken_raport' => 'nullable|boolean',
            'taken_skhun' => 'nullable|boolean',
            'nomor_ijazah' => 'nullable|string|max:64',
            'kode_blangko' => 'nullable|string|max:64',
            'dokumen_lainnya' => 'nullable|string|max:1000',
            'foto' => 'required|image|max:5120',
            'received_by' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:65535',
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'Siswa (alumni) wajib dipilih.',
            'student_id.exists' => 'Siswa tidak ditemukan.',
            'pickup_date.required' => 'Tanggal pengambilan wajib diisi.',
            'foto.required' => 'Foto serah terima ijazah wajib diisi (ambil dari kamera).',
            'foto.image' => 'Berkas harus berupa gambar (JPG, PNG, dll).',
            'foto.max' => 'Ukuran foto maksimal 5 MB.',
        ];
    }
}
