<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherViolationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'employee_id' => $this->employee_id,
            'piket_incident_id' => $this->piket_incident_id,
            'violation_type_id' => $this->violation_type_id,
            'violation_date' => $this->violation_date?->format('Y-m-d'),
            'point_value' => $this->point_value,
            'notes' => $this->notes,
            'evidence_path' => $this->evidence_path,
            'evidence_url' => $this->evidence_path ? asset('storage/' . $this->evidence_path) : null,
            'status' => $this->status,
            'sanction' => $this->sanction,
            'reported_by' => $this->reported_by,
            'reviewed_by' => $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'review_notes' => $this->review_notes,
            'academic_year_id' => $this->academic_year_id,
            'semester_id' => $this->semester_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'from_piket' => (bool) $this->piket_incident_id,
            'piket_incident' => $this->whenLoaded('piketIncident', fn () => $this->piketIncident ? [
                'id' => $this->piketIncident->id,
                'incident_type' => $this->piketIncident->incident_type,
                'type_label' => $this->piketIncident->type_label,
                'incident_date' => $this->piketIncident->incident_date?->format('Y-m-d'),
                'minutes_late' => $this->piketIncident->minutes_late,
                'description' => $this->piketIncident->description,
            ] : null),
            'employee' => $this->whenLoaded('employee', fn () => [
                'id' => $this->employee->id,
                'name' => $this->employee->name,
                'nip' => $this->employee->nip,
                'type' => $this->employee->type,
                'subject' => $this->employee->subject,
            ]),
            'violation_type' => $this->whenLoaded('violationType', fn () => [
                'id' => $this->violationType->id,
                'name' => $this->violationType->name,
                'code' => $this->violationType->code,
                'point_weight' => $this->violationType->point_weight,
                'category' => $this->violationType->category,
            ]),
            'reporter' => $this->whenLoaded('reporter', fn () => $this->reporter ? [
                'id' => $this->reporter->id,
                'name' => $this->reporter->name,
            ] : null),
            'reviewer' => $this->whenLoaded('reviewer', fn () => $this->reviewer ? [
                'id' => $this->reviewer->id,
                'name' => $this->reviewer->name,
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
        ];
    }
}
