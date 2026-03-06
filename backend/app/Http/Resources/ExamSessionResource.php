<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'exam_id' => $this->exam_id,
            'name' => $this->name,
            'scheduled_start_at' => $this->scheduled_start_at?->toIso8601String(),
            'scheduled_end_at' => $this->scheduled_end_at?->toIso8601String(),
            'started_at' => $this->started_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'status' => $this->status,
            'entry_pin' => $this->when($this->status === 'started', $this->entry_pin),
            'entry_pin_updated_at' => $this->when($this->status === 'started', $this->entry_pin_updated_at?->toIso8601String()),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'exam' => $this->whenLoaded('exam', fn () => new ExamResource($this->exam)),
            'participants_count' => $this->when(isset($this->participants_count), $this->participants_count),
            'participants' => ExamParticipantResource::collection($this->whenLoaded('participants')),
        ];
    }
}
