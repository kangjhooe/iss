<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PiketLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'duty_date' => $this->duty_date?->format('Y-m-d'),
            'employee_id' => $this->employee_id,
            'piket_schedule_id' => $this->piket_schedule_id,
            'summary' => $this->summary,
            'handoff_notes' => $this->handoff_notes,
            'status' => $this->status,
            'status_label' => \App\Models\PiketLog::STATUSES[$this->status] ?? $this->status,
            'created_by' => $this->created_by,
            'reviewed_by' => $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'review_notes' => $this->review_notes,
            'employee' => $this->when($this->relationLoaded('employee') && $this->employee, [
                'id' => $this->employee->id,
                'name' => $this->employee->name,
                'nip' => $this->employee->nip,
            ]),
            'incidents_count' => $this->when(isset($this->incidents_count), $this->incidents_count),
            'incidents' => PiketIncidentResource::collection($this->whenLoaded('incidents')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
