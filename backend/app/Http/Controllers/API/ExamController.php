<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Requests\StoreExamRequest;
use App\Http\Requests\UpdateExamRequest;
use App\Http\Resources\ExamResource;
use App\Models\Exam;
use App\Models\QuestionBank;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class ExamController extends Controller
{
    use ResolvesInstitution;
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $query = Exam::forInstitution($institutionId)->with(['subject', 'sessions' => fn ($q) => $q->withCount('participants')]);

            if ($request->filled('subject_id')) {
                $query->where('subject_id', $request->get('subject_id'));
            }
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where('name', 'like', '%' . $search . '%');
            }

            $query->orderByDesc('created_at');
            $perPage = min($request->get('per_page', 15), 100);
            $items = $query->paginate($perPage);

            return ExamResource::collection($items);
        } catch (\Exception $e) {
            Log::error('Exam index failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil data ujian.', 'error' => config('app.debug') ? $e->getMessage() : null], 500);
        }
    }

    public function store(StoreExamRequest $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $data = $request->validated();
            $data['institution_id'] = $institutionId;
            $data['created_by'] = $request->user()->id;
            if (!isset($data['duration_minutes']) || $data['duration_minutes'] === null) {
                $data['duration_minutes'] = 60;
            }
            $exam = Exam::create($data);
            $exam->load(['subject']);

            return (new ExamResource($exam))->response()->setStatusCode(201);
        } catch (\Exception $e) {
            Log::error('Exam store failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal membuat ujian.', 'error' => config('app.debug') ? $e->getMessage() : null], 500);
        }
    }

    public function show(Request $request, Exam $exam): ExamResource|JsonResponse
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId || $exam->institution_id != $institutionId) {
            return response()->json(['message' => 'Ujian tidak ditemukan.'], 404);
        }
        $exam->load(['subject', 'sessions', 'examQuestions.questionBank']);
        return new ExamResource($exam);
    }

    /**
     * Get exam by code (for URL-friendly routing). Code is unique per institution.
     */
    public function showByCode(Request $request, string $code): ExamResource|JsonResponse
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }
        $exam = Exam::forInstitution($institutionId)
            ->where('code', $code)
            ->first();
        if (!$exam) {
            return response()->json(['message' => 'Ujian tidak ditemukan.'], 404);
        }
        $exam->load(['subject', 'sessions', 'examQuestions.questionBank']);
        return new ExamResource($exam);
    }

    public function update(UpdateExamRequest $request, Exam $exam): ExamResource|JsonResponse
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId || $exam->institution_id != $institutionId) {
            return response()->json(['message' => 'Ujian tidak ditemukan.'], 404);
        }
        $exam->update($request->validated());
        $exam->load(['subject']);
        return new ExamResource($exam);
    }

    public function destroy(Request $request, Exam $exam): JsonResponse
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId || $exam->institution_id != $institutionId) {
            return response()->json(['message' => 'Ujian tidak ditemukan.'], 404);
        }
        $exam->delete();
        return response()->json(['message' => 'Ujian dihapus.']);
    }

    /**
     * Mata pelajaran yang siap diujikan (punya minimal 1 soal di bank soal).
     */
    public function subjectsReady(Request $request): JsonResponse
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }
        $subjects = Subject::forInstitution($institutionId)
            ->withCount('questionBanks')
            ->having('question_banks_count', '>', 0)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'institution_id']);
        $data = $subjects->map(fn ($s) => [
            'id' => $s->id,
            'code' => $s->code,
            'name' => $s->name,
            'questions_count' => (int) $s->question_banks_count,
        ]);
        return response()->json(['data' => $data]);
    }

    /**
     * Attach questions to exam (replace existing).
     * Hanya soal yang institution_id-nya sama dengan ujian yang boleh dilampirkan.
     */
    public function attachQuestions(Request $request, Exam $exam): JsonResponse
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId || $exam->institution_id != $institutionId) {
            return response()->json(['message' => 'Ujian tidak ditemukan.'], 404);
        }
        $request->validate(['question_bank_ids' => 'required|array', 'question_bank_ids.*' => 'exists:question_bank,id']);
        $ids = array_map('intval', $request->input('question_bank_ids'));

        $allowedIds = QuestionBank::where('institution_id', $institutionId)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->flip()
            ->all();
        foreach ($ids as $qbId) {
            if (!isset($allowedIds[$qbId])) {
                return response()->json([
                    'message' => 'Beberapa soal tidak ditemukan atau bukan milik institusi Anda.',
                ], 422);
            }
        }

        $exam->examQuestions()->delete();
        foreach ($ids as $i => $qbId) {
            $exam->examQuestions()->create(['question_bank_id' => $qbId, 'sort_order' => $i]);
        }
        $exam->load(['examQuestions.questionBank']);
        return response()->json(['message' => 'Soal berhasil diset.', 'exam' => new ExamResource($exam)]);
    }
}
