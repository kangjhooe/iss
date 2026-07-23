<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeacherRewardLogRequest;
use App\Http\Resources\TeacherRewardLogResource;
use App\Models\Employee;
use App\Models\TeacherPointReward;
use App\Models\TeacherRewardLog;
use App\Services\TeacherPointService;
use App\Support\InstitutionContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class TeacherRewardLogController extends Controller
{
    use ResolvesInstitution;

    public function __construct(
        protected TeacherPointService $pointService
    ) {}

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            [$academicYearId, $semesterId] = $this->pointService->resolvePeriodFromRequest($request, $institutionId);

            $query = TeacherRewardLog::with([
                'employee:id,name,nip',
                'reward:id,reward_name,point_min,point_max',
                'recorder:id,name',
                'academicYear:id,name,code',
                'semester:id,name',
            ])
                ->forInstitution($institutionId)
                ->orderByDesc('reward_date')
                ->orderByDesc('id');

            if ($academicYearId) {
                $query->where('academic_year_id', $academicYearId);
            }
            if ($semesterId) {
                $query->where('semester_id', $semesterId);
            }
            if ($request->filled('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }

            $perPage = min((int) $request->get('per_page', 15), 100);

            return TeacherRewardLogResource::collection($query->paginate($perPage));
        } catch (\Exception $e) {
            Log::error('TeacherRewardLog index failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil catatan reward.'], 500);
        }
    }

    public function store(StoreTeacherRewardLogRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $employee = Employee::where('id', $request->employee_id)
                ->where('institution_id', $institutionId)
                ->firstOrFail();

            $rewardName = $request->reward_name;
            $rewardId = $request->teacher_point_reward_id;
            if ($rewardId) {
                $reward = TeacherPointReward::where('id', $rewardId)
                    ->where('institution_id', $institutionId)
                    ->firstOrFail();
                $rewardName = $rewardName ?: $reward->reward_name;
            }

            [$defaultYear, $defaultSemester] = $this->pointService->resolveActivePeriod($institutionId);
            $score = $request->filled('score_at_reward')
                ? (int) $request->score_at_reward
                : $this->pointService->getTotalPoints(
                    $employee->id,
                    $institutionId,
                    $request->input('academic_year_id') ?: $defaultYear,
                    $request->input('semester_id') ?: $defaultSemester
                );

            $log = TeacherRewardLog::create([
                'institution_id' => $institutionId,
                'employee_id' => $employee->id,
                'teacher_point_reward_id' => $rewardId,
                'reward_name' => $rewardName,
                'reward_date' => $request->reward_date,
                'score_at_reward' => $score,
                'notes' => $request->notes,
                'recorded_by' => $user->id,
                'academic_year_id' => $request->input('academic_year_id') ?: $defaultYear,
                'semester_id' => $request->input('semester_id') ?: $defaultSemester,
            ]);

            $log->load(['employee', 'reward', 'recorder', 'academicYear:id,name,code', 'semester:id,name']);

            return (new TeacherRewardLogResource($log))->response()->setStatusCode(201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Guru atau reward tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('TeacherRewardLog store failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mencatat reward.'], 500);
        }
    }

    public function destroy(Request $request, TeacherRewardLog $teacher_reward_log): JsonResponse
    {
        $user = $request->user();
        if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $teacher_reward_log->institution_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $teacher_reward_log->delete();

        return response()->json(['message' => 'Catatan reward berhasil dihapus.']);
    }

    public function byEmployee(Request $request, int $employeeId): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            [$academicYearId, $semesterId] = $this->pointService->resolvePeriodFromRequest($request, $institutionId);

            $query = TeacherRewardLog::with(['recorder:id,name', 'reward'])
                ->forInstitution($institutionId)
                ->forEmployee($employeeId)
                ->orderByDesc('reward_date');

            if ($academicYearId) {
                $query->where('academic_year_id', $academicYearId);
            }
            if ($semesterId) {
                $query->where('semester_id', $semesterId);
            }

            return TeacherRewardLogResource::collection($query->paginate(20));
        } catch (\Exception $e) {
            Log::error('TeacherRewardLog byEmployee failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil riwayat reward.'], 500);
        }
    }
}
