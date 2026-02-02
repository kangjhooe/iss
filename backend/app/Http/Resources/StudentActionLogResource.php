<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentActionLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'point_threshold_id' => $this->point_threshold_id,
            'action_name' => $this->action_name,
            'action_date' => $this->action_date?->format('Y-m-d'),
            'notes' => $this->notes,
            'recorded_by' => $this->recorded_by,
            'created_at' => $this->created_at->toIso8601String(),
            'recorder' => $this->whenLoaded('recorder', fn () => [
                'id' => $this->recorder->id,
                'name' => $this->recorder->name,
            ]),
        ];
    }
}
