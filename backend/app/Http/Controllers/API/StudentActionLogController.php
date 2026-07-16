<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentActionLogRequest;
use App\Http\Resources\StudentActionLogResource;
use App\Models\Institution;
use App\Models\StudentActionLog;
use App\Models\Violation;
use App\Services\StudentPointService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentActionLogController extends Controller
{
    public function __construct(
        protected StudentPointService $pointService
    ) {}

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $request->user()->institution_id;
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }
            $query = StudentActionLog::with(['student:id,name,nis,nisn', 'recorder:id,name'])
                ->forInstitution($institutionId)
                ->orderBy('action_date', 'desc');

            if ($request->filled('student_id')) {
                $query->where('student_id', $request->student_id);
            }
            if ($request->filled('academic_year_id')) {
                $query->where('academic_year_id', $request->academic_year_id);
            }
            if ($request->filled('semester_id')) {
                $query->where('semester_id', $request->semester_id);
            }

            $perPage = min($request->get('per_page', 15), 100);
            $items = $query->paginate($perPage);
            return StudentActionLogResource::collection($items);
        } catch (\Exception $e) {
            Log::error('StudentActionLog index failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil catatan tindakan.'], 500);
        }
    }

    public function store(StoreStudentActionLogRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $user->institution_id;
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }
            $student = \App\Models\Student::where('id', $request->student_id)
                ->where('institution_id', $institutionId)
                ->firstOrFail();

            $institution = Institution::find($institutionId);
            $academicYearId = $request->filled('academic_year_id')
                ? (int) $request->academic_year_id
                : ($institution?->active_academic_year_id);
            $semesterId = $request->filled('semester_id')
                ? (int) $request->semester_id
                : ($institution?->active_semester_id);

            $yearId = $academicYearId ? (int) $academicYearId : null;
            $semId = $semesterId ? (int) $semesterId : null;

            $currentScore = $this->pointService->getTotalPoint(
                $student->id,
                $institutionId,
                $yearId,
                $semId
            );

            $markResolved = $request->boolean('mark_violations_resolved');
            $actionDate = $request->action_date;

            $log = DB::transaction(function () use (
                $request,
                $user,
                $institutionId,
                $student,
                $yearId,
                $semId,
                $markResolved,
                $currentScore,
                $actionDate
            ) {
                $log = StudentActionLog::create([
                    'institution_id' => $institutionId,
                    'student_id' => $student->id,
                    'point_threshold_id' => $request->point_threshold_id,
                    'action_name' => $request->action_name,
                    'action_date' => $actionDate,
                    'recorded_by' => $user->id,
                    'notes' => $request->notes,
                    'score_at_action' => $currentScore,
                    'academic_year_id' => $yearId,
                    'semester_id' => $semId,
                ]);

                $this->syncViolationStatuses(
                    $institutionId,
                    $student->id,
                    $yearId,
                    $semId,
                    $request->action_name,
                    $actionDate,
                    $markResolved
                );

                return $log->load(['student', 'recorder']);
            });

            return (new StudentActionLogResource($log))->response()->setStatusCode(201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Siswa tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('StudentActionLog store failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mencatat tindakan.'], 500);
        }
    }

    /**
     * Update open violations with violation_date <= action_date only.
     * Pelanggaran setelah tanggal tindakan tetap terbuka.
     */
    protected function syncViolationStatuses(
        int $institutionId,
        int $studentId,
        ?int $academicYearId,
        ?int $semesterId,
        string $actionName,
        string $actionDate,
        bool $markResolved
    ): void {
        $query = Violation::where('institution_id', $institutionId)
            ->where('student_id', $studentId)
            ->whereIn('status', ['dicatat', 'sanksi_diberikan', 'follow_up'])
            ->whereDate('violation_date', '<=', $actionDate);

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }
        if ($semesterId) {
            $query->where('semester_id', $semesterId);
        }

        $newStatus = $markResolved ? 'selesai' : 'follow_up';
        $noteLine = sprintf(
            '[%s] Tindakan "%s" dicatat (menutup pelanggaran s.d. tanggal ini). Status → %s.',
            $actionDate,
            $actionName,
            $newStatus === 'selesai' ? 'Selesai' : 'Follow Up'
        );

        $query->get()->each(function (Violation $violation) use ($noteLine, $newStatus) {
            $existing = trim((string) $violation->follow_up_notes);
            $violation->update([
                'status' => $newStatus,
                'follow_up_notes' => $existing === '' ? $noteLine : $existing . "\n" . $noteLine,
            ]);
        });
    }

    public function byStudent(Request $request, int $studentId): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $request->user()->institution_id;
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }
            $query = StudentActionLog::with('recorder')
                ->forInstitution($institutionId)
                ->forStudent($studentId)
                ->orderBy('action_date', 'desc');

            if ($request->filled('academic_year_id')) {
                $query->where('academic_year_id', $request->academic_year_id);
            }
            if ($request->filled('semester_id')) {
                $query->where('semester_id', $request->semester_id);
            }

            $items = $query->paginate(20);
            return StudentActionLogResource::collection($items);
        } catch (\Exception $e) {
            Log::error('StudentActionLog byStudent failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil riwayat tindakan.'], 500);
        }
    }
}
