<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinanceExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'category' => $this->category,
            'title' => $this->title,
            'amount' => (float) $this->amount,
            'expense_date' => $this->expense_date?->toIso8601String(),
            'method' => $this->method,
            'reference' => $this->reference,
            'notes' => $this->notes,
            'source' => $this->source,
            'payroll_run_id' => $this->payroll_run_id,
            'payroll_run' => $this->whenLoaded('payrollRun', fn () => [
                'id' => $this->payrollRun->id,
                'label' => $this->payrollRun->label,
                'status' => $this->payrollRun->status,
                'period' => $this->payrollRun->relationLoaded('period') && $this->payrollRun->period ? [
                    'id' => $this->payrollRun->period->id,
                    'label' => $this->payrollRun->period->label,
                ] : null,
            ]),
            'recorded_by' => $this->recorded_by,
            'recorder' => $this->whenLoaded('recorder', fn () => $this->recorder ? [
                'id' => $this->recorder->id,
                'name' => $this->recorder->name,
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
