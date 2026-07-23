<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherMutationResource extends JsonResource
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
            'origin_institution_id' => $this->origin_institution_id,
            'origin_npsn' => $this->origin_npsn,
            'origin_school_name' => $this->origin_school_name,
            'target_institution_id' => $this->target_institution_id,
            'target_npsn' => $this->target_npsn,
            'target_school_name' => $this->target_school_name,
            'employee_id' => $this->employee_id,
            'employee_nuptk' => $this->employee_nuptk,
            'employee_nip' => $this->employee_nip,
            'employee_gender' => $this->employee_gender,
            'initiated_by' => $this->initiated_by,
            'status' => $this->status,
            'rejection_reason' => $this->rejection_reason,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'notes' => $this->notes,
            'cancel_reason' => $this->cancel_reason,
            'cancel_requested_at' => $this->cancel_requested_at?->toIso8601String(),
            'cancel_rejection_reason' => $this->cancel_rejection_reason,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'can_request_cancel' => $request->user() ? $this->canRequestCancelBy($request->user()) : false,
            'can_decide_cancel' => $request->user() ? $this->canDecideCancelBy($request->user()) : false,
            // Relations
            'origin_institution' => $this->whenLoaded('originInstitution', fn () => $this->originInstitution ? [
                'id' => $this->originInstitution->id,
                'name' => $this->originInstitution->name,
                'npsn' => $this->originInstitution->npsn,
                'level' => $this->originInstitution->level,
            ] : null),
            'is_external_origin' => $this->origin_institution_id === null,
            'target_institution' => $this->whenLoaded('targetInstitution', fn () => $this->targetInstitution ? [
                'id' => $this->targetInstitution->id,
                'name' => $this->targetInstitution->name,
                'npsn' => $this->targetInstitution->npsn,
                'level' => $this->targetInstitution->level,
            ] : null),
            'is_external_target' => $this->target_institution_id === null,
            'employee' => $this->whenLoaded('employee', fn () => [
                'id' => $this->employee->id,
                'nuptk' => $this->employee->nuptk,
                'nip' => $this->employee->nip,
                'name' => $this->employee->name,
                'gender' => $this->employee->gender,
                'status' => $this->employee->status,
                'email' => $this->employee->email,
            ]),
            'requester' => $this->whenLoaded('requester', fn () => [
                'id' => $this->requester->id,
                'name' => $this->requester->name,
                'email' => $this->requester->email,
            ]),
            'approver' => $this->whenLoaded('approver', fn () => [
                'id' => $this->approver->id,
                'name' => $this->approver->name,
            ]),
            'cancel_requester' => $this->whenLoaded('cancelRequester', fn () => $this->cancelRequester ? [
                'id' => $this->cancelRequester->id,
                'name' => $this->cancelRequester->name,
            ] : null),
        ];
    }
}
