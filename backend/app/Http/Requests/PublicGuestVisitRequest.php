<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublicGuestVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'npsn' => 'required|string|max:20',
            'nama_tamu' => 'required|string|max:255',
            'no_identitas' => 'nullable|string|max:64',
            'instansi_asal' => 'nullable|string|max:255',
            'no_telepon' => 'nullable|string|max:32',
            'tujuan_kunjungan' => 'required|string|max:255',
            'orang_ditemui' => 'nullable|string|max:255',
            'catatan' => 'nullable|string|max:2000',
            'foto' => 'nullable|file|image|max:5120',
            // Honeypot: bot biasanya mengisi. Harus kosong.
            'website' => 'nullable|string|max:0',
        ];
    }

    /**
     * Configure the validator. Reject if honeypot filled.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->filled('website')) {
                $validator->errors()->add('website', 'Invalid.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'npsn.required' => 'NPSN sekolah wajib.',
            'nama_tamu.required' => 'Nama tamu wajib diisi.',
            'tujuan_kunjungan.required' => 'Tujuan kunjungan wajib diisi.',
        ];
    }
}
