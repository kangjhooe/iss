<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuestVisitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'nama_tamu' => $this->nama_tamu,
            'no_identitas' => $this->no_identitas,
            'instansi_asal' => $this->instansi_asal,
            'no_telepon' => $this->no_telepon,
            'tujuan_kunjungan' => $this->tujuan_kunjungan,
            'orang_ditemui' => $this->orang_ditemui,
            'waktu_masuk' => $this->waktu_masuk?->toIso8601String(),
            'waktu_keluar' => $this->waktu_keluar?->toIso8601String(),
            'foto_path' => $this->foto_path,
            'foto_url' => $this->foto_url,
            'catatan' => $this->catatan,
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
