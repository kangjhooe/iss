<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'subject_catalog_id' => $this->subject_catalog_id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'from_catalog' => (bool) $this->subject_catalog_id,
            'catalog' => $this->whenLoaded('catalog', function () {
                if (! $this->catalog) {
                    return null;
                }

                return [
                    'id' => $this->catalog->id,
                    'code' => $this->catalog->code,
                    'name' => $this->catalog->name,
                    'jenjang' => $this->catalog->jenjang,
                ];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
