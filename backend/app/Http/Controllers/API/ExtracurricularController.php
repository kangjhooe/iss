<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExtracurricularRequest;
use App\Http\Requests\StoreExtracurricularStudentRequest;
use App\Http\Requests\UpdateExtracurricularRequest;
use App\Http\Requests\UpdateExtracurricularStudentRequest;
use App\Http\Resources\ExtracurricularResource;
use App\Http\Resources\ExtracurricularStudentResource;
use App\Models\Extracurricular;
use App\Models\ExtracurricularGrade;
use App\Models\ExtracurricularSession;
use App\Models\ExtracurricularSessionGrade;
use App\Models\ExtracurricularStudent;
use App\Models\Institution;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Support\ExtracurricularAccess;
use App\Support\InstitutionContext;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExtracurricularController extends Controller
{
    /**
     * List extracurriculars for current institution.
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = InstitutionContext::resolveForUser($user, $request, $request->get('institution_id'));
            if (!$institutionId && !$user->isSuperAdmin()) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }
            if (!$institutionId) {
                return response()->json(['message' => 'Pilih institusi.'], 400);
            }

            $query = Extracurricular::forInstitution($institutionId)
                ->withCount(['extracurricularStudents as participants_count' => function ($q) use ($institutionId) {
                    $activeSemesterId = $this->getActiveSemesterId($institutionId);
                    if ($activeSemesterId) {
                        $q->where('semester_id', $activeSemesterId);
                    }
                    $q->where('status', 'aktif');
                }])
                ->with(['supervisor:id,name,nip,email', 'academicYear:id,name,code', 'semester:id,name', 'room:id,name,code']);

            ExtracurricularAccess::scopeVisible($query, $user);

            if ($request->filled('status')) {
                $query->where('status', $request->get('status'));
            }
            if ($request->filled('semester_id')) {
                $query->where('semester_id', $request->get('semester_id'));
            }
            if ($request->filled('academic_year_id')) {
                $query->where('academic_year_id', $request->get('academic_year_id'));
            }
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%');
                });
            }
            if ($request->filled('is_pramuka')) {
                $query->where('is_pramuka', filter_var($request->get('is_pramuka'), FILTER_VALIDATE_BOOLEAN));
            }

            $query->orderBy('name');
            $perPage = min($request->get('per_page', 15), 100);
            $items = $query->paginate($perPage);

            return ExtracurricularResource::collection($items)->additional([
                'meta_access' => [
                    'can_manage_all' => ExtracurricularAccess::canManageAll($user),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Extracurricular index failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil data ekstrakurikuler.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Store a new extracurricular.
     */
    public function store(StoreExtracurricularRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!ExtracurricularAccess::canMutateCatalog($user)) {
                return response()->json(['message' => 'Hanya admin/koordinator yang dapat menambah ekstrakurikuler.'], 403);
            }
            $institutionId = InstitutionContext::resolveForUser($user, $request, $request->get('institution_id'));
            if (!$institutionId && !$user->isSuperAdmin()) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }
            if (!$institutionId) {
                return response()->json(['message' => 'Pilih institusi.'], 400);
            }

            $data = $request->validated();
            // KKM hanya diisi pembina, bukan saat admin membuat katalog
            unset($data['kkm']);
            $data['institution_id'] = $institutionId;
            if (!isset($data['status'])) {
                $data['status'] = 'Aktif';
            }

            $institution = Institution::find($institutionId);
            $data['semester_id'] = $institution?->active_semester_id;
            $data['academic_year_id'] = $institution?->active_academic_year_id
                ?? $institution?->activeSemester?->academic_year_id;

            if (isset($data['days_of_week']) && is_array($data['days_of_week'])) {
                $data['days_of_week'] = array_values(array_unique(array_map('intval', $data['days_of_week'])));
                sort($data['days_of_week']);
            }
            if (!empty($data['is_outdoor'])) {
                $data['room_id'] = null;
            } else {
                $data['is_outdoor'] = false;
                $data['location_note'] = $data['location_note'] ?? null;
                if (!empty($data['room_id'])) {
                    $data['location_note'] = null;
                }
            }

            if (!array_key_exists('is_pramuka', $data) || $data['is_pramuka'] === null) {
                $hay = strtolower(($data['name'] ?? '') . ' ' . ($data['description'] ?? ''));
                $data['is_pramuka'] = str_contains($hay, 'pramuka');
            } else {
                $data['is_pramuka'] = (bool) $data['is_pramuka'];
            }

            $extracurricular = Extracurricular::create($data);
            ExtracurricularAccess::grantAccessForEmployee($extracurricular->supervisor_employee_id);

            return (new ExtracurricularResource($extracurricular->load(['supervisor', 'academicYear', 'semester', 'room'])))
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            Log::error('Extracurricular store failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal menambahkan ekstrakurikuler.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Show single extracurricular.
     */
    public function show(Request $request, Extracurricular $extracurricular): ExtracurricularResource|JsonResponse
    {
        $user = $request->user();
        if (!ExtracurricularAccess::canAccess($user, $extracurricular)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $extracurricular->load(['supervisor', 'academicYear', 'semester', 'room']);
        if ($request->boolean('with_participants')) {
            $semesterId = $request->get('semester_id') ?? $this->getActiveSemesterId($extracurricular->institution_id);
            $extracurricular->load(['extracurricularStudents' => function ($q) use ($semesterId) {
                $q->with('student.class');
                if ($semesterId) {
                    $q->where('semester_id', $semesterId);
                }
            }]);
        }

        return (new ExtracurricularResource($extracurricular))->additional([
            'meta_access' => [
                'can_manage_all' => ExtracurricularAccess::canManageAll($user),
                'can_mutate_catalog' => ExtracurricularAccess::canMutateCatalog($user),
                'can_set_kkm' => ExtracurricularAccess::canSetKkm($user, $extracurricular),
            ],
        ]);
    }

    /**
     * Update extracurricular.
     */
    public function update(UpdateExtracurricularRequest $request, Extracurricular $extracurricular): ExtracurricularResource|JsonResponse
    {
        try {
            $user = $request->user();
            if (!ExtracurricularAccess::canAccess($user, $extracurricular)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $data = $request->validated();
            if (!ExtracurricularAccess::canMutateCatalog($user)) {
                unset($data['supervisor_employee_id'], $data['status']);
            }
            // KKM hanya boleh diubah oleh pembina ekskul ini
            if (array_key_exists('kkm', $data) && !ExtracurricularAccess::canSetKkm($user, $extracurricular)) {
                return response()->json([
                    'message' => 'Hanya pembina ekstrakurikuler ini yang boleh mengubah KKM.',
                ], 403);
            }
            if (isset($data['days_of_week']) && is_array($data['days_of_week'])) {
                $data['days_of_week'] = array_values(array_unique(array_map('intval', $data['days_of_week'])));
                sort($data['days_of_week']);
            }
            if (array_key_exists('is_outdoor', $data) && $data['is_outdoor']) {
                $data['room_id'] = null;
            } elseif (!empty($data['room_id'])) {
                $data['is_outdoor'] = false;
                $data['location_note'] = null;
            }
            if (array_key_exists('is_pramuka', $data)) {
                $data['is_pramuka'] = (bool) $data['is_pramuka'];
            } elseif (array_key_exists('name', $data) || array_key_exists('description', $data)) {
                $hay = strtolower(($data['name'] ?? $extracurricular->name) . ' ' . ($data['description'] ?? $extracurricular->description));
                $data['is_pramuka'] = str_contains($hay, 'pramuka');
            }

            $extracurricular->update($data);
            if (array_key_exists('supervisor_employee_id', $data)) {
                ExtracurricularAccess::grantAccessForEmployee($extracurricular->supervisor_employee_id);
            }
            if (array_key_exists('kkm', $data)) {
                $this->recalculatePredicatesForKkm($extracurricular);
            }
            return new ExtracurricularResource($extracurricular->fresh(['supervisor', 'academicYear', 'semester', 'room']));
        } catch (\Exception $e) {
            Log::error('Extracurricular update failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal memperbarui ekstrakurikuler.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Delete extracurricular.
     */
    public function destroy(Request $request, Extracurricular $extracurricular): JsonResponse
    {
        $user = $request->user();
        if (!ExtracurricularAccess::canMutateCatalog($user) || !ExtracurricularAccess::canAccess($user, $extracurricular)) {
            return response()->json(['message' => 'Hanya admin/koordinator yang dapat menghapus ekstrakurikuler.'], 403);
        }

        if ($extracurricular->extracurricularStudents()->exists()) {
            return response()->json([
                'message' => 'Ekstrakurikuler tidak dapat dihapus karena masih memiliki peserta. Keluarkan peserta terlebih dahulu.',
            ], 422);
        }

        $extracurricular->delete();
        return response()->json(['message' => 'Ekstrakurikuler berhasil dihapus.']);
    }

    /**
     * List participants (extracurricular_student) for an extracurricular.
     */
    public function getStudents(Request $request, Extracurricular $extracurricular): JsonResponse
    {
        $user = $request->user();
        if (!ExtracurricularAccess::canAccess($user, $extracurricular)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $semesterId = $request->get('semester_id') ?? $this->getActiveSemesterId($extracurricular->institution_id);
        $query = $extracurricular->extracurricularStudents()->with(['student.class', 'academicYear', 'semester']);
        if ($semesterId) {
            $query->where('semester_id', $semesterId);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        $participants = $query->orderBy('joined_at', 'desc')->get();

        return response()->json([
            'data' => ExtracurricularStudentResource::collection($participants)->resolve(),
        ]);
    }

    /**
     * Lightweight class list for the participant picker.
     * Pembina/koordinator ekskul tidak punya modul class|student.
     */
    public function classesLite(Request $request): JsonResponse
    {
        $user = $request->user();
        $institutionId = InstitutionContext::resolveForUser($user, $request, $request->get('institution_id'));
        if (!$institutionId && !$user->isSuperAdmin()) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }
        if (!$institutionId) {
            return response()->json(['message' => 'Pilih institusi.'], 400);
        }

        $institution = Institution::find($institutionId);
        $query = SchoolClass::query()
            ->where('institution_id', $institutionId)
            ->where('status', 'Aktif')
            ->orderBy('grade')
            ->orderBy('name');

        if ($institution?->active_academic_year_id) {
            $query->where('academic_year_id', $institution->active_academic_year_id);
        }

        return response()->json([
            'data' => $query->get(['id', 'name', 'grade']),
        ]);
    }

    /**
     * List students available to add. Requires class_id.
     */
    public function getAvailableStudents(Request $request, Extracurricular $extracurricular): JsonResponse
    {
        $user = $request->user();
        if (!ExtracurricularAccess::canAccess($user, $extracurricular)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$request->filled('class_id')) {
            return response()->json(['data' => []]);
        }

        try {
            $semesterId = $this->getActiveSemesterId($extracurricular->institution_id);
            $alreadyEnrolledQuery = $extracurricular->extracurricularStudents();
            if ($semesterId) {
                $alreadyEnrolledQuery->where('semester_id', $semesterId);
            } else {
                $alreadyEnrolledQuery->where('status', 'aktif');
            }

            $query = Student::where('institution_id', $extracurricular->institution_id)
                ->where('status', 'Aktif')
                ->where('class_id', $request->get('class_id'))
                ->whereNotIn('id', $alreadyEnrolledQuery->pluck('student_id'));

            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('nis', 'like', '%' . $search . '%')
                        ->orWhere('nisn', 'like', '%' . $search . '%');
                });
            }

            $limit = min((int) $request->get('per_page', 200), 200);
            $students = $query
                ->with('schoolClass:id,name')
                ->orderBy('name')
                ->limit($limit)
                ->get(['id', 'name', 'nis', 'nisn', 'class_id']);

            return response()->json([
                'data' => $students->map(function (Student $student) {
                    $class = $student->schoolClass;
                    return [
                        'id' => (int) $student->id,
                        'name' => $student->name,
                        'nis' => $student->nis,
                        'nisn' => $student->nisn,
                        'class_id' => $student->class_id ? (int) $student->class_id : null,
                        'class' => $class ? [
                            'id' => (int) $class->id,
                            'name' => $class->name,
                        ] : null,
                    ];
                })->values(),
            ]);
        } catch (\Exception $e) {
            Log::error('Extracurricular getAvailableStudents failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal memuat daftar siswa.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Add students as participants (semester/tahun ajaran aktif diisi otomatis).
     */
    public function addStudents(StoreExtracurricularStudentRequest $request, Extracurricular $extracurricular): JsonResponse
    {
        $user = $request->user();
        if (!ExtracurricularAccess::canAccess($user, $extracurricular)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $institution = Institution::find($extracurricular->institution_id);
        $semesterId = $institution?->active_semester_id;
        $academicYearId = $institution?->active_academic_year_id
            ?? $institution?->activeSemester?->academic_year_id
            ?? $extracurricular->academic_year_id;

        if (!$semesterId) {
            return response()->json(['message' => 'Semester aktif tidak ditemukan. Tetapkan semester aktif di profil instansi.'], 400);
        }

        $studentIds = $request->validated()['student_ids'];
        $alreadyEnrolledIds = $extracurricular->extracurricularStudents()
            ->where('semester_id', $semesterId)
            ->pluck('student_id')
            ->all();

        $validStudents = Student::where('institution_id', $extracurricular->institution_id)
            ->whereIn('id', $studentIds)
            ->whereNotIn('id', $alreadyEnrolledIds)
            ->pluck('id');

        $added = 0;
        $joinedAt = now()->format('Y-m-d');

        DB::beginTransaction();
        try {
            foreach ($validStudents as $studentId) {
                ExtracurricularStudent::create([
                    'extracurricular_id' => $extracurricular->id,
                    'student_id' => $studentId,
                    'academic_year_id' => $academicYearId,
                    'semester_id' => $semesterId,
                    'joined_at' => $joinedAt,
                    'status' => 'aktif',
                ]);
                $added++;
            }
            DB::commit();
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            Log::error('Extracurricular addStudents failed', ['error' => $e->getMessage()]);
            if ((string) $e->getCode() === '23000') {
                return response()->json([
                    'message' => 'Sebagian siswa sudah terdaftar sebagai peserta di semester ini.',
                ], 422);
            }
            return response()->json([
                'message' => 'Gagal menambahkan peserta.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Extracurricular addStudents failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal menambahkan peserta.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }

        if ($added === 0) {
            return response()->json(['message' => 'Tidak ada siswa baru yang ditambahkan (sudah terdaftar atau tidak valid).'], 422);
        }

        return response()->json([
            'message' => $added . ' peserta berhasil ditambahkan.',
            'added_count' => $added,
        ], 201);
    }

    /**
     * Remove one student from extracurricular (set left_at and status keluar, or delete pivot).
     */
    public function removeStudent(Request $request, Extracurricular $extracurricular, int $studentId): JsonResponse
    {
        $user = $request->user();
        if (!ExtracurricularAccess::canAccess($user, $extracurricular)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $semesterId = $request->get('semester_id') ?? $this->getActiveSemesterId($extracurricular->institution_id);
        $enrollment = $extracurricular->extracurricularStudents()
            ->where('student_id', $studentId);
        if ($semesterId) {
            $enrollment->where('semester_id', $semesterId);
        }
        $enrollment = $enrollment->first();

        if (!$enrollment) {
            return response()->json(['message' => 'Peserta tidak ditemukan di ekstrakurikuler ini.'], 404);
        }

        $leaveRecord = filter_var($request->get('leave_record', false), FILTER_VALIDATE_BOOLEAN);
        if ($leaveRecord) {
            $enrollment->update([
                'left_at' => $request->get('left_at') ?? now()->format('Y-m-d'),
                'status' => 'keluar',
            ]);
            return response()->json(['message' => 'Peserta berhasil dikeluarkan.']);
        }

        $enrollment->delete();
        return response()->json(['message' => 'Peserta berhasil dihapus dari ekstrakurikuler.']);
    }

    /**
     * List extracurricular enrollments by student (for Student profile / riwayat ekskul).
     */
    public function getByStudent(Request $request, int $studentId): JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = InstitutionContext::resolveForUser($user, $request, $request->get('institution_id'));
            if ($user->isStudent()) {
                $profile = $user->studentProfile;
                if (!$profile || (int) $profile->id !== $studentId) {
                    return response()->json(['message' => 'Anda hanya dapat melihat data sendiri.'], 403);
                }
                $institutionId = $profile->institution_id;
            }
            if (!$institutionId && !$user->isSuperAdmin()) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $student = Student::where('id', $studentId)
                ->when(!$user->isSuperAdmin(), fn ($q) => $q->where('institution_id', $institutionId))
                ->first();

            if (!$student) {
                return response()->json(['message' => 'Siswa tidak ditemukan.'], 404);
            }

            $enrollments = ExtracurricularStudent::where('student_id', $studentId)
                ->with([
                    'extracurricular:id,name,institution_id,kkm,supervisor_employee_id',
                    'extracurricular.supervisor:id,name',
                    'semester:id,name',
                    'academicYear:id,name',
                ])
                ->orderByDesc('joined_at')
                ->limit(100)
                ->get();

            return response()->json(['data' => ExtracurricularStudentResource::collection($enrollments)]);
        } catch (\Exception $e) {
            Log::error('Extracurricular getByStudent failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil riwayat ekstrakurikuler siswa.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Export participants (CSV) for an extracurricular.
     */
    public function exportParticipants(Request $request, Extracurricular $extracurricular): StreamedResponse|JsonResponse
    {
        $user = $request->user();
        if (!ExtracurricularAccess::canAccess($user, $extracurricular)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $semesterId = $request->get('semester_id') ?? $this->getActiveSemesterId($extracurricular->institution_id);
        $query = $extracurricular->extracurricularStudents()->with(['student.class', 'semester']);
        if ($semesterId) {
            $query->where('semester_id', $semesterId);
        }
        $participants = $query->get()->sortBy(fn ($p) => $p->student?->name ?? '')->values();

        $filename = 'peserta-ekskul-' . \Illuminate\Support\Str::slug($extracurricular->name) . '-' . date('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($participants) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, ['No', 'Nama', 'NIS', 'NISN', 'Kelas', 'Semester', 'Bergabung', 'Keluar', 'Status', 'Catatan']);
            $no = 1;
            foreach ($participants as $p) {
                fputcsv($out, [
                    $no++,
                    $p->student?->name ?? '-',
                    $p->student?->nis ?? '-',
                    $p->student?->nisn ?? '-',
                    self::studentClassName($p->student),
                    $p->semester?->name ?? '-',
                    $p->joined_at?->format('Y-m-d') ?? '-',
                    $p->left_at?->format('Y-m-d') ?? '-',
                    $p->status ?? '-',
                    $p->notes ?? '',
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Update enrollment (status, left_at, notes).
     */
    public function updateEnrollment(UpdateExtracurricularStudentRequest $request, Extracurricular $extracurricular, int $enrollmentId): JsonResponse
    {
        $user = $request->user();
        if (!ExtracurricularAccess::canAccess($user, $extracurricular)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $enrollment = ExtracurricularStudent::where('id', $enrollmentId)
            ->where('extracurricular_id', $extracurricular->id)
            ->first();

        if (!$enrollment) {
            return response()->json(['message' => 'Data peserta tidak ditemukan.'], 404);
        }

        $data = $request->validated();
        if (isset($data['status']) && in_array($data['status'], ['keluar', 'lulus']) && empty($enrollment->left_at)) {
            $data['left_at'] = $data['left_at'] ?? now()->format('Y-m-d');
        }
        $enrollment->update($data);

        return response()->json([
            'message' => 'Data peserta berhasil diperbarui.',
            'data' => new ExtracurricularStudentResource($enrollment->load('student', 'semester')),
        ]);
    }

    private function getActiveSemesterId(?int $institutionId): ?int
    {
        if (!$institutionId) {
            return null;
        }
        $institution = Institution::find($institutionId);

        return $institution?->active_semester_id;
    }

    /**
     * Recalculate predicates for all session + final grades when KKM changes.
     */
    private function recalculatePredicatesForKkm(Extracurricular $extracurricular): void
    {
        $kkm = $extracurricular->kkm_value;
        $sessionIds = ExtracurricularSession::where('extracurricular_id', $extracurricular->id)->pluck('id');

        if ($sessionIds->isNotEmpty()) {
            $rows = ExtracurricularSessionGrade::whereIn('session_id', $sessionIds)->get();
            foreach ($rows as $row) {
                $row->predicate = ExtracurricularGrade::predicateFromScore(
                    $row->score !== null ? (float) $row->score : null,
                    $kkm
                );
                $row->save();
            }
        }

        $finals = ExtracurricularGrade::where('extracurricular_id', $extracurricular->id)->get();
        foreach ($finals as $final) {
            $final->predicate = ExtracurricularGrade::predicateFromScore(
                $final->score !== null ? (float) $final->score : null,
                $kkm
            );
            $final->save();
        }
    }

    /**
     * Student has both string column `class` and relation `class()`.
     * Never use $student->class->name directly.
     */
    private static function studentClassName(?Student $student): string
    {
        if (!$student) {
            return '-';
        }
        if ($student->relationLoaded('class')) {
            $related = $student->getRelation('class');
            if ($related instanceof SchoolClass) {
                return $related->name ?: '-';
            }
        }
        if ($student->class_id) {
            $name = SchoolClass::where('id', $student->class_id)->value('name');
            if ($name) {
                return $name;
            }
        }

        return is_string($student->getAttributes()['class'] ?? null)
            ? ($student->getAttributes()['class'] ?: '-')
            : '-';
    }
}
