<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\TeacherAchievementResource;
use App\Http\Resources\TeacherViolationResource;
use App\Models\AcademicYear;
use App\Models\Employee;
use App\Models\Institution;
use App\Models\Semester;
use App\Models\TeacherAchievement;
use App\Models\TeacherViolation;
use App\Services\TeacherPointService;
use App\Support\InstitutionContext;
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
        $institutionId = InstitutionContext::resolveForUser($request->user(), $request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $this->pointService->ensureDefaultTypes($institutionId);
        $this->pointService->ensureDefaultRewards($institutionId);
        $this->pointService->ensureDefaultViolationTypes($institutionId);

        [$academicYearId, $semesterId] = $this->pointService->resolvePeriodFromRequest($request, $institutionId);

        $query = Employee::forInstitution($institutionId)
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
        $institutionId = InstitutionContext::resolveForUser($user, $request);

        if ($user->isTeacherOrStaff()) {
            $profile = $user->employeeProfile;
            if ($profile && (int) $profile->id === $employeeId) {
                // Self view: tetap di institusi aktif (bukan memaksa home employee)
                $institutionId = InstitutionContext::resolveForUser($user, $request)
                    ?: $profile->institution_id;
            } elseif (!$user->hasModuleAccess('teacher_appreciation')
                && !$user->isAdminOrSuperAdmin()
                && !$user->isInstitutionAdmin()) {
                return response()->json(['message' => 'Anda hanya dapat melihat poin sendiri.'], 403);
            }
        }

        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $employee = Employee::where('id', $employeeId)->first();
        if (!$employee || !InstitutionContext::employeeBelongsToInstitution($employee, (int) $institutionId)) {
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

        $rank = $this->pointService->findEmployeeRank(
            $employeeId,
            $institutionId,
            $academicYearId,
            $semesterId,
            $employee->type
        );

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
                'leaderboard_mode' => $this->pointService->getLeaderboardMode($institutionId),
                'achievements' => $achievements->values()->all(),
                'violations' => $violations->values()->all(),
                'reward_logs' => $rewardLogs->values()->all(),
            ]),
        ]);
    }

    public function leaderboard(Request $request): JsonResponse
    {
        $institutionId = InstitutionContext::resolveForUser($request->user(), $request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        [$academicYearId, $semesterId] = $this->pointService->resolvePeriodFromRequest($request, $institutionId);
        $limit = min((int) $request->get('limit', 20), 100);
        $bundle = $this->pointService->getLeaderboardBundle($institutionId, $academicYearId, $semesterId, $limit);

        return response()->json([
            'data' => $bundle,
            'meta' => [
                'academic_year_id' => $academicYearId,
                'semester_id' => $semesterId,
                'mode' => $bundle['mode'],
                'limit' => $limit,
            ],
        ]);
    }

    public function reportSummary(Request $request): JsonResponse
    {
        $institutionId = InstitutionContext::resolveForUser($request->user(), $request);
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
        $institutionId = InstitutionContext::resolveForUser($request->user(), $request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $query = Employee::forInstitution($institutionId)
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
     * Cheap badge counts — avoid paginating full achievement/violation lists just for totals.
     */
    public function pendingCounts(Request $request): JsonResponse
    {
        $institutionId = InstitutionContext::resolveForUser($request->user(), $request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        return response()->json([
            'data' => $this->pointService->getPendingCounts($institutionId),
        ]);
    }

    /**
     * Single payload for first paint of Apresiasi Guru page.
     * Replaces separate calls: years + semesters + institution period + pending + list.
     */
    public function bootstrap(Request $request): JsonResponse
    {
        $institutionId = InstitutionContext::resolveForUser($request->user(), $request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $institution = Institution::query()
            ->select([
                'id',
                'name',
                'foundation_name',
                'npsn',
                'nss',
                'level',
                'address',
                'village',
                'sub_district',
                'district',
                'province',
                'postal_code',
                'phone',
                'email',
                'website',
                'logo',
                'principal_name',
                'principal_nip',
                'active_academic_year_id',
                'active_semester_id',
            ])
            ->find($institutionId);

        $academicYears = AcademicYear::query()
            ->select('id', 'code', 'name', 'status', 'start_date')
            ->orderByDesc('start_date')
            ->limit(40)
            ->get()
            ->map(fn (AcademicYear $year) => [
                'id' => $year->id,
                'code' => $year->code,
                'name' => $year->name,
                'status' => $year->status,
                'is_active' => $year->status === 'Aktif',
            ])
            ->values();

        $academicYearId = $institution?->active_academic_year_id
            ? (int) $institution->active_academic_year_id
            : null;
        $semesterId = $institution?->active_semester_id
            ? (int) $institution->active_semester_id
            : null;

        if (!$academicYearId && $academicYears->isNotEmpty()) {
            $activeYear = $academicYears->firstWhere('is_active', true) ?: $academicYears->first();
            $academicYearId = (int) $activeYear['id'];
        }

        $semesters = collect();
        if ($academicYearId) {
            $semesters = Semester::query()
                ->select('id', 'academic_year_id', 'name', 'order', 'status')
                ->where('academic_year_id', $academicYearId)
                ->orderBy('order')
                ->get()
                ->map(fn (Semester $semester) => [
                    'id' => $semester->id,
                    'academic_year_id' => $semester->academic_year_id,
                    'name' => $semester->name,
                    'order' => $semester->order,
                    'status' => $semester->status,
                    'is_active' => $semester->status === 'Aktif',
                ])
                ->values();

            if (!$semesterId && $semesters->isNotEmpty()) {
                $activeSem = $semesters->firstWhere('is_active', true) ?: $semesters->first();
                $semesterId = (int) $activeSem['id'];
            }
        }

        $tab = $request->get('tab', 'achievements');
        $perPage = min((int) $request->get('per_page', 15), 50);
        $filters = [
            'academic_year_id' => $academicYearId,
            'semester_id' => $semesterId,
        ];

        // Load both main lists in one request so Prestasi ↔ Pelanggaran tab switch is instant.
        $achievementsPaginator = $this->pointService->paginateAchievements($institutionId, $filters, $perPage);
        $violationsPaginator = $this->pointService->paginateViolations($institutionId, $filters, $perPage);
        $achievementsPayload = TeacherAchievementResource::collection($achievementsPaginator)->response()->getData(true);
        $violationsPayload = TeacherViolationResource::collection($violationsPaginator)->response()->getData(true);

        $listKey = in_array($tab, ['violations', 'pending_violations'], true) ? 'violations' : 'achievements';
        $listPayload = $listKey === 'violations' ? $violationsPayload : $achievementsPayload;

        $pending = $this->pointService->getPendingCounts($institutionId);

        $institutionPayload = null;
        if ($institution) {
            $principal = $institution->resolvedPrincipal();
            $institutionPayload = $institution->toArray();
            $institutionPayload['principal_name'] = $principal['name'];
            $institutionPayload['principal_nip'] = $principal['nip'];
            $institutionPayload['logo'] = $institution->logo
                ? asset('storage/' . $institution->logo)
                : null;
            $institutionPayload['active_academic_year_id'] = $institution->active_academic_year_id;
            $institutionPayload['active_semester_id'] = $institution->active_semester_id;
        }

        return response()->json([
            'data' => [
                'period' => [
                    'academic_year_id' => $academicYearId ? (string) $academicYearId : '',
                    'semester_id' => $semesterId ? (string) $semesterId : '',
                ],
                'academic_years' => $academicYears,
                'semesters' => $semesters,
                'pending_achievements' => $pending['pending_achievements'],
                'pending_violations' => $pending['pending_violations'],
                'institution' => $institutionPayload,
                'list_key' => $listKey,
                'list' => $listPayload['data'] ?? [],
                'meta' => $listPayload['meta'] ?? [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                    'from' => null,
                    'to' => null,
                ],
                'achievements' => $achievementsPayload['data'] ?? [],
                'achievements_meta' => $achievementsPayload['meta'] ?? null,
                'violations' => $violationsPayload['data'] ?? [],
                'violations_meta' => $violationsPayload['meta'] ?? null,
            ],
        ]);
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
