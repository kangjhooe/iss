<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PointThresholdResource;
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
     * List students with point summary (violation_pts, achievement_pts, total_pts, required_action).
     * Optional: needs_action=1 hanya mengembalikan siswa yang perlu tindakan (skor > 0).
     */
    public function index(Request $request): JsonResponse
    {
        $institutionId = $request->user()->institution_id;
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $needsActionOnly = $request->boolean('needs_action');

        if ($needsActionOnly) {
            return $this->indexNeedsActionOnly($request, $institutionId);
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

        $items = $this->mapStudentsToPointItems($students->getCollection(), $institutionId);
        $students->setCollection($items);
        return response()->json([
            'data' => $students->items(),
            'meta' => [
                'current_page' => $students->currentPage(),
                'last_page' => $students->lastPage(),
                'per_page' => $students->perPage(),
                'total' => $students->total(),
            ],
        ]);
    }

    /**
     * List only students who need action (total_points > 0).
     */
    protected function indexNeedsActionOnly(Request $request, int $institutionId): JsonResponse
    {
        $studentIds = Violation::where('institution_id', $institutionId)
            ->distinct()
            ->pluck('student_id');

        if ($studentIds->isEmpty()) {
            return response()->json([
                'data' => [],
                'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 15, 'total' => 0],
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
        $items = $this->mapStudentsToPointItems($all, $institutionId);
        $filtered = $items->filter(fn ($row) => $row['required_action'] !== null)->values();

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
            ],
        ]);
    }

    /**
     * @param  Collection<int, Student>  $students
     * @return Collection<int, array<string, mixed>>
     */
    protected function mapStudentsToPointItems(Collection $students, int $institutionId): Collection
    {
        return $students->map(function (Student $student) use ($institutionId) {
            $summary = $this->pointService->getPointSummary($student->id, $institutionId);
            $requiredAction = $this->pointService->getRequiredAction($institutionId, $summary['total_points']);
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
                'required_action' => $requiredAction ? [
                    'id' => $requiredAction->id,
                    'action_name' => $requiredAction->action_name,
                    'point_min' => $requiredAction->point_min,
                    'point_max' => $requiredAction->point_max,
                    'description' => $requiredAction->description,
                ] : null,
            ];
        });
    }

    /**
     * Get point summary for a student: violation_pts, achievement_pts, total_pts, required_action.
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

        $summary = $this->pointService->getPointSummary($studentId, $institutionId);
        $requiredAction = $this->pointService->getRequiredAction($institutionId, $summary['total_points']);

        return response()->json([
            'data' => [
                'student_id' => $studentId,
                'violation_points' => $summary['violation_points'],
                'achievement_points' => $summary['achievement_points'],
                'total_points' => $summary['total_points'],
                'achievement_bank' => $summary['achievement_bank'] ?? $summary['achievement_points'],
                'required_action' => $requiredAction ? (new PointThresholdResource($requiredAction))->toArray($request) : null,
            ],
        ]);
    }
}
