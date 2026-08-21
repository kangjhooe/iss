<?php

namespace App\Http\Resources;

use App\Support\RegionAddress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PpdbApplicantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ppdb_period_id' => $this->ppdb_period_id,
            'ppdb_channel_id' => $this->ppdb_channel_id,
            'period' => $this->whenLoaded('period', fn () => new PpdbPeriodResource($this->period)),
            'channel' => $this->whenLoaded('channel', fn () => new PpdbChannelResource($this->channel)),
            'registration_number' => $this->registration_number,
            'status' => $this->status,
            'rank' => $this->rank,
            'announcement_at' => $this->announcement_at?->toIso8601String(),
            're_registration_deadline' => $this->re_registration_deadline?->format('Y-m-d'),
            're_registration_confirmed_at' => $this->re_registration_confirmed_at?->toIso8601String(),
            'student_id' => $this->student_id,
            'student' => $this->whenLoaded('student', fn () => ['id' => $this->student->id, 'nis' => $this->student->nis, 'name' => $this->student->name]),
            'result_notes' => $this->result_notes,
            'name' => $this->name,
            'nik' => $this->nik,
            'nisn' => $this->nisn,
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
            'previous_school' => $this->previous_school,
            'previous_school_npsn' => $this->previous_school_npsn,
            'previous_school_address' => $this->previous_school_address,
            'father_name' => $this->father_name,
            'father_phone' => $this->father_phone,
            'mother_name' => $this->mother_name,
            'mother_phone' => $this->mother_phone,
            'guardian_name' => $this->guardian_name,
            'guardian_phone' => $this->guardian_phone,
            'guardian_relation' => $this->guardian_relation,
            'documents_verified' => $this->documents_verified,
            'verification_notes' => $this->verification_notes,
            'document_summary' => $this->when(
                $this->relationLoaded('channel') || $this->relationLoaded('documents'),
                fn () => \App\Support\PpdbDocumentChecklist::summarize(
                    $this->channel,
                    $this->relationLoaded('documents') ? $this->documents : []
                )
            ),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'notes' => $this->notes,
            'payment_status' => $this->payment_status ?? 'unpaid',
            'payment_amount' => $this->payment_amount !== null ? (float) $this->payment_amount : null,
            'payment_type' => $this->payment_type,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'payment_notes' => $this->payment_notes,
            'documents' => $this->whenLoaded('documents', fn () => $this->documents->map(fn ($d) => [
                'id' => $d->id,
                'name' => $d->name,
                'document_key' => $d->document_key,
                'file_name' => $d->file_name,
                'file_size' => $d->file_size,
                'mime_type' => $d->mime_type,
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
