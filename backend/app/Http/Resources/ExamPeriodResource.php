<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamPeriodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'window_start_at' => $this->window_start_at?->toIso8601String(),
            'window_end_at' => $this->window_end_at?->toIso8601String(),
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'exams_count' => $this->when(isset($this->exams_count), (int) $this->exams_count),
            'exams_ready_count' => $this->when(isset($this->exams_ready_count), (int) $this->exams_ready_count),
            'exams' => ExamResource::collection($this->whenLoaded('exams')),
        ];
    }
}
