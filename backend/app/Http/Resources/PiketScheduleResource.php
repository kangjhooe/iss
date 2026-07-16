<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PiketScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'academic_year_id' => $this->academic_year_id,
            'semester_id' => $this->semester_id,
            'employee_id' => $this->employee_id,
            'day_of_week' => $this->day_of_week,
            'day_name' => $this->day_name,
            'shift' => $this->shift,
            'shift_label' => $this->shift_label,
            'start_time' => $this->start_time?->format('H:i'),
            'end_time' => $this->end_time?->format('H:i'),
            'notes' => $this->notes,
            'employee' => $this->when($this->relationLoaded('employee') && $this->employee, [
                'id' => $this->employee->id,
                'name' => $this->employee->name,
                'nip' => $this->employee->nip,
            ]),
        ];
    }
}
