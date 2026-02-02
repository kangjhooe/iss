<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
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
            'institution' => $this->whenLoaded('institution', function () {
                return [
                    'id' => $this->institution->id,
                    'name' => $this->institution->name,
                    'npsn' => $this->institution->npsn,
                ];
            }),
            'nik' => $this->nik,
            'nis' => $this->nis,
            'nisn' => $this->nisn,
            'name' => $this->name,
            'gender' => $this->gender,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'birth_place' => $this->birth_place,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'religion' => $this->religion,
            'no_kk' => $this->no_kk,
            'aspiration' => $this->aspiration,
            'hobby' => $this->hobby,
            'disability' => $this->disability,
            'height' => $this->height,
            'weight' => $this->weight,
            'previous_school' => $this->previous_school,
            'residence_type' => $this->residence_type,
            'class' => $this->getRawOriginal('class'), // string column; use class_detail for relation
            'class_id' => $this->class_id,
            'class_detail' => $this->whenLoaded('class', function () {
                return [
                    'id' => $this->class->id,
                    'name' => $this->class->name,
                    'grade' => $this->class->grade,
                ];
            }),
            'academic_year' => $this->academic_year,
            'academic_year_id' => $this->academic_year_id,
            'academic_year_detail' => $this->whenLoaded('academicYear', function () {
                return [
                    'id' => $this->academicYear->id,
                    'name' => $this->academicYear->name,
                    'code' => $this->academicYear->code,
                ];
            }),
            'semester_id' => $this->semester_id,
            'semester' => $this->whenLoaded('semester', function () {
                return [
                    'id' => $this->semester->id,
                    'name' => $this->semester->name,
                    'academic_year_id' => $this->semester->academic_year_id,
                ];
            }),
            'status' => $this->status,
            'father_name' => $this->father_name,
            'father_status' => $this->father_status,
            'father_nik' => $this->father_nik,
            'father_birth_place' => $this->father_birth_place,
            'father_birth_date' => $this->father_birth_date?->format('Y-m-d'),
            'father_education' => $this->father_education,
            'father_occupation' => $this->father_occupation,
            'father_income' => $this->father_income,
            'mother_name' => $this->mother_name,
            'mother_status' => $this->mother_status,
            'mother_nik' => $this->mother_nik,
            'mother_birth_place' => $this->mother_birth_place,
            'mother_birth_date' => $this->mother_birth_date?->format('Y-m-d'),
            'mother_education' => $this->mother_education,
            'mother_occupation' => $this->mother_occupation,
            'mother_income' => $this->mother_income,
            'guardian_name' => $this->guardian_name,
            'guardian_phone' => $this->guardian_phone,
            'guardian_type' => $this->guardian_type,
            'guardian_status' => $this->guardian_status,
            'guardian_nik' => $this->guardian_nik,
            'guardian_birth_place' => $this->guardian_birth_place,
            'guardian_birth_date' => $this->guardian_birth_date?->format('Y-m-d'),
            'guardian_education' => $this->guardian_education,
            'guardian_occupation' => $this->guardian_occupation,
            'guardian_income' => $this->guardian_income,
            'notes' => $this->notes,
            'documents' => $this->whenLoaded('documents', function () {
                return $this->documents->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'name' => $doc->name,
                        'file_name' => $doc->file_name,
                        'file_size' => $doc->file_size,
                        'file_size_human' => $doc->file_size_human,
                        'mime_type' => $doc->mime_type,
                        'description' => $doc->description,
                        'created_at' => $doc->created_at?->toISOString(),
                    ];
                });
            }),
            'has_user_account' => $this->hasUserAccount(),
            'user_account' => $this->whenLoaded('userAccount', function () {
                return [
                    'id' => $this->userAccount->id,
                    'name' => $this->userAccount->name,
                    'email' => $this->userAccount->email,
                    'role' => $this->userAccount->role,
                ];
            }),
            'class_history' => $this->whenLoaded('classHistory', function () {
                return $this->classHistory->map(function ($history) {
                    return [
                        'id' => $history->id,
                        'class_id' => $history->class_id,
                        'academic_year_id' => $history->academic_year_id,
                        'semester_id' => $history->semester_id,
                        'academic_year' => $history->academic_year,
                        'start_date' => $history->start_date?->format('Y-m-d'),
                        'end_date' => $history->end_date?->format('Y-m-d'),
                        'status' => $history->status,
                        'notes' => $history->notes,
                    ];
                });
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
