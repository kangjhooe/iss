<?php

namespace App\Services;

use App\Models\ExamAnswer;
use App\Models\QuestionBank;

class ExamQuestionSnapshot
{
    /**
     * Capture a frozen copy of a bank question for an exam package.
     *
     * @return array<string, mixed>
     */
    public static function capture(QuestionBank $question): array
    {
        $question->loadMissing(['options', 'stimulus', 'bankSoal']);

        return [
            'question_bank_id' => $question->id,
            'bank_soal_id' => $question->bank_soal_id,
            'bank_code' => $question->bankSoal?->code,
            'bank_name' => $question->bankSoal?->name,
            'bank_grade' => $question->bankSoal?->grade,
            'subject_id' => $question->subject_id,
            'type' => $question->type,
            'body' => $question->body,
            'weight' => (float) $question->weight,
            'key_answer' => $question->key_answer,
            'key_answer_aliases' => $question->key_answer_aliases,
            'matching_data' => $question->matching_data,
            'stimulus' => $question->stimulus ? [
                'id' => $question->stimulus->id,
                'title' => $question->stimulus->title,
                'content' => $question->stimulus->content,
                'type' => $question->stimulus->type,
            ] : null,
            'options' => $question->options->map(fn ($o) => [
                'id' => $o->id,
                'option_key' => $o->option_key,
                'body' => $o->body,
                'is_correct' => (bool) $o->is_correct,
                'option_weight' => (float) $o->option_weight,
                'sort_order' => $o->sort_order,
            ])->values()->all(),
            'snapshotted_at' => now()->toIso8601String(),
        ];
    }

    public static function hasPayload(?array $snapshot): bool
    {
        return is_array($snapshot) && $snapshot !== [] && ! empty($snapshot['type']);
    }

