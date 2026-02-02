<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AchievementTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'name' => $this->name,
            'code' => $this->code,
            'point_value' => $this->point_value,
            'category' => $this->category,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
