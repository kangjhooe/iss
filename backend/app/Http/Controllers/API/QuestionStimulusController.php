<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Requests\StoreQuestionStimulusRequest;
use App\Http\Resources\QuestionStimulusResource;
use App\Models\BankSoal;
use App\Models\QuestionStimulus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use App\Services\HtmlPurifier;

class QuestionStimulusController extends Controller
{
    use ResolvesInstitution;

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $bankSoalId = $request->get('bank_soal_id');
            if (!$bankSoalId) {
                return response()->json(['message' => 'bank_soal_id wajib diisi.'], 422);
            }

            $bank = BankSoal::accessibleBy($request)->find($bankSoalId);
            if (!$bank) {
                return response()->json(['message' => 'Bank soal tidak ditemukan.'], 404);
            }

            $query = QuestionStimulus::where('bank_soal_id', $bank->id)
                ->withCount('questions');

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
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $bankSoalId = (int) $request->validated('bank_soal_id');
            $bank = BankSoal::accessibleBy($request)->find($bankSoalId);
            if (!$bank) {
                return response()->json(['message' => 'Bank soal tidak ditemukan.'], 404);
            }

            $data = $request->validated();
            $data['institution_id'] = $bank->institution_id;
            $data['bank_soal_id'] = $bankSoalId;
            $content = $data['content'] ?? '';
            $data['content'] = $this->sanitizeStimulusHtml(is_string($content) ? $content : (string) json_encode($content));
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
        if (!$sameInstitution && !$bank) {
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
        if (!$sameInstitution && !$bank) {
            return response()->json(['message' => 'Stimulus tidak ditemukan.'], 404);
        }
        $data = $request->validated();
        $data['content'] = $this->sanitizeStimulusHtml($data['content'] ?? '');
        unset($data['bank_soal_id']);
        $question_stimulus->update($data);
        return new QuestionStimulusResource($question_stimulus);
    }

    public function destroy(Request $request, QuestionStimulus $question_stimulus): JsonResponse
    {
        $bank = $question_stimulus->bank_soal_id
            ? BankSoal::accessibleBy($request)->find($question_stimulus->bank_soal_id)
            : null;
        $sameInstitution = $question_stimulus->institution_id == $this->resolveInstitutionId($request);
        if (!$sameInstitution && !$bank) {
            return response()->json(['message' => 'Stimulus tidak ditemukan.'], 404);
        }
        $question_stimulus->delete();
        return response()->json(['message' => 'Stimulus dihapus.']);
    }

    private function sanitizeStimulusHtml(string $html): string
    {
        $html = is_array($html) ? '' : (string) $html;
        return HtmlPurifier::sanitizeQuestion($html, 'question');
    }
}
