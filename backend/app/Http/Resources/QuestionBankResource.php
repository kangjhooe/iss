<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionBankResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'bank_soal_id' => $this->bank_soal_id,
            'subject_id' => $this->subject_id,
            'stimulus_id' => $this->stimulus_id,
            'type' => $this->type,
            'body' => $this->body,
            'weight' => (float) $this->weight,
            'sort_order' => (int) ($this->sort_order ?? 0),
            'key_answer' => $this->when($request->user() && !$request->routeIs('exam-attempt.*'), $this->key_answer),
            'key_answer_aliases' => $this->when(
                $request->user() && !$request->routeIs('exam-attempt.*') && $this->type === 'isian',
                fn () => $this->key_answer_aliases ?? []
            ),
            'matching_data' => $this->when($this->type === 'matching', $this->matching_data),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'exams_count' => $this->when(isset($this->exam_questions_count), fn () => (int) $this->exam_questions_count),
            'bank' => $this->whenLoaded('bankSoal', fn () => $this->bankSoal ? [
                'id' => $this->bankSoal->id,
                'code' => $this->bankSoal->code,
                'name' => $this->bankSoal->name,
                'grade' => $this->bankSoal->grade,
            ] : null),
            'stimulus' => $this->whenLoaded('stimulus', fn () => new QuestionStimulusResource($this->stimulus)),
            'options' => $this->whenLoaded('options', fn () => $this->options->map(fn ($o) => [
                'id' => $o->id,
                'option_key' => $o->option_key,
                'body' => $o->body,
                'is_correct' => $this->when($request->user() && !$request->routeIs('exam-attempt.*'), (bool) $o->is_correct),
                'option_weight' => $this->when($request->user() && !$request->routeIs('exam-attempt.*'), (float) $o->option_weight),
                'sort_order' => $o->sort_order,
            ])),
        ];
    }
}
