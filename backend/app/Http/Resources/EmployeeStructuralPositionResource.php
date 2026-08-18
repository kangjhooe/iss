<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeStructuralPositionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'employee_id' => $this->employee_id,
            'structural_position_id' => $this->structural_position_id,
            'employee_decree_id' => $this->employee_decree_id,
            'started_at' => $this->started_at?->format('Y-m-d'),
            'ended_at' => $this->ended_at?->format('Y-m-d'),
            'decree_number' => $this->decree_number,
            'notes' => $this->notes,
            'is_active' => $this->is_active,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'employee' => $this->whenLoaded('employee', fn () => [
                'id' => $this->employee->id,
                'name' => $this->employee->name,
                'nip' => $this->employee->nip,
                'nuptk' => $this->employee->nuptk,
                'type' => $this->employee->type,
                'email' => $this->employee->email,
            ]),
            'position' => $this->whenLoaded('position', fn () => $this->position ? [
                'id' => $this->position->id,
                'key' => $this->position->key,
                'label' => $this->position->label,
                'description' => $this->position->description,
            ] : null),
            'decree' => $this->whenLoaded('decree', fn () => $this->decree ? [
                'id' => $this->decree->id,
                'number' => $this->decree->number,
                'title' => $this->decree->title,
                'decree_date' => $this->decree->decree_date?->format('Y-m-d'),
            ] : null),
            'creator' => $this->whenLoaded('creator', fn () => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ] : null),
        ];
    }
}
