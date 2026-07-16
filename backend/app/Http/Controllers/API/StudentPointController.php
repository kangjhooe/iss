<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PointThresholdResource;
use App\Models\Institution;
use App\Models\Student;
use App\Models\Violation;
use App\Services\StudentPointService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class StudentPointController extends Controller
{
    public function __construct(
        protected StudentPointService $pointService
    ) {}

    /**
     * List students with point summary (scoped to academic year / semester).
     * needs_action=1 → only students with skor > 0 in the period.
     */
    public function index(Request $request): JsonResponse
    {
        $institutionId = $request->user()->institution_id;
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        [$academicYearId, $semesterId] = $this->resolvePeriod($request, $institutionId);

        if ($request->boolean('needs_action')) {
            return $this->indexNeedsActionOnly($request, $institutionId, $academicYearId, $semesterId);
        }

        $query = Student::where('institution_id', $institutionId)
            ->select('id', 'name', 'nis', 'nisn', 'class', 'status')
            ->orderBy('name');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('nis', 'like', "%{$s}%")
                    ->orWhere('nisn', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = min($request->get('per_page', 15), 50);
        $students = $query->paginate($perPage);

        $items = $this->mapStudentsToPointItems($students->getCollection(), $institutionId, $academicYearId, $semesterId);
        $students->setCollection($items);

        return response()->json([
            'data' => $students->items(),
            'meta' => [
                'current_page' => $students->currentPage(),
                'last_page' => $students->lastPage(),
                'per_page' => $students->perPage(),
                'total' => $students->total(),
                'academic_year_id' => $academicYearId,
                'semester_id' => $semesterId,
            ],
        ]);
    }

    /**
     * Students with skor > 0 in the period (regardless of threshold match).
     */
    protected function indexNeedsActionOnly(
        Request $request,
        int $institutionId,
        ?int $academicYearId,
        ?int $semesterId
    ): JsonResponse {
        $violationQuery = Violation::where('institution_id', $institutionId);
        if ($academicYearId) {
            $violationQuery->where('academic_year_id', $academicYearId);
        }
        if ($semesterId) {
            $violationQuery->where('semester_id', $semesterId);
        }
        $studentIds = $violationQuery->distinct()->pluck('student_id');

        if ($studentIds->isEmpty()) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 15,
                    'total' => 0,
                    'academic_year_id' => $academicYearId,
                    'semester_id' => $semesterId,
                ],
            ]);
        }

        $query = Student::where('institution_id', $institutionId)
            ->whereIn('id', $studentIds)
            ->select('id', 'name', 'nis', 'nisn', 'class', 'status')
            ->orderBy('name');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('nis', 'like', "%{$s}%")
                    ->orWhere('nisn', 'like', "%{$s}%");
            });
        }

        $all = $query->get();
        $items = $this->mapStudentsToPointItems($all, $institutionId, $academicYearId, $semesterId);

        // Skor > 0; optionally pending_only = belum dicatat tindakannya
        $withScore = $items->filter(fn ($row) => $row['total_points'] > 0)->values();
        $pendingCount = $withScore->filter(fn ($row) => $row['action_pending'] === true)->count();

        $filtered = $withScore;
        if ($request->boolean('pending_only')) {
            $filtered = $withScore->filter(fn ($row) => $row['action_pending'] === true)->values();
        }

        // Sort: pending first, then by skor desc
        $filtered = $filtered->sortBy([
            ['action_pending', 'desc'],
            ['total_points', 'desc'],
        ])->values();

        $perPage = min($request->get('per_page', 15), 50);
        $page = max(1, (int) $request->get('page', 1));
        $total = $filtered->count();
        $lastPage = $perPage > 0 ? (int) ceil($total / $perPage) : 1;
        $offset = ($page - 1) * $perPage;
        $paginated = $filtered->slice($offset, $perPage)->values();

        return response()->json([
            'data' => $paginated->all(),
            'meta' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
                'pending_count' => $pendingCount,
                'academic_year_id' => $academicYearId,
                'semester_id' => $semesterId,
            ],
        ]);
    }

    /**
     * @param  Collection<int, Student>  $students
     * @return Collection<int, array<string, mixed>>
     */
    protected function mapStudentsToPointItems(
        Collection $students,
        int $institutionId,
        ?int $academicYearId,
        ?int $semesterId
    ): Collection {
        return $students->map(function (Student $student) use ($institutionId, $academicYearId, $semesterId) {
            $summary = $this->pointService->getPointSummary(
                $student->id,
                $institutionId,
                $academicYearId,
                $semesterId
            );
            $requiredAction = $this->pointService->getRequiredAction($institutionId, $summary['total_points']);
            $cycle = $this->pointService->evaluateActionCycle(
                $student->id,
                $institutionId,
                $summary['total_points'],
                $requiredAction,
                $academicYearId,
                $semesterId
            );

            $latestAction = null;
            if ($cycle['latest_action']) {
                $log = $cycle['latest_action'];
                $latestAction = [
                    'id' => $log->id,
                    'action_name' => $log->action_name,
                    'action_date' => $log->action_date?->format('Y-m-d'),
                    'notes' => $log->notes,
                    'score_at_action' => $log->score_at_action,
                    'recorder' => $log->recorder ? [
                        'id' => $log->recorder->id,
                        'name' => $log->recorder->name,
                    ] : null,
                ];
            }

            $violations = $this->pointService->listViolationsForStudent(
                $student->id,
                $institutionId,
                $academicYearId,
                $semesterId,
                15
            );
            $achievements = $this->pointService->listAchievementsForStudent(
                $student->id,
                $institutionId,
                $academicYearId,
                $semesterId,
                15
            );

            return [
                'student_id' => $student->id,
                'student' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'nis' => $student->nis,
                    'nisn' => $student->nisn,
                    'class' => $student->class,
                    'status' => $student->status,
                ],
                'violation_points' => $summary['violation_points'],
                'achievement_points' => $summary['achievement_points'],
                'total_points' => $summary['total_points'],
                'achievement_bank' => $summary['achievement_bank'] ?? $summary['achievement_points'],
                'academic_year_id' => $academicYearId,
                'semester_id' => $semesterId,
                'required_action' => $requiredAction ? [
                    'id' => $requiredAction->id,
                    'action_name' => $requiredAction->action_name,
                    'point_min' => $requiredAction->point_min,
                    'point_max' => $requiredAction->point_max,
                    'description' => $requiredAction->description,
                ] : null,
                'action_pending' => $cycle['action_pending'],
                'action_fulfilled' => $cycle['action_fulfilled'],
                'reopen_reason' => $cycle['reopen_reason'],
                'score_at_action' => $cycle['score_at_action'],
                'new_points_since_action' => $cycle['new_points_since_action'],
                'open_after_action_count' => $cycle['open_after_action_count'],
                'latest_action' => $latestAction,
                'violations' => $violations->values()->all(),
                'violations_count' => $violations->count(),
                'achievements' => $achievements->values()->all(),
                'achievements_count' => $achievements->count(),
            ];
        });
    }

    /**
     * Point summary for one student (defaults to active period).
     */
    public function summary(Request $request, int $studentId): JsonResponse
    {
        $user = $request->user();
        $institutionId = $user->institution_id;
        if ($user->isStudent()) {
            $profile = $user->studentProfile;
            if (!$profile || (int) $profile->id !== $studentId) {
                return response()->json(['message' => 'Anda hanya dapat melihat poin sendiri.'], 403);
            }
            $institutionId = $profile->institution_id;
        }
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        [$academicYearId, $semesterId] = $this->resolvePeriod($request, $institutionId);
        $summary = $this->pointService->getPointSummary($studentId, $institutionId, $academicYearId, $semesterId);
        $requiredAction = $this->pointService->getRequiredAction($institutionId, $summary['total_points']);
        $cycle = $this->pointService->evaluateActionCycle(
            $studentId,
            $institutionId,
            $summary['total_points'],
            $requiredAction,
            $academicYearId,
            $semesterId
        );

        return response()->json([
            'data' => [
                'student_id' => $studentId,
                'violation_points' => $summary['violation_points'],
                'achievement_points' => $summary['achievement_points'],
                'total_points' => $summary['total_points'],
                'achievement_bank' => $summary['achievement_bank'] ?? $summary['achievement_points'],
                'academic_year_id' => $academicYearId,
                'semester_id' => $semesterId,
                'required_action' => $requiredAction
                    ? (new PointThresholdResource($requiredAction))->toArray($request)
                    : null,
                'action_pending' => $cycle['action_pending'],
                'action_fulfilled' => $cycle['action_fulfilled'],
                'reopen_reason' => $cycle['reopen_reason'],
                'score_at_action' => $cycle['score_at_action'],
                'new_points_since_action' => $cycle['new_points_since_action'],
                'open_after_action_count' => $cycle['open_after_action_count'],
            ],
        ]);
    }

    /**
     * Resolve academic year / semester from request, defaulting to institution active period.
     *
     * @return array{0: ?int, 1: ?int}
     */
    protected function resolvePeriod(Request $request, int $institutionId): array
    {
        $academicYearId = $request->filled('academic_year_id')
            ? (int) $request->academic_year_id
            : null;
        $semesterId = $request->filled('semester_id')
            ? (int) $request->semester_id
            : null;

        // Explicit empty string / "all" means no period filter
        if ($request->has('academic_year_id') && $request->academic_year_id === '') {
            $academicYearId = null;
        }
        if ($request->has('semester_id') && $request->semester_id === '') {
            $semesterId = null;
        }

        if (!$request->has('academic_year_id') || !$request->has('semester_id')) {
            $institution = Institution::find($institutionId);
            if ($institution) {
                if (!$request->has('academic_year_id') && $institution->active_academic_year_id) {
                    $academicYearId = (int) $institution->active_academic_year_id;
                }
                if (!$request->has('semester_id') && $institution->active_semester_id) {
                    $semesterId = (int) $institution->active_semester_id;
                }
            }
        }

        return [$academicYearId, $semesterId];
    }
}
