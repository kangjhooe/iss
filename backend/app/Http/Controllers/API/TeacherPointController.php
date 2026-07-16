<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\TeacherAchievement;
use App\Models\TeacherViolation;
use App\Services\TeacherPointService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class TeacherPointController extends Controller
{
    public function __construct(
        protected TeacherPointService $pointService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $institutionId = $request->user()->institution_id;
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $this->pointService->ensureDefaultTypes($institutionId);
        $this->pointService->ensureDefaultRewards($institutionId);
        $this->pointService->ensureDefaultViolationTypes($institutionId);

        [$academicYearId, $semesterId] = $this->pointService->resolvePeriodFromRequest($request, $institutionId);

        $query = Employee::where('institution_id', $institutionId)
            ->where('status', 'Aktif')
            ->select('id', 'name', 'nip', 'nuptk', 'type', 'subject', 'status')
            ->orderBy('name');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('nip', 'like', "%{$s}%")
                    ->orWhere('nuptk', 'like', "%{$s}%");
            });
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->boolean('with_points_only')) {
            $employeeIds = TeacherAchievement::forInstitution($institutionId)
                ->approved()
                ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
                ->when($semesterId, fn ($q) => $q->where('semester_id', $semesterId))
                ->distinct()
                ->pluck('employee_id');
            $query->whereIn('id', $employeeIds);
        }

        $perPage = min((int) $request->get('per_page', 15), 50);
        $employees = $query->paginate($perPage);

        $items = $this->mapEmployeesToPointItems(
            $employees->getCollection(),
            $institutionId,
            $academicYearId,
            $semesterId
        );
        $employees->setCollection($items);

        $pendingCount = (int) TeacherAchievement::forInstitution($institutionId)->pending()->count();
        $pendingViolationCount = (int) TeacherViolation::forInstitution($institutionId)->pending()->count();

        return response()->json([
            'data' => $employees->items(),
            'meta' => [
                'current_page' => $employees->currentPage(),
                'last_page' => $employees->lastPage(),
                'per_page' => $employees->perPage(),
                'total' => $employees->total(),
                'pending_count' => $pendingCount,
                'pending_violation_count' => $pendingViolationCount,
                'academic_year_id' => $academicYearId,
                'semester_id' => $semesterId,
            ],
        ]);
    }

    public function summary(Request $request, int $employeeId): JsonResponse
    {
        $user = $request->user();
        $institutionId = $user->institution_id;

        if ($user->isTeacherOrStaff()) {
            $profile = $user->employeeProfile;
            if ($profile && (int) $profile->id === $employeeId) {
                $institutionId = $profile->institution_id;
            } elseif (!$user->permissions()->where('key', 'teacher_appreciation')->exists()
                && !$user->isAdminOrSuperAdmin()
                && !$user->isInstitutionAdmin()) {
                return response()->json(['message' => 'Anda hanya dapat melihat poin sendiri.'], 403);
            }
        }

        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $employee = Employee::where('id', $employeeId)
            ->where('institution_id', $institutionId)
            ->first();

        if (!$employee) {
            return response()->json(['message' => 'Guru tidak ditemukan.'], 404);
        }

        [$academicYearId, $semesterId] = $this->pointService->resolvePeriodFromRequest($request, $institutionId);
        $summary = $this->pointService->getPointSummary($employeeId, $institutionId, $academicYearId, $semesterId);
        $achievements = $this->pointService->listAchievementsForEmployee(
            $employeeId,
            $institutionId,
            $academicYearId,
            $semesterId,
            20
        );
        $violations = $this->pointService->listViolationsForEmployee(
            $employeeId,
            $institutionId,
            $academicYearId,
            $semesterId,
            20
        );
        $rewardLogs = $this->pointService->listRewardLogsForEmployee(
            $employeeId,
            $institutionId,
            $academicYearId,
            $semesterId,
            10
        );

        $rank = null;
        $leaderboard = $this->pointService->getLeaderboard($institutionId, $academicYearId, $semesterId, 500);
        $found = $leaderboard->firstWhere('employee_id', $employeeId);
        if ($found) {
            $rank = $found['rank'];
        }

        return response()->json([
            'data' => array_merge($summary, [
                'employee' => [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'nip' => $employee->nip,
                    'type' => $employee->type,
                    'subject' => $employee->subject,
                ],
                'rank' => $rank,
                'achievements' => $achievements->values()->all(),
                'violations' => $violations->values()->all(),
                'reward_logs' => $rewardLogs->values()->all(),
            ]),
        ]);
    }

    public function leaderboard(Request $request): JsonResponse
    {
        $institutionId = $request->user()->institution_id;
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        [$academicYearId, $semesterId] = $this->pointService->resolvePeriodFromRequest($request, $institutionId);
        $limit = min((int) $request->get('limit', 20), 100);

        return response()->json([
            'data' => $this->pointService->getLeaderboard($institutionId, $academicYearId, $semesterId, $limit)->all(),
            'meta' => [
                'academic_year_id' => $academicYearId,
                'semester_id' => $semesterId,
            ],
        ]);
    }

    public function reportSummary(Request $request): JsonResponse
    {
        $institutionId = $request->user()->institution_id;
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        [$academicYearId, $semesterId] = $this->pointService->resolvePeriodFromRequest($request, $institutionId);

        return response()->json([
            'data' => $this->pointService->getReportSummary($institutionId, $academicYearId, $semesterId),
        ]);
    }

    /**
     * Lightweight employee list for appreciation forms (no teacher module required).
     */
    public function employees(Request $request): JsonResponse
    {
        $institutionId = $request->user()->institution_id;
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $query = Employee::where('institution_id', $institutionId)
            ->where('status', 'Aktif')
            ->select('id', 'name', 'nip', 'nuptk', 'type', 'subject')
            ->orderBy('name');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('nip', 'like', "%{$s}%");
            });
        }

        $items = $query->limit(300)->get();

        return response()->json(['data' => $items]);
    }

    /**
     * @param  Collection<int, Employee>  $employees
     * @return Collection<int, array<string, mixed>>
     */
    protected function mapEmployeesToPointItems(
        Collection $employees,
        int $institutionId,
        ?int $academicYearId,
        ?int $semesterId
    ): Collection {
        return $employees->map(function (Employee $employee) use ($institutionId, $academicYearId, $semesterId) {
            $summary = $this->pointService->getPointSummary(
                $employee->id,
                $institutionId,
                $academicYearId,
                $semesterId
            );

            $achievements = $this->pointService->listAchievementsForEmployee(
                $employee->id,
                $institutionId,
                $academicYearId,
                $semesterId,
                10,
                TeacherAchievement::STATUS_APPROVED
            );
            $violations = $this->pointService->listViolationsForEmployee(
                $employee->id,
                $institutionId,
                $academicYearId,
                $semesterId,
                10,
                TeacherViolation::STATUS_APPROVED
            );

            return [
                'employee_id' => $employee->id,
                'employee' => [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'nip' => $employee->nip,
                    'nuptk' => $employee->nuptk,
                    'type' => $employee->type,
                    'subject' => $employee->subject,
                    'status' => $employee->status,
                ],
                'achievement_points' => $summary['achievement_points'],
                'violation_points' => $summary['violation_points'],
                'total_points' => $summary['total_points'],
                'approved_count' => $summary['approved_count'],
                'violation_count' => $summary['violation_count'],
                'pending_count' => $summary['pending_count'],
                'pending_violation_count' => $summary['pending_violation_count'],
                'by_category' => $summary['by_category'],
                'violations_by_category' => $summary['violations_by_category'],
                'matched_reward' => $summary['matched_reward'],
                'unlocked_rewards' => $summary['unlocked_rewards'],
                'achievements' => $achievements->values()->all(),
                'violations' => $violations->values()->all(),
                'academic_year_id' => $academicYearId,
                'semester_id' => $semesterId,
            ];
        });
    }
}
