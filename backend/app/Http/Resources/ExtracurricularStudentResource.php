<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExtracurricularStudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'extracurricular_id' => $this->extracurricular_id,
            'student_id' => $this->student_id,
            'student' => $this->whenLoaded('student', function () {
                return [
                    'id' => $this->student->id,
                    'name' => $this->student->name,
                    'nis' => $this->student->nis,
                    'nisn' => $this->student->nisn,
                    'class_id' => $this->student->class_id,
                    'class' => $this->student->relationLoaded('class') && $this->student->class
                        ? ['id' => $this->student->class->id, 'name' => $this->student->class->name]
                        : null,
                ];
            }),
            'academic_year_id' => $this->academic_year_id,
            'semester' => $this->whenLoaded('semester', fn () => $this->semester ? ['id' => $this->semester->id, 'name' => $this->semester->name] : null),
            'semester_id' => $this->semester_id,
            'joined_at' => $this->joined_at?->format('Y-m-d'),
            'left_at' => $this->left_at?->format('Y-m-d'),
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
