<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentPickupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'student_id' => $this->student_id,
            'student' => $this->whenLoaded('student', function () {
                return [
                    'id' => $this->student->id,
                    'name' => $this->student->name,
                    'nis' => $this->student->nis,
                    'nisn' => $this->student->nisn,
                    'graduation_year' => $this->student->graduation_year,
                    'class_detail' => $this->student->relationLoaded('class') && $this->student->class
                        ? ['id' => $this->student->class->id, 'name' => $this->student->class->name]
                        : null,
                ];
            }),
            'pickup_date' => $this->pickup_date?->format('Y-m-d\TH:i'),
            'taken_ijazah' => (bool) $this->taken_ijazah,
            'taken_raport' => (bool) $this->taken_raport,
            'taken_skhun' => (bool) $this->taken_skhun,
            'nomor_ijazah' => $this->nomor_ijazah,
            'kode_blangko' => $this->kode_blangko,
            'dokumen_lainnya' => $this->dokumen_lainnya,
            'photo_path' => $this->photo_path,
            'photo_url' => $this->foto_url,
            'received_by' => $this->received_by,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                ];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
