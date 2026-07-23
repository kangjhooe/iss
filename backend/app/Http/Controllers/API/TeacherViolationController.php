<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeacherViolationRequest;
use App\Http\Requests\UpdateTeacherViolationRequest;
use App\Http\Resources\TeacherViolationResource;
use App\Models\Employee;
use App\Models\TeacherViolation;
use App\Models\TeacherViolationType;
use App\Models\User;
use App\Services\TeacherPointService;
use App\Support\InstitutionContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TeacherViolationController extends Controller
{
    use ResolvesInstitution;

    public function __construct(
        protected TeacherPointService $pointService
    ) {}

    /**
     * KS / admin / pemegang modul teacher_appreciation boleh approve langsung.
     * Guru piket (hanya teacher_violation_report) selalu pending.
     */
    protected function canDirectApprove(User $user): bool
    {
        if ($user->isAdminOrSuperAdmin() || $user->isInstitutionAdmin()) {
            return true;
        }

        return $user->hasModuleAccess('teacher_appreciation');
    }

    protected function canManageTypes(User $user): bool
    {
        return $this->canDirectApprove($user);
    }

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            [$academicYearId, $semesterId] = $this->pointService->resolvePeriodFromRequest($request, $institutionId);

            $paginator = $this->pointService->paginateViolations($institutionId, [
                'academic_year_id' => $academicYearId,
                'semester_id' => $semesterId,
                'employee_id' => $request->input('employee_id'),
                'violation_type_id' => $request->input('violation_type_id'),
                'status' => $request->input('status'),
                'search' => $request->input('search'),
            ], (int) $request->get('per_page', 15));

            return TeacherViolationResource::collection($paginator);
        } catch (\Exception $e) {
            Log::error('TeacherViolation index failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil data pelanggaran guru.'], 500);
        }
    }

    public function store(StoreTeacherViolationRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            if (!$user->hasModuleAccess('teacher_appreciation') && !$user->hasModuleAccess('teacher_violation_report')) {
                return response()->json(['message' => 'Anda tidak berwenang mencatat pelanggaran guru.'], 403);
            }

            $employee = Employee::where('id', $request->employee_id)
                ->where('institution_id', $institutionId)
                ->firstOrFail();

            $type = TeacherViolationType::where('id', $request->violation_type_id)
                ->where('institution_id', $institutionId)
                ->where('is_active', true)
                ->firstOrFail();

            [$defaultYear, $defaultSemester] = $this->pointService->resolveActivePeriod($institutionId);
            $pointValue = $request->filled('point_value')
                ? (int) $request->point_value
                : (int) $type->point_weight;

            $evidencePath = null;
            if ($request->hasFile('evidence')) {
                $evidencePath = $request->file('evidence')->store(
                    "teacher-violations/{$institutionId}",
                    'public'
                );
            }

            $direct = $this->canDirectApprove($user) && !$request->boolean('force_pending');
            $status = $direct
                ? TeacherViolation::STATUS_APPROVED
                : TeacherViolation::STATUS_PENDING;

            $violation = TeacherViolation::create([
                'institution_id' => $institutionId,
                'employee_id' => $employee->id,
                'violation_type_id' => $type->id,
                'violation_date' => $request->violation_date,
                'point_value' => $pointValue,
                'notes' => $request->notes,
                'sanction' => $request->sanction ?: $type->default_sanction,
                'evidence_path' => $evidencePath,
                'status' => $status,
                'reported_by' => $user->id,
                'reviewed_by' => $direct ? $user->id : null,
                'reviewed_at' => $direct ? now() : null,
                'academic_year_id' => $request->input('academic_year_id') ?: $defaultYear,
                'semester_id' => $request->input('semester_id') ?: $defaultSemester,
            ]);

            $violation->load([
                'employee', 'violationType', 'reporter', 'reviewer',
                'academicYear:id,name,code', 'semester:id,name',
            ]);

            $this->pointService->forgetPendingCounts($institutionId);

            return (new TeacherViolationResource($violation))->response()->setStatusCode(201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Guru atau jenis pelanggaran tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('TeacherViolation store failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mencatat pelanggaran guru.'], 500);
        }
    }

    public function show(Request $request, TeacherViolation $teacher_violation): TeacherViolationResource|JsonResponse
    {
        $user = $request->user();
        if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $teacher_violation->institution_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $teacher_violation->load([
            'employee', 'violationType', 'reporter', 'reviewer', 'piketIncident',
            'academicYear:id,name,code', 'semester:id,name',
        ]);

        return new TeacherViolationResource($teacher_violation);
    }

    public function update(UpdateTeacherViolationRequest $request, TeacherViolation $teacher_violation): TeacherViolationResource|JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $teacher_violation->institution_id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            if (!$this->canManageTypes($user)) {
                return response()->json(['message' => 'Hanya Kepala Sekolah / admin yang dapat mengubah catatan.'], 403);
            }

            $institutionId = $this->resolveInstitutionId($request) ?: (int) $teacher_violation->institution_id;
            $type = TeacherViolationType::where('id', $request->violation_type_id)
                ->where('institution_id', $institutionId)
                ->where('is_active', true)
                ->firstOrFail();

            $pointValue = $request->filled('point_value')
                ? (int) $request->point_value
                : (int) $type->point_weight;

            $payload = [
                'violation_type_id' => $type->id,
                'violation_date' => $request->violation_date,
                'point_value' => $pointValue,
                'notes' => $request->notes,
                'sanction' => $request->sanction,
            ];

            if ($request->filled('employee_id')) {
                Employee::where('id', $request->employee_id)->where('institution_id', $institutionId)->firstOrFail();
                $payload['employee_id'] = $request->employee_id;
            }
            if ($request->filled('academic_year_id')) {
                $payload['academic_year_id'] = $request->academic_year_id;
            }
            if ($request->filled('semester_id')) {
                $payload['semester_id'] = $request->semester_id;
            }

            if ($request->boolean('remove_evidence') && $teacher_violation->evidence_path) {
                Storage::disk('public')->delete($teacher_violation->evidence_path);
                $payload['evidence_path'] = null;
            }
            if ($request->hasFile('evidence')) {
                if ($teacher_violation->evidence_path) {
                    Storage::disk('public')->delete($teacher_violation->evidence_path);
                }
                $payload['evidence_path'] = $request->file('evidence')->store(
                    "teacher-violations/{$institutionId}",
                    'public'
                );
            }

            $teacher_violation->update($payload);
            $teacher_violation->load([
                'employee', 'violationType', 'reporter', 'reviewer',
                'academicYear:id,name,code', 'semester:id,name',
            ]);

            return new TeacherViolationResource($teacher_violation);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Guru atau jenis pelanggaran tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('TeacherViolation update failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal memperbarui pelanggaran.'], 500);
        }
    }

    public function destroy(Request $request, TeacherViolation $teacher_violation): JsonResponse
    {
        $user = $request->user();
        if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $teacher_violation->institution_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if (!$this->canManageTypes($user)) {
            return response()->json(['message' => 'Hanya Kepala Sekolah / admin yang dapat menghapus.'], 403);
        }

        if ($teacher_violation->evidence_path) {
            Storage::disk('public')->delete($teacher_violation->evidence_path);
        }
        $teacher_violation->delete();

        $this->pointService->forgetPendingCounts((int) $teacher_violation->institution_id);

        return response()->json(['message' => 'Pelanggaran berhasil dihapus.']);
    }

    public function approve(Request $request, TeacherViolation $teacher_violation): TeacherViolationResource|JsonResponse
    {
        $user = $request->user();
        if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $teacher_violation->institution_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if (!$this->canDirectApprove($user)) {
            return response()->json(['message' => 'Hanya Kepala Sekolah / admin yang dapat menyetujui.'], 403);
        }
        if ($teacher_violation->status !== TeacherViolation::STATUS_PENDING) {
            return response()->json(['message' => 'Hanya laporan menunggu yang dapat disetujui.'], 422);
        }

        $teacher_violation->update([
            'status' => TeacherViolation::STATUS_APPROVED,
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'review_notes' => $request->input('review_notes'),
        ]);

        $this->pointService->resolveLinkedPiketIncident($teacher_violation, $user);
        $this->pointService->forgetPendingCounts((int) $teacher_violation->institution_id);

        $teacher_violation->load([
            'employee', 'violationType', 'reporter', 'reviewer', 'piketIncident',
            'academicYear:id,name,code', 'semester:id,name',
        ]);

        return new TeacherViolationResource($teacher_violation);
    }

    public function reject(Request $request, TeacherViolation $teacher_violation): TeacherViolationResource|JsonResponse
    {
        $user = $request->user();
        if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $teacher_violation->institution_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if (!$this->canDirectApprove($user)) {
            return response()->json(['message' => 'Hanya Kepala Sekolah / admin yang dapat menolak.'], 403);
        }
        if ($teacher_violation->status !== TeacherViolation::STATUS_PENDING) {
            return response()->json(['message' => 'Hanya laporan menunggu yang dapat ditolak.'], 422);
        }

        $request->validate(['review_notes' => 'required|string|max:1000']);

        $teacher_violation->update([
            'status' => TeacherViolation::STATUS_REJECTED,
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'review_notes' => $request->review_notes,
        ]);

        $this->pointService->forgetPendingCounts((int) $teacher_violation->institution_id);

        $teacher_violation->load([
            'employee', 'violationType', 'reporter', 'reviewer', 'piketIncident',
            'academicYear:id,name,code', 'semester:id,name',
        ]);

        return new TeacherViolationResource($teacher_violation);
    }
}
