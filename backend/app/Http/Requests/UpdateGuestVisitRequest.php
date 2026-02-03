<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGuestVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_tamu' => 'sometimes|string|max:255',
            'no_identitas' => 'nullable|string|max:64',
            'instansi_asal' => 'nullable|string|max:255',
            'no_telepon' => 'nullable|string|max:32',
            'tujuan_kunjungan' => 'sometimes|string|max:255',
            'orang_ditemui' => 'nullable|string|max:255',
            'waktu_masuk' => 'nullable|date',
            'waktu_keluar' => 'nullable|date',
            'foto' => 'nullable|image|max:5120',
            'catatan' => 'nullable|string|max:65535',
        ];
    }
}
