<?php

namespace App\Http\Resources;

use App\Support\WaliKelasAccess;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $homeroomClassIds = [];
        $bkScopeMode = 'all';
        try {
            $homeroomClassIds = WaliKelasAccess::homeroomClassIds($this->resource)->all();
            $bkScopeMode = WaliKelasAccess::mustScopeBkToHomeroom($this->resource) ? 'homeroom' : 'all';
        } catch (\Throwable $e) {
            // ignore — jangan gagalkan payload user
        }

        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'permissions' => $this->when(
                $this->relationLoaded('permissions'),
                function () {
                    try {
                        return $this->permissions->pluck('key')->values();
                    } catch (\Exception $e) {
                        return [];
                    }
                },
                []
            ),
            'homeroom_class_ids' => $homeroomClassIds,
            'bk_scope' => $bkScopeMode,
            'is_lab_responsible' => (function () {
                try {
                    return $this->resource->isLabResponsible();
                } catch (\Throwable $e) {
                    return false;
                }
            })(),
            'is_extracurricular_supervisor' => (function () {
                try {
                    return $this->resource->isExtracurricularSupervisor();
                } catch (\Throwable $e) {
                    return false;
                }
            })(),
            'is_piket_scheduled' => (function () use ($request) {
                try {
                    $institutionId = $request->attributes->get('current_institution_id');
                    return \App\Support\PiketAccess::isScheduled(
                        $this->resource,
                        $institutionId ? (int) $institutionId : null
                    );
                } catch (\Throwable $e) {
                    return false;
                }
            })(),
            'is_piket_on_duty' => (function () use ($request) {
                try {
                    $institutionId = $request->attributes->get('current_institution_id');
                    return \App\Support\PiketAccess::isOnDutyToday(
                        $this->resource,
                        $institutionId ? (int) $institutionId : null
                    );
                } catch (\Throwable $e) {
                    return false;
                }
            })(),
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'is_active' => $this->is_active !== false,
            'is_locked' => $this->isLocked(),
            'failed_login_attempts' => $this->failed_login_attempts ?? 0,
            'institution' => $this->when(
                $this->relationLoaded('institution') && $this->institution
                || ($this->relationLoaded('studentProfile') && $this->studentProfile?->relationLoaded('institution') && $this->studentProfile?->institution),
                function () {
                    $inst = $this->institution ?? $this->studentProfile?->institution;
                    return $inst ? new InstitutionResource($inst) : null;
                }
            ),
            'student_profile' => $this->whenLoaded('studentProfile', function () {
                $sp = $this->studentProfile;
                return [
                    'id' => $sp->id,
                    'nis' => $sp->nis,
                    'nisn' => $sp->nisn,
                    'class' => $sp->class,
                    'class_id' => $sp->class_id,
                    'class_name' => $sp->relationLoaded('schoolClass') && $sp->schoolClass
                        ? $sp->schoolClass->name
                        : null,
                    'status' => $sp->status,
                ];
            }),
            'teacher_profile' => $this->whenLoaded('teacherProfile', function () {
                return [
                    'id' => $this->teacherProfile->id,
                    'nip' => $this->teacherProfile->nip,
                    'nuptk' => $this->teacherProfile->nuptk,
                    'status' => $this->teacherProfile->status,
                    'employment_status' => $this->teacherProfile->employment_status,
                ];
            }),
            'change_requests_count' => $this->whenLoaded('changeRequests', function () {
                return $this->changeRequests->count();
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
