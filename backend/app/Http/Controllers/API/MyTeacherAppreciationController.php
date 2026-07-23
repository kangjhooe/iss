<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitTeacherAchievementRequest;
use App\Http\Resources\TeacherAchievementResource;
use App\Models\TeacherAchievement;
use App\Models\TeacherAchievementType;
use App\Services\TeacherPointService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class MyTeacherAppreciationController extends Controller
{
    public function __construct(
        protected TeacherPointService $pointService
    ) {}

    protected function resolveEmployee(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->isTeacherOrStaff()) {
            return null;
        }

        return $user->employeeProfile ?? $user->teacherProfile;
    }

    public function summary(Request $request): JsonResponse
    {
        $employee = $this->resolveEmployee($request);
        if (!$employee) {
            return response()->json(['message' => 'Profil pegawai tidak ditemukan.'], 404);
        }

        $institutionId = (int) $employee->institution_id;
        $this->pointService->ensureDefaultTypes($institutionId);
        $this->pointService->ensureDefaultRewards($institutionId);

        [$academicYearId, $semesterId] = $this->pointService->resolvePeriodFromRequest($request, $institutionId);
        $summary = $this->pointService->getPointSummary($employee->id, $institutionId, $academicYearId, $semesterId);

        $rank = $this->pointService->findEmployeeRank(
            $employee->id,
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
            ]),
        ]);
    }

    public function achievements(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $employee = $this->resolveEmployee($request);
            if (!$employee) {
                return response()->json(['message' => 'Profil pegawai tidak ditemukan.'], 404);
            }

            $institutionId = (int) $employee->institution_id;
            [$academicYearId, $semesterId] = $this->pointService->resolvePeriodFromRequest($request, $institutionId);

            $query = TeacherAchievement::with(['achievementType', 'giver', 'reviewer', 'academicYear:id,name,code', 'semester:id,name'])
                ->forInstitution($institutionId)
                ->forEmployee($employee->id)
                ->orderByDesc('achievement_date')
                ->orderByDesc('id');

            if ($academicYearId) {
                $query->where('academic_year_id', $academicYearId);
            }
            if ($semesterId) {
                $query->where('semester_id', $semesterId);
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            return TeacherAchievementResource::collection($query->paginate(20));
        } catch (\Exception $e) {
            Log::error('MyTeacherAppreciation achievements failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil riwayat prestasi.'], 500);
        }
    }

    public function submit(SubmitTeacherAchievementRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $employee = $this->resolveEmployee($request);
            if (!$employee) {
                return response()->json(['message' => 'Profil pegawai tidak ditemukan.'], 404);
            }

            $institutionId = (int) $employee->institution_id;
            $type = TeacherAchievementType::where('id', $request->achievement_type_id)
                ->where('institution_id', $institutionId)
                ->where('is_active', true)
                ->firstOrFail();

            [$defaultYear, $defaultSemester] = $this->pointService->resolveActivePeriod($institutionId);
            $yearId = $request->input('academic_year_id') ?: $defaultYear;
            $semesterId = $request->input('semester_id') ?: $defaultSemester;
            $pointValue = $type->resolvePointValue(
                $request->input('level'),
                $request->filled('point_value') ? (int) $request->point_value : null
            );

            $evidencePath = null;
            if ($request->hasFile('evidence')) {
                $evidencePath = $request->file('evidence')->store(
                    "teacher-achievements/{$institutionId}",
                    'public'
                );
            }

            $achievement = TeacherAchievement::create([
                'institution_id' => $institutionId,
                'employee_id' => $employee->id,
                'achievement_type_id' => $type->id,
                'title' => $request->title ?: $type->name,
                'achievement_date' => $request->achievement_date,
                'point_value' => $pointValue,
                'level' => $request->level,
                'notes' => $request->notes,
                'evidence_path' => $evidencePath,
                'status' => TeacherAchievement::STATUS_PENDING,
                'submitted_by' => $user->id,
                'academic_year_id' => $yearId,
                'semester_id' => $semesterId,
            ]);

            $achievement->load(['achievementType', 'submitter', 'academicYear:id,name,code', 'semester:id,name']);

            $this->pointService->forgetPendingCounts($institutionId);

            return (new TeacherAchievementResource($achievement))->response()->setStatusCode(201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Jenis prestasi tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('MyTeacherAppreciation submit failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengajukan prestasi.'], 500);
        }
    }

    public function show(Request $request, TeacherAchievement $teacher_achievement): TeacherAchievementResource|JsonResponse
    {
        $employee = $this->resolveEmployee($request);
        if (!$employee || (int) $teacher_achievement->employee_id !== (int) $employee->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $teacher_achievement->load([
            'achievementType', 'giver', 'submitter', 'reviewer',
            'academicYear:id,name,code', 'semester:id,name',
        ]);

        return new TeacherAchievementResource($teacher_achievement);
    }

    public function types(Request $request): JsonResponse
    {
        $employee = $this->resolveEmployee($request);
        if (!$employee) {
            return response()->json(['message' => 'Profil pegawai tidak ditemukan.'], 404);
        }

        $institutionId = (int) $employee->institution_id;
        $this->pointService->ensureDefaultTypes($institutionId);

        $types = TeacherAchievementType::forInstitution($institutionId)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $types->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'code' => $t->code,
                'point_value' => $t->point_value,
                'category' => $t->category,
                'level_multipliers' => $t->getMultipliers(),
                'description' => $t->description,
            ]),
        ]);
    }

    public function violations(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $employee = $this->resolveEmployee($request);
            if (!$employee) {
                return response()->json(['message' => 'Profil pegawai tidak ditemukan.'], 404);
            }

            $institutionId = (int) $employee->institution_id;
            [$academicYearId, $semesterId] = $this->pointService->resolvePeriodFromRequest($request, $institutionId);

            $query = \App\Models\TeacherViolation::with(['violationType', 'reporter', 'reviewer', 'academicYear:id,name,code', 'semester:id,name'])
                ->forInstitution($institutionId)
                ->forEmployee($employee->id)
                ->orderByDesc('violation_date')
                ->orderByDesc('id');

            if ($academicYearId) {
                $query->where('academic_year_id', $academicYearId);
            }
            if ($semesterId) {
                $query->where('semester_id', $semesterId);
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            return \App\Http\Resources\TeacherViolationResource::collection($query->paginate(20));
        } catch (\Exception $e) {
            Log::error('MyTeacherAppreciation violations failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil riwayat pelanggaran.'], 500);
        }
    }

    public function leaderboard(Request $request): JsonResponse
    {
        $employee = $this->resolveEmployee($request);
        if (!$employee) {
            return response()->json(['message' => 'Profil pegawai tidak ditemukan.'], 404);
        }

        $institutionId = (int) $employee->institution_id;
        [$academicYearId, $semesterId] = $this->pointService->resolvePeriodFromRequest($request, $institutionId);
        $limit = min(max((int) $request->get('limit', 50), 1), 100);
        $bundle = $this->pointService->getLeaderboardBundle($institutionId, $academicYearId, $semesterId, $limit);
        $mode = $bundle['mode'];

        // Untuk tampilan pribadi: jika dipisah, tampilkan grup sesuai tipe pegawai.
        $rows = $bundle['rows'];
        $myGroup = null;
        if ($mode === \App\Models\Institution::TEACHER_APPRECIATION_LEADERBOARD_SEPARATED) {
            $myGroup = $employee->type === 'Guru' ? 'guru' : 'staff';
            $rows = $bundle[$myGroup] ?? [];
        }

        return response()->json([
            'data' => $rows,
            'meta' => [
                'academic_year_id' => $academicYearId,
                'semester_id' => $semesterId,
                'my_employee_id' => (int) $employee->id,
                'limit' => $limit,
                'mode' => $mode,
                'group' => $myGroup,
                'bundle' => $bundle,
            ],
        ]);
    }
}
