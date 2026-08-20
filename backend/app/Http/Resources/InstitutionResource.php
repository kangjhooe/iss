<?php

namespace App\Http\Resources;

use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class InstitutionResource extends JsonResource
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
            'name' => $this->name,
            'foundation_name' => $this->foundation_name,
            'npsn' => $this->npsn,
            'nss' => $this->nss,
            'level' => $this->level,
            'type' => $this->type,
            'address' => $this->address,
            'village' => $this->village,
            'sub_district' => $this->sub_district,
            'district' => $this->district,
            'province' => $this->province,
            'province_code' => $this->province_code,
            'district_code' => $this->district_code,
            'postal_code' => $this->postal_code,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'principal_name' => $this->principal_name,
            'principal_nip' => $this->principal_nip,
            'description' => $this->description,
            'vision' => $this->vision,
            'mission' => $this->mission,
            'logo' => $this->logo ? asset('storage/' . $this->logo) : null,
            'cover_image' => $this->cover_image ? asset('storage/' . $this->cover_image) : null,
            'is_active' => $this->is_active,
            'is_demo' => (bool) ($this->is_demo ?? false),
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'location_radius' => $this->location_radius !== null ? (int) $this->location_radius : null,
            'teacher_appreciation_leaderboard_mode' => $this->teacher_appreciation_leaderboard_mode
                ?: Institution::TEACHER_APPRECIATION_LEADERBOARD_GURU_ONLY,
            'admission_label' => $this->resolvedAdmissionLabel(),
            'nis_numbering' => $this->nis_numbering,
            'users_count' => $this->whenLoaded('users', function () {
                return $this->users->count();
            }),
            'students_count' => $this->whenLoaded('students', function () {
                return $this->students->count();
            }),
            'teachers_count' => $this->whenLoaded('teachers', function () {
                return $this->teachers->count();
            }),
            'users' => $this->whenLoaded('users', function () {
                return $this->users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                    ];
                });
            }),
            'students' => $this->whenLoaded('students', function () {
                return $this->students->map(function ($student) {
                    return [
                        'id' => $student->id,
                        'name' => $student->name,
                        'nis' => $student->nis,
                        'nisn' => $student->nisn,
                        'class' => $student->class,
                        'status' => $student->status,
                    ];
                });
            }),
            'teachers' => $this->whenLoaded('teachers', function () {
                return $this->teachers->map(function ($teacher) {
                    return [
                        'id' => $teacher->id,
                        'name' => $teacher->name,
                        'nip' => $teacher->nip,
                        'nuptk' => $teacher->nuptk,
                        'status' => $teacher->status,
                    ];
                });
            }),
            'change_requests' => $this->whenLoaded('changeRequests', function () {
                return $this->changeRequests->map(function ($request) {
                    return [
                        'id' => $request->id,
                        'field_name' => $request->field_name,
                        'old_value' => $request->old_value,
                        'new_value' => $request->new_value,
                        'status' => $request->status,
                        'created_at' => $request->created_at?->toISOString(),
                    ];
                });
            }),
            'active_academic_year_id' => $this->active_academic_year_id,
            'active_semester_id' => $this->active_semester_id,
            'active_academic_year' => new AcademicYearResource($this->whenLoaded('activeAcademicYear')),
            'active_semester' => new SemesterResource($this->whenLoaded('activeSemester')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
