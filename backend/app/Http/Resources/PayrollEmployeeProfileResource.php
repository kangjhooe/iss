<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollEmployeeProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'employee_id' => $this->employee_id,
            'base_salary' => (float) $this->base_salary,
            'payment_method' => $this->payment_method,
            'bank_name' => $this->bank_name,
            'bank_account' => $this->bank_account,
            'effective_from' => $this->effective_from?->toDateString(),
            'notes' => $this->notes,
            'employee' => $this->whenLoaded('employee', fn () => [
                'id' => $this->employee->id,
                'nip' => $this->employee->nip,
                'name' => $this->employee->name,
                'type' => $this->employee->type,
                'employment_status' => $this->employee->employment_status,
                'status' => $this->employee->status,
            ]),
            'components' => $this->when($this->relationLoaded('employee_components'), function () {
                return collect($this->employee_components)->map(fn ($row) => [
                    'id' => $row->id,
                    'component_id' => $row->component_id,
                    'amount' => $row->amount !== null ? (float) $row->amount : null,
                    'is_active' => (bool) $row->is_active,
                    'component' => $row->relationLoaded('component') ? [
                        'id' => $row->component->id,
                        'code' => $row->component->code,
                        'name' => $row->component->name,
                        'type' => $row->component->type,
                    ] : null,
                ]);
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
