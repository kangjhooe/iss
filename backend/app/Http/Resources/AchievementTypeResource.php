<?php

namespace App\Http\Resources;

use App\Models\AchievementType;
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
            'level_point_values' => $this->level_point_values,
            'category' => $this->category,
            'purpose' => $this->purpose ?? AchievementType::PURPOSE_AKREDITASI,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
