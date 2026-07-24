<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Validator;

trait ValidatesQuestionBankPayload
{
    protected function addQuestionBankTypeRules(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $data = $validator->getData();
            $type = $data['type'] ?? null;

            // On update, type may be omitted — skip type-specific rules unless type or options/matching present
            if (! $type && ! array_key_exists('options', $data) && ! array_key_exists('matching_data', $data) && ! array_key_exists('key_answer', $data)) {
                return;
            }

            // Prefer explicit type; for update without type, infer from route model if available
            if (! $type && $this->route('question_bank')) {
                $type = $this->route('question_bank')->type;
            }

            if (! $type) {
                return;
            }

            $bodyPlain = trim(html_entity_decode(strip_tags((string) ($data['body'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($bodyPlain === '' && array_key_exists('body', $data)) {
                $validator->errors()->add('body', 'Pertanyaan wajib diisi (teks tidak boleh kosong).');
            }

            if (in_array($type, ['pg', 'pg_kompleks'], true)) {
                $options = $data['options'] ?? null;
                if ($options === null && $this->isMethod('POST')) {
                    $validator->errors()->add('options', 'Soal pilihan ganda wajib memiliki opsi.');

                    return;
                }
                if (is_array($options)) {
                    if (count($options) < 2) {
                        $validator->errors()->add('options', 'Minimal 2 opsi jawaban.');
                    }
                    $correctCount = 0;
                    foreach ($options as $opt) {
                        if (! empty($opt['is_correct'])) {
                            $correctCount++;
                        }
                    }
                    if ($type === 'pg' && $correctCount !== 1) {
                        $validator->errors()->add('options', 'Pilihan ganda harus punya tepat satu jawaban benar.');
                    }
                    if ($type === 'pg_kompleks' && $correctCount < 1) {
                        $validator->errors()->add('options', 'Pilihan ganda kompleks minimal satu opsi benar.');
                    }
                    if ($type === 'pg_kompleks') {
                        foreach ($options as $i => $opt) {
                            if (! empty($opt['is_correct']) && (! isset($opt['option_weight']) || (float) $opt['option_weight'] <= 0)) {
                                $validator->errors()->add("options.$i.option_weight", 'Opsi benar harus punya bobot lebih dari 0.');
                            }
                        }
                    }
                }
            }

            if ($type === 'isian') {
                $key = trim((string) ($data['key_answer'] ?? ''));
                $aliases = $data['key_answer_aliases'] ?? [];
                $hasAlias = is_array($aliases) && collect($aliases)->filter(fn ($a) => trim((string) $a) !== '')->isNotEmpty();
                if ($key === '' && ! $hasAlias && ($this->isMethod('POST') || array_key_exists('key_answer', $data) || array_key_exists('key_answer_aliases', $data))) {
                    $validator->errors()->add('key_answer', 'Kunci jawaban wajib diisi untuk soal isian (atau isi alias).');
                }
            }

            if ($type === 'matching') {
                $matching = $data['matching_data'] ?? null;
                if ($matching === null && $this->isMethod('POST')) {
                    $validator->errors()->add('matching_data', 'Data mencocokkan wajib diisi.');

                    return;
                }
                if (is_array($matching)) {
                    $left = $matching['left'] ?? [];
                    $right = $matching['right'] ?? [];
                    $correct = $matching['correct'] ?? [];
                    if (count($left) < 2 || count($right) < 2) {
                        $validator->errors()->add('matching_data', 'Kolom kiri dan kanan minimal 2 baris.');
                    }
                    if (count($correct) < 1) {
                        $validator->errors()->add('matching_data.correct', 'Minimal satu pasangan benar harus ditentukan.');
                    }
                }
            }
        });
    }
}
