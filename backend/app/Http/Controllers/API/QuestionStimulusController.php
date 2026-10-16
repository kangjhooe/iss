<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Requests\StoreQuestionStimulusRequest;
use App\Http\Resources\QuestionStimulusResource;
use App\Models\BankSoal;
use App\Models\QuestionStimulus;
use App\Services\HtmlPurifier;
use App\Services\StimulusMediaNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class QuestionStimulusController extends Controller
{
    use ResolvesInstitution;

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (! $institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $bankSoalId = $request->get('bank_soal_id');
            $subjectId = $request->get('subject_id');

            if ($bankSoalId) {
                $bank = BankSoal::accessibleBy($request)->find($bankSoalId);
                if (! $bank) {
                    return response()->json(['message' => 'Bank soal tidak ditemukan.'], 404);
                }
                $query = QuestionStimulus::where('bank_soal_id', $bank->id)
                    ->withCount('questions');
            } elseif ($subjectId) {
                $query = QuestionStimulus::forInstitution($institutionId)
                    ->where(function ($q) use ($subjectId) {
                        $q->where('subject_id', $subjectId)
                            ->orWhereHas('bankSoal', fn ($b) => $b->where('subject_id', $subjectId));
                    })
                    ->withCount('questions');
            } else {
                return response()->json(['message' => 'bank_soal_id atau subject_id wajib diisi.'], 422);
            }

            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('content', 'like', '%' . $search . '%')
                        ->orWhere('title', 'like', '%' . $search . '%');
                });
            }

            $query->orderByDesc('created_at');
            $perPage = min($request->get('per_page', 15), 100);
            $items = $query->paginate($perPage);

            return QuestionStimulusResource::collection($items);
        } catch (\Exception $e) {
            Log::error('QuestionStimulus index failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal mengambil data stimulus.', 'error' => config('app.debug') ? $e->getMessage() : null], 500);
        }
    }

    public function store(StoreQuestionStimulusRequest $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (! $institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $bankSoalId = (int) $request->validated('bank_soal_id');
            $bank = BankSoal::accessibleBy($request)->find($bankSoalId);
            if (! $bank) {
                return response()->json(['message' => 'Bank soal tidak ditemukan.'], 404);
            }

            $data = $this->prepareStimulusData($request->validated(), true);
            $data['institution_id'] = $bank->institution_id;
            $data['bank_soal_id'] = $bankSoalId;
            $stimulus = QuestionStimulus::create($data);

            return (new QuestionStimulusResource($stimulus))->response()->setStatusCode(201);
        } catch (\Exception $e) {
            Log::error('QuestionStimulus store failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal membuat stimulus.', 'error' => config('app.debug') ? $e->getMessage() : null], 500);
        }
    }

    public function show(Request $request, QuestionStimulus $question_stimulus): QuestionStimulusResource|JsonResponse
    {
        $bank = $question_stimulus->bank_soal_id
            ? BankSoal::accessibleBy($request)->find($question_stimulus->bank_soal_id)
            : null;
        $sameInstitution = $question_stimulus->institution_id == $this->resolveInstitutionId($request);
        if (! $sameInstitution && ! $bank) {
            return response()->json(['message' => 'Stimulus tidak ditemukan.'], 404);
        }

        return new QuestionStimulusResource($question_stimulus);
    }

    public function update(StoreQuestionStimulusRequest $request, QuestionStimulus $question_stimulus): QuestionStimulusResource|JsonResponse
    {
        $bank = $question_stimulus->bank_soal_id
            ? BankSoal::accessibleBy($request)->find($question_stimulus->bank_soal_id)
            : null;
        $sameInstitution = $question_stimulus->institution_id == $this->resolveInstitutionId($request);
        if (! $sameInstitution && ! $bank) {
            return response()->json(['message' => 'Stimulus tidak ditemukan.'], 404);
        }
        $data = $this->prepareStimulusData($request->validated(), false, $question_stimulus);
        unset($data['bank_soal_id']);
        $question_stimulus->update($data);

        return new QuestionStimulusResource($question_stimulus->fresh());
    }

    public function destroy(Request $request, QuestionStimulus $question_stimulus): JsonResponse
    {
        $bank = $question_stimulus->bank_soal_id
            ? BankSoal::accessibleBy($request)->find($question_stimulus->bank_soal_id)
            : null;
        $sameInstitution = $question_stimulus->institution_id == $this->resolveInstitutionId($request);
        if (! $sameInstitution && ! $bank) {
            return response()->json(['message' => 'Stimulus tidak ditemukan.'], 404);
        }
        $question_stimulus->delete();

        return response()->json(['message' => 'Stimulus dihapus.']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareStimulusData(array $data, bool $isCreate, ?QuestionStimulus $existing = null): array
    {
        if (array_key_exists('content', $data)) {
            $content = $data['content'] ?? '';
            $data['content'] = $this->sanitizeStimulusHtml(is_string($content) ? $content : (string) json_encode($content));
        } elseif ($isCreate) {
            $data['content'] = '';
        }

        $hasMediaKey = array_key_exists('media', $data);
        if ($hasMediaKey || $isCreate) {
            $media = StimulusMediaNormalizer::normalize($data['media'] ?? []);
            $data['media'] = $media;
        } else {
            unset($data['media']);
            $media = is_array($existing?->media) ? $existing->media : [];
        }

        $contentForType = (string) ($data['content'] ?? $existing?->content ?? '');
        $data['type'] = $data['type'] ?? StimulusMediaNormalizer::inferType($media, $contentForType);

        if ($hasMediaKey && $media !== [] && empty($data['attachment_path'])) {
            $firstFile = collect($media)->first(fn ($m) => in_array($m['type'], ['image', 'audio', 'video'], true));
            if ($firstFile && str_contains((string) $firstFile['url'], '/storage/')) {
                $path = preg_replace('#^.*?/storage/#', '', (string) $firstFile['url']);
                $data['attachment_path'] = $path ?: null;
            }
        }

        return $data;
    }

    private function sanitizeStimulusHtml(string $html): string
    {
        $html = is_array($html) ? '' : (string) $html;

        return HtmlPurifier::sanitizeQuestion($html, 'question');
    }
}
