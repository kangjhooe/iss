<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionStimulusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'bank_soal_id' => $this->bank_soal_id,
            'subject_id' => $this->subject_id,
            'title' => $this->title,
            'content' => $this->content,
            'type' => $this->type,
            'attachment_path' => $this->attachment_path,
            'questions_count' => $this->when(isset($this->questions_count), fn () => $this->questions_count),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
