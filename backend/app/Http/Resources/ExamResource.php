<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'subject_id' => $this->subject_id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'duration_minutes' => $this->duration_minutes,
            'shuffle_questions' => (bool) $this->shuffle_questions,
            'shuffle_options' => (bool) $this->shuffle_options,
            'start_type' => $this->start_type,
            'scheduled_start_at' => $this->scheduled_start_at?->toIso8601String(),
            'scheduled_end_at' => $this->scheduled_end_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'subject' => $this->whenLoaded('subject', fn () => $this->subject ? [
                'id' => $this->subject->id,
                'name' => $this->subject->name,
                'code' => $this->subject->code,
            ] : null),
            'participants_count' => $this->whenLoaded('sessions', fn () => $this->sessions->sum('participants_count')),
            'sessions' => ExamSessionResource::collection($this->whenLoaded('sessions')),
            'exam_questions' => $this->whenLoaded('examQuestions'),
        ];
    }
}
