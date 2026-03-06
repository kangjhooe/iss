<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Requests\StoreExamSessionRequest;
use App\Http\Requests\UpdateExamSessionRequest;
use App\Http\Resources\ExamSessionResource;
use App\Models\Exam;
use App\Models\ExamSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ExamSessionController extends Controller
{
    use ResolvesInstitution;

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $query = ExamSession::query()
                ->whereHas('exam', fn ($q) => $q->where('institution_id', $institutionId))
                ->with(['exam.subject'])
                ->withCount('participants');

            if ($request->filled('exam_id')) {
                $query->where('exam_id', $request->get('exam_id'));
            }
            if ($request->filled('status')) {
                $query->where('status', $request->get('status'));
            }

            $query->orderByDesc('created_at');
            $perPage = min($request->get('per_page', 15), 100);
            $items = $query->paginate($perPage);

            return ExamSessionResource::collection($items);
        } catch (\Exception $e) {
            Log::error('ExamSession index failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil data sesi ujian.', 'error' => config('app.debug') ? $e->getMessage() : null], 500);
        }
    }

    public function store(StoreExamSessionRequest $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }
            $examId = (int) $request->input('exam_id');
            $exam = Exam::where('id', $examId)->where('institution_id', $institutionId)->first();
            if (!$exam) {
                return response()->json(['message' => 'Ujian tidak ditemukan atau bukan milik institusi Anda.'], 404);
            }

            $data = $request->validated();
            $data['exam_id'] = $exam->id;
            $session = ExamSession::create($data);
            $session->load(['exam.subject']);

            return (new ExamSessionResource($session))->response()->setStatusCode(201);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Ujian tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('ExamSession store failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal membuat sesi ujian.', 'error' => config('app.debug') ? $e->getMessage() : null], 500);
        }
    }

    public function show(Request $request, ExamSession $exam_session): ExamSessionResource|JsonResponse
    {
        if ($exam_session->exam->institution_id != $this->resolveInstitutionId($request)) {
            return response()->json(['message' => 'Sesi ujian tidak ditemukan.'], 404);
        }
        $exam_session->load(['exam.subject', 'participants.student']);
        return new ExamSessionResource($exam_session);
    }

    public function update(UpdateExamSessionRequest $request, ExamSession $exam_session): ExamSessionResource|JsonResponse
    {
        if ($exam_session->exam->institution_id != $this->resolveInstitutionId($request)) {
            return response()->json(['message' => 'Sesi ujian tidak ditemukan.'], 404);
        }
        $exam_session->update($request->validated());
        $exam_session->load(['exam.subject']);
        return new ExamSessionResource($exam_session);
    }

    public function destroy(Request $request, ExamSession $exam_session): JsonResponse
    {
        if ($exam_session->exam->institution_id != $this->resolveInstitutionId($request)) {
            return response()->json(['message' => 'Sesi ujian tidak ditemukan.'], 404);
        }
        $exam_session->delete();
        return response()->json(['message' => 'Sesi ujian dihapus.']);
    }
}
