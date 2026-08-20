<?php

namespace App\Http\Resources;

use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExtracurricularStudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $student = $this->relationLoaded('student') ? $this->student : null;
        $classModel = null;
        if ($student && $student->relationLoaded('class')) {
            $related = $student->getRelation('class');
            if ($related instanceof SchoolClass) {
                $classModel = $related;
            }
        }

        return [
            'id' => $this->id,
            'extracurricular_id' => $this->extracurricular_id,
            'student_id' => $this->student_id,
            'student' => $student ? [
                'id' => $student->id,
                'name' => $student->name,
                'nis' => $student->nis,
                'nisn' => $student->nisn,
                'class_id' => $student->class_id,
                'class' => $classModel ? [
                    'id' => $classModel->id,
                    'name' => $classModel->name,
                    'grade' => $classModel->grade !== null ? (int) $classModel->grade : null,
                ] : null,
            ] : null,
            'academic_year_id' => $this->academic_year_id,
            'extracurricular' => $this->whenLoaded('extracurricular', fn () => $this->extracurricular ? [
                'id' => $this->extracurricular->id,
                'name' => $this->extracurricular->name,
                'kkm' => $this->extracurricular->kkm !== null ? (float) $this->extracurricular->kkm : 75.0,
                'supervisor' => $this->extracurricular->relationLoaded('supervisor') && $this->extracurricular->supervisor
                    ? [
                        'id' => $this->extracurricular->supervisor->id,
                        'name' => $this->extracurricular->supervisor->name,
                    ]
                    : null,
            ] : null),
            'semester' => $this->whenLoaded('semester', fn () => $this->semester ? [
                'id' => $this->semester->id,
                'name' => $this->semester->name,
            ] : null),
            'semester_id' => $this->semester_id,
            'joined_at' => $this->joined_at?->format('Y-m-d'),
            'left_at' => $this->left_at?->format('Y-m-d'),
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
