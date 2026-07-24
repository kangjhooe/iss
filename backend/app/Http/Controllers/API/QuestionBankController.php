<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Requests\StoreQuestionBankRequest;
use App\Http\Requests\UpdateQuestionBankRequest;
use App\Http\Resources\QuestionBankResource;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use App\Models\BankSoal;
use App\Models\QuestionStimulus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use App\Services\HtmlPurifier;

class QuestionBankController extends Controller
{
    use ResolvesInstitution;

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            if ($request->filled('bank_soal_id')) {
                $bank = BankSoal::accessibleBy($request)->find($request->input('bank_soal_id'));
                if (!$bank) {
                    return response()->json(['message' => 'Bank soal tidak ditemukan.'], 404);
                }
                $query = QuestionBank::query()->where('bank_soal_id', $bank->id)->with(['subject', 'stimulus', 'options']);
            } else {
                $query = QuestionBank::forInstitution($institutionId)->with(['subject', 'stimulus', 'options']);
            }

            if ($request->filled('subject_id')) {
                $query->where('subject_id', $request->get('subject_id'));
            }
            if ($request->filled('stimulus_id')) {
                $stimulusId = $request->get('stimulus_id');
                if ($stimulusId === 'none' || $stimulusId === 'null') {
                    $query->whereNull('stimulus_id');
                } else {
                    $query->where('stimulus_id', $stimulusId);
                }
            }
            if ($request->filled('type')) {
                $query->where('type', $request->get('type'));
            }
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where('body', 'like', '%' . $search . '%');
            }

            $query->orderBy('sort_order')->orderBy('id');
            $perPage = min($request->get('per_page', 15), 100);
            $items = $query->paginate($perPage);

            return QuestionBankResource::collection($items);
        } catch (\Exception $e) {
            Log::error('QuestionBank index failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil data bank soal.', 'error' => config('app.debug') ? $e->getMessage() : null], 500);
        }
    }

    public function store(StoreQuestionBankRequest $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $data = $request->validated();
            $data['institution_id'] = $institutionId;
            $options = $data['options'] ?? [];
            $matchingData = $data['matching_data'] ?? null;
            unset($data['options'], $data['matching_data']);

            if ($request->filled('bank_soal_id')) {
                $bank = BankSoal::accessibleBy($request)->find($request->input('bank_soal_id'));
                if (!$bank) {
                    return response()->json(['message' => 'Bank soal tidak ditemukan.'], 404);
                }
                $data['bank_soal_id'] = $bank->id;
                $data['subject_id'] = $bank->subject_id;
                $data['institution_id'] = $bank->institution_id;
            }

            if (!empty($data['stimulus_id']) && !empty($data['bank_soal_id'])) {
                $stimulus = QuestionStimulus::where('bank_soal_id', $data['bank_soal_id'])
                    ->find($data['stimulus_id']);
                if (!$stimulus) {
                    return response()->json(['message' => 'Stimulus tidak ditemukan atau bukan milik bank soal ini.'], 422);
                }
            }

            if ($matchingData !== null) {
                $data['matching_data'] = $this->normalizeMatchingData($matchingData);
            }

            if (array_key_exists('key_answer_aliases', $data)) {
                $data['key_answer_aliases'] = $this->normalizeAliases($data['key_answer_aliases'] ?? []);
            }

            $data['body'] = $this->sanitizeQuestionHtml($data['body'] ?? '');
            if (!empty($options)) {
                foreach ($options as $i => $opt) {
                    $options[$i]['body'] = $this->sanitizeQuestionHtml($opt['body'] ?? '');
                }
            }

            if (!empty($data['bank_soal_id'])) {
                $maxOrder = (int) QuestionBank::query()->where('bank_soal_id', $data['bank_soal_id'])->max('sort_order');
                $data['sort_order'] = $maxOrder > 0 ? $maxOrder + 1 : 1;
            } else {
                $data['sort_order'] = 0;
            }

            $question = QuestionBank::create($data);
            if (in_array($question->type, ['pg', 'pg_kompleks'], true) && !empty($options)) {
                foreach ($options as $i => $opt) {
                    QuestionOption::create([
                        'question_bank_id' => $question->id,
                        'option_key' => $opt['option_key'],
                        'body' => $opt['body'],
                        'is_correct' => $opt['is_correct'] ?? false,
                        'option_weight' => array_key_exists('option_weight', $opt) ? (float) $opt['option_weight'] : 0,
                        'sort_order' => $i,
                    ]);
                }
                if ($question->type === QuestionBank::TYPE_PG_KOMPLEKS) {
                    $question->weight = $question->options()->where('is_correct', true)->sum('option_weight');
                    $question->save();
                }
            }
            $question->load(['subject', 'stimulus', 'options']);

            return (new QuestionBankResource($question))->response()->setStatusCode(201);
        } catch (\Exception $e) {
            Log::error('QuestionBank store failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal membuat soal.', 'error' => config('app.debug') ? $e->getMessage() : null], 500);
        }
    }

    public function show(Request $request, QuestionBank $question_bank): QuestionBankResource|JsonResponse
    {
        $institutionId = $this->resolveInstitutionId($request);
        $sameInstitution = $question_bank->institution_id == $institutionId;
        $bankAccessible = $question_bank->bank_soal_id && BankSoal::accessibleBy($request)->where('id', $question_bank->bank_soal_id)->exists();
        if (!$sameInstitution && !$bankAccessible) {
            return response()->json(['message' => 'Soal tidak ditemukan.'], 404);
        }
        $question_bank->load(['subject', 'stimulus', 'options']);
        return new QuestionBankResource($question_bank);
    }

    public function update(UpdateQuestionBankRequest $request, QuestionBank $question_bank): QuestionBankResource|JsonResponse
    {
        $institutionId = $this->resolveInstitutionId($request);
        $sameInstitution = $question_bank->institution_id == $institutionId;
        $bankAccessible = $question_bank->bank_soal_id && BankSoal::accessibleBy($request)->where('id', $question_bank->bank_soal_id)->exists();
        if (!$sameInstitution && !$bankAccessible) {
            return response()->json(['message' => 'Soal tidak ditemukan.'], 404);
        }

        $data = $request->validated();
        $options = $data['options'] ?? null;
        $matchingData = $data['matching_data'] ?? null;
        unset($data['options'], $data['matching_data']);

        if (array_key_exists('stimulus_id', $data) && $data['stimulus_id'] && $question_bank->bank_soal_id) {
            $stimulus = QuestionStimulus::where('institution_id', $question_bank->institution_id)
                ->where('bank_soal_id', $question_bank->bank_soal_id)
                ->find($data['stimulus_id']);
            if (!$stimulus) {
                return response()->json(['message' => 'Stimulus tidak ditemukan atau bukan milik bank soal ini.'], 422);
            }
        }

        if ($matchingData !== null) {
            $data['matching_data'] = $this->normalizeMatchingData($matchingData);
        }

        if (array_key_exists('key_answer_aliases', $data)) {
            $data['key_answer_aliases'] = $this->normalizeAliases($data['key_answer_aliases'] ?? []);
        }

        $data['body'] = $this->sanitizeQuestionHtml($data['body'] ?? '');
        if ($options !== null && in_array($question_bank->type, ['pg', 'pg_kompleks'], true)) {
            foreach ($options as $i => $opt) {
                $options[$i]['body'] = $this->sanitizeQuestionHtml($opt['body'] ?? '');
            }
        }

        $question_bank->update($data);

        if ($options !== null && in_array($question_bank->type, ['pg', 'pg_kompleks'], true)) {
            $question_bank->options()->delete();
            foreach ($options as $i => $opt) {
                QuestionOption::create([
                    'question_bank_id' => $question_bank->id,
                    'option_key' => $opt['option_key'],
                    'body' => $opt['body'],
                    'is_correct' => $opt['is_correct'] ?? false,
                    'option_weight' => array_key_exists('option_weight', $opt) ? (float) $opt['option_weight'] : 0,
                    'sort_order' => $i,
                ]);
            }
            if ($question_bank->type === QuestionBank::TYPE_PG_KOMPLEKS) {
                $question_bank->weight = $question_bank->options()->where('is_correct', true)->sum('option_weight');
                $question_bank->save();
            }
        }
        $question_bank->load(['subject', 'stimulus', 'options']);
        return new QuestionBankResource($question_bank);
    }

    private function normalizeMatchingData(array $data): array
    {
        $left = $data['left'] ?? [];
        $right = $data['right'] ?? [];
        $correct = $data['correct'] ?? [];
        foreach ($left as $i => $item) {
            if (empty($item['id'])) {
                $left[$i]['id'] = (string) ($i + 1);
            }
        }
        foreach ($right as $i => $item) {
            if (empty($item['id'])) {
                $right[$i]['id'] = (string) (count($left) + $i + 1);
            }
        }
        return ['left' => $left, 'right' => $right, 'correct' => $correct];
    }

    /**
     * @param  mixed  $aliases
     * @return list<string>|null
     */
    private function normalizeAliases(mixed $aliases): ?array
    {
        if (! is_array($aliases)) {
            return null;
        }
        $out = [];
        foreach ($aliases as $a) {
            $t = trim((string) $a);
            if ($t !== '') {
                $out[] = $t;
            }
        }
        $out = array_values(array_unique($out));

        return $out === [] ? null : $out;
    }

    /**
     * Reorder questions inside a bank.
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'bank_soal_id' => 'required|exists:bank_soal,id',
            'question_ids' => 'required|array|min:1',
            'question_ids.*' => 'integer|exists:question_bank,id',
        ]);

        $bank = BankSoal::accessibleBy($request)->find($request->input('bank_soal_id'));
        if (! $bank) {
            return response()->json(['message' => 'Bank soal tidak ditemukan.'], 404);
        }

        $ids = array_map('intval', $request->input('question_ids'));
        $owned = QuestionBank::query()
            ->where('bank_soal_id', $bank->id)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->all();
        if (count($owned) !== count($ids)) {
            return response()->json(['message' => 'Beberapa soal tidak berada di bank ini.'], 422);
        }

        foreach ($ids as $i => $id) {
            QuestionBank::where('id', $id)->update(['sort_order' => $i + 1]);
        }

        return response()->json(['message' => 'Urutan soal diperbarui.']);
    }

    /**
     * Import questions from Excel/CSV into a bank.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'bank_soal_id' => 'required|exists:bank_soal,id',
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ]);

        $bank = BankSoal::accessibleBy($request)->find($request->input('bank_soal_id'));
        if (! $bank) {
            return response()->json(['message' => 'Bank soal tidak ditemukan.'], 404);
        }

        try {
            $service = app(\App\Services\QuestionBankImportService::class);
            $result = $service->import($request->file('file'), $bank);

            return response()->json([
                'message' => 'Import selesai.',
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            Log::error('QuestionBank import failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => $e->getMessage() ?: 'Gagal mengimpor soal.',
            ], 422);
        }
    }

    /**
     * Download Excel template for import.
     */
    public function importTemplate()
    {
        $headers = \App\Services\QuestionBankImportService::TEMPLATE_HEADERS;
        $sample = [
            ['pg', 'Ibu kota Indonesia adalah…', 1, '', '', 'Jakarta', 'Bandung', 'Surabaya', 'Medan', '', 'A', ''],
            ['isian', 'Lambang kimia air adalah…', 1, 'H2O', 'h2o|air', '', '', '', '', '', '', ''],
            ['uraian', 'Jelaskan proses fotosintesis.', 5, '', '', '', '', '', '', '', '', 'Bacaan Fotosintesis'],
        ];

        $export = new class($headers, $sample) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithTitle {
            public function __construct(private array $headers, private array $sample) {}

            public function array(): array
            {
                return array_merge([$this->headers], $this->sample);
            }

            public function title(): string
            {
                return 'Template Soal';
            }
        };

        return app(\Maatwebsite\Excel\Excel::class)->download(
            $export,
            'template-import-soal.xlsx',
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

    public function destroy(Request $request, QuestionBank $question_bank): JsonResponse
    {
        $institutionId = $this->resolveInstitutionId($request);
        $sameInstitution = $question_bank->institution_id == $institutionId;
        $bankAccessible = $question_bank->bank_soal_id && BankSoal::accessibleBy($request)->where('id', $question_bank->bank_soal_id)->exists();
        if (!$sameInstitution && !$bankAccessible) {
            return response()->json(['message' => 'Soal tidak ditemukan.'], 404);
        }
        $question_bank->delete();
        return response()->json(['message' => 'Soal dihapus.']);
    }

    /**
     * Duplicate a question (same bank, same stimulus if any, copied options/matching).
     */
    public function duplicate(Request $request, QuestionBank $question_bank): JsonResponse
    {
        $institutionId = $this->resolveInstitutionId($request);
        $sameInstitution = $question_bank->institution_id == $institutionId;
        $bankAccessible = $question_bank->bank_soal_id && BankSoal::accessibleBy($request)->where('id', $question_bank->bank_soal_id)->exists();
        if (!$sameInstitution && !$bankAccessible) {
            return response()->json(['message' => 'Soal tidak ditemukan.'], 404);
        }

        $question_bank->load(['options']);

        $copy = $question_bank->replicate([
            'created_at',
            'updated_at',
        ]);
        $plain = trim(html_entity_decode(strip_tags((string) $question_bank->body), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $suffix = ' (salinan)';
        if (! str_ends_with($plain, '(salinan)')) {
            $copy->body = rtrim((string) $question_bank->body).$suffix;
        }
        if ($question_bank->bank_soal_id) {
            $maxOrder = (int) QuestionBank::query()->where('bank_soal_id', $question_bank->bank_soal_id)->max('sort_order');
            $copy->sort_order = $maxOrder > 0 ? $maxOrder + 1 : 1;
        }
        $copy->save();

        foreach ($question_bank->options as $opt) {
            QuestionOption::create([
                'question_bank_id' => $copy->id,
                'option_key' => $opt->option_key,
                'body' => $opt->body,
                'is_correct' => $opt->is_correct,
                'option_weight' => $opt->option_weight,
                'sort_order' => $opt->sort_order,
            ]);
        }

        $copy->load(['subject', 'stimulus', 'options']);

        return (new QuestionBankResource($copy))->response()->setStatusCode(201);
    }

    private function sanitizeQuestionHtml(string $html): string
    {
        return HtmlPurifier::sanitizeQuestion($html, 'question');
    }
}
