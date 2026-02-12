<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PpdbPeriodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'academic_year_id' => $this->academic_year_id,
            'academic_year' => $this->whenLoaded('academicYear', fn () => [
                'id' => $this->academicYear->id,
                'code' => $this->academicYear->code,
                'name' => $this->academicYear->name,
            ]),
            'name' => $this->name,
            'level' => $this->level,
            'open_date' => $this->open_date?->format('Y-m-d'),
            'close_date' => $this->close_date?->format('Y-m-d'),
            're_registration_deadline' => $this->re_registration_deadline?->format('Y-m-d'),
            'status' => $this->status,
            'description' => $this->description,
            'applicants_count' => $this->when(isset($this->applicants_count), $this->applicants_count),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
