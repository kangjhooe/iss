<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkGradesRequest;
use App\Http\Requests\StoreGradeRequest;
use App\Http\Requests\UpdateGradeRequest;
use App\Http\Resources\GradeResource;
use App\Models\Grade;
use App\Models\LessonSchedule;
use App\Services\GradeService;
use App\Support\InstitutionContext;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GradeController extends Controller
{
    public function __construct(
        protected GradeService $gradeService
    ) {}

    private function resolveInstitutionId(Request $request): ?int
    {
        return InstitutionContext::resolveForUser(
            $request->user(),
            $request,
            $request->get('institution_id')
        );
    }

    private function canAccessGrade($user, Grade $grade): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return InstitutionContext::canAccessInstitution($user, (int) $grade->institution_id);
    }

    /**
     * List grades with filters.
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $filters = $request->only(['semester_id', 'class_id', 'subject_id', 'student_id']);
            $perPage = min($request->get('per_page', 50), 100);
            $grades = $this->gradeService->listForInstitution($institutionId, $filters, $perPage);

            return GradeResource::collection($grades);
        } catch (\Exception $e) {
            Log::error('Grade index failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil data nilai.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get grades by class + subject + semester (for buku nilai grid).
     */
    public function getByClassSubjectSemester(Request $request): JsonResponse
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

            if ($user->isTeacherOrStaff()) {
                $user->load(['teacherProfile', 'employeeProfile']);
                $teacher = $user->teacherProfile ?? $user->employeeProfile;
                if ($teacher) {
                    $canTeach = LessonSchedule::where('semester_id', $semesterId)
                        ->where('class_id', $classId)
                        ->where('subject_id', $subjectId)
                        ->where('employee_id', $teacher->id)
                        ->where('institution_id', $institutionId)
                        ->exists();
                    if (!$canTeach) {
                        return response()->json(['message' => 'Anda tidak mengajar mapel ini di kelas tersebut.'], 403);
                    }
                }
            }

            $rows = $this->gradeService->getByClassSubjectSemester($institutionId, $classId, $subjectId, $semesterId);
            return response()->json(['data' => $rows]);
        } catch (\Exception $e) {
            Log::error('Grade getByClassSubjectSemester failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil data buku nilai.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Bulk save grades for class + subject + semester.
     */
    public function bulkUpsert(BulkGradesRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $data = $request->validated();
            $semesterId = (int) $data['semester_id'];
            $classId = (int) $data['class_id'];
            $subjectId = (int) $data['subject_id'];
            $grades = $data['grades'];

            $employeeId = null;
            if ($user->isTeacherOrStaff()) {
                $user->load(['teacherProfile', 'employeeProfile']);
                $teacher = $user->teacherProfile ?? $user->employeeProfile;
                if ($teacher) {
                    $employeeId = $teacher->id;
                    $canTeach = LessonSchedule::where('semester_id', $semesterId)
                        ->where('class_id', $classId)
                        ->where('subject_id', $subjectId)
                        ->where('employee_id', $teacher->id)
                        ->where('institution_id', $institutionId)
                        ->exists();
                    if (!$canTeach) {
                        return response()->json(['message' => 'Anda tidak mengajar mapel ini di kelas tersebut.'], 403);
                    }
                }
            }

            $count = $this->gradeService->bulkUpsert($institutionId, $semesterId, $classId, $subjectId, $grades, $employeeId);
            return response()->json([
                'message' => 'Nilai berhasil disimpan.',
                'data' => ['saved_count' => $count],
            ]);
        } catch (\Exception $e) {
            Log::error('Grade bulkUpsert failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal menyimpan nilai.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Store a single grade.
     */
    public function store(StoreGradeRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $data = $request->validated();
            $semesterId = (int) $data['semester_id'];
            $classId = (int) $data['class_id'];
            $subjectId = (int) $data['subject_id'];

            if ($user->isTeacherOrStaff()) {
                $user->load(['teacherProfile', 'employeeProfile']);
                $teacher = $user->teacherProfile ?? $user->employeeProfile;
                if ($teacher) {
                    $data['employee_id'] = $teacher->id;
                    $canTeach = LessonSchedule::where('semester_id', $semesterId)
                        ->where('class_id', $classId)
                        ->where('subject_id', $subjectId)
                        ->where('employee_id', $teacher->id)
                        ->where('institution_id', $institutionId)
                        ->exists();
                    if (!$canTeach) {
                        return response()->json(['message' => 'Anda tidak mengajar mapel ini di kelas tersebut.'], 403);
                    }
                }
            }

            $academicYearId = \App\Models\Semester::find($semesterId)?->academic_year_id;
            $grade = Grade::create([
                'institution_id' => $institutionId,
                'academic_year_id' => $academicYearId,
                'semester_id' => $semesterId,
                'class_id' => $classId,
                'subject_id' => $subjectId,
                'student_id' => $data['student_id'],
                'employee_id' => $data['employee_id'] ?? null,
                'grade_type' => $data['grade_type'],
                'value' => $data['value'],
                'notes' => $data['notes'] ?? null,
            ]);
            $grade->load(['student', 'subject', 'schoolClass', 'semester']);
            return (new GradeResource($grade))->response()->setStatusCode(201);
        } catch (\Exception $e) {
            Log::error('Grade store failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mencatat nilai.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Show single grade.
     */
    public function show(Request $request, Grade $grade): GradeResource|JsonResponse
    {
        $user = $request->user();
        if (!$this->canAccessGrade($user, $grade)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $grade->load(['student', 'subject', 'schoolClass', 'semester', 'employee']);
        return new GradeResource($grade);
    }

    /**
     * Update single grade.
     */
    public function update(UpdateGradeRequest $request, Grade $grade): GradeResource|JsonResponse
    {
        try {
            $user = $request->user();
            if (!$this->canAccessGrade($user, $grade)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            $grade->update($request->validated());
            $grade->load(['student', 'subject', 'schoolClass', 'semester', 'employee']);
            return new GradeResource($grade);
        } catch (\Exception $e) {
            Log::error('Grade update failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal memperbarui nilai.'], 500);
        }
    }

    /**
     * Delete grade.
     */
    public function destroy(Request $request, Grade $grade): JsonResponse
    {
        $user = $request->user();
        if (!$this->canAccessGrade($user, $grade)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $grade->delete();
        return response()->json(['message' => 'Nilai berhasil dihapus.'], 200);
    }

    /**
     * Export buku nilai (class + subject + semester) as CSV.
     */
    public function export(Request $request): StreamedResponse|JsonResponse
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

            if ($user->isTeacherOrStaff()) {
                $user->load(['teacherProfile', 'employeeProfile']);
                $teacher = $user->teacherProfile ?? $user->employeeProfile;
                if ($teacher) {
                    $canTeach = LessonSchedule::where('semester_id', $semesterId)
                        ->where('class_id', $classId)
                        ->where('subject_id', $subjectId)
                        ->where('employee_id', $teacher->id)
                        ->where('institution_id', $institutionId)
                        ->exists();
                    if (!$canTeach) {
                        return response()->json(['message' => 'Anda tidak mengajar mapel ini di kelas tersebut.'], 403);
                    }
                }
            }

            $rows = $this->gradeService->getByClassSubjectSemester($institutionId, $classId, $subjectId, $semesterId);
            $filename = 'buku-nilai-' . date('Y-m-d-His') . '.csv';

            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($out, ['No', 'Nama', 'NIS', 'NISN', 'UH', 'UTS', 'UAS', 'Tugas', 'Nilai Akhir']);
                foreach ($rows as $i => $row) {
                    fputcsv($out, [
                        $i + 1,
                        $row['student']['name'] ?? '',
                        $row['student']['nis'] ?? '',
                        $row['student']['nisn'] ?? '',
                        $row['uh'] ?? '',
                        $row['uts'] ?? '',
                        $row['uas'] ?? '',
                        $row['tugas'] ?? '',
                        $row['nilai_akhir'] ?? '',
                    ]);
                }
                fclose($out);
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        } catch (\Exception $e) {
            Log::error('Grade export failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengekspor buku nilai.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get grades by student + semester (raport per siswa).
     */
    public function getByStudentSemester(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $studentId = (int) $request->get('student_id');
            $semesterId = (int) $request->get('semester_id');
            if (!$studentId || !$semesterId) {
                return response()->json(['message' => 'student_id dan semester_id wajib diisi.'], 422);
            }

            if ($user->isStudent()) {
                $profile = $user->studentProfile;
                if (!$profile || (int) $profile->id !== $studentId) {
                    return response()->json(['message' => 'Anda hanya dapat melihat nilai sendiri.'], 403);
                }
                $institutionId = $profile->institution_id;
            } else {
                $institutionId = $this->resolveInstitutionId($request);
            }

            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $rows = $this->gradeService->getByStudentSemester($institutionId, $studentId, $semesterId);
            return response()->json(['data' => $rows]);
        } catch (\Exception $e) {
            Log::error('Grade getByStudentSemester failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil raport siswa.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Export raport per siswa (CSV).
     */
    public function exportStudentRaport(Request $request): StreamedResponse|JsonResponse
    {
        try {
            $user = $request->user();
            $studentId = (int) $request->get('student_id');
            $semesterId = (int) $request->get('semester_id');
            if (!$studentId || !$semesterId) {
                return response()->json(['message' => 'student_id dan semester_id wajib diisi.'], 422);
            }

            if ($user->isStudent()) {
                $profile = $user->studentProfile;
                if (!$profile || (int) $profile->id !== $studentId) {
                    return response()->json(['message' => 'Anda hanya dapat mengunduh raport sendiri.'], 403);
                }
                $institutionId = $profile->institution_id;
            } else {
                $institutionId = $this->resolveInstitutionId($request);
            }

            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $student = \App\Models\Student::where('id', $studentId)->where('institution_id', $institutionId)->first();
            if (!$student) {
                return response()->json(['message' => 'Siswa tidak ditemukan.'], 404);
            }

            $rows = $this->gradeService->getByStudentSemester($institutionId, $studentId, $semesterId);
            $filename = 'raport-' . Str::slug($student->name) . '-' . date('Y-m-d-His') . '.csv';

            return response()->streamDownload(function () use ($student, $rows) {
                $out = fopen('php://output', 'w');
                fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($out, ['Raport Siswa: ' . $student->name . ' (NIS: ' . ($student->nis ?? '-') . ')']);
                fputcsv($out, []);
                fputcsv($out, ['Mata Pelajaran', 'UH', 'UTS', 'UAS', 'Tugas', 'Nilai Akhir']);
                foreach ($rows as $row) {
                    fputcsv($out, [
                        $row['subject']['name'] ?? '',
                        $row['uh'] ?? '',
                        $row['uts'] ?? '',
                        $row['uas'] ?? '',
                        $row['tugas'] ?? '',
                        $row['nilai_akhir'] ?? '',
                    ]);
                }
                fclose($out);
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        } catch (\Exception $e) {
            Log::error('Grade exportStudentRaport failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengekspor raport.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
