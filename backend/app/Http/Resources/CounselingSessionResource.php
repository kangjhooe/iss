<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CounselingSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'student_id' => $this->student_id,
            'counselor_id' => $this->counselor_id,
            'counseling_type_id' => $this->counseling_type_id,
            'session_date' => $this->session_date?->format('Y-m-d'),
            'status' => $this->status,
            'summary' => $this->summary,
            'follow_up_notes' => $this->follow_up_notes,
            'academic_year_id' => $this->academic_year_id,
            'semester_id' => $this->semester_id,
            'class_id' => $this->class_id,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'student' => $this->whenLoaded('student', fn () => [
                'id' => $this->student->id,
                'name' => $this->student->name,
                'nis' => $this->student->nis,
                'nisn' => $this->student->nisn,
                'class' => $this->student->relationLoaded('class') && $this->student->getRelation('class') instanceof \App\Models\SchoolClass
                    ? ['id' => $this->student->getRelation('class')->id, 'name' => $this->student->getRelation('class')->name]
                    : (($this->student->getRawOriginal('class') ?? null) ? ['name' => $this->student->getRawOriginal('class')] : null),
            ]),
            'counselor' => $this->whenLoaded('counselor', fn () => [
                'id' => $this->counselor->id,
                'name' => $this->counselor->name,
                'email' => $this->counselor->email,
            ]),
            'counseling_type' => $this->whenLoaded('counselingType', fn () => $this->counselingType ? [
                'id' => $this->counselingType->id,
                'name' => $this->counselingType->name,
                'code' => $this->counselingType->code,
                'description' => $this->counselingType->description,
            ] : null),
            'academic_year' => $this->whenLoaded('academicYear', fn () => $this->academicYear ? [
                'id' => $this->academicYear->id,
                'name' => $this->academicYear->name,
                'code' => $this->academicYear->code,
            ] : null),
            'semester' => $this->whenLoaded('semester', fn () => $this->semester ? [
                'id' => $this->semester->id,
                'name' => $this->semester->name,
            ] : null),
            'school_class' => $this->whenLoaded('schoolClass', fn () => $this->schoolClass ? [
                'id' => $this->schoolClass->id,
                'name' => $this->schoolClass->name,
            ] : null),
        ];
    }
}
