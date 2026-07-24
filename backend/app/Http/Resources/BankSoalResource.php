<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BankSoalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'created_by_user_id' => $this->created_by_user_id,
            'code' => $this->code,
            'name' => $this->name,
            'subject_id' => $this->subject_id,
            'grade' => $this->grade,
            'keterangan' => $this->keterangan,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'subject' => $this->whenLoaded('subject', fn () => ['id' => $this->subject->id, 'name' => $this->subject->name]),
            'created_by_user' => $this->whenLoaded('createdByUser', fn () => $this->createdByUser ? ['id' => $this->createdByUser->id, 'name' => $this->createdByUser->name, 'email' => $this->createdByUser->email] : null),
            'questions_count' => $this->when(isset($this->questions_count), fn () => $this->questions_count),
            'questions_by_type' => $this->when(
                isset($this->pg_count) || isset($this->isian_count) || isset($this->uraian_count),
                fn () => [
                    'pg' => (int) ($this->pg_count ?? 0),
                    'pg_kompleks' => (int) ($this->pg_kompleks_count ?? 0),
                    'matching' => (int) ($this->matching_count ?? 0),
                    'isian' => (int) ($this->isian_count ?? 0),
                    'uraian' => (int) ($this->uraian_count ?? 0),
                ]
            ),
        ];
    }
}
