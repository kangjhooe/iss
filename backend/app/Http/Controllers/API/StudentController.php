<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\PromoteStudentsRequest;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Institution;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Services\FeederAlumniEnrollmentService;
use App\Services\StudentAccountService;
use App\Services\StudentImportService;
use App\Services\StudentService;
use App\Support\InstitutionContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StudentController extends Controller
{
    protected StudentService $studentService;

    protected StudentAccountService $studentAccountService;

    protected StudentImportService $studentImportService;

    protected FeederAlumniEnrollmentService $feederAlumniEnrollmentService;

    public function __construct(
        StudentService $studentService,
        StudentAccountService $studentAccountService,
        StudentImportService $studentImportService,
        FeederAlumniEnrollmentService $feederAlumniEnrollmentService
    ) {
        $this->studentService = $studentService;
        $this->studentAccountService = $studentAccountService;
        $this->studentImportService = $studentImportService;
        $this->feederAlumniEnrollmentService = $feederAlumniEnrollmentService;
    }

    /**
     * Resolve institution for student APIs.
     * Admin/super admin may omit institution_id (list all) or pass one; others use active context.
     */
    private function resolveStudentInstitutionId(Request $request): ?int
    {
        $user = $request->user();
        if ($user->isAdminOrSuperAdmin()) {
            return $request->filled('institution_id') ? (int) $request->get('institution_id') : null;
        }

        return InstitutionContext::resolveForUser(
            $user,
            $request,
            $request->get('institution_id')
        );
    }

    private function userCanAccessStudent(Request $request, Student $student): bool
    {
        $user = $request->user();
        if ($user->isAdminOrSuperAdmin()) {
            return true;
        }

        $institutionId = $this->resolveStudentInstitutionId($request);

        return $this->studentService->canAccess($student, $institutionId, false);
    }

    /**
     * Display a listing of students.
     *
     * @OA\Get(
     *     path="/api/v1/student",
     *     summary="Daftar siswa",
     *     tags={"Student"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string"), description="Cari nama/NIS/NISN"),
     *     @OA\Parameter(name="class", in="query", required=false, @OA\Schema(type="string"), description="Filter kelas"),
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string"), description="Filter status. Default Aktif jika kosong."),
     *     @OA\Parameter(name="inactive", in="query", required=false, @OA\Schema(type="boolean"), description="Daftar siswa keluar (Pindah, Drop Out, Tidak Aktif)"),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer"), description="Jumlah per halaman (max 100)"),
     *
     *     @OA\Response(response=200, description="Berhasil",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="nis", type="string"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="gender", type="string"),
     *                 @OA\Property(property="class", type="string"),
     *                 @OA\Property(property="status", type="string")
     *             ))
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(Request $request)
    {
        try {
            $institutionId = $this->resolveStudentInstitutionId($request);

            $filters = $this->resolveListFilters($request, $institutionId);

            // Get per page
            $perPage = min($request->get('per_page', 15), 100);

            // Use service to get students
            $students = $this->studentService->list($filters, $institutionId, $perPage);

            return StudentResource::collection($students);
        } catch (\Exception $e) {
            Log::error('Failed to list students', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data siswa',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Export daftar siswa (semua baris sesuai filter, field lengkap untuk Excel).
     */
    public function export(Request $request)
    {
        try {
            $institutionId = $this->resolveStudentInstitutionId($request);

            $filters = $this->resolveListFilters($request, $institutionId);
            $students = $this->studentService->listForExport($filters, $institutionId);

            return StudentResource::collection($students);
        } catch (\Exception $e) {
            Log::error('Failed to export students', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengekspor data siswa',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Build list/export filters from request (shared).
     */
    protected function resolveListFilters(Request $request, ?int $institutionId): array
    {
        $filters = $request->only([
            'search',
            'class',
            'class_id',
            'academic_year',
            'academic_year_id',
            'semester_id',
            'status',
            'gender',
            'tingkat',
            'account_status',
            'missing_nis',
            'sort_by',
            'sort_dir',
        ]);
        $filters['with_trashed'] = filter_var($request->get('with_trashed'), FILTER_VALIDATE_BOOLEAN);
        $filters['only_trashed'] = filter_var($request->get('only_trashed'), FILTER_VALIDATE_BOOLEAN);
        $filters['inactive'] = filter_var($request->get('inactive'), FILTER_VALIDATE_BOOLEAN);

        $hasUnassignedClass = isset($filters['class_id']) && StudentService::isUnassignedFilter($filters['class_id']);
        $hasUnassignedTingkat = isset($filters['tingkat']) && StudentService::isUnassignedFilter($filters['tingkat']);

        $status = isset($filters['status']) ? trim((string) $filters['status']) : '';
        $isInactiveList = ! empty($filters['inactive'])
            || ($status !== '' && $status !== 'Aktif' && strtolower($status) !== 'all');

        // Semester aktif hanya sebagai default daftar siswa aktif.
        // Jangan paksa jika class_id / academic_year_id sudah dipilih, filter "tanpa kelas/tingkat",
        // kotak sampah, atau arsip siswa keluar (semester lama / null).
        if (
            ! isset($filters['semester_id'])
            && ! isset($filters['class_id'])
            && ! isset($filters['academic_year_id'])
            && ! $hasUnassignedClass
            && ! $hasUnassignedTingkat
            && empty($filters['only_trashed'])
            && ! $isInactiveList
            && $institutionId
        ) {
            $institution = \App\Models\Institution::find($institutionId);
            if ($institution && $institution->active_semester_id) {
                $filters['semester_id'] = $institution->active_semester_id;
            }
        }

        return $filters;
    }

    /**
     * Store a newly created student.
     *
     * @OA\Post(
     *     path="/api/v1/student",
     *     summary="Tambah siswa",
     *     tags={"Student"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"nis","nisn","name","gender","class","status"},
     *
     *             @OA\Property(property="institution_id", type="integer", description="ID institusi (untuk super admin)"),
     *             @OA\Property(property="nis", type="string", example="12345"),
     *             @OA\Property(property="nisn", type="string", example="1234567890"),
     *             @OA\Property(property="name", type="string", example="Ahmad Budi"),
     *             @OA\Property(property="gender", type="string", enum={"L","P"}, example="L"),
     *             @OA\Property(property="class", type="string", example="7A"),
     *             @OA\Property(property="status", type="string", example="Aktif")
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Siswa berhasil ditambahkan",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Siswa berhasil ditambahkan"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="Validasi gagal")
     * )
     */
    public function store(StoreStudentRequest $request)
    {
        try {
            $institutionId = $this->resolveStudentInstitutionId($request);

            if (! $institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
            }

            // Get institution with active semester
            $institution = \App\Models\Institution::with('activeSemester')->findOrFail($institutionId);

            if (! $institution->active_semester_id) {
                return response()->json([
                    'message' => 'Semester aktif belum ditetapkan untuk institusi ini',
                ], 400);
            }

            $validated = $request->validated();
            $validated['institution_id'] = $institutionId;
            $validated['semester_id'] = $validated['semester_id'] ?? $institution->active_semester_id; // Set otomatis dari semester aktif jika tidak ada

            // Use service to create student (also auto-creates login account)
            $student = $this->studentService->create($validated);
            $account = $this->studentAccountService->findAccount($student);

            Log::info('Student created', [
                'student_id' => $student->id,
                'institution_id' => $institutionId,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Siswa berhasil ditambahkan'
                    .($account ? '. Akun login dibuat (NIK + tanggal lahir DDMMYYYY).' : ''),
                'data' => new StudentResource($student->loadMissing('userAccount')),
                'user_created' => (bool) $account,
                'login_hint' => $account ? $this->studentAccountService->loginHintFor($student) : null,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create student', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat menambahkan siswa',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Daftar alumni jenjang sebelumnya berdasarkan NPSN sekolah asal.
     */
    public function feederAlumni(Request $request)
    {
        try {
            $institutionId = $this->resolveStudentInstitutionId($request);
            if (! $institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
            }

            $validated = $request->validate([
                'origin_npsn' => 'required|string|size:8|regex:/^[0-9]{8}$/',
                'search' => 'nullable|string|max:100',
                'graduation_year' => 'nullable|integer|min:1990|max:'.(date('Y') + 1),
            ], [
                'origin_npsn.required' => 'NPSN sekolah asal wajib diisi.',
                'origin_npsn.size' => 'NPSN sekolah asal harus 8 digit.',
                'origin_npsn.regex' => 'NPSN sekolah asal harus 8 digit angka.',
            ]);

            $target = Institution::findOrFail($institutionId);
            $result = $this->feederAlumniEnrollmentService->listAlumni(
                $target,
                $validated['origin_npsn'],
                $validated['search'] ?? null,
                isset($validated['graduation_year']) ? (int) $validated['graduation_year'] : null
            );

            return response()->json($result);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to list feeder alumni', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mengambil daftar alumni sekolah asal.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Tarik alumni terpilih dari jenjang sebelumnya menjadi siswa baru.
     */
    public function pullFromFeeder(Request $request)
    {
        try {
            $institutionId = $this->resolveStudentInstitutionId($request);
            if (! $institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
            }

            $validated = $request->validate([
                'origin_npsn' => 'required|string|size:8|regex:/^[0-9]{8}$/',
                'student_ids' => 'required|array|min:1|max:100',
                'student_ids.*' => 'integer',
                'tingkat' => 'nullable|integer',
                'class_id' => 'nullable|integer|exists:class,id',
            ], [
                'origin_npsn.required' => 'NPSN sekolah asal wajib diisi.',
                'student_ids.required' => 'Pilih minimal satu alumni.',
                'student_ids.min' => 'Pilih minimal satu alumni.',
            ]);

            $target = Institution::findOrFail($institutionId);
            $result = $this->feederAlumniEnrollmentService->pull(
                $target,
                $validated['origin_npsn'],
                $validated['student_ids'],
                isset($validated['tingkat']) ? (int) $validated['tingkat'] : null,
                isset($validated['class_id']) ? (int) $validated['class_id'] : null
            );

            $createdCount = count($result['created']);
            $message = $createdCount > 0
                ? $createdCount.' siswa berhasil ditarik dari jenjang sebelumnya.'
                : 'Tidak ada siswa yang ditambahkan.';

            return response()->json([
                'message' => $message,
                'created_count' => $createdCount,
                'skipped_count' => count($result['skipped']),
                'error_count' => count($result['errors']),
                'data' => $result,
            ], $createdCount > 0 ? 201 : 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to pull feeder alumni', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal menarik alumni dari jenjang sebelumnya.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Display the specified student.
     *
     * @OA\Get(
     *     path="/api/v1/student/{id}",
     *     summary="Detail siswa",
     *     tags={"Student"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Berhasil", @OA\JsonContent(@OA\Property(property="data", type="object"))),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Siswa tidak ditemukan")
     * )
     */
    public function show(Request $request, $id)
    {
        try {
            // Use service to find student
            $student = $this->studentService->find($id, ['institution', 'documents', 'class', 'academicYear', 'classHistory', 'userAccount']);

            // Check authorization
            if (! $this->userCanAccessStudent($request, $student)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            return new StudentResource($student);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Siswa tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to get student', [
                'student_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data siswa',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Update the specified student.
     *
     * @OA\Put(
     *     path="/api/v1/student/{id}",
     *     summary="Perbarui siswa",
     *     tags={"Student"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(@OA\JsonContent(
     *
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="class", type="string"),
     *         @OA\Property(property="status", type="string")
     *     )),
     *
     *     @OA\Response(response=200, description="Siswa berhasil diperbarui"),
     *     @OA\Response(response=404, description="Siswa tidak ditemukan")
     * )
     */
    public function update(UpdateStudentRequest $request, $id)
    {
        try {
            // Use service to find student
            $student = $this->studentService->find($id);

            // Check authorization
            if (! $this->userCanAccessStudent($request, $student)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Use service to update student (with automatic history tracking)
            $student = $this->studentService->update($student, $request->validated());

            Log::info('Student updated', [
                'student_id' => $student->id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Siswa berhasil diperbarui',
                'data' => new StudentResource($student),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Siswa tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to update student', [
                'student_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat memperbarui siswa',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Ringkasan kesiapan akun login siswa (sesuai filter daftar).
     */
    public function accountStatus(Request $request)
    {
        try {
            $institutionId = $this->resolveStudentInstitutionId($request);
            $filters = $this->resolveListFilters($request, $institutionId);
            $summary = $this->studentService->accountStatusSummary($filters, $institutionId);

            return response()->json([
                'data' => $summary,
                'login_hint' => [
                    'login' => 'NIK',
                    'default_password' => 'Tanggal lahir (DDMMYYYY)',
                    'must_change_password' => true,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to load student account status', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil status akun siswa',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Buat akun login massal untuk siswa tanpa akun (atau id terpilih).
     */
    public function ensureAccountsBulk(Request $request)
    {
        try {
            $user = $request->user();
            if (! $user->isAdminOrSuperAdmin() && ! $user->isInstitutionAdmin()
                && ! $user->hasModuleAccess('student')) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $validated = $request->validate([
                'class_id' => ['nullable'],
                'student_ids' => ['nullable', 'array'],
                'student_ids.*' => ['integer'],
                'only_missing' => ['nullable', 'boolean'],
                'limit' => ['nullable', 'integer', 'min:1', 'max:2000'],
                'status' => ['nullable', 'string'],
                'tingkat' => ['nullable'],
                'account_status' => ['nullable', 'string'],
            ]);

            $institutionId = $this->resolveStudentInstitutionId($request);
            $filters = $this->resolveListFilters($request, $institutionId);

            if (array_key_exists('class_id', $validated) && $validated['class_id'] !== null && $validated['class_id'] !== '') {
                $filters['class_id'] = $validated['class_id'];
            }
            if (! empty($validated['status'])) {
                $filters['status'] = $validated['status'];
            }
            if (array_key_exists('tingkat', $validated) && $validated['tingkat'] !== null && $validated['tingkat'] !== '') {
                $filters['tingkat'] = $validated['tingkat'];
            }
            if (! empty($validated['account_status'])) {
                $filters['account_status'] = $validated['account_status'];
            }

            $onlyMissing = array_key_exists('only_missing', $validated)
                ? (bool) $validated['only_missing']
                : true;

            $result = $this->studentService->bulkEnsureAccounts(
                $filters,
                $institutionId,
                $validated['student_ids'] ?? null,
                $onlyMissing,
                (int) ($validated['limit'] ?? 500)
            );

            Log::info('Bulk ensure student accounts', [
                'institution_id' => $institutionId,
                'user_id' => $user->id,
                'result' => [
                    'processed' => $result['processed'],
                    'created' => $result['created'],
                    'updated' => $result['updated'],
                    'skipped' => $result['skipped'],
                ],
            ]);

            return response()->json([
                'message' => sprintf(
                    'Selesai: %d dibuat, %d sudah ada/diperbarui, %d dilewati (dari %d diproses).',
                    $result['created'],
                    $result['updated'],
                    $result['skipped'],
                    $result['processed']
                ),
                'data' => $result,
                'login_hint' => [
                    'login' => 'NIK',
                    'default_password' => 'Tanggal lahir (DDMMYYYY)',
                    'must_change_password' => true,
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to bulk ensure student accounts', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat membuat akun login massal',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Ensure student login account exists (NIK + birth date password).
     */
    public function ensureAccount(Request $request, $id)
    {
        try {
            $student = $this->studentService->find($id);
            if (! $this->userCanAccessStudent($request, $student)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if (! $request->user()->isAdminOrSuperAdmin() && ! $request->user()->isInstitutionAdmin()
                && ! $request->user()->hasModuleAccess('student')) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $result = $this->studentAccountService->ensureAccount($student);

            if (! $result['user'] && $result['skipped_reason']) {
                return response()->json([
                    'message' => 'Akun login tidak dapat dibuat: '.$result['skipped_reason'],
                ], 422);
            }

            $student->load('userAccount');

            return response()->json([
                'message' => $result['user_created']
                    ? 'Akun login siswa berhasil dibuat. Sandi awal = tanggal lahir (DDMMYYYY).'
                    : 'Akun login siswa sudah tersedia / diperbarui.',
                'data' => new StudentResource($student),
                'user_created' => $result['user_created'],
                'login_hint' => $this->studentAccountService->loginHintFor($student),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Siswa tidak ditemukan'], 404);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to ensure student account', [
                'student_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat membuat akun login siswa',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Reset student login password to birth date (DDMMYYYY) and force change on next login.
     */
    public function resetPassword(Request $request, $id)
    {
        try {
            $student = $this->studentService->find($id);
            if (! $this->userCanAccessStudent($request, $student)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $user = $request->user();
            if (! $user->isAdminOrSuperAdmin() && ! $user->isInstitutionAdmin()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $account = $this->studentAccountService->resetPasswordToBirthDate($student);
            $student->load('userAccount');

            return response()->json([
                'message' => 'Sandi berhasil direset ke tanggal lahir (DDMMYYYY). Siswa wajib ganti sandi saat login berikutnya.',
                'data' => new StudentResource($student),
                'must_change_password' => (bool) $account->must_change_password,
                'login_hint' => $this->studentAccountService->loginHintFor($student),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Siswa tidak ditemukan'], 404);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to reset student password', [
                'student_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat reset sandi siswa',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Naik kelas: pindahkan siswa dari kelas/tahun ajaran sumber ke kelas/tahun ajaran tujuan (bulk).
     */
    public function promote(PromoteStudentsRequest $request)
    {
        try {
            $validated = $request->validated();
            $sourceClass = \App\Models\SchoolClass::find((int) $validated['source_class_id']);

            $institutionId = $request->user()->isAdminOrSuperAdmin()
                ? ((int) ($request->input('institution_id') ?: ($sourceClass?->institution_id ?? 0)))
                : $this->resolveStudentInstitutionId($request);

            if (! $institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 400);
            }

            if ($sourceClass && (int) $sourceClass->institution_id !== (int) $institutionId) {
                return response()->json(['message' => 'Kelas sumber tidak sesuai institusi.'], 422);
            }

            $studentIds = isset($validated['student_ids']) && is_array($validated['student_ids'])
                ? array_values($validated['student_ids'])
                : null;

            $result = $this->studentService->promoteBulk(
                $institutionId,
                (int) $validated['source_class_id'],
                (int) $validated['source_academic_year_id'],
                (int) $validated['target_class_id'],
                (int) $validated['target_academic_year_id'],
                isset($validated['target_semester_id']) ? (int) $validated['target_semester_id'] : null,
                $studentIds
            );

            $message = $result['success'].' siswa berhasil naik kelas.';
            if (count($result['failed']) > 0) {
                $message .= ' '.count($result['failed']).' gagal.';
            }

            return response()->json([
                'message' => $message,
                'success' => $result['success'],
                'failed' => $result['failed'],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to promote students', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat memproses naik kelas',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Remove the specified student (soft delete).
     *
     * @OA\Delete(
     *     path="/api/v1/student/{id}",
     *     summary="Hapus siswa",
     *     tags={"Student"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Siswa berhasil dihapus"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Siswa tidak ditemukan")
     * )
     */
    public function destroy(Request $request, $id)
    {
        try {
            // Use service to find student
            $student = $this->studentService->find($id);

            // Check authorization
            if (! $this->userCanAccessStudent($request, $student)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Use service to delete student
            $this->studentService->delete($student);

            Log::info('Student deleted', [
                'student_id' => $id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Siswa berhasil dihapus',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Siswa tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to delete student', [
                'student_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat menghapus siswa',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Restore a soft-deleted student.
     */
    public function restore(Request $request, $id)
    {
        try {
            $student = Student::withTrashed()->findOrFail($id);

            if (! $this->userCanAccessStudent($request, $student)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if ($student->trashed()) {
                $student->restore();
            }

            return response()->json([
                'message' => 'Siswa berhasil dipulihkan',
                'data' => new StudentResource($student->fresh()),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Siswa tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to restore student', [
                'student_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat memulihkan siswa',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Permanently delete a student from the trash.
     */
    public function forceDestroy(Request $request, $id)
    {
        try {
            $student = Student::withTrashed()->findOrFail($id);

            if (! $this->userCanAccessStudent($request, $student)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $this->studentService->forceDelete($student);

            Log::info('Student permanently deleted', [
                'student_id' => $id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Siswa dihapus secara permanen',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Siswa tidak ditemukan',
            ], 404);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to permanently delete student', [
                'student_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat menghapus permanen siswa',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Import students from Excel data.
     */
    public function import(Request $request)
    {
        try {
            $studentsData = $request->input('students', []);

            if (empty($studentsData) || ! is_array($studentsData)) {
                return response()->json([
                    'message' => 'Data siswa tidak valid',
                ], 400);
            }

            $institutionId = $this->resolveStudentInstitutionId($request);

            if (! $institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
            }

            // Ambil semester aktif institusi agar siswa impor muncul di list (list difilter by semester_id)
            $institution = \App\Models\Institution::find($institutionId);
            if (! $institution) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
            }

            $validGrades = $this->validGradesForLevel($institution->level);
            $defaults = ['institution_id' => $institutionId];
            if ($institution && $institution->active_semester_id) {
                $defaults['semester_id'] = $institution->active_semester_id;
                $semester = \App\Models\Semester::find($institution->active_semester_id);
                if ($semester && $semester->academic_year_id) {
                    $defaults['academic_year_id'] = $semester->academic_year_id;
                }
            }

            $validatedRows = [];
            $errors = [];

            foreach ($studentsData as $index => $studentData) {
                $rowNumber = is_array($studentData)
                    ? (int) ($studentData['excel_row'] ?? ($index + 1))
                    : ($index + 1);

                $rowValidator = Validator::make(is_array($studentData) ? $studentData : [], [
                    'nik' => ['required'],
                    'name' => ['required'],
                    'birth_place' => ['required', 'string'],
                    'birth_date' => ['required', 'date'],
                    'tingkat' => $validGrades === null
                        ? ['required', 'integer']
                        : ['required', 'integer', Rule::in($validGrades)],
                ], [
                    'nik.required' => 'NIK wajib diisi',
                    'name.required' => 'Nama Lengkap wajib diisi',
                    'birth_place.required' => 'Tempat Lahir wajib diisi',
                    'birth_date.required' => 'Tanggal Lahir wajib diisi',
                    'birth_date.date' => 'Tanggal Lahir tidak valid',
                    'tingkat.required' => 'Tingkat wajib diisi',
                    'tingkat.integer' => 'Tingkat harus berupa angka',
                    'tingkat.in' => 'Tingkat tidak sesuai dengan jenjang institusi',
                ]);

                if ($rowValidator->fails()) {
                    $errors[] = "Baris {$rowNumber}: ".$rowValidator->errors()->first();

                    continue;
                }

                $validatedRows[] = $studentData;
            }

            $result = $this->studentImportService->importFromRows($validatedRows, $institution, $defaults);
            $result['errors'] = array_values(array_merge($errors, $result['errors']));
            $result['error_count'] = count($result['errors']);

            Log::info('Students imported', [
                'success_count' => $result['success_count'],
                'created_count' => $result['created_count'],
                'updated_count' => $result['updated_count'],
                'error_count' => $result['error_count'],
                'institution_id' => $institutionId,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Import selesai',
                'success_count' => $result['success_count'],
                'created_count' => $result['created_count'],
                'updated_count' => $result['updated_count'],
                'error_count' => $result['error_count'],
                'errors' => $result['errors'],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to import students', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengimpor data siswa',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Upload document for student.
     */
    public function uploadDocument(Request $request, $id)
    {
        try {
            $student = Student::findOrFail($id);

            // Jika bukan admin/super admin, hanya bisa upload dokumen siswa dari institusi sendiri
            if (! $this->userCanAccessStudent($request, $student)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Check document count limit (max 20)
            $documentCount = $student->documents()->count();
            if ($documentCount >= 20) {
                return response()->json([
                    'message' => 'Maksimal 20 file dokumen per siswa',
                ], 400);
            }

            // Use standardized file upload validation
            $rules = array_merge(
                \App\Helpers\FileUploadRules::studentDocument(),
                [
                    'name' => 'required|string|max:255',
                    'description' => 'nullable|string',
                ]
            );
            $messages = \App\Helpers\FileUploadRules::messages(
                \App\Helpers\FileUploadRules::TYPE_MIXED,
                \App\Helpers\FileUploadRules::SIZE_SMALL,
                'file',
                false
            );

            $request->validate($rules, $messages);

            $file = $request->file('file');
            // Sanitize file name to prevent path traversal
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
            $fileName = time().'_'.$safeName.'.'.$extension;
            $filePath = $file->storeAs('student_documents/'.$student->id, $fileName, 'public');

            $document = $student->documents()->create([
                'name' => $request->name,
                'file_path' => $filePath,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'description' => $request->description,
            ]);

            Log::info('Student document uploaded', [
                'student_id' => $student->id,
                'document_id' => $document->id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Dokumen berhasil diupload',
                'data' => $document,
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Siswa tidak ditemukan',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to upload student document', [
                'student_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengupload dokumen',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Delete document for student.
     */
    public function deleteDocument(Request $request, $id, $documentId)
    {
        try {
            $student = Student::findOrFail($id);
            $document = StudentDocument::findOrFail($documentId);

            // Verifikasi dokumen milik siswa yang benar
            if ($document->student_id != $student->id) {
                return response()->json(['message' => 'Dokumen tidak ditemukan'], 404);
            }

            // Jika bukan admin/super admin, hanya bisa hapus dokumen siswa dari institusi sendiri
            if (! $this->userCanAccessStudent($request, $student)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Hapus file dari storage
            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            $document->delete();

            Log::info('Student document deleted', [
                'student_id' => $student->id,
                'document_id' => $documentId,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Dokumen berhasil dihapus',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Siswa atau dokumen tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to delete student document', [
                'student_id' => $id,
                'document_id' => $documentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat menghapus dokumen',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Download document for student.
     */
    public function downloadDocument(Request $request, $id, $documentId)
    {
        try {
            $student = Student::findOrFail($id);
            $document = StudentDocument::findOrFail($documentId);

            // Verifikasi dokumen milik siswa yang benar
            if ($document->student_id != $student->id) {
                return response()->json(['message' => 'Dokumen tidak ditemukan'], 404);
            }

            // Jika bukan admin/super admin, hanya bisa download dokumen siswa dari institusi sendiri
            if (! $this->userCanAccessStudent($request, $student)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if (! Storage::disk('public')->exists($document->file_path)) {
                return response()->json([
                    'message' => 'File dokumen tidak ditemukan',
                ], 404);
            }

            return Storage::disk('public')->download($document->file_path, $document->file_name);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Siswa atau dokumen tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to download student document', [
                'student_id' => $id,
                'document_id' => $documentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengunduh dokumen',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function uploadPhoto(Request $request, $id)
    {
        try {
            $student = Student::findOrFail($id);
            if (! $this->userCanAccessStudent($request, $student)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $request->validate(
                \App\Helpers\FileUploadRules::studentPhoto(true),
                \App\Helpers\FileUploadRules::profilePhotoMessages()
            );

            $updated = $this->studentService->storePhoto($student, $request->file('photo'));

            return response()->json([
                'message' => 'Foto siswa berhasil diunggah',
                'data' => new StudentResource($updated),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Siswa tidak ditemukan'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to upload student photo', [
                'student_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengunggah foto',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function deletePhoto(Request $request, $id)
    {
        try {
            $student = Student::findOrFail($id);
            if (! $this->userCanAccessStudent($request, $student)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $updated = $this->studentService->deletePhoto($student);

            return response()->json([
                'message' => 'Foto siswa dihapus',
                'data' => new StudentResource($updated),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Siswa tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to delete student photo', [
                'student_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat menghapus foto',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    private function validGradesForLevel(?string $level): ?array
    {
        return match ($level) {
            'PAUD', 'TK' => null,
            'SD', 'MI' => [1, 2, 3, 4, 5, 6],
            'SMP', 'MTs' => [7, 8, 9],
            'SMA', 'MA', 'MAK', 'SMK' => [10, 11, 12],
            default => range(1, 12),
        };
    }
}
