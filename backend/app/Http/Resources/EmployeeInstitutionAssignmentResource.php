<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeInstitutionAssignmentResource extends JsonResource
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
            'employee_id' => $this->employee_id,
            'employee' => $this->whenLoaded('employee', function () {
                return [
                    'id' => $this->employee->id,
                    'name' => $this->employee->name,
                    'nik' => $this->employee->nik,
                    'institution_id' => $this->employee->institution_id,
                    'institution' => $this->employee->relationLoaded('institution') && $this->employee->institution ? [
                        'id' => $this->employee->institution->id,
                        'name' => $this->employee->institution->name,
                    ] : null,
                ];
            }),
            'institution_id' => $this->institution_id,
            'institution' => $this->whenLoaded('institution', function () {
                return [
                    'id' => $this->institution->id,
                    'name' => $this->institution->name,
                    'npsn' => $this->institution->npsn,
                ];
            }),
            'assignment_type' => $this->assignment_type,
            'status' => $this->status,
            'subject' => $this->subject,
            'assignment_title' => $this->assignment_title,
            'assignment_notes' => $this->assignment_notes,
            'requested_by' => $this->requested_by,
            'requester' => $this->whenLoaded('requester', function () {
                return [
                    'id' => $this->requester->id,
                    'name' => $this->requester->name,
                    'email' => $this->requester->email,
                ];
            }),
            'approved_by' => $this->approved_by,
            'approver' => $this->whenLoaded('approver', function () {
                return [
                    'id' => $this->approver->id,
                    'name' => $this->approver->name,
                    'email' => $this->approver->email,
                ];
            }),
            'approved_at' => $this->approved_at?->toISOString(),
            'rejection_reason' => $this->rejection_reason,
            'started_at' => $this->started_at?->format('Y-m-d'),
            'ended_at' => $this->ended_at?->format('Y-m-d'),
            'ended_reason' => $this->ended_reason,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
