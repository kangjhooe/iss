<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherResource extends JsonResource
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
            'type' => $this->type,
            'nik' => $this->nik,
            'institution' => $this->whenLoaded('institution', function () {
                return [
                    'id' => $this->institution->id,
                    'name' => $this->institution->name,
                    'npsn' => $this->institution->npsn,
                ];
            }),
            'nip' => $this->nip,
            'nuptk' => $this->nuptk,
            'name' => $this->name,
            'gender' => $this->gender,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'birth_place' => $this->birth_place,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'religion' => $this->religion,
            'employment_status' => $this->employment_status,
            'education_level' => $this->education_level,
            'major' => $this->major,
            'subject' => $this->subject,
            'status' => $this->status,
            'join_date' => $this->join_date?->format('Y-m-d'),
            'notes' => $this->notes,
            'affiliation' => $this->getAffiliation($request),
            'current_assignment' => $this->getCurrentAssignment($request),
            'has_user_account' => $this->hasUserAccount(),
            'user_account' => $this->whenLoaded('userAccount', function () {
                return [
                    'id' => $this->userAccount->id,
                    'name' => $this->userAccount->name,
                    'email' => $this->userAccount->email,
                    'role' => $this->userAccount->role,
                ];
            }),
            'assignments' => $this->whenLoaded('assignments', function () {
                return EmployeeInstitutionAssignmentResource::collection($this->assignments);
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function getAffiliation(Request $request): ?string
    {
        $currentInstitutionId = $request->attributes->get('current_institution_id')
            ?? $request->get('institution_id')
            ?? $request->user()?->institution_id;

        if (!$currentInstitutionId) {
            return null;
        }

        return $this->institution_id == $currentInstitutionId ? 'induk' : 'non_induk';
    }

    private function getCurrentAssignment(Request $request): ?array
    {
        if (!$this->relationLoaded('assignments')) {
            return null;
        }

        $currentInstitutionId = $request->attributes->get('current_institution_id')
            ?? $request->get('institution_id')
            ?? $request->user()?->institution_id;

        if (!$currentInstitutionId) {
            return null;
        }

        $assignment = $this->assignments
            ->where('institution_id', (int) $currentInstitutionId)
            ->where('status', 'approved')
            ->first();

        if (!$assignment) {
            return null;
        }

        return (new EmployeeInstitutionAssignmentResource($assignment))->toArray($request);
    }
}
