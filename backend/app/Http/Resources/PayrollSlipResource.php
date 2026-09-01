<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollSlipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'run_id' => $this->run_id,
            'period_id' => $this->period_id,
            'employee_id' => $this->employee_id,
            'gross' => (float) $this->gross,
            'total_deductions' => (float) $this->total_deductions,
            'net' => (float) $this->net,
            'attendance_snapshot' => $this->attendance_snapshot,
            'status' => $this->status,
            'notes' => $this->notes,
            'employee' => $this->whenLoaded('employee', fn () => [
                'id' => $this->employee->id,
                'nip' => $this->employee->nip,
                'name' => $this->employee->name,
                'type' => $this->employee->type,
                'employment_status' => $this->employee->employment_status,
            ]),
            'period' => $this->whenLoaded('period', fn () => new PayrollPeriodResource($this->period)),
            'run' => $this->whenLoaded('run', fn () => new PayrollRunResource($this->run)),
            'lines' => PayrollSlipLineResource::collection($this->whenLoaded('lines')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
