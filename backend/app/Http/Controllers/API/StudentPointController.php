<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PointThresholdResource;
use App\Models\Student;
use App\Services\StudentPointService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StudentPointController extends Controller
{
    public function __construct(
        protected StudentPointService $pointService
    ) {}

    /**
     * List students with point summary (violation_pts, achievement_pts, total_pts, required_action).
     */
    public function index(Request $request): JsonResponse
    {
        $institutionId = $request->user()->institution_id;
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
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

        $items = $students->getCollection()->map(function (Student $student) use ($institutionId) {
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
     * Get point summary for a student: violation_pts, achievement_pts, total_pts, required_action.
     */
    public function summary(Request $request, int $studentId): JsonResponse
    {
        $institutionId = $request->user()->institution_id;
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
