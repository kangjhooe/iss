<?php

namespace App\Services;

use App\Models\BankSoal;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use App\Models\QuestionStimulus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class QuestionBankImportService
{
    /** @var list<string> */
    public const TEMPLATE_HEADERS = [
        'type',
        'body',
        'weight',
        'key_answer',
        'key_aliases',
        'option_a',
        'option_b',
        'option_c',
        'option_d',
        'option_e',
        'correct',
        'stimulus_title',
    ];

    /**
     * @return array{created:int,failed:int,errors:list<string>}
     */
    public function import(UploadedFile $file, BankSoal $bank): array
    {
        $path = $file->getRealPath();
        if (! $path) {
            throw new \InvalidArgumentException('File tidak valid.');
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        if ($rows === []) {
            throw new \InvalidArgumentException('File kosong atau tidak bisa dibaca.');
        }

        $headerRow = array_map(fn ($h) => $this->normalizeHeader((string) $h), $rows[0] ?? []);
        $dataRows = array_slice($rows, 1);

        $results = ['created' => 0, 'failed' => 0, 'errors' => []];
        $nextOrder = (int) QuestionBank::query()
            ->where('bank_soal_id', $bank->id)
            ->max('sort_order');
        $nextOrder = $nextOrder > 0 ? $nextOrder + 1 : 1;

        $stimulusCache = [];

        DB::beginTransaction();
        try {
            foreach ($dataRows as $index => $raw) {
                $rowNumber = $index + 2;
                if (! is_array($raw) || $this->rowEmpty($raw)) {
                    continue;
                }
                $row = $this->mapRow($headerRow, $raw);
                try {
                    $this->importRow($row, $bank, $nextOrder, $stimulusCache);
                    $nextOrder++;
                    $results['created']++;
                } catch (\Throwable $e) {
                    $results['failed']++;
                    $results['errors'][] = "Baris {$rowNumber}: ".$e->getMessage();
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $results;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, int>  $stimulusCache
     */
    private function importRow(array $row, BankSoal $bank, int $sortOrder, array &$stimulusCache): void
    {
        $type = strtolower(trim((string) ($row['type'] ?? '')));
        $allowed = ['pg', 'isian', 'uraian', 'pg_kompleks'];
        if (! in_array($type, $allowed, true)) {
            throw new \InvalidArgumentException('type harus salah satu: pg, isian, uraian, pg_kompleks.');
        }

        $body = trim((string) ($row['body'] ?? ''));
        if ($body === '') {
            throw new \InvalidArgumentException('body (pertanyaan) wajib diisi.');
        }

        $weight = isset($row['weight']) && $row['weight'] !== '' ? (float) $row['weight'] : 1.0;
        if ($weight < 0) {
            $weight = 0;
        }

        $stimulusId = null;
        $stimulusTitle = trim((string) ($row['stimulus_title'] ?? ''));
        if ($stimulusTitle !== '') {
            $cacheKey = mb_strtolower($stimulusTitle);
            if (! isset($stimulusCache[$cacheKey])) {
                $stimulus = QuestionStimulus::query()
                    ->where('bank_soal_id', $bank->id)
                    ->where('title', $stimulusTitle)
                    ->first();
                if (! $stimulus) {
                    $stimulus = QuestionStimulus::create([
                        'institution_id' => $bank->institution_id,
                        'bank_soal_id' => $bank->id,
                        'title' => $stimulusTitle,
                        'content' => '<p>'.e($stimulusTitle).'</p>',
                    ]);
                }
                $stimulusCache[$cacheKey] = $stimulus->id;
            }
            $stimulusId = $stimulusCache[$cacheKey];
        }

        $keyAnswer = trim((string) ($row['key_answer'] ?? ''));
        $aliases = $this->parseAliases((string) ($row['key_aliases'] ?? ''));

        if ($type === 'isian' && $keyAnswer === '') {
            throw new \InvalidArgumentException('Soal isian wajib punya key_answer.');
        }

        $question = QuestionBank::create([
            'institution_id' => $bank->institution_id,
            'bank_soal_id' => $bank->id,
            'subject_id' => $bank->subject_id,
            'stimulus_id' => $stimulusId,
            'type' => $type,
            'body' => '<p>'.e($body).'</p>',
            'weight' => $weight,
            'sort_order' => $sortOrder,
            'key_answer' => $type === 'isian' ? $keyAnswer : null,
            'key_answer_aliases' => $type === 'isian' && $aliases !== [] ? $aliases : null,
        ]);

        if (in_array($type, ['pg', 'pg_kompleks'], true)) {
            $this->createOptionsFromRow($question, $row, $type);
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function createOptionsFromRow(QuestionBank $question, array $row, string $type): void
    {
        $keys = ['a', 'b', 'c', 'd', 'e'];
        $correctRaw = strtoupper(trim((string) ($row['correct'] ?? '')));
        $correctKeys = array_filter(array_map('trim', preg_split('/[|,;\/]+/', $correctRaw) ?: []));

        $options = [];
        foreach ($keys as $i => $letter) {
            $text = trim((string) ($row['option_'.$letter] ?? ''));
            if ($text === '') {
                continue;
            }
            $isCorrect = in_array(strtoupper($letter), $correctKeys, true)
                || str_starts_with($text, '*');
            if (str_starts_with($text, '*')) {
                $text = ltrim(substr($text, 1));
            }
            $options[] = [
                'key' => strtoupper($letter),
                'body' => $text,
                'is_correct' => $isCorrect,
                'weight' => $isCorrect && $type === 'pg_kompleks' ? 1.0 : 0.0,
                'sort' => $i,
            ];
        }

        if (count($options) < 2) {
            throw new \InvalidArgumentException('Soal PG minimal 2 opsi (option_a, option_b, ...).');
        }

        $correctCount = count(array_filter($options, fn ($o) => $o['is_correct']));
        if ($type === 'pg' && $correctCount !== 1) {
            throw new \InvalidArgumentException('PG harus punya tepat 1 jawaban benar (kolom correct atau awali opsi dengan *).');
        }
        if ($type === 'pg_kompleks' && $correctCount < 1) {
            throw new \InvalidArgumentException('PG kompleks minimal 1 opsi benar.');
        }

        foreach ($options as $opt) {
            QuestionOption::create([
                'question_bank_id' => $question->id,
                'option_key' => $opt['key'],
                'body' => '<p>'.e($opt['body']).'</p>',
                'is_correct' => $opt['is_correct'],
                'option_weight' => $opt['weight'],
                'sort_order' => $opt['sort'],
            ]);
        }

        if ($type === 'pg_kompleks') {
            $question->weight = $question->options()->where('is_correct', true)->sum('option_weight');
            $question->save();
        }
    }

    /**
     * @return list<string>
     */
    private function parseAliases(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }
        $parts = preg_split('/[|;]+/', $raw) ?: [];
        $out = [];
        foreach ($parts as $p) {
            $t = trim($p);
            if ($t !== '') {
                $out[] = $t;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * @param  list<string>  $headers
     * @param  list<mixed>  $raw
     * @return array<string, mixed>
     */
    private function mapRow(array $headers, array $raw): array
    {
        $mapped = [];
        foreach ($headers as $i => $key) {
            if ($key === '') {
                continue;
            }
            $mapped[$key] = $raw[$i] ?? '';
        }

        return $mapped;
    }

    private function normalizeHeader(string $h): string
    {
        $h = strtolower(trim($h));
        $h = str_replace([' ', '-'], '_', $h);
        $aliases = [
            'tipe' => 'type',
            'pertanyaan' => 'body',
            'soal' => 'body',
            'bobot' => 'weight',
            'kunci' => 'key_answer',
            'kunci_jawaban' => 'key_answer',
            'alias' => 'key_aliases',
            'aliases' => 'key_aliases',
            'jawaban_benar' => 'correct',
            'benar' => 'correct',
            'stimulus' => 'stimulus_title',
            'judul_stimulus' => 'stimulus_title',
            'opsi_a' => 'option_a',
            'opsi_b' => 'option_b',
            'opsi_c' => 'option_c',
            'opsi_d' => 'option_d',
            'opsi_e' => 'option_e',
        ];

        return $aliases[$h] ?? $h;
    }

    /**
     * @param  list<mixed>  $raw
     */
    private function rowEmpty(array $raw): bool
    {
        foreach ($raw as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
