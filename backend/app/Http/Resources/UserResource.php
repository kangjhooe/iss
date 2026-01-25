<?php

namespace App\Http\Resources;

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
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'is_locked' => $this->isLocked(),
            'failed_login_attempts' => $this->failed_login_attempts ?? 0,
            'institution' => $this->whenLoaded('institution', function () {
                return new InstitutionResource($this->institution);
            }),
            'student_profile' => $this->whenLoaded('studentProfile', function () {
                return [
                    'id' => $this->studentProfile->id,
                    'nis' => $this->studentProfile->nis,
                    'nisn' => $this->studentProfile->nisn,
                    'class' => $this->studentProfile->class,
                    'status' => $this->studentProfile->status,
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
