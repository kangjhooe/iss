<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentAttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'teaching_journal_id' => $this->teaching_journal_id,
            'student_id' => $this->student_id,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'student' => $this->whenLoaded('student', fn () => $this->student ? [
                'id' => $this->student->id,
                'nis' => $this->student->nis,
                'nisn' => $this->student->nisn,
                'name' => $this->student->name,
                'gender' => $this->student->gender,
            ] : null),
            'teaching_journal' => $this->whenLoaded('teachingJournal', fn () => $this->teachingJournal ? [
                'id' => $this->teachingJournal->id,
                'journal_date' => $this->teachingJournal->journal_date?->format('Y-m-d'),
                'period' => $this->teachingJournal->period,
                'class_id' => $this->teachingJournal->class_id,
                'subject_id' => $this->teachingJournal->subject_id,
                'employee_id' => $this->teachingJournal->employee_id,
            ] : null),
        ];
    }
}
