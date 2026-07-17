<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeacherAchievementRequest;
use App\Http\Requests\UpdateTeacherAchievementRequest;
use App\Http\Resources\TeacherAchievementResource;
use App\Models\Employee;
use App\Models\TeacherAchievement;
use App\Models\TeacherAchievementType;
use App\Services\TeacherPointService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TeacherAchievementController extends Controller
{
    public function __construct(
        protected TeacherPointService $pointService
    ) {}

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $request->user()->institution_id;
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            [$academicYearId, $semesterId] = $this->pointService->resolvePeriodFromRequest($request, $institutionId);

            $paginator = $this->pointService->paginateAchievements($institutionId, [
                'academic_year_id' => $academicYearId,
                'semester_id' => $semesterId,
                'employee_id' => $request->input('employee_id'),
                'achievement_type_id' => $request->input('achievement_type_id'),
                'status' => $request->input('status'),
                'category' => $request->input('category'),
                'search' => $request->input('search'),
            ], (int) $request->get('per_page', 15));

            return TeacherAchievementResource::collection($paginator);
        } catch (\Exception $e) {
            Log::error('TeacherAchievement index failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil data prestasi guru.'], 500);
        }
    }

    public function store(StoreTeacherAchievementRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $user->institution_id;
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $employee = Employee::where('id', $request->employee_id)
                ->where('institution_id', $institutionId)
                ->firstOrFail();

            $type = TeacherAchievementType::where('id', $request->achievement_type_id)
                ->where('institution_id', $institutionId)
                ->where('is_active', true)
                ->firstOrFail();

            [$defaultYear, $defaultSemester] = $this->pointService->resolveActivePeriod($institutionId);
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
                'status' => TeacherAchievement::STATUS_APPROVED,
                'given_by' => $user->id,
                'submitted_by' => $user->id,
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'academic_year_id' => $request->input('academic_year_id') ?: $defaultYear,
                'semester_id' => $request->input('semester_id') ?: $defaultSemester,
            ]);

            $achievement->load([
                'employee', 'achievementType', 'giver', 'submitter', 'reviewer',
                'academicYear:id,name,code', 'semester:id,name',
            ]);

            $this->pointService->forgetPendingCounts($institutionId);

            return (new TeacherAchievementResource($achievement))->response()->setStatusCode(201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Guru atau jenis prestasi tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('TeacherAchievement store failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mencatat prestasi guru.'], 500);
        }
    }

    public function show(Request $request, TeacherAchievement $teacher_achievement): TeacherAchievementResource|JsonResponse
    {
        if ($request->user()->institution_id !== $teacher_achievement->institution_id && !$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $teacher_achievement->load([
            'employee', 'achievementType', 'giver', 'submitter', 'reviewer',
            'academicYear:id,name,code', 'semester:id,name',
        ]);

        return new TeacherAchievementResource($teacher_achievement);
    }

    public function update(UpdateTeacherAchievementRequest $request, TeacherAchievement $teacher_achievement): TeacherAchievementResource|JsonResponse
    {
        try {
            if ($request->user()->institution_id !== $teacher_achievement->institution_id && !$request->user()->isSuperAdmin()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $institutionId = $request->user()->institution_id;
            $type = TeacherAchievementType::where('id', $request->achievement_type_id)
                ->where('institution_id', $institutionId)
                ->where('is_active', true)
                ->firstOrFail();

            if ($request->filled('employee_id')) {
                Employee::where('id', $request->employee_id)
                    ->where('institution_id', $institutionId)
                    ->firstOrFail();
            }

            $pointValue = $type->resolvePointValue(
                $request->input('level'),
                $request->filled('point_value') ? (int) $request->point_value : null
            );

            $payload = [
                'achievement_type_id' => $type->id,
                'title' => $request->title ?: $type->name,
                'achievement_date' => $request->achievement_date,
                'point_value' => $pointValue,
                'level' => $request->level,
                'notes' => $request->notes,
            ];

            if ($request->filled('employee_id')) {
                $payload['employee_id'] = $request->employee_id;
            }
            if ($request->filled('academic_year_id')) {
                $payload['academic_year_id'] = $request->academic_year_id;
            }
            if ($request->filled('semester_id')) {
                $payload['semester_id'] = $request->semester_id;
            }

            if ($request->boolean('remove_evidence') && $teacher_achievement->evidence_path) {
                Storage::disk('public')->delete($teacher_achievement->evidence_path);
                $payload['evidence_path'] = null;
            }

            if ($request->hasFile('evidence')) {
                if ($teacher_achievement->evidence_path) {
                    Storage::disk('public')->delete($teacher_achievement->evidence_path);
                }
                $payload['evidence_path'] = $request->file('evidence')->store(
                    "teacher-achievements/{$institutionId}",
                    'public'
                );
            }

            $teacher_achievement->update($payload);
            $teacher_achievement->load([
                'employee', 'achievementType', 'giver', 'submitter', 'reviewer',
                'academicYear:id,name,code', 'semester:id,name',
            ]);

            return new TeacherAchievementResource($teacher_achievement);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Guru atau jenis prestasi tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('TeacherAchievement update failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal memperbarui prestasi guru.'], 500);
        }
    }

    public function destroy(Request $request, TeacherAchievement $teacher_achievement): JsonResponse
    {
        if ($request->user()->institution_id !== $teacher_achievement->institution_id && !$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($teacher_achievement->evidence_path) {
            Storage::disk('public')->delete($teacher_achievement->evidence_path);
        }

        $teacher_achievement->delete();

        $this->pointService->forgetPendingCounts((int) $teacher_achievement->institution_id);

        return response()->json(['message' => 'Prestasi guru berhasil dihapus.']);
    }

    public function approve(Request $request, TeacherAchievement $teacher_achievement): TeacherAchievementResource|JsonResponse
    {
        if ($request->user()->institution_id !== $teacher_achievement->institution_id && !$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($teacher_achievement->status !== TeacherAchievement::STATUS_PENDING) {
            return response()->json(['message' => 'Hanya usulan berstatus menunggu yang dapat disetujui.'], 422);
        }

        $teacher_achievement->update([
            'status' => TeacherAchievement::STATUS_APPROVED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_notes' => $request->input('review_notes'),
            'given_by' => $teacher_achievement->given_by ?: $request->user()->id,
        ]);

        $this->pointService->forgetPendingCounts((int) $teacher_achievement->institution_id);

        $teacher_achievement->load([
            'employee', 'achievementType', 'giver', 'submitter', 'reviewer',
            'academicYear:id,name,code', 'semester:id,name',
        ]);

        return new TeacherAchievementResource($teacher_achievement);
    }

    public function reject(Request $request, TeacherAchievement $teacher_achievement): TeacherAchievementResource|JsonResponse
    {
        if ($request->user()->institution_id !== $teacher_achievement->institution_id && !$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($teacher_achievement->status !== TeacherAchievement::STATUS_PENDING) {
            return response()->json(['message' => 'Hanya usulan berstatus menunggu yang dapat ditolak.'], 422);
        }

        $request->validate([
            'review_notes' => 'required|string|max:1000',
        ]);

        $teacher_achievement->update([
            'status' => TeacherAchievement::STATUS_REJECTED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_notes' => $request->review_notes,
        ]);

        $this->pointService->forgetPendingCounts((int) $teacher_achievement->institution_id);

        $teacher_achievement->load([
            'employee', 'achievementType', 'giver', 'submitter', 'reviewer',
            'academicYear:id,name,code', 'semester:id,name',
        ]);

        return new TeacherAchievementResource($teacher_achievement);
    }

    public function byEmployee(Request $request, int $employeeId): AnonymousResourceCollection|JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $user->institution_id;

            if ($user->isTeacherOrStaff()) {
                $profile = $user->employeeProfile;
                if (!$profile || (int) $profile->id !== $employeeId) {
                    if (!$user->hasModuleAccess('teacher_appreciation')) {
                        return response()->json(['message' => 'Anda hanya dapat melihat data sendiri.'], 403);
                    }
                }
                $institutionId = $profile?->institution_id ?: $institutionId;
            }

            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            [$academicYearId, $semesterId] = $this->pointService->resolvePeriodFromRequest($request, $institutionId);

            $query = TeacherAchievement::with(['achievementType', 'giver', 'reviewer'])
                ->forInstitution($institutionId)
                ->forEmployee($employeeId)
                ->orderByDesc('achievement_date');

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
            Log::error('TeacherAchievement byEmployee failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil riwayat prestasi guru.'], 500);
        }
    }
}
