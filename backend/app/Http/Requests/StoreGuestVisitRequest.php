<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGuestVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_tamu' => 'required|string|max:255',
            'no_identitas' => 'nullable|string|max:64',
            'instansi_asal' => 'nullable|string|max:255',
            'no_telepon' => 'nullable|string|max:32',
            'tujuan_kunjungan' => 'required|string|max:255',
            'orang_ditemui' => 'nullable|string|max:255',
            'waktu_masuk' => 'nullable|date',
            'foto' => 'required|image|max:5120', // 5MB
            'catatan' => 'nullable|string|max:65535',
        ];
    }

    public function messages(): array
    {
        return [
            'nama_tamu.required' => 'Nama tamu wajib diisi.',
            'tujuan_kunjungan.required' => 'Tujuan kunjungan wajib diisi.',
            'foto.required' => 'Foto tamu wajib diambil/diunggah.',
            'foto.image' => 'Berkas harus berupa gambar (JPG, PNG, dll).',
            'foto.max' => 'Ukuran foto maksimal 5 MB.',
        ];
    }
}
