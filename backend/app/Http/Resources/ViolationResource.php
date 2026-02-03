<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ViolationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'student_id' => $this->student_id,
            'violation_type_id' => $this->violation_type_id,
            'reported_by' => $this->reported_by,
            'violation_date' => $this->violation_date?->format('Y-m-d'),
            'sanction' => $this->sanction,
            'status' => $this->status,
            'description' => $this->description,
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
            'violation_type' => $this->whenLoaded('violationType', fn () => [
                'id' => $this->violationType->id,
                'name' => $this->violationType->name,
                'code' => $this->violationType->code,
                'category' => $this->violationType->category,
                'point_weight' => $this->violationType->point_weight,
                'default_sanction' => $this->violationType->default_sanction,
            ]),
            'reporter' => $this->whenLoaded('reporter', fn () => [
                'id' => $this->reporter->id,
                'name' => $this->reporter->name,
                'email' => $this->reporter->email,
            ]),
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
