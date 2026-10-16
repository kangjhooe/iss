<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryNeedAnalysisResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'academic_year_id' => $this->academic_year_id,
            'academic_year_label' => $this->academic_year_label,
            'academic_year' => $this->whenLoaded('academicYear', fn () => $this->academicYear ? [
                'id' => $this->academicYear->id,
                'name' => $this->academicYear->name,
                'code' => $this->academicYear->code,
            ] : null),
            'title' => $this->title,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'student_count' => (int) $this->student_count,
            'rombel_count' => (int) $this->rombel_count,
            'shortage_lines' => (int) $this->shortage_lines,
            'high_priority_lines' => (int) $this->high_priority_lines,
            'estimated_budget' => $this->estimated_budget !== null ? (float) $this->estimated_budget : 0,
            'procure_budget' => $this->procure_budget !== null ? (float) $this->procure_budget : 0,
            'repair_budget' => $this->repair_budget !== null ? (float) $this->repair_budget : 0,
            'notes' => $this->notes,
            'finalized_at' => $this->finalized_at?->toIso8601String(),
            'counts_synced_at' => $this->counts_synced_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'lines' => $this->whenLoaded('lines', fn () => $this->lines->map(fn ($line) => [
                'id' => $line->id,
                'section' => $line->section,
                'name' => $line->name,
                'unit' => $line->unit,
                'basis_type' => $line->basis_type,
                'basis_label' => $line->basisLabel(),
                'basis_room_type' => $line->basis_room_type,
                'room_type' => $line->room_type,
                'basis_value' => (int) $line->basis_value,
                'ratio_numerator' => (int) $line->ratio_numerator,
                'ratio_denominator' => (int) $line->ratio_denominator,
                'ratio_label' => $line->ratioLabel(),
                'usable_policy' => $line->usable_policy,
                'usable_policy_label' => $line->usablePolicyLabel(),
                'category_id' => $line->category_id,
                'category_name' => $line->relationLoaded('category') ? $line->category?->name : null,
                'source_item_ids' => array_values(array_map('intval', $line->source_item_ids ?? [])),
                'priority' => $line->priority,
                'priority_label' => $line->priorityLabel(),
                'unit_price' => $line->unit_price !== null ? (float) $line->unit_price : null,
                'unit_repair_price' => $line->unit_repair_price !== null ? (float) $line->unit_repair_price : null,
                'notes' => $line->notes,
                'sort_order' => (int) $line->sort_order,
                'existing_baik' => (int) $line->existing_baik,
                'existing_rusak_ringan' => (int) $line->existing_rusak_ringan,
                'existing_rusak_sedang' => (int) $line->existing_rusak_sedang,
                'existing_rusak_berat' => (int) $line->existing_rusak_berat,
                'existing_habis' => (int) $line->existing_habis,
                'required_qty' => (int) $line->required_qty,
                'usable_qty' => (int) $line->usable_qty,
                'shortage_qty' => (int) $line->shortage_qty,
                'procure_qty' => (int) $line->procure_qty,
                'repair_qty' => (int) $line->repair_qty,
                'surplus_qty' => (int) $line->surplus_qty,
                'estimated_cost' => $line->estimated_cost !== null ? (float) $line->estimated_cost : null,
                'procure_cost' => $line->procure_cost !== null ? (float) $line->procure_cost : null,
                'repair_cost' => $line->repair_cost !== null ? (float) $line->repair_cost : null,
                'gap_label' => $line->gapLabel(),
            ])->values()),
        ];
    }
}
