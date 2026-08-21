<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamQuestionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $snap = is_array($this->snapshot) ? $this->snapshot : [];
        $live = $this->relationLoaded('questionBank') ? $this->questionBank : null;
        $bank = $live?->bankSoal;

        $body = $snap['body'] ?? $live?->body ?? '';
        $plain = trim(html_entity_decode(strip_tags((string) $body), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return [
            'id' => $this->id,
            'question_bank_id' => $this->question_bank_id,
            'sort_order' => (int) $this->sort_order,
            'has_snapshot' => $snap !== [] && ! empty($snap['type']),
            'type' => $snap['type'] ?? $live?->type,
            'body_preview' => mb_substr($plain, 0, 120),
            'weight' => (float) ($snap['weight'] ?? $live?->weight ?? 0),
            'bank_soal_id' => $snap['bank_soal_id'] ?? $live?->bank_soal_id,
            'bank_code' => $snap['bank_code'] ?? $bank?->code,
            'bank_name' => $snap['bank_name'] ?? $bank?->name,
            'bank_grade' => $snap['bank_grade'] ?? $bank?->grade,
        ];
    }
}
