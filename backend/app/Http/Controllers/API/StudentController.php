<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\PromoteStudentsRequest;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Services\StudentService;
use App\Support\InstitutionContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    protected StudentService $studentService;

    public function __construct(StudentService $studentService)
    {
        $this->studentService = $studentService;
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
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string"), description="Cari nama/NIS/NISN"),
     *     @OA\Parameter(name="class", in="query", required=false, @OA\Schema(type="string"), description="Filter kelas"),
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string"), description="Filter status (Aktif/Nonaktif/Lulus)"),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer"), description="Jumlah per halaman (max 100)"),
     *     @OA\Response(response=200, description="Berhasil",
     *         @OA\JsonContent(
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
            'sort_by',
            'sort_dir',
        ]);
        $filters['with_trashed'] = filter_var($request->get('with_trashed'), FILTER_VALIDATE_BOOLEAN);
        $filters['only_trashed'] = filter_var($request->get('only_trashed'), FILTER_VALIDATE_BOOLEAN);

        $hasUnassignedClass = isset($filters['class_id']) && StudentService::isUnassignedFilter($filters['class_id']);
        $hasUnassignedTingkat = isset($filters['tingkat']) && StudentService::isUnassignedFilter($filters['tingkat']);

        // Semester aktif hanya sebagai default daftar umum.
        // Jangan paksa jika class_id / academic_year_id sudah dipilih, atau filter "tanpa kelas/tingkat".
        if (
            !isset($filters['semester_id'])
            && !isset($filters['class_id'])
            && !isset($filters['academic_year_id'])
            && !$hasUnassignedClass
            && !$hasUnassignedTingkat
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
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nis","nisn","name","gender","class","status"},
     *             @OA\Property(property="institution_id", type="integer", description="ID institusi (untuk super admin)"),
     *             @OA\Property(property="nis", type="string", example="12345"),
     *             @OA\Property(property="nisn", type="string", example="1234567890"),
     *             @OA\Property(property="name", type="string", example="Ahmad Budi"),
     *             @OA\Property(property="gender", type="string", enum={"L","P"}, example="L"),
     *             @OA\Property(property="class", type="string", example="7A"),
     *             @OA\Property(property="status", type="string", example="Aktif")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Siswa berhasil ditambahkan",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Siswa berhasil ditambahkan"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validasi gagal")
     * )
     */
    public function store(StoreStudentRequest $request)
    {
        try {
            $institutionId = $this->resolveStudentInstitutionId($request);

            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
            }

            // Get institution with active semester
            $institution = \App\Models\Institution::with('activeSemester')->findOrFail($institutionId);
            
            if (!$institution->active_semester_id) {
                return response()->json([
                    'message' => 'Semester aktif belum ditetapkan untuk institusi ini'
                ], 400);
            }

            $validated = $request->validated();
            $validated['institution_id'] = $institutionId;
            $validated['semester_id'] = $validated['semester_id'] ?? $institution->active_semester_id; // Set otomatis dari semester aktif jika tidak ada

            // Use service to create student
            $student = $this->studentService->create($validated);

            Log::info('Student created', [
                'student_id' => $student->id,
                'institution_id' => $institutionId,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Siswa berhasil ditambahkan',
                'data' => new StudentResource($student),
            ], 201);
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
     * Display the specified student.
     *
     * @OA\Get(
     *     path="/api/v1/student/{id}",
     *     summary="Detail siswa",
     *     tags={"Student"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Berhasil", @OA\JsonContent(@OA\Property(property="data", type="object"))),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Siswa tidak ditemukan")
     * )
     */
    public function show(Request $request, $id)
    {
        try {
            // Use service to find student
            $student = $this->studentService->find($id, ['institution', 'documents', 'class', 'academicYear', 'classHistory']);

            // Check authorization
            if (!$this->userCanAccessStudent($request, $student)) {
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
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="class", type="string"),
     *         @OA\Property(property="status", type="string")
     *     )),
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
            if (!$this->userCanAccessStudent($request, $student)) {
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

            if (!$institutionId) {
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

            $message = $result['success'] . ' siswa berhasil naik kelas.';
            if (count($result['failed']) > 0) {
                $message .= ' ' . count($result['failed']) . ' gagal.';
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
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
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
            if (!$this->userCanAccessStudent($request, $student)) {
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

            if (!$this->userCanAccessStudent($request, $student)) {
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
     * Import students from Excel data.
     */
    public function import(Request $request)
    {
        try {
            $studentsData = $request->input('students', []);
            
            if (empty($studentsData) || !is_array($studentsData)) {
                return response()->json([
                    'message' => 'Data siswa tidak valid',
                ], 400);
            }

            $institutionId = $this->resolveStudentInstitutionId($request);

            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
            }

            // Ambil semester aktif institusi agar siswa impor muncul di list (list difilter by semester_id)
            $institution = \App\Models\Institution::find($institutionId);
            $validGrades = $this->validGradesForLevel($institution?->level);
            $defaults = ['institution_id' => $institutionId];
            if ($institution && $institution->active_semester_id) {
                $defaults['semester_id'] = $institution->active_semester_id;
                $semester = \App\Models\Semester::find($institution->active_semester_id);
                if ($semester && $semester->academic_year_id) {
                    $defaults['academic_year_id'] = $semester->academic_year_id;
                }
            }

            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            foreach ($studentsData as $index => $studentData) {
                try {
                    // Validasi data minimal
                    if (empty($studentData['nik']) || empty($studentData['name'])) {
                        $errors[] = "Baris " . ($index + 1) . ": NIK dan Nama Lengkap wajib diisi";
                        $errorCount++;
                        continue;
                    }

                    $rowValidator = Validator::make($studentData, [
                        'tingkat' => $validGrades === null
                            ? ['nullable', 'integer', Rule::in([])]
                            : ['required', 'integer', Rule::in($validGrades)],
                    ], [
                        'tingkat.required' => 'Tingkat wajib diisi',
                        'tingkat.integer' => 'Tingkat harus berupa angka',
                        'tingkat.in' => 'Tingkat tidak sesuai dengan jenjang institusi',
                    ]);

                    if ($rowValidator->fails()) {
                        $errors[] = "Baris " . ($index + 1) . ': ' . $rowValidator->errors()->first();
                        $errorCount++;
                        continue;
                    }

                    if ($validGrades !== null) {
                        $studentData['tingkat'] = (int) $studentData['tingkat'];
                    }

                    // Siswa impor wajib punya semester_id agar muncul di daftar (isi dari semester aktif jika belum ada)
                    $payload = array_merge($studentData, $defaults);
                    if (empty($payload['semester_id']) && !empty($defaults['semester_id'])) {
                        $payload['semester_id'] = $defaults['semester_id'];
                    }
                    if (empty($payload['academic_year_id']) && !empty($defaults['academic_year_id'])) {
                        $payload['academic_year_id'] = $defaults['academic_year_id'];
                    }

                    // Cek apakah siswa sudah ada berdasarkan NIK
                    $existingStudent = Student::where('institution_id', $institutionId)
                        ->where('nik', $studentData['nik'])
                        ->first();

                    if ($existingStudent) {
                        // Update jika sudah ada
                        $existingStudent->update($payload);
                        $successCount++;
                    } else {
                        // Create jika belum ada
                        Student::create($payload);
                        $successCount++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Baris " . ($index + 1) . ": " . $e->getMessage();
                    $errorCount++;
                    Log::error('Failed to import student', [
                        'row' => $index + 1,
                        'error' => $e->getMessage(),
                        'data' => $studentData,
                    ]);
                }
            }

            Log::info('Students imported', [
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'institution_id' => $institutionId,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Import selesai',
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'errors' => $errors,
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
            if (!$this->userCanAccessStudent($request, $student)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Check document count limit (max 20)
            $documentCount = $student->documents()->count();
            if ($documentCount >= 20) {
                return response()->json([
                    'message' => 'Maksimal 20 file dokumen per siswa'
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
            $fileName = time() . '_' . $safeName . '.' . $extension;
            $filePath = $file->storeAs('student_documents/' . $student->id, $fileName, 'public');

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
            if (!$this->userCanAccessStudent($request, $student)) {
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
            if (!$this->userCanAccessStudent($request, $student)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if (!Storage::disk('public')->exists($document->file_path)) {
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
