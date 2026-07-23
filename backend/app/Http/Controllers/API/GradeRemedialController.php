<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\GradeRemedial;
use App\Models\LessonSchedule;
use App\Services\GradeRemedialService;
use App\Support\InstitutionContext;
use App\Support\WaliKelasAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class GradeRemedialController extends Controller
{
    public function __construct(
        protected GradeRemedialService $remedialService
    ) {}

    private function resolveInstitutionId(Request $request): ?int
    {
        return InstitutionContext::resolveForUser(
            $request->user(),
            $request,
            $request->get('institution_id')
        );
    }

    private function teacherOwnsPair($user, int $institutionId, int $semesterId, int $classId, int $subjectId): bool
    {
        if (!$user->isTeacherOrStaff()) {
            return true;
        }

        $employee = WaliKelasAccess::employeeFor($user);
        if (!$employee) {
            return false;
        }

        return LessonSchedule::query()
            ->where('institution_id', $institutionId)
            ->where('semester_id', $semesterId)
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('employee_id', $employee->id)
            ->exists();
    }

    private function scopeEmployeeId($user): ?int
    {
        if (!$user->isTeacherOrStaff()) {
            return null;
        }

        return WaliKelasAccess::employeeFor($user)?->id;
    }

    public function belowKkm(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $semesterId = (int) $request->get('semester_id');
            $classId = (int) $request->get('class_id');
            $subjectId = (int) $request->get('subject_id');
            if (!$semesterId || !$classId || !$subjectId) {
                return response()->json(['message' => 'Semester, kelas, dan mata pelajaran wajib dipilih.'], 422);
            }

            if (!$this->teacherOwnsPair($user, $institutionId, $semesterId, $classId, $subjectId)) {
                return response()->json(['message' => 'Anda tidak mengajar mapel ini di kelas tersebut.'], 403);
            }

            $data = $this->remedialService->belowKkm($institutionId, $classId, $subjectId, $semesterId);

            return response()->json(['data' => $data['students'], 'meta' => array_merge($data['meta'], [
                'kkm' => $data['kkm'],
            ])]);
        } catch (\Exception $e) {
            Log::error('GradeRemedial belowKkm failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal memuat daftar siswa di bawah KKM.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $filters = $request->only(['semester_id', 'class_id', 'subject_id', 'student_id', 'status', 'type']);
            $scopeEmployeeId = $this->scopeEmployeeId($user);

            if (!empty($filters['semester_id']) && !empty($filters['class_id']) && !empty($filters['subject_id'])) {
                if (!$this->teacherOwnsPair(
                    $user,
                    $institutionId,
                    (int) $filters['semester_id'],
                    (int) $filters['class_id'],
                    (int) $filters['subject_id']
                )) {
                    return response()->json(['message' => 'Anda tidak mengajar mapel ini di kelas tersebut.'], 403);
                }
                $scopeEmployeeId = null;
            }

            $rows = $this->remedialService->list($institutionId, $filters, $scopeEmployeeId);

            return response()->json(['data' => $rows]);
        } catch (\Exception $e) {
            Log::error('GradeRemedial index failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal memuat data remidi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $data = $request->validate([
                'semester_id' => 'required|integer|exists:semesters,id',
                'class_id' => 'required|integer|exists:class,id',
                'subject_id' => 'required|integer|exists:subjects,id',
                'student_id' => 'required|integer|exists:student,id',
                'type' => ['nullable', Rule::in(array_keys(GradeRemedial::TYPES))],
                'source_grade_type' => 'nullable|string|max:40',
                'original_value' => 'nullable|numeric|min:0|max:100',
                'kkm_snapshot' => 'nullable|numeric|min:0|max:100',
                'scheduled_date' => 'nullable|date',
                'apply_to_grade' => 'nullable|boolean',
                'notes' => 'nullable|string|max:2000',
            ]);

            if (!$this->teacherOwnsPair(
                $user,
                $institutionId,
                (int) $data['semester_id'],
                (int) $data['class_id'],
                (int) $data['subject_id']
            )) {
                return response()->json(['message' => 'Anda tidak mengajar mapel ini di kelas tersebut.'], 403);
            }

            $employeeId = $this->scopeEmployeeId($user);
            $row = $this->remedialService->create($institutionId, $data, $employeeId);

            return response()->json([
                'message' => 'Remidi/pengayaan berhasil dijadwalkan.',
                'data' => $this->remedialService->toArray($row->load([
                    'student:id,name,nis,nisn',
                    'schoolClass:id,name',
                    'subject:id,name,code',
                    'employee:id,name',
                ])),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('GradeRemedial store failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal menjadwalkan remidi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function complete(Request $request, GradeRemedial $gradeRemedial): JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId || (int) $gradeRemedial->institution_id !== (int) $institutionId) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if (!$this->teacherOwnsPair(
                $user,
                $institutionId,
                (int) $gradeRemedial->semester_id,
                (int) $gradeRemedial->class_id,
                (int) $gradeRemedial->subject_id
            )) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $data = $request->validate([
                'remedial_value' => 'required|numeric|min:0|max:100',
                'completed_date' => 'nullable|date',
                'apply_to_grade' => 'nullable|boolean',
                'notes' => 'nullable|string|max:2000',
            ]);

            $row = $this->remedialService->complete(
                $gradeRemedial,
                $data,
                $this->scopeEmployeeId($user)
            );

            return response()->json([
                'message' => 'Nilai remidi berhasil disimpan.',
                'data' => $this->remedialService->toArray($row),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('GradeRemedial complete failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal menyimpan nilai remidi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function cancel(Request $request, GradeRemedial $gradeRemedial): JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId || (int) $gradeRemedial->institution_id !== (int) $institutionId) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if (!$this->teacherOwnsPair(
                $user,
                $institutionId,
                (int) $gradeRemedial->semester_id,
                (int) $gradeRemedial->class_id,
                (int) $gradeRemedial->subject_id
            )) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $notes = $request->input('notes');
            $row = $this->remedialService->cancel($gradeRemedial, is_string($notes) ? $notes : null);

            return response()->json([
                'message' => 'Remidi dibatalkan.',
                'data' => $this->remedialService->toArray($row),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('GradeRemedial cancel failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal membatalkan remidi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
