<?php

namespace App\Http\Resources;

use App\Support\InstitutionContext;
use App\Support\KaprogAccess;
use App\Support\ReportAccess;
use App\Support\TeacherMenuContext;
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
        $homeroomClasses = [];
        $teachingAssignments = [];
        $supervisedExtracurriculars = [];
        $managedLabs = [];
        $bkScopeMode = 'all';
        try {
            $homeroomClassIds = WaliKelasAccess::homeroomClassIds($this->resource)->all();
            $bkScopeMode = WaliKelasAccess::mustScopeBkToHomeroom($this->resource) ? 'homeroom' : 'all';
        } catch (\Throwable $e) {
            // ignore — jangan gagalkan payload user
        }

        try {
            if (in_array($this->role, ['teacher', 'staff'], true)) {
                $homeroomClasses = TeacherMenuContext::homeroomClasses($this->resource, $request)->all();
                $teachingAssignments = TeacherMenuContext::teachingAssignments($this->resource, $request)->all();
                $supervisedExtracurriculars = TeacherMenuContext::supervisedExtracurriculars($this->resource, $request)->all();
                $managedLabs = TeacherMenuContext::managedLabs($this->resource, $request)->all();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        $activeInstitutionId = $request->attributes->get('current_institution_id');
        $activeInstitutionId = $activeInstitutionId !== null && $activeInstitutionId !== ''
            ? (int) $activeInstitutionId
            : null;

        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'name' => $this->name,
            'email' => $this->email,
            'login_nik' => $this->login_nik,
            'must_change_password' => (bool) $this->must_change_password,
            'role' => $this->role,
            'permissions' => $this->when(
                $this->relationLoaded('permissions'),
                function () use ($request, $activeInstitutionId) {
                    try {
                        return collect(
                            InstitutionContext::effectivePermissionKeys(
                                $this->resource,
                                $activeInstitutionId,
                                $request
                            )
                        )->values();
                    } catch (\Exception $e) {
                        return [];
                    }
                },
                []
            ),
            'homeroom_class_ids' => $homeroomClassIds,
            'homeroom_classes' => $homeroomClasses,
            'teaching_assignments' => $teachingAssignments,
            'supervised_extracurriculars' => $supervisedExtracurriculars,
            'managed_labs' => $managedLabs,
            'kaprog_program_ids' => (function () {
                try {
                    if (! KaprogAccess::isKaprog($this->resource)) {
                        return [];
                    }

                    return KaprogAccess::programIds($this->resource);
                } catch (\Throwable $e) {
                    return [];
                }
            })(),
            'is_kaprog' => (function () {
                try {
                    return KaprogAccess::isKaprog($this->resource);
                } catch (\Throwable $e) {
                    return false;
                }
            })(),
            'is_kepala_sekolah' => (function () {
                try {
                    return ReportAccess::isKepalaSekolah($this->resource);
                } catch (\Throwable $e) {
                    return false;
                }
            })(),
            'bk_scope' => $bkScopeMode,
            'is_lab_responsible' => (function () use ($activeInstitutionId) {
                try {
                    return $this->resource->isLabResponsible($activeInstitutionId);
                } catch (\Throwable $e) {
                    return false;
                }
            })(),
            'is_extracurricular_supervisor' => (function () use ($activeInstitutionId) {
                try {
                    return $this->resource->isExtracurricularSupervisor($activeInstitutionId);
                } catch (\Throwable $e) {
                    return false;
                }
            })(),
            'is_piket_scheduled' => (function () use ($activeInstitutionId) {
                try {
                    return \App\Support\PiketAccess::isScheduled(
                        $this->resource,
                        $activeInstitutionId
                    );
                } catch (\Throwable $e) {
                    return false;
                }
            })(),
            'is_piket_on_duty' => (function () use ($activeInstitutionId) {
                try {
                    return \App\Support\PiketAccess::isOnDutyToday(
                        $this->resource,
                        $activeInstitutionId
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
                    'nik' => $sp->nik,
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
            'linked_children_count' => $this->when(
                $this->role === 'parent',
                function () {
                    try {
                        return count(\App\Support\ParentAccess::linkedStudentIds($this->resource));
                    } catch (\Throwable $e) {
                        return 0;
                    }
                }
            ),
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