    /**
     * @return list<string>
     */
    public static function acceptedIsianAnswers(array $snapshot): array
    {
        $keys = [];
        if (! empty($snapshot['key_answer']) && trim((string) $snapshot['key_answer']) !== '') {
            $keys[] = trim(mb_strtolower((string) $snapshot['key_answer']));
        }
        foreach ($snapshot['key_answer_aliases'] ?? [] as $alias) {
            $t = trim(mb_strtolower((string) $alias));
            if ($t !== '') {
                $keys[] = $t;
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * Student-facing question payload (keys stripped).
     *
     * @param  array<string, mixed>  $snapshot
     * @param  array<string, mixed>|null  $saved
     * @return array<string, mixed>
     */
    public static function toAttemptPayload(array $snapshot, int $index, int $total, bool $shuffleOptions, ?array $saved = null): array
    {
        $type = (string) ($snapshot['type'] ?? '');
        $options = array_map(fn ($opt) => [
            'id' => $opt['id'] ?? null,
            'option_key' => $opt['option_key'] ?? null,
            'body' => $opt['body'] ?? '',
            'sort_order' => $opt['sort_order'] ?? 0,
        ], $snapshot['options'] ?? []);

        $canShuffleOptions = $shuffleOptions
            && in_array($type, [QuestionBank::TYPE_PG, QuestionBank::TYPE_PG_KOMPLEKS], true)
            && count($options) > 0;
        if ($canShuffleOptions) {
            shuffle($options);
        }

        $stimulus = $snapshot['stimulus'] ?? null;
        $payload = [
            'question_bank_id' => (int) ($snapshot['question_bank_id'] ?? 0),
            'type' => $type,
            'body' => $snapshot['body'] ?? '',
            'weight' => (float) ($snapshot['weight'] ?? 0),
            'stimulus' => $stimulus ? [
                'id' => $stimulus['id'] ?? null,
                'content' => $stimulus['content'] ?? '',
                'type' => $stimulus['type'] ?? null,
            ] : null,
            'index' => $index,
            'total' => $total,
            'saved_answer_text' => $saved['answer_text'] ?? null,
            'saved_question_option_id' => $saved['question_option_id'] ?? null,
            'saved_selected_option_ids' => $saved['selected_option_ids'] ?? null,
            'saved_matching_answer' => $saved['matching_answer'] ?? null,
        ];

        if ($type === QuestionBank::TYPE_MATCHING) {
            $matching = $snapshot['matching_data'] ?? ['left' => [], 'right' => []];
            $left = $matching['left'] ?? [];
            $right = $matching['right'] ?? [];
            if ($shuffleOptions && count($right) > 0) {
                shuffle($right);
            }
            $payload['matching_left'] = $left;
            $payload['matching_right'] = $right;
            $payload['options'] = [];
        } else {
            $payload['options'] = $options;
        }

        return $payload;
    }

    /**
     * Auto-score one answer from a frozen snapshot. Uraian is left unchanged.
     *
     * @param  array<string, mixed>  $snapshot
     */
    public static function applyAutoScore(ExamAnswer $answer, array $snapshot): float
    {
        $type = $snapshot['type'] ?? '';
        $weight = (float) ($snapshot['weight'] ?? 0);
        $score = 0.0;

        if ($type === QuestionBank::TYPE_PG) {
            $optId = (int) $answer->question_option_id;
            $correct = false;
            foreach ($snapshot['options'] ?? [] as $opt) {
                if ((int) ($opt['id'] ?? 0) === $optId && ! empty($opt['is_correct'])) {
                    $correct = true;
                    break;
                }
            }
            $score = $correct ? $weight : 0;
            $answer->update(['score' => $score, 'saved_at' => $answer->saved_at ?? now()]);
        } elseif ($type === QuestionBank::TYPE_PG_KOMPLEKS) {
            $optionsById = [];
            foreach ($snapshot['options'] ?? [] as $opt) {
                $optionsById[(int) ($opt['id'] ?? 0)] = $opt;
            }
            $selectedIds = $answer->selected_option_ids ?? [];
            $selectedIds = is_array($selectedIds) ? array_map('intval', $selectedIds) : [];
            $score = 0;
            foreach ($selectedIds as $optId) {
                $opt = $optionsById[$optId] ?? null;
                if ($opt && ! empty($opt['is_correct'])) {
                    $score += (float) ($opt['option_weight'] ?? 0);
                }
            }
            $answer->update(['score' => $score, 'saved_at' => $answer->saved_at ?? now()]);
        } elseif ($type === QuestionBank::TYPE_MATCHING) {
            $matchingData = $snapshot['matching_data'] ?? [];
            $correctPairs = $matchingData['correct'] ?? [];
            $studentPairs = $answer->matching_answer ?? [];
            $correctCount = 0;
            foreach ($correctPairs as $pair) {
                $l = (string) ($pair['left_id'] ?? $pair['l'] ?? '');
                $r = (string) ($pair['right_id'] ?? $pair['r'] ?? '');
                foreach ($studentPairs as $sp) {
                    $sl = (string) ($sp['left_id'] ?? $sp['l'] ?? '');
                    $sr = (string) ($sp['right_id'] ?? $sp['r'] ?? '');
                    if ($sl === $l && $sr === $r) {
                        $correctCount++;
                        break;
                    }
                }
            }
            $totalPairs = count($correctPairs);
            $score = $totalPairs > 0 ? $weight * ($correctCount / $totalPairs) : 0;
            $answer->update(['score' => $score, 'saved_at' => $answer->saved_at ?? now()]);
        } elseif ($type === QuestionBank::TYPE_ISIAN) {
            $accepted = self::acceptedIsianAnswers($snapshot);
            $trimAnswer = trim(mb_strtolower($answer->answer_text ?? ''));
            $correct = $trimAnswer !== '' && in_array($trimAnswer, $accepted, true);
            $score = $correct ? $weight : 0;
            $answer->update(['score' => $score, 'saved_at' => $answer->saved_at ?? now()]);
        }

        return (float) $score;
    }
}
