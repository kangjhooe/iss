<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkGradesRequest;
use App\Http\Requests\StoreGradeRequest;
use App\Http\Requests\UpdateGradeRequest;
use App\Http\Requests\UpsertGradeWeightRequest;
use App\Http\Requests\UpsertSubjectKkmRequest;
use App\Http\Resources\GradeResource;
use App\Models\Grade;
use App\Models\GradeWeight;
use App\Models\LessonSchedule;
use App\Models\SchoolClass;
use App\Models\SubjectKkm;
use App\Services\GradeService;
use App\Support\InstitutionContext;
use App\Support\StructuralPositionResolver;
use App\Support\WaliKelasAccess;
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

        if (!InstitutionContext::canAccessInstitution($user, (int) $grade->institution_id)) {
            return false;
        }

        if ($user->isTeacherOrStaff()) {
            $user->loadMissing(['teacherProfile', 'employeeProfile']);
            $teacher = $user->teacherProfile ?? $user->employeeProfile;
            if (!$teacher) {
                return false;
            }

            return LessonSchedule::where('semester_id', $grade->semester_id)
                ->where('class_id', $grade->class_id)
                ->where('subject_id', $grade->subject_id)
                ->where('employee_id', $teacher->id)
                ->where('institution_id', $grade->institution_id)
                ->exists();
        }

        return true;
    }

    private function canViewClassRaport($user, int $classId): bool
    {
        if (!$user->isTeacherOrStaff()) {
            return true;
        }

        if ($user->hasModuleAccess('student')) {
            return true;
        }

        return WaliKelasAccess::homeroomClassIds($user)->contains($classId);
    }

    private function teacherOwnsPair($user, int $institutionId, int $semesterId, int $classId, int $subjectId): bool
    {
        if (!$user->isTeacherOrStaff()) {
            return true;
        }

        $user->loadMissing(['teacherProfile', 'employeeProfile']);
        $teacher = $user->teacherProfile ?? $user->employeeProfile;
        if (!$teacher) {
            return false;
        }

        return LessonSchedule::where('semester_id', $semesterId)
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('employee_id', $teacher->id)
            ->where('institution_id', $institutionId)
            ->exists();
    }

    /**
     * Guru boleh set KKM jika mengajar mapel tersebut di minimal satu kelas tingkat itu
     * pada semester terkait. Non-guru dengan akses modul tetap boleh.
     */
    private function canSetSubjectKkm($user, int $institutionId, int $semesterId, int $subjectId, int $grade): bool
    {
        if (!$user->isTeacherOrStaff()) {
            return true;
        }

        $user->loadMissing(['teacherProfile', 'employeeProfile']);
        $teacher = $user->teacherProfile ?? $user->employeeProfile;
        if (!$teacher) {
            return false;
        }

        $classIds = SchoolClass::query()
            ->where('institution_id', $institutionId)
            ->where('grade', $grade)
            ->pluck('id');

        if ($classIds->isEmpty()) {
            return false;
        }

        return LessonSchedule::where('semester_id', $semesterId)
            ->where('subject_id', $subjectId)
            ->where('employee_id', $teacher->id)
            ->where('institution_id', $institutionId)
            ->whereIn('class_id', $classIds)
            ->exists();
    }

    private function resolveSubjectKkm(int $institutionId, int $subjectId, int $grade, int $semesterId): ?SubjectKkm
    {
        return SubjectKkm::query()
            ->where('institution_id', $institutionId)
            ->where('subject_id', $subjectId)
            ->where('grade', $grade)
            ->where('semester_id', $semesterId)
            ->first();
    }

    private function enrichRowsWithKkm(array $rows, ?float $kkm): array
    {
        return array_map(function (array $row) use ($kkm) {
            $nilaiAkhir = isset($row['nilai_akhir']) && $row['nilai_akhir'] !== null
                ? (float) $row['nilai_akhir']
                : null;
            $row['predicate'] = SubjectKkm::predicateFromScore($nilaiAkhir, $kkm);
            $tuntas = SubjectKkm::isTuntas($nilaiAkhir, $kkm);
            $row['is_tuntas'] = $tuntas;
            $row['tuntas_label'] = $tuntas === null ? null : ($tuntas ? 'Tuntas' : 'Belum tuntas');
            return $row;
        }, $rows);
    }

    /**
     * List grades with filters.
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $filters = $request->only(['semester_id', 'class_id', 'subject_id', 'student_id']);
            $perPage = min($request->get('per_page', 50), 100);

            if ($user->isTeacherOrStaff()) {
                $user->loadMissing(['teacherProfile', 'employeeProfile']);
                $teacher = $user->teacherProfile ?? $user->employeeProfile;
                if (!$teacher) {
                    return response()->json(['message' => 'Profil guru tidak ditemukan.'], 403);
                }
                $filters['taught_by_employee_id'] = $teacher->id;
            }

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

            if (!$this->teacherOwnsPair($user, $institutionId, $semesterId, $classId, $subjectId)) {
                return response()->json(['message' => 'Anda tidak mengajar mapel ini di kelas tersebut.'], 403);
            }

            $schoolClass = SchoolClass::query()
                ->where('id', $classId)
                ->where('institution_id', $institutionId)
                ->first(['id', 'name', 'grade']);

            if (!$schoolClass) {
                return response()->json(['message' => 'Kelas tidak ditemukan.'], 404);
            }

            $gradeLevel = (int) ($schoolClass->grade ?? 0);
            $kkmRow = $gradeLevel > 0
                ? $this->resolveSubjectKkm($institutionId, $subjectId, $gradeLevel, $semesterId)
                : null;
            $kkmValue = $kkmRow?->kkm !== null ? (float) $kkmRow->kkm : null;

            $page = max(1, (int) $request->get('page', 1));
            $perPage = min(max(1, (int) $request->get('per_page', 20)), 100);

            $result = $this->gradeService->getByClassSubjectSemester(
                $institutionId,
                $classId,
                $subjectId,
                $semesterId,
                $page,
                $perPage
            );
            $rows = $this->enrichRowsWithKkm($result['data'], $kkmValue);
            $pagination = $result['meta'];

            $weights = $this->gradeService->resolveWeights($institutionId, $classId, $subjectId, $semesterId);
            $canSetWeights = $this->teacherOwnsPair($user, $institutionId, $semesterId, $classId, $subjectId)
                || !$user->isTeacherOrStaff();
            $assessmentCount = max(
                (int) $weights['assessment_count'],
                (int) ($result['assessment_max'] ?? 0),
                1
            );

            $rankingScores = [];
            foreach (($result['ranking_scores'] ?? []) as $sid => $score) {
                $rankingScores[(string) $sid] = $score;
            }

            return response()->json([
                'data' => $rows,
                'meta' => [
                    'class_id' => $classId,
                    'class_name' => $schoolClass->name,
                    'grade' => $gradeLevel ?: null,
                    'subject_id' => $subjectId,
                    'semester_id' => $semesterId,
                    'kkm' => $kkmValue,
                    'kkm_set' => $kkmRow !== null,
                    'can_set_kkm' => $gradeLevel > 0
                        && $this->canSetSubjectKkm($user, $institutionId, $semesterId, $subjectId, $gradeLevel),
                    'weights' => [
                        'penilaian' => (float) $weights['weight_penilaian'],
                        'uts' => (float) $weights['weight_uts'],
                        'uas' => (float) $weights['weight_uas'],
                        'set' => (bool) $weights['set'],
                    ],
                    'deadlines' => [
                        'penilaian' => $weights['deadline_penilaian'] ?? null,
                        'uts' => $weights['deadline_uts'] ?? null,
                        'uas' => $weights['deadline_uas'] ?? null,
                        'nilai_akhir' => $weights['deadline_nilai_akhir'] ?? null,
                    ],
                    'assessment_count' => $assessmentCount,
                    'can_set_weights' => $canSetWeights,
                    'ranking_scores' => $rankingScores,
                    'current_page' => $pagination['current_page'],
                    'last_page' => $pagination['last_page'],
                    'per_page' => $pagination['per_page'],
                    'total' => $pagination['total'],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Grade getByClassSubjectSemester failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil data buku nilai.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Upsert bobot nilai (Penilaian / UTS / UAS) per kelas + mapel + semester.
     */
    public function upsertGradeWeight(UpsertGradeWeightRequest $request): JsonResponse
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

            if (!$this->teacherOwnsPair($user, $institutionId, $semesterId, $classId, $subjectId)) {
                return response()->json([
                    'message' => 'Anda tidak berwenang mengatur bobot nilai untuk mapel/kelas ini.',
                ], 403);
            }

            $employeeId = null;
            if ($user->isTeacherOrStaff()) {
                $user->loadMissing(['teacherProfile', 'employeeProfile']);
                $employeeId = ($user->teacherProfile ?? $user->employeeProfile)?->id;
            }

            $payload = [
                'weight_penilaian' => round((float) $data['weight_penilaian'], 2),
                'weight_uts' => round((float) $data['weight_uts'], 2),
                'weight_uas' => round((float) $data['weight_uas'], 2),
                'set_by_employee_id' => $employeeId,
            ];
            if (isset($data['assessment_count'])) {
                $payload['assessment_count'] = max(1, (int) $data['assessment_count']);
            }
            foreach (['deadline_penilaian', 'deadline_uts', 'deadline_uas', 'deadline_nilai_akhir'] as $deadlineKey) {
                if (array_key_exists($deadlineKey, $data)) {
                    $payload[$deadlineKey] = $data[$deadlineKey] ?: null;
                }
            }

            $row = GradeWeight::query()->updateOrCreate(
                [
                    'institution_id' => $institutionId,
                    'class_id' => $classId,
                    'subject_id' => $subjectId,
                    'semester_id' => $semesterId,
                ],
                $payload
            );

            $recalculated = $this->gradeService->recalculateNilaiAkhirForPair(
                $institutionId,
                $classId,
                $subjectId,
                $semesterId,
                [
                    'weight_penilaian' => (float) $row->weight_penilaian,
                    'weight_uts' => (float) $row->weight_uts,
                    'weight_uas' => (float) $row->weight_uas,
                ],
                $employeeId
            );

            return response()->json([
                'message' => $recalculated > 0
                    ? "Bobot nilai berhasil disimpan. {$recalculated} nilai akhir dihitung ulang."
                    : 'Bobot nilai berhasil disimpan.',
                'data' => [
                    'id' => $row->id,
                    'class_id' => $row->class_id,
                    'subject_id' => $row->subject_id,
                    'semester_id' => $row->semester_id,
                    'weight_penilaian' => (float) $row->weight_penilaian,
                    'weight_uts' => (float) $row->weight_uts,
                    'weight_uas' => (float) $row->weight_uas,
                    'assessment_count' => (int) $row->assessment_count,
                    'deadline_penilaian' => optional($row->deadline_penilaian)?->format('Y-m-d'),
                    'deadline_uts' => optional($row->deadline_uts)?->format('Y-m-d'),
                    'deadline_uas' => optional($row->deadline_uas)?->format('Y-m-d'),
                    'deadline_nilai_akhir' => optional($row->deadline_nilai_akhir)?->format('Y-m-d'),
                    'recalculated_students' => $recalculated,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Grade upsertGradeWeight failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal menyimpan bobot nilai.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Kelengkapan nilai + status deadline untuk kelas+mapel+semester.
     */
    public function completeness(Request $request): JsonResponse
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

            $summary = app(\App\Services\GradeRemedialService::class)->completeness(
                $institutionId,
                $classId,
                $subjectId,
                $semesterId
            );

            return response()->json(['data' => $summary]);
        } catch (\Exception $e) {
            Log::error('Grade completeness failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal memuat kelengkapan nilai.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Upsert KKM mapel per tingkat (grade) per semester.
     */
    public function upsertSubjectKkm(UpsertSubjectKkmRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $data = $request->validated();
            $semesterId = (int) $data['semester_id'];
            $subjectId = (int) $data['subject_id'];
            $gradeLevel = (int) $data['grade'];
            $kkm = round((float) $data['kkm'], 2);

            if (!empty($data['class_id'])) {
                $schoolClass = SchoolClass::query()
                    ->where('id', (int) $data['class_id'])
                    ->where('institution_id', $institutionId)
                    ->first(['id', 'grade']);
                if (!$schoolClass) {
                    return response()->json(['message' => 'Kelas tidak ditemukan.'], 404);
                }
                if ((int) $schoolClass->grade !== $gradeLevel) {
                    return response()->json([
                        'message' => 'Tingkat tidak sesuai dengan kelas yang dipilih.',
                    ], 422);
                }
            }

            if (!$this->canSetSubjectKkm($user, $institutionId, $semesterId, $subjectId, $gradeLevel)) {
                return response()->json([
                    'message' => 'Anda tidak berwenang mengatur KKM mapel untuk tingkat ini.',
                ], 403);
            }

            $employeeId = null;
            if ($user->isTeacherOrStaff()) {
                $user->loadMissing(['teacherProfile', 'employeeProfile']);
                $employeeId = ($user->teacherProfile ?? $user->employeeProfile)?->id;
            }

            $row = SubjectKkm::query()->updateOrCreate(
                [
                    'institution_id' => $institutionId,
                    'subject_id' => $subjectId,
                    'grade' => $gradeLevel,
                    'semester_id' => $semesterId,
                ],
                [
                    'kkm' => $kkm,
                    'set_by_employee_id' => $employeeId,
                ]
            );

            return response()->json([
                'message' => 'KKM berhasil disimpan.',
                'data' => [
                    'id' => $row->id,
                    'subject_id' => $row->subject_id,
                    'grade' => $row->grade,
                    'semester_id' => $row->semester_id,
                    'kkm' => (float) $row->kkm,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Grade upsertSubjectKkm failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal menyimpan KKM.',
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
                    if (!$this->teacherOwnsPair($user, $institutionId, $semesterId, $classId, $subjectId)) {
                        return response()->json(['message' => 'Anda tidak mengajar mapel ini di kelas tersebut.'], 403);
                    }
                }
            }

            $weights = $this->gradeService->resolveWeights($institutionId, $classId, $subjectId, $semesterId);
            $weightPayload = [
                'weight_penilaian' => (float) $weights['weight_penilaian'],
                'weight_uts' => (float) $weights['weight_uts'],
                'weight_uas' => (float) $weights['weight_uas'],
            ];

            if (!empty($data['assessment_count'])) {
                $countWanted = max(1, (int) $data['assessment_count']);
                GradeWeight::query()->updateOrCreate(
                    [
                        'institution_id' => $institutionId,
                        'class_id' => $classId,
                        'subject_id' => $subjectId,
                        'semester_id' => $semesterId,
                    ],
                    [
                        'weight_penilaian' => $weightPayload['weight_penilaian'],
                        'weight_uts' => $weightPayload['weight_uts'],
                        'weight_uas' => $weightPayload['weight_uas'],
                        'assessment_count' => $countWanted,
                        'set_by_employee_id' => $employeeId,
                    ]
                );
            }

            $count = $this->gradeService->bulkUpsert(
                $institutionId,
                $semesterId,
                $classId,
                $subjectId,
                $grades,
                $employeeId,
                $weightPayload
            );
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
                    if (!$this->teacherOwnsPair($user, $institutionId, $semesterId, $classId, $subjectId)) {
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

            if (!$this->teacherOwnsPair($user, $institutionId, $semesterId, $classId, $subjectId)) {
                return response()->json(['message' => 'Anda tidak mengajar mapel ini di kelas tersebut.'], 403);
            }

            $result = $this->gradeService->getByClassSubjectSemester(
                $institutionId,
                $classId,
                $subjectId,
                $semesterId,
                1,
                10000
            );
            $rows = $result['data'];
            $weights = $this->gradeService->resolveWeights($institutionId, $classId, $subjectId, $semesterId);
            $assessmentCount = max(
                (int) $weights['assessment_count'],
                (int) ($result['assessment_max'] ?? 0),
                1
            );
            $filename = 'buku-nilai-' . date('Y-m-d-His') . '.csv';

            return response()->streamDownload(function () use ($rows, $assessmentCount) {
                $out = fopen('php://output', 'w');
                fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
                $header = ['No', 'Peringkat', 'Nama', 'NIS', 'NISN'];
                for ($i = 1; $i <= $assessmentCount; $i++) {
                    $header[] = 'Penilaian ' . $i;
                }
                $header = array_merge($header, ['Rata Penilaian', 'UTS', 'UAS', 'Nilai Akhir']);
                fputcsv($out, $header);
                foreach ($rows as $i => $row) {
                    $penilaian = (array) ($row['penilaian'] ?? []);
                    $line = [
                        $i + 1,
                        $row['rank'] ?? '',
                        $row['student']['name'] ?? '',
                        $row['student']['nis'] ?? '',
                        $row['student']['nisn'] ?? '',
                    ];
                    for ($n = 1; $n <= $assessmentCount; $n++) {
                        $line[] = $penilaian[(string) $n] ?? $penilaian[$n] ?? '';
                    }
                    $line[] = $row['rata_penilaian'] ?? '';
                    $line[] = $row['uts'] ?? '';
                    $line[] = $row['uas'] ?? '';
                    $line[] = $row['nilai_akhir'] ?? '';
                    fputcsv($out, $line);
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
     * Cetak buku nilai (PDF) untuk class + subject + semester.
     */
    public function exportPdf(Request $request)
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

            $schoolClass = SchoolClass::query()
                ->where('id', $classId)
                ->where('institution_id', $institutionId)
                ->first(['id', 'name', 'grade']);
            if (!$schoolClass) {
                return response()->json(['message' => 'Kelas tidak ditemukan.'], 404);
            }

            $subject = \App\Models\Subject::query()->find($subjectId, ['id', 'name', 'code']);
            $semester = \App\Models\Semester::query()->find($semesterId, ['id', 'name', 'end_date', 'start_date']);
            $institution = \App\Models\Institution::query()->find($institutionId);

            $gradeLevel = (int) ($schoolClass->grade ?? 0);
            $kkmRow = $gradeLevel > 0
                ? $this->resolveSubjectKkm($institutionId, $subjectId, $gradeLevel, $semesterId)
                : null;
            $kkmValue = $kkmRow?->kkm !== null ? (float) $kkmRow->kkm : null;

            $result = $this->gradeService->getByClassSubjectSemester(
                $institutionId,
                $classId,
                $subjectId,
                $semesterId,
                1,
                10000
            );
            $rows = $this->enrichRowsWithKkm($result['data'], $kkmValue);
            $weights = $this->gradeService->resolveWeights($institutionId, $classId, $subjectId, $semesterId);
            $assessmentCount = max(
                (int) $weights['assessment_count'],
                (int) ($result['assessment_max'] ?? 0),
                1
            );

            // Urutkan: yang punya peringkat dulu (asc), lalu tanpa peringkat by nama
            usort($rows, function ($a, $b) {
                $ra = $a['rank'] ?? null;
                $rb = $b['rank'] ?? null;
                if ($ra !== null && $rb !== null && $ra !== $rb) {
                    return $ra <=> $rb;
                }
                if ($ra !== null && $rb === null) {
                    return -1;
                }
                if ($ra === null && $rb !== null) {
                    return 1;
                }
                $na = (string) ($a['student']['name'] ?? '');
                $nb = (string) ($b['student']['name'] ?? '');

                return strcasecmp($na, $nb);
            });

            $teacherEmployee = $this->resolveSubjectTeacher(
                $user,
                $institutionId,
                $semesterId,
                $classId,
                $subjectId
            );

            $asOfDate = StructuralPositionResolver::semesterAsOfDate($semester);
            $signatureDate = $asOfDate->locale('id')->translatedFormat('d F Y');

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('grade_book.print', [
                'institution' => $institution,
                'class_name' => $schoolClass->name,
                'subject_name' => $subject?->name ?? '-',
                'semester_name' => $semester?->name ?? '-',
                'grade' => $gradeLevel ?: null,
                'kkm' => $kkmValue,
                'weights' => [
                    'penilaian' => (float) $weights['weight_penilaian'],
                    'uts' => (float) $weights['weight_uts'],
                    'uas' => (float) $weights['weight_uas'],
                ],
                'assessment_count' => $assessmentCount,
                'rows' => $rows,
                'printed_at' => now()->timezone(config('app.timezone'))->format('d/m/Y H:i'),
                'printed_by' => $user?->name,
                'teacher' => $teacherEmployee,
                'signature_date' => $signatureDate,
                'as_of_date' => $asOfDate,
            ])->setPaper('a4', 'landscape');

            $filename = 'buku-nilai-' . date('Ymd-His') . '.pdf';

            return $pdf->stream($filename, ['Attachment' => false]);
        } catch (\Exception $e) {
            Log::error('Grade exportPdf failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mencetak buku nilai PDF.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Guru mapel untuk kelas + subject + semester (dari jadwal).
     * Preferensi: profil guru yang sedang login jika mengajar pair tersebut.
     */
    private function resolveSubjectTeacher(
        $user,
        int $institutionId,
        int $semesterId,
        int $classId,
        int $subjectId
    ): ?\App\Models\Employee {
        $query = LessonSchedule::query()
            ->where('institution_id', $institutionId)
            ->where('semester_id', $semesterId)
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->whereNotNull('employee_id');

        $employeeId = null;
        if ($user && method_exists($user, 'isTeacherOrStaff') && $user->isTeacherOrStaff()) {
            $user->loadMissing(['teacherProfile', 'employeeProfile']);
            $profile = $user->teacherProfile ?? $user->employeeProfile;
            if ($profile && (clone $query)->where('employee_id', $profile->id)->exists()) {
                $employeeId = (int) $profile->id;
            }
        }

        if (!$employeeId) {
            $employeeId = (int) (clone $query)->value('employee_id');
        }

        if (!$employeeId) {
            return null;
        }

        return \App\Models\Employee::query()
            ->where('id', $employeeId)
            ->first(['id', 'name', 'nip']);
    }

    /**
     * Rekap nilai akhir semua mapel per siswa dalam satu kelas (wali kelas).
     */
    public function getByClassSemester(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $semesterId = (int) $request->get('semester_id');
            $classId = (int) $request->get('class_id');
            if (!$semesterId || !$classId) {
                return response()->json(['message' => 'Semester dan kelas wajib dipilih.'], 422);
            }

            if (!$this->canViewClassRaport($user, $classId)) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke rekap nilai kelas ini.'], 403);
            }

            $payload = $this->gradeService->getByClassSemester($institutionId, $classId, $semesterId);

            return response()->json(['data' => $payload]);
        } catch (\Exception $e) {
            Log::error('Grade getByClassSemester failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mengambil rekap nilai kelas.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Export rekap nilai kelas (semua mapel + rata-rata + ranking) as CSV.
     */
    public function exportClassRaport(Request $request): StreamedResponse|JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $semesterId = (int) $request->get('semester_id');
            $classId = (int) $request->get('class_id');
            if (!$semesterId || !$classId) {
                return response()->json(['message' => 'Semester dan kelas wajib dipilih.'], 422);
            }

            if (!$this->canViewClassRaport($user, $classId)) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke rekap nilai kelas ini.'], 403);
            }

            $schoolClass = SchoolClass::query()
                ->where('id', $classId)
                ->where('institution_id', $institutionId)
                ->first(['id', 'name']);
            if (!$schoolClass) {
                return response()->json(['message' => 'Kelas tidak ditemukan.'], 404);
            }

            $payload = $this->gradeService->getByClassSemester($institutionId, $classId, $semesterId);
            $subjects = $payload['subjects'] ?? [];
            $rows = $payload['rows'] ?? [];
            $filename = 'rekap-nilai-kelas-' . Str::slug($schoolClass->name) . '-' . date('Y-m-d-His') . '.csv';

            return response()->streamDownload(function () use ($subjects, $rows) {
                $out = fopen('php://output', 'w');
                fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
                $header = ['No', 'NIS', 'NISN', 'Nama'];
                foreach ($subjects as $subject) {
                    $label = $subject['name'] ?? ('Mapel ' . ($subject['id'] ?? ''));
                    if (isset($subject['kkm']) && $subject['kkm'] !== null) {
                        $label .= ' (KKM ' . $subject['kkm'] . ')';
                    }
                    $header[] = $label;
                }
                $header[] = 'Rata-rata';
                $header[] = 'Ranking';
                fputcsv($out, $header);

                foreach ($rows as $i => $row) {
                    $gradesBySubject = [];
                    foreach ($row['subjects'] ?? [] as $entry) {
                        $gradesBySubject[(int) ($entry['subject_id'] ?? 0)] = $entry['nilai_akhir'] ?? null;
                    }

                    $line = [
                        $i + 1,
                        $row['student']['nis'] ?? '',
                        $row['student']['nisn'] ?? '',
                        $row['student']['name'] ?? '',
                    ];
                    foreach ($subjects as $subject) {
                        $sid = (int) ($subject['id'] ?? 0);
                        $line[] = $gradesBySubject[$sid] ?? '';
                    }
                    $line[] = $row['average'] ?? '';
                    $line[] = $row['rank'] ?? '';
                    fputcsv($out, $line);
                }
                fclose($out);
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        } catch (\Exception $e) {
            Log::error('Grade exportClassRaport failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mengekspor rekap nilai kelas.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Cetak rekap nilai kelas (PDF): semua mapel, rata-rata, ranking.
     */
    public function exportClassRaportPdf(Request $request)
    {
        try {
            $user = $request->user();
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $semesterId = (int) $request->get('semester_id');
            $classId = (int) $request->get('class_id');
            if (!$semesterId || !$classId) {
                return response()->json(['message' => 'Semester dan kelas wajib dipilih.'], 422);
            }

            if (!$this->canViewClassRaport($user, $classId)) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke rekap nilai kelas ini.'], 403);
            }

            $schoolClass = SchoolClass::query()
                ->with('teacher:id,name,nip')
                ->where('id', $classId)
                ->where('institution_id', $institutionId)
                ->first(['id', 'name', 'grade', 'teacher_id']);
            if (!$schoolClass) {
                return response()->json(['message' => 'Kelas tidak ditemukan.'], 404);
            }

            $semester = \App\Models\Semester::query()->find($semesterId, ['id', 'name', 'end_date', 'start_date']);
            $institution = \App\Models\Institution::query()->find($institutionId);
            $payload = $this->gradeService->getByClassSemester($institutionId, $classId, $semesterId);

            $asOfDate = StructuralPositionResolver::semesterAsOfDate($semester);
            $signatureDate = $asOfDate->locale('id')->translatedFormat('d F Y');

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('grade_book.class_raport', [
                'institution' => $institution,
                'class_name' => $schoolClass->name,
                'grade' => $schoolClass->grade ?: null,
                'semester_name' => $semester?->name ?? '-',
                'subjects' => $payload['subjects'] ?? [],
                'rows' => $payload['rows'] ?? [],
                'printed_at' => now()->timezone(config('app.timezone'))->format('d/m/Y H:i'),
                'printed_by' => $user?->name,
                'wali_kelas' => $schoolClass->teacher,
                'signature_date' => $signatureDate,
                'as_of_date' => $asOfDate,
            ])->setPaper('a4', 'landscape');

            $filename = 'rekap-nilai-kelas-' . date('Ymd-His') . '.pdf';

            return $pdf->stream($filename, ['Attachment' => false]);
        } catch (\Exception $e) {
            Log::error('Grade exportClassRaportPdf failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mencetak rekap nilai kelas PDF.',
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

            $maxPenilaian = 1;
            foreach ($rows as $row) {
                $penilaian = (array) ($row['penilaian'] ?? []);
                foreach (array_keys($penilaian) as $k) {
                    $maxPenilaian = max($maxPenilaian, (int) $k);
                }
            }

            return response()->streamDownload(function () use ($student, $rows, $maxPenilaian) {
                $out = fopen('php://output', 'w');
                fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($out, ['Raport Siswa: ' . $student->name . ' (NIS: ' . ($student->nis ?? '-') . ')']);
                fputcsv($out, []);
                $header = ['Mata Pelajaran'];
                for ($i = 1; $i <= $maxPenilaian; $i++) {
                    $header[] = 'Penilaian ' . $i;
                }
                $header = array_merge($header, ['Rata Penilaian', 'UTS', 'UAS', 'Nilai Akhir', 'KKM', 'Predikat', 'Ketuntasan']);
                fputcsv($out, $header);
                foreach ($rows as $row) {
                    $penilaian = (array) ($row['penilaian'] ?? []);
                    $line = [$row['subject']['name'] ?? ''];
                    for ($n = 1; $n <= $maxPenilaian; $n++) {
                        $line[] = $penilaian[(string) $n] ?? $penilaian[$n] ?? '';
                    }
                    $line[] = $row['rata_penilaian'] ?? '';
                    $line[] = $row['uts'] ?? '';
                    $line[] = $row['uas'] ?? '';
                    $line[] = $row['nilai_akhir'] ?? '';
                    $line[] = $row['kkm'] ?? '';
                    $line[] = $row['predicate'] ?? '';
                    $line[] = $row['tuntas_label'] ?? '';
                    fputcsv($out, $line);
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
