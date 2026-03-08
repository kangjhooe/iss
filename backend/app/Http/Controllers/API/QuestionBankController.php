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
                $query->where('stimulus_id', $request->get('stimulus_id'));
            }
            if ($request->filled('type')) {
                $query->where('type', $request->get('type'));
            }
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where('body', 'like', '%' . $search . '%');
            }

            $query->orderByDesc('created_at');
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

            $data['body'] = $this->sanitizeQuestionHtml($data['body'] ?? '');
            if (!empty($options)) {
                foreach ($options as $i => $opt) {
                    $options[$i]['body'] = $this->sanitizeQuestionHtml($opt['body'] ?? '');
                }
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

    private function sanitizeQuestionHtml(string $html): string
    {
        return HtmlPurifier::sanitizeQuestion($html, 'question');
    }
}
