<?php

namespace App\Http\Resources;

use App\Services\StructuralDutySync;
use App\Support\RegionAddress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
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
            'village' => $this->village,
            'sub_district' => $this->sub_district,
            'district' => $this->district,
            'province' => $this->province,
            'postal_code' => $this->postal_code,
            'wilayah_province_code' => $this->wilayah_province_code,
            'wilayah_regency_code' => $this->wilayah_regency_code,
            'wilayah_district_code' => $this->wilayah_district_code,
            'wilayah_village_code' => $this->wilayah_village_code,
            'full_address' => RegionAddress::format($this->resource),
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
            'certification_status' => $this->certification_status,
            'certification_date' => $this->certification_date?->format('Y-m-d'),
            'teacher_registration_number' => $this->teacher_registration_number,
            'certification_number' => $this->certification_number,
            'certification_issuing_authority' => $this->certification_issuing_authority,
            'affiliation' => $this->getAffiliation($request),
            'current_assignment' => $this->getCurrentAssignment($request),
            'has_user_account' => $this->hasUserAccount(),
            'is_teacher' => $this->isTeacher(),
            'user_account' => $this->whenLoaded('userAccount', function () {
                return [
                    'id' => $this->userAccount->id,
                    'name' => $this->userAccount->name,
                    'email' => $this->userAccount->email,
                    'role' => $this->userAccount->role,
                    'permissions' => $this->userAccount->permissions()->pluck('key')->values(),
                ];
            }),
            'educations' => $this->whenLoaded('educations', function () {
                return $this->educations->map(function ($education) {
                    return [
                        'id' => $education->id,
                        'level' => $education->level,
                        'school_name' => $education->school_name,
                        'major' => $education->major,
                        'graduation_year' => $education->graduation_year,
                        'certificate_number' => $education->certificate_number,
                        'city' => $education->city,
                        'notes' => $education->notes,
                        'order' => $education->order,
                    ];
                });
            }),
            'documents' => $this->whenLoaded('documents', function () {
                return $this->documents->map(function ($document) {
                    return [
                        'id' => $document->id,
                        'name' => $document->name,
                        'file_name' => $document->file_name,
                        'file_size' => $document->file_size,
                        'file_size_human' => $document->file_size_human,
                        'mime_type' => $document->mime_type,
                        'description' => $document->description,
                        'file_url' => asset('storage/' . $document->file_path),
                        'created_at' => $document->created_at?->toISOString(),
                    ];
                });
            }),
            'additional_duties' => $this->whenLoaded('additionalDuties', function () {
                $sync = app(StructuralDutySync::class);

                return $this->additionalDuties
                    ->filter(function ($d) {
                        $ended = $d->pivot->ended_at ?? null;
                        if (!$ended) {
                            return true;
                        }

                        return (string) $ended > now()->toDateString();
                    })
                    ->map(fn ($d) => [
                        'id' => $d->id,
                        'key' => $d->key,
                        'label' => $d->label,
                        'is_structural' => $sync->isKey($d->key),
                    ])
                    ->values();
            }),
            'program_keahlians' => $this->whenLoaded('programKeahlians', function () {
                return $this->programKeahlians->map(fn ($p) => [
                    'id' => $p->id,
                    'code' => $p->code,
                    'name' => $p->name,
                ]);
            }),
            'program_keahlian_ids' => $this->whenLoaded('programKeahlians', function () {
                return $this->programKeahlians->pluck('id')->map(fn ($id) => (int) $id)->values();
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
