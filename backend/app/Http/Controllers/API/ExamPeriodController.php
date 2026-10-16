<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Controllers\Controller;
use App\Http\Requests\AddExamPeriodSubjectsRequest;
use App\Http\Requests\StoreExamPeriodRequest;
use App\Http\Requests\SyncExamPeriodParticipantsRequest;
use App\Http\Requests\UpdateExamPeriodRequest;
use App\Http\Resources\ExamPeriodResource;
use App\Http\Resources\ExamResource;
use App\Models\ExamPeriod;
use App\Services\ExamPeriodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class ExamPeriodController extends Controller
{
    use ResolvesInstitution;

    public function __construct(
        protected ExamPeriodService $periodService
    ) {}

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $perPage = min((int) $request->get('per_page', 15), 100);
            $result = $this->periodService->listForInstitution(
                $institutionId,
                $request->only(['status', 'search']),
                $perPage
            );

            return ExamPeriodResource::collection($result);
        } catch (\Exception $e) {
            Log::error('ExamPeriod index failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mengambil data gelombang ujian.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function store(StoreExamPeriodRequest $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $period = $this->periodService->create(
                $institutionId,
                $request->validated(),
                $request->user()->id
            );

            return response()->json([
                'message' => 'Gelombang ujian berhasil dibuat.',
                'data' => new ExamPeriodResource($period),
            ], 201);
        } catch (\Exception $e) {
            Log::error('ExamPeriod store failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal membuat gelombang ujian.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function show(Request $request, ExamPeriod $exam_period): ExamPeriodResource|JsonResponse
    {
        if ($denied = $this->denyUnlessOwns($request, $exam_period)) {
            return $denied;
        }

        return new ExamPeriodResource($this->loadDetail($exam_period));
    }

    public function update(UpdateExamPeriodRequest $request, ExamPeriod $exam_period): JsonResponse
    {
        try {
            if ($denied = $this->denyUnlessOwns($request, $exam_period)) {
                return $denied;
            }

            $period = $this->periodService->update($exam_period, $request->validated());
            $period->loadCount('exams');

            return response()->json([
                'message' => 'Gelombang ujian berhasil diperbarui.',
                'data' => new ExamPeriodResource($period),
            ]);
        } catch (\Exception $e) {
            Log::error('ExamPeriod update failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal memperbarui gelombang ujian.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function destroy(Request $request, ExamPeriod $exam_period): JsonResponse
    {
        try {
            if ($denied = $this->denyUnlessOwns($request, $exam_period)) {
                return $denied;
            }

            $this->periodService->delete($exam_period);

            return response()->json(['message' => 'Gelombang ujian dihapus.']);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('ExamPeriod destroy failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal menghapus gelombang ujian.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Admin: tambah mapel ke gelombang (buat ujian mapel + sesi utama).
     */
    public function addSubjects(AddExamPeriodSubjectsRequest $request, ExamPeriod $exam_period): JsonResponse
    {
        if ($denied = $this->denyUnlessOwns($request, $exam_period)) {
            return $denied;
        }

        $data = $request->validated();
        $created = $this->periodService->addSubjects(
            $exam_period,
            $data['subject_ids'],
            $request->user()->id,
            (int) ($data['duration_minutes'] ?? 90)
        );

        return response()->json([
            'message' => count($created).' mapel ditambahkan.',
            'created' => ExamResource::collection(collect($created)),
            'period' => new ExamPeriodResource(
                $exam_period->fresh()->load($this->detailRelations())->loadCount('exams')
            ),
        ]);
    }

    /**
     * Admin: isi peserta dari kelas ke sesi utama semua (atau sebagian) mapel.
     */
    public function syncParticipants(SyncExamPeriodParticipantsRequest $request, ExamPeriod $exam_period): JsonResponse
    {
        if ($denied = $this->denyUnlessOwns($request, $exam_period)) {
            return $denied;
        }

        $data = $request->validated();
        $summary = $this->periodService->syncParticipantsByClasses(
            $exam_period,
            $data['class_ids'],
            $data['exam_ids'] ?? null
        );

        return response()->json([
            'message' => "Peserta disalin ke {$summary['exams']} mapel ({$summary['added']} penambahan).",
            'summary' => $summary,
            'period' => new ExamPeriodResource(
                $exam_period->fresh()->load($this->detailRelations())->loadCount('exams')
            ),
        ]);
    }

    private function denyUnlessOwns(Request $request, ExamPeriod $period): ?JsonResponse
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId || (int) $period->institution_id !== (int) $institutionId) {
            return response()->json(['message' => 'Gelombang ujian tidak ditemukan.'], 404);
        }

        return null;
    }

    private function loadDetail(ExamPeriod $period): ExamPeriod
    {
        $period->load($this->detailRelations());
        $period->loadCount('exams');
        $period->loadCount(['exams as exams_ready_count' => function ($q) {
            $q->whereHas('examQuestions');
        }]);

        return $period;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function detailRelations(): array
    {
        return [
            'exams.subject',
            'exams.sessions' => fn ($q) => $q->withCount('participants'),
            'exams.examQuestions',
        ];
    }
}
