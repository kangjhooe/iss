<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'institution' => $this->whenLoaded('institution', function () {
                return [
                    'id' => $this->institution->id,
                    'name' => $this->institution->name,
                    'level' => $this->institution->level,
                ];
            }),
            'room_id' => $this->room_id,
            'room' => $this->whenLoaded('room', function () {
                return [
                    'id' => $this->room->id,
                    'name' => $this->room->name,
                    'code' => $this->room->code,
                ];
            }),
            'teacher_id' => $this->teacher_id,
            'teacher' => $this->whenLoaded('teacher', function () {
                return [
                    'id' => $this->teacher->id,
                    'name' => $this->teacher->name,
                    'nip' => $this->teacher->nip,
                ];
            }),
            'code' => $this->code,
            'name' => $this->name,
            'grade' => $this->grade,
            'academic_year_id' => $this->academic_year_id,
            'academic_year' => $this->whenLoaded('academicYear', function () {
                return $this->academicYear->code ?? $this->academic_year;
            }, $this->academic_year),
            'semester_id' => $this->semester_id,
            'semester' => $this->whenLoaded('semester', function () {
                return [
                    'id' => $this->semester->id,
                    'name' => $this->semester->name,
                    'academic_year_id' => $this->semester->academic_year_id,
                ];
            }),
            'capacity' => $this->capacity,
            'status' => $this->status,
            'description' => $this->description,
            'students_count' => $this->when(isset($this->students_count), $this->students_count, function () {
                return $this->whenLoaded('students', fn() => $this->students->count());
            }),
            'available_capacity' => $this->when(isset($this->available_capacity), $this->available_capacity),
            'students' => $this->whenLoaded('students', function () {
                return \App\Http\Resources\StudentResource::collection($this->students);
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
