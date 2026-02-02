<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ViolationTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'name' => $this->name,
            'code' => $this->code,
            'category' => $this->category,
            'point_weight' => $this->point_weight,
            'default_sanction' => $this->default_sanction,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
