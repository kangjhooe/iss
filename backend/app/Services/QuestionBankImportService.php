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
        'cognitive_level',
        'difficulty',
        'status',
        'topic',
        'tags',
        'key_answer',
        'key_aliases',
        'option_a',
        'option_b',
        'option_c',
        'option_d',
        'option_e',
        'correct',
        'stimulus_title',
        'weight_a',
        'weight_b',
        'weight_c',
        'weight_d',
        'weight_e',
        'left_1',
        'left_2',
        'left_3',
        'left_4',
        'right_1',
        'right_2',
        'right_3',
        'right_4',
        'match_1',
        'match_2',
        'match_3',
        'match_4',
    ];

    /**
     * @return list<list<string|int|float>>
     */
    public static function templateSampleRows(): array
    {
        return [
            self::sampleRow([
                'type' => 'pg',
                'body' => 'Ibu kota Indonesia adalah…',
                'weight' => 1,
                'cognitive_level' => 'c1',
                'difficulty' => 'mudah',
                'status' => 'ready',
                'topic' => 'geografi',
                'tags' => 'ibukota',
                'option_a' => 'Jakarta',
                'option_b' => 'Bandung',
                'option_c' => 'Surabaya',
                'option_d' => 'Medan',
                'correct' => 'A',
            ]),
            self::sampleRow([
                'type' => 'pg_kompleks',
                'body' => 'Pilih bilangan genap.',
                'cognitive_level' => 'c1',
                'difficulty' => 'mudah',
                'status' => 'ready',
                'topic' => 'bilangan',
                'option_a' => '2',
                'option_b' => '3',
                'option_c' => '4',
                'option_d' => '5',
                'correct' => 'A|C',
                'weight_a' => 1,
                'weight_c' => 1,
            ]),
            self::sampleRow([
                'type' => 'isian',
                'body' => 'Lambang kimia air adalah…',
                'weight' => 1,
                'cognitive_level' => 'c2',
                'difficulty' => 'mudah',
                'status' => 'ready',
                'key_answer' => 'H2O',
                'key_aliases' => 'h2o|air',
            ]),
            self::sampleRow([
                'type' => 'uraian',
                'body' => 'Jelaskan proses fotosintesis.',
                'weight' => 5,
                'cognitive_level' => 'c4',
                'difficulty' => 'sedang',
                'status' => 'ready',
                'topic' => 'biologi',
                'stimulus_title' => 'Bacaan Fotosintesis',
            ]),
            self::sampleRow([
                'type' => 'matching',
                'body' => 'Pasangkan ibu kota dengan negaranya.',
                'weight' => 2,
                'cognitive_level' => 'c1',
                'difficulty' => 'mudah',
                'status' => 'ready',
                'topic' => 'geografi',
                'left_1' => 'Indonesia',
                'left_2' => 'Jepang',
                'right_1' => 'Tokyo',
                'right_2' => 'Jakarta',
                'match_1' => 2,
                'match_2' => 1,
            ]),
        ];
    }

    /**
     * @param  array<string, string|int|float>  $values
     * @return list<string|int|float>
     */
    private static function sampleRow(array $values): array
    {
        $row = array_fill(0, count(self::TEMPLATE_HEADERS), '');
        foreach (self::TEMPLATE_HEADERS as $i => $key) {
            if (array_key_exists($key, $values)) {
                $row[$i] = $values[$key];
            }
        }

        return $row;
    }

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
        $allowed = ['pg', 'isian', 'uraian', 'pg_kompleks', 'matching'];
        if (! in_array($type, $allowed, true)) {
            throw new \InvalidArgumentException('type harus salah satu: pg, isian, uraian, pg_kompleks, matching.');
        }

        $body = trim((string) ($row['body'] ?? ''));
        if ($body === '') {
            throw new \InvalidArgumentException('body (pertanyaan) wajib diisi.');
        }

        $weight = isset($row['weight']) && $row['weight'] !== '' ? (float) $row['weight'] : 1.0;
        if ($weight < 0) {
            $weight = 0;
        }

        $cognitiveLevel = strtolower(trim((string) ($row['cognitive_level'] ?? '')));
        if ($cognitiveLevel === '') {
            $cognitiveLevel = null;
        } elseif (! array_key_exists($cognitiveLevel, QuestionBank::COGNITIVE_LEVELS)) {
            throw new \InvalidArgumentException('cognitive_level harus c1–c6 atau kosong.');
        }

        $difficulty = strtolower(trim((string) ($row['difficulty'] ?? '')));
        if ($difficulty === '') {
            $difficulty = null;
        } elseif (! array_key_exists($difficulty, QuestionBank::DIFFICULTIES)) {
            throw new \InvalidArgumentException('difficulty harus mudah, sedang, sulit, atau kosong.');
        }

        $status = strtolower(trim((string) ($row['status'] ?? '')));
        if ($status === '') {
            $status = QuestionBank::STATUS_READY;
        } elseif (! in_array($status, [QuestionBank::STATUS_DRAFT, QuestionBank::STATUS_READY], true)) {
            throw new \InvalidArgumentException('status harus draft, ready, atau kosong.');
        }

        $topic = trim((string) ($row['topic'] ?? ''));
        $topic = $topic === '' ? null : mb_substr($topic, 0, 120);
        $tags = $this->parseTags((string) ($row['tags'] ?? ''));

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
            'cognitive_level' => $cognitiveLevel,
            'difficulty' => $difficulty,
            'status' => $status,
            'topic' => $topic,
            'tags' => $tags !== [] ? $tags : null,
            'sort_order' => $sortOrder,
            'key_answer' => $type === 'isian' ? $keyAnswer : null,
            'key_answer_aliases' => $type === 'isian' && $aliases !== [] ? $aliases : null,
        ]);

        if (in_array($type, ['pg', 'pg_kompleks'], true)) {
            $this->createOptionsFromRow($question, $row, $type);
        }
        if ($type === 'matching') {
            $this->createMatchingFromRow($question, $row);
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
            $optionWeight = 0.0;
            if ($isCorrect && $type === 'pg_kompleks') {
                $explicit = $row['weight_'.$letter] ?? '';
                $optionWeight = ($explicit !== '' && $explicit !== null) ? (float) $explicit : 1.0;
                if ($optionWeight <= 0) {
                    throw new \InvalidArgumentException('Bobot opsi benar '.strtoupper($letter).' harus lebih dari 0 (kolom weight_'.strtoupper($letter).').');
                }
            }
            $options[] = [
                'key' => strtoupper($letter),
                'body' => $text,
                'is_correct' => $isCorrect,
                'weight' => $optionWeight,
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
     * @param  array<string, mixed>  $row
     */
    private function createMatchingFromRow(QuestionBank $question, array $row): void
    {
        $left = [];
        $right = [];
        for ($i = 1; $i <= 4; $i++) {
            $leftText = trim((string) ($row['left_'.$i] ?? ''));
            $rightText = trim((string) ($row['right_'.$i] ?? ''));
            if ($leftText !== '') {
                $left[] = ['col' => $i, 'text' => $leftText];
            }
            if ($rightText !== '') {
                $right[] = ['col' => $i, 'text' => $rightText];
            }
        }
        if (count($left) < 2 || count($right) < 2) {
            throw new \InvalidArgumentException('Soal mencocokkan minimal 2 baris kiri dan 2 baris kanan (left_1, right_1, ...).');
        }

        $leftItems = [];
        foreach ($left as $i => $item) {
            $leftItems[] = ['id' => (string) ($i + 1), 'text' => $item['text'], 'col' => $item['col']];
        }
        $rightItems = [];
        $rightByCol = [];
        foreach ($right as $i => $item) {
            $built = ['id' => (string) (count($leftItems) + $i + 1), 'text' => $item['text']];
            $rightItems[] = $built;
            $rightByCol[$item['col']] = $built['id'];
        }

        $correct = [];
        foreach ($leftItems as $item) {
            $raw = trim((string) ($row['match_'.$item['col']] ?? ''));
            if ($raw === '') {
                continue;
            }
            $col = (int) $raw;
            if (! isset($rightByCol[$col])) {
                throw new \InvalidArgumentException('match_'.$item['col'].' harus nomor kolom kanan (1–4) yang terisi.');
            }
            $correct[] = ['left_id' => $item['id'], 'right_id' => $rightByCol[$col]];
        }
        if (count($correct) < 1) {
            throw new \InvalidArgumentException('Soal mencocokkan wajib punya minimal satu pasangan (match_1, match_2, ...).');
        }

        $question->matching_data = [
            'left' => array_map(fn ($item) => ['id' => $item['id'], 'text' => $item['text']], $leftItems),
            'right' => $rightItems,
            'correct' => $correct,
        ];
        $question->save();
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
     * @return list<string>
     */
    private function parseTags(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        $parts = preg_split('/[,|;]+/', $raw) ?: [];
        $out = [];
        foreach ($parts as $part) {
            $text = mb_strtolower(trim($part));
            $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
            if ($text === '' || mb_strlen($text) > 40 || isset($out[$text])) {
                continue;
            }
            $out[$text] = $text;
            if (count($out) >= 8) {
                break;
            }
        }

        return array_values($out);
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
            'cognitive_level' => 'cognitive_level',
            'level' => 'cognitive_level',
            'level_kognitif' => 'cognitive_level',
            'taksonomi' => 'cognitive_level',
            'c1_c6' => 'cognitive_level',
            'kesulitan' => 'difficulty',
            'tingkat_kesulitan' => 'difficulty',
            'topik' => 'topic',
            'tag' => 'tags',
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
            'bobot_a' => 'weight_a',
            'bobot_b' => 'weight_b',
            'bobot_c' => 'weight_c',
            'bobot_d' => 'weight_d',
            'bobot_e' => 'weight_e',
            'kiri_1' => 'left_1',
            'kiri_2' => 'left_2',
            'kiri_3' => 'left_3',
            'kiri_4' => 'left_4',
            'kanan_1' => 'right_1',
            'kanan_2' => 'right_2',
            'kanan_3' => 'right_3',
            'kanan_4' => 'right_4',
            'pasangan_1' => 'match_1',
            'pasangan_2' => 'match_2',
            'pasangan_3' => 'match_3',
            'pasangan_4' => 'match_4',
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
