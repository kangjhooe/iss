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
            'cognitive_level' => $question->cognitive_level,
            'difficulty' => $question->difficulty,
            'status' => $question->status,
            'topic' => $question->topic,
            'tags' => $question->tags,
            'key_answer' => $question->key_answer,
            'key_answer_aliases' => $question->key_answer_aliases,
            'matching_data' => $question->matching_data,
            'stimulus' => $question->stimulus ? [
                'id' => $question->stimulus->id,
                'title' => $question->stimulus->title,
                'content' => $question->stimulus->content,
                'type' => $question->stimulus->type,
                'media' => is_array($question->stimulus->media) ? $question->stimulus->media : [],
                'attachment_path' => $question->stimulus->attachment_path,
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
        if (! empty($snapshot['key_answer'])) {
            $t = self::normalizeIsian((string) $snapshot['key_answer']);
            if ($t !== '') {
                $keys[] = $t;
            }
        }
        foreach ($snapshot['key_answer_aliases'] ?? [] as $alias) {
            $t = self::normalizeIsian((string) $alias);
            if ($t !== '') {
                $keys[] = $t;
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * Huruf kecil, spasi dirapikan, titik di akhir diabaikan.
     */
    public static function normalizeIsian(string $value): string
    {
        $text = trim(mb_strtolower($value));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return rtrim($text, " \t.");
    }

    /**
     * Urutan opsi/kolom kanan yang sama setiap kali soal dibuka.
     * Saat diacak, huruf A/B/C mengikuti posisi tampilan, bukan kunci asli.
     *
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    public static function forStudentDisplay(array $snapshot, bool $shuffleOptions, string $shuffleSeed): array
    {
        $options = array_values($snapshot['options'] ?? []);
        $type = (string) ($snapshot['type'] ?? '');
        $shuffleChoices = $shuffleOptions
            && in_array($type, [QuestionBank::TYPE_PG, QuestionBank::TYPE_PG_KOMPLEKS], true)
            && count($options) > 1;
        if ($shuffleChoices) {
            $options = self::stableShuffle($options, $shuffleSeed.':options');
            foreach ($options as $i => $opt) {
                $options[$i]['option_key'] = $i < 26 ? chr(65 + $i) : (string) ($i + 1);
            }
        }
        $snapshot['options'] = $options;

        $matching = $snapshot['matching_data'] ?? null;
        if (is_array($matching)) {
            $right = array_values($matching['right'] ?? []);
            if ($shuffleOptions && $type === QuestionBank::TYPE_MATCHING && count($right) > 1) {
                $right = self::stableShuffle($right, $shuffleSeed.':matching');
            }
            $matching['right'] = $right;
            $snapshot['matching_data'] = $matching;
        }

        return $snapshot;
    }

    /**
     * @param  list<mixed>  $items
     * @return list<mixed>
     */
    public static function stableShuffle(array $items, string $seed): array
    {
        $n = count($items);
        for ($i = $n - 1; $i > 0; $i--) {
            $hash = sprintf('%u', crc32($seed.'|'.$i));
            $j = (int) ((int) $hash % ($i + 1));
            [$items[$i], $items[$j]] = [$items[$j], $items[$i]];
        }

        return array_values($items);
    }

    /**
     * Jawaban siswa harus merujuk ke opsi di snapshot, bukan baris opsi yang masih hidup di bank.
     *
     * @param  array<string, mixed>  $snapshot
     */
    public static function answerError(string $type, array $snapshot, mixed $optionId, mixed $selectedIds, mixed $matching): ?string
    {
        $optionIds = [];
        foreach ($snapshot['options'] ?? [] as $opt) {
            $optionIds[] = (int) ($opt['id'] ?? 0);
        }

        if ($type === QuestionBank::TYPE_PG) {
            if ($optionId === null || $optionId === '') {
                return null;
            }
            if (! in_array((int) $optionId, $optionIds, true)) {
                return 'Opsi tidak termasuk soal ini.';
            }

            return null;
        }

        if ($type === QuestionBank::TYPE_PG_KOMPLEKS) {
            foreach ((array) $selectedIds as $id) {
                if (! in_array((int) $id, $optionIds, true)) {
                    return 'Opsi tidak termasuk soal ini.';
                }
            }

            return null;
        }

        if ($type === QuestionBank::TYPE_MATCHING) {
            $left = [];
            foreach ($snapshot['matching_data']['left'] ?? [] as $item) {
                $left[(string) ($item['id'] ?? '')] = true;
            }
            $right = [];
            foreach ($snapshot['matching_data']['right'] ?? [] as $item) {
                $right[(string) ($item['id'] ?? '')] = true;
            }
            $seenLeft = [];
            foreach ((array) $matching as $pair) {
                if (! is_array($pair)) {
                    return 'Jawaban mencocokkan tidak valid.';
                }
                $l = (string) ($pair['left_id'] ?? '');
                $r = (string) ($pair['right_id'] ?? '');
                if ($l === '' || $r === '') {
                    continue;
                }
                if (! isset($left[$l]) || ! isset($right[$r])) {
                    return 'Pasangan tidak termasuk soal ini.';
                }
                if (isset($seenLeft[$l])) {
                    return 'Setiap item kiri hanya boleh dipasangkan sekali.';
                }
                $seenLeft[$l] = true;
            }
        }

        return null;
    }

    /**
     * Student-facing question payload (keys stripped).
     *
     * @param  array<string, mixed>  $snapshot
     * @param  array<string, mixed>|null  $saved
     * @return array<string, mixed>
     */
    public static function toAttemptPayload(array $snapshot, int $index, int $total, bool $shuffleOptions, ?array $saved = null, string $shuffleSeed = ''): array
    {
        $snapshot = self::forStudentDisplay($snapshot, $shuffleOptions, $shuffleSeed);
        $type = (string) ($snapshot['type'] ?? '');
        $options = array_map(fn ($opt) => [
            'id' => $opt['id'] ?? null,
            'option_key' => $opt['option_key'] ?? null,
            'body' => $opt['body'] ?? '',
            'sort_order' => $opt['sort_order'] ?? 0,
        ], $snapshot['options'] ?? []);

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
                'media' => is_array($stimulus['media'] ?? null) ? $stimulus['media'] : [],
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
            $payload['matching_left'] = $matching['left'] ?? [];
            $payload['matching_right'] = $matching['right'] ?? [];
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
            $selectedIds = is_array($selectedIds) ? array_values(array_unique(array_map('intval', $selectedIds))) : [];
            $score = 0;
            $wrong = false;
            foreach ($selectedIds as $optId) {
                $opt = $optionsById[$optId] ?? null;
                if (! $opt || empty($opt['is_correct'])) {
                    $wrong = true;
                    break;
                }
                $score += (float) ($opt['option_weight'] ?? 0);
            }
            if ($wrong) {
                $score = 0;
            }
            $answer->update(['score' => $score, 'saved_at' => $answer->saved_at ?? now()]);
        } elseif ($type === QuestionBank::TYPE_MATCHING) {
            $matchingData = $snapshot['matching_data'] ?? [];
            $correctPairs = $matchingData['correct'] ?? [];
            $studentPairs = is_array($answer->matching_answer) ? $answer->matching_answer : [];
            $studentMap = [];
            $rightUse = [];
            $invalid = false;
            foreach ($studentPairs as $sp) {
                if (! is_array($sp)) {
                    continue;
                }
                $sl = (string) ($sp['left_id'] ?? $sp['l'] ?? '');
                $sr = (string) ($sp['right_id'] ?? $sp['r'] ?? '');
                if ($sl === '' || $sr === '') {
                    continue;
                }
                if (isset($studentMap[$sl])) {
                    $invalid = true;
                    break;
                }
                $studentMap[$sl] = $sr;
                $rightUse[$sr] = ($rightUse[$sr] ?? 0) + 1;
            }
            if (! $invalid) {
                foreach ($rightUse as $times) {
                    if ($times > 1) {
                        $invalid = true;
                        break;
                    }
                }
            }
            $correctCount = 0;
            $correctMap = [];
            foreach ($correctPairs as $pair) {
                $l = (string) ($pair['left_id'] ?? $pair['l'] ?? '');
                $r = (string) ($pair['right_id'] ?? $pair['r'] ?? '');
                if ($l !== '') {
                    $correctMap[$l] = $r;
                }
            }
            if (! $invalid) {
                foreach ($studentMap as $l => $r) {
                    if (! isset($correctMap[$l]) || $correctMap[$l] !== $r) {
                        $invalid = true;
                        break;
                    }
                    $correctCount++;
                }
            }
            $totalPairs = count($correctPairs);
            $score = $invalid ? 0 : ($totalPairs > 0 ? $weight * ($correctCount / $totalPairs) : 0);
            $answer->update(['score' => $score, 'saved_at' => $answer->saved_at ?? now()]);
        } elseif ($type === QuestionBank::TYPE_ISIAN) {
            $accepted = self::acceptedIsianAnswers($snapshot);
            $trimAnswer = self::normalizeIsian((string) ($answer->answer_text ?? ''));
            $correct = $trimAnswer !== '' && in_array($trimAnswer, $accepted, true);
            $score = $correct ? $weight : 0;
            $answer->update(['score' => $score, 'saved_at' => $answer->saved_at ?? now()]);
        }

        return (float) $score;
    }
}
