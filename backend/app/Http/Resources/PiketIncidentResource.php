<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PiketIncidentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'incident_date' => $this->incident_date?->format('Y-m-d'),
            'incident_type' => $this->incident_type,
            'type_label' => $this->type_label,
            'period' => $this->period,
            'class_id' => $this->class_id,
            'subject_id' => $this->subject_id,
            'employee_id' => $this->employee_id,
            'student_id' => $this->student_id,
            'lesson_schedule_id' => $this->lesson_schedule_id,
            'piket_log_id' => $this->piket_log_id,
            'detected_at' => $this->detected_at?->format('H:i'),
            'minutes_late' => $this->minutes_late,
            'description' => $this->description,
            'source' => $this->source,
            'status' => $this->status,
            'status_label' => \App\Models\PiketIncident::STATUSES[$this->status] ?? $this->status,
            'school_class' => $this->when($this->relationLoaded('schoolClass') && $this->schoolClass, [
                'id' => $this->schoolClass->id,
                'name' => $this->schoolClass->name,
                'grade' => $this->schoolClass->grade,
            ]),
            'subject' => $this->when($this->relationLoaded('subject') && $this->subject, [
                'id' => $this->subject->id,
                'name' => $this->subject->name,
            ]),
            'employee' => $this->when($this->relationLoaded('employee') && $this->employee, [
                'id' => $this->employee->id,
                'name' => $this->employee->name,
                'nip' => $this->employee->nip,
            ]),
            'student' => $this->when($this->relationLoaded('student') && $this->student, [
                'id' => $this->student->id,
                'name' => $this->student->name,
                'nis' => $this->student->nis,
            ]),
            'violation' => $this->when($this->relationLoaded('violation') && $this->violation, [
                'id' => $this->violation->id,
                'status' => $this->violation->status,
                'violation_type' => $this->violation->relationLoaded('violationType') && $this->violation->violationType
                    ? [
                        'id' => $this->violation->violationType->id,
                        'name' => $this->violation->violationType->name,
                        'point_weight' => $this->violation->violationType->point_weight,
                    ]
                    : null,
            ]),
            'teacher_violation' => $this->when($this->relationLoaded('teacherViolation') && $this->teacherViolation, [
                'id' => $this->teacherViolation->id,
                'status' => $this->teacherViolation->status,
                'point_value' => $this->teacherViolation->point_value,
                'violation_type' => $this->teacherViolation->relationLoaded('violationType') && $this->teacherViolation->violationType
                    ? [
                        'id' => $this->teacherViolation->violationType->id,
                        'name' => $this->teacherViolation->violationType->name,
                        'point_weight' => $this->teacherViolation->violationType->point_weight,
                        'code' => $this->teacherViolation->violationType->code,
                    ]
                    : null,
            ]),
            'can_propose_violation' => $this->when(
                $this->relationLoaded('violation'),
                fn () => (bool) $this->student_id
                    && (
                        !$this->violation
                        || $this->violation->status === \App\Models\Violation::STATUS_DITOLAK
                    )
            ),
            'can_propose_teacher_violation' => $this->when(
                $this->relationLoaded('teacherViolation'),
                fn () => $this->isTeacherRelated()
                    && (bool) $this->employee_id
                    && (
                        !$this->teacherViolation
                        || $this->teacherViolation->status === \App\Models\TeacherViolation::STATUS_REJECTED
                    )
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
        ];
    }
}
