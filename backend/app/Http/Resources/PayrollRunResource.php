<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollRunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'period_id' => $this->period_id,
            'batch_key' => $this->batch_key,
            'label' => $this->label,
            'status' => $this->status,
            'employee_filter' => $this->employee_filter,
            'generated_at' => $this->generated_at,
            'finalized_at' => $this->finalized_at,
            'paid_at' => $this->paid_at,
            'notes' => $this->notes,
            'period' => $this->whenLoaded('period', fn () => new PayrollPeriodResource($this->period)),
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'slips_count' => $this->when(isset($this->slips_count), (int) $this->slips_count),
            'total_net' => $this->when(isset($this->total_net), (float) $this->total_net),
            'finance_expense' => $this->whenLoaded('financeExpense', fn () => $this->financeExpense ? [
                'id' => $this->financeExpense->id,
                'amount' => (float) $this->financeExpense->amount,
                'expense_date' => $this->financeExpense->expense_date?->toIso8601String(),
            ] : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
