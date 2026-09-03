<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WaliNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'class_id' => $this->class_id,
            'student_id' => $this->student_id,
            'author_user_id' => $this->author_user_id,
            'body' => $this->body,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'author' => $this->whenLoaded('author', fn () => [
                'id' => $this->author->id,
                'name' => $this->author->name,
            ]),
            'class' => $this->whenLoaded('schoolClass', fn () => [
                'id' => $this->schoolClass->id,
                'name' => $this->schoolClass->name,
            ]),
            'is_current_class' => (int) $this->class_id === (int) ($request->route('classId') ?? 0),
            'can_edit' => $request->user()
                ? (int) $request->user()->id === (int) $this->author_user_id
                : false,
        ];
    }
}
