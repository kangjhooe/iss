<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\AchievementResource;
use App\Http\Resources\AchievementTypeResource;
use App\Http\Resources\StudentMutationResource;
use App\Http\Resources\StudentResource;
use App\Http\Resources\ViolationResource;
use App\Http\Resources\ViolationTypeResource;
use App\Http\Resources\WaliNoteResource;
use App\Models\Achievement;
use App\Models\AchievementType;
use App\Models\Institution;
use App\Models\Student;
use App\Models\StudentMutation;
use App\Models\Violation;
use App\Models\ViolationType;
use App\Models\WaliNote;
use App\Services\AchievementService;
use App\Services\LessonScheduleExportService;
use App\Services\LessonScheduleService;
use App\Services\StudentAccountService;
use App\Services\StudentAttendanceService;
use App\Services\StudentMutationService;
use App\Services\StudentService;
use App\Services\ViolationService;
use App\Services\WaliKelasDashboardService;
use App\Support\WaliKelasAccess;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WaliKelasController extends Controller
{
    public function __construct(
        protected WaliKelasDashboardService $dashboardService,
        protected ViolationService $violationService,
        protected AchievementService $achievementService,
        protected LessonScheduleService $lessonScheduleService,
        protected LessonScheduleExportService $lessonScheduleExportService,
        protected StudentMutationService $studentMutationService,
        protected StudentAttendanceService $studentAttendanceService,
        protected StudentAccountService $studentAccountService,
        protected StudentService $studentService,
    ) {}

    public function showStudent(Request $request, int $classId, int $studentId): JsonResponse
    {
        $user = $request->user();
        if (!$user?->isTeacherOrStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $student = WaliKelasAccess::resolveHomeroomStudent($user, $classId, $studentId);
        if (!$student) {
            return response()->json(['message' => 'Siswa tidak ditemukan di kelas yang Anda waliki.'], 403);
        }

        $student->load(['class', 'academicYear', 'semester', 'userAccount']);

        $payload = (new StudentResource($student))->resolve();
        $class = WaliKelasAccess::resolveHomeroomClass($user, $classId);
        if ($class) {
            $payload['snapshot'] = $this->dashboardService->studentSnapshot($user, $class, $student);
        }

        return response()->json(['data' => $payload]);
    }

    /**
     * Lengkapi NIK / tanggal lahir siswa kelas wali (untuk akun login).
     */
    public function updateLoginFields(Request $request, int $classId, int $studentId): JsonResponse
    {
        $user = $request->user();
        if (!$user?->isTeacherOrStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $student = WaliKelasAccess::resolveHomeroomStudent($user, $classId, $studentId);
        if (!$student) {
            return response()->json(['message' => 'Siswa tidak ditemukan di kelas yang Anda waliki.'], 403);
        }

        $validated = $request->validate([
            'nik' => [
                'required',
                'digits:16',
                Rule::unique('student', 'nik')
                    ->ignore($student->id)
                    ->where(fn ($q) => $q->where('institution_id', $student->institution_id)),
            ],
            'birth_date' => ['required', 'date'],
            'birth_place' => ['nullable', 'string', 'max:255'],
        ]);

        $payload = [
            'nik' => $validated['nik'],
            'birth_date' => $validated['birth_date'],
        ];
        if (array_key_exists('birth_place', $validated) && $validated['birth_place'] !== null) {
            $payload['birth_place'] = $validated['birth_place'];
        }

        $updated = $this->studentService->update($student, $payload);

        return response()->json([
            'message' => 'Data login siswa diperbarui. Akun login disinkronkan otomatis jika NIK & tanggal lahir valid.',
            'data' => new StudentResource($updated->loadMissing(['class', 'academicYear', 'semester', 'userAccount'])),
        ]);
    }

    public function ensureStudentAccount(Request $request, int $classId, int $studentId): JsonResponse
    {
        $user = $request->user();
        if (!$user?->isTeacherOrStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $student = WaliKelasAccess::resolveHomeroomStudent($user, $classId, $studentId);
        if (!$student) {
            return response()->json(['message' => 'Siswa tidak ditemukan di kelas yang Anda waliki.'], 403);
        }

        $result = $this->studentAccountService->ensureAccount($student);
        if (!$result['user'] && $result['skipped_reason']) {
            return response()->json([
                'message' => 'Akun login tidak dapat dibuat: ' . $result['skipped_reason'],
            ], 422);
        }

        $student->load(['class', 'academicYear', 'semester', 'userAccount']);

        return response()->json([
            'message' => $result['user_created']
                ? 'Akun login siswa berhasil dibuat. Sandi awal = tanggal lahir (DDMMYYYY).'
                : 'Akun login siswa sudah tersedia / diperbarui.',
            'data' => new StudentResource($student),
            'user_created' => $result['user_created'],
        ]);
    }

    public function resetStudentPassword(Request $request, int $classId, int $studentId): JsonResponse
    {
        $user = $request->user();
        if (!$user?->isTeacherOrStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $student = WaliKelasAccess::resolveHomeroomStudent($user, $classId, $studentId);
        if (!$student) {
            return response()->json(['message' => 'Siswa tidak ditemukan di kelas yang Anda waliki.'], 403);
        }

        try {
            $account = $this->studentAccountService->resetPasswordToBirthDate($student);
            $student->load(['class', 'academicYear', 'semester', 'userAccount']);

            return response()->json([
                'message' => 'Sandi berhasil direset ke tanggal lahir (DDMMYYYY). Siswa wajib ganti sandi saat login berikutnya.',
                'data' => new StudentResource($student),
                'must_change_password' => (bool) $account->must_change_password,
            ]);
        } catch (ValidationException $e) {
            throw $e;
        }
    }

    /**
     * Buat akun login massal untuk siswa aktif di kelas wali.
     */
    public function ensureAccountsBulk(Request $request, int $classId): JsonResponse
    {
        $user = $request->user();
        if (!$user?->isTeacherOrStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $class = WaliKelasAccess::resolveHomeroomClass($user, $classId);
        if (!$class) {
            return response()->json(['message' => 'Anda hanya dapat mengelola akun siswa di kelas yang Anda waliki.'], 403);
        }

        $validated = $request->validate([
            'only_missing' => ['nullable', 'boolean'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $onlyMissing = array_key_exists('only_missing', $validated)
            ? (bool) $validated['only_missing']
            : true;

        $result = $this->studentService->bulkEnsureAccounts(
            [
                'class_id' => $class->id,
                'status' => 'Aktif',
                'account_status' => $onlyMissing ? 'missing' : null,
            ],
            (int) $class->institution_id,
            $validated['student_ids'] ?? null,
            $onlyMissing,
            (int) ($validated['limit'] ?? 500)
        );

        Log::info('Wali kelas bulk ensure student accounts', [
            'class_id' => $class->id,
            'user_id' => $user->id,
            'created' => $result['created'],
            'skipped' => $result['skipped'],
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
        ]);
    }

    public function dashboard(Request $request, int $classId): JsonResponse
    {
        $user = $request->user();
        if (!$user?->isTeacherOrStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $class = WaliKelasAccess::resolveHomeroomClass($user, $classId);
        if (!$class) {
            return response()->json(['message' => 'Anda hanya dapat melihat dashboard kelas yang Anda waliki.'], 403);
        }

        return response()->json(['data' => $this->dashboardService->build($user, $class)]);
    }

    public function attendanceSummary(Request $request, int $classId): JsonResponse
    {
        $user = $request->user();
        if (!$user?->isTeacherOrStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $class = WaliKelasAccess::resolveHomeroomClass($user, $classId);
        if (!$class) {
            return response()->json(['message' => 'Anda hanya dapat melihat absensi kelas yang Anda waliki.'], 403);
        }

        $period = (string) $request->query('period', 'week');

        return response()->json([
            'data' => $this->dashboardService->attendanceSummary($class, $period),
        ]);
    }

    public function gradesOverview(Request $request, int $classId): JsonResponse
    {
        $user = $request->user();
        if (!$user?->isTeacherOrStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $class = WaliKelasAccess::resolveHomeroomClass($user, $classId);
        if (!$class) {
            return response()->json(['message' => 'Anda hanya dapat melihat nilai kelas yang Anda waliki.'], 403);
        }

        return response()->json([
            'data' => $this->dashboardService->gradesOverview($class),
        ]);
    }

    public function indexNotes(Request $request, int $classId, int $studentId): JsonResponse
    {
        $user = $request->user();
        $student = WaliKelasAccess::resolveHomeroomStudent($user, $classId, $studentId);
        if (!$student) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $notes = WaliNote::with('author:id,name')
            ->where('class_id', $classId)
            ->where('student_id', $studentId)
            ->orderByDesc('created_at')
            ->get();

        return WaliNoteResource::collection($notes)->response();
    }

    public function storeNote(Request $request, int $classId, int $studentId): JsonResponse
    {
        $user = $request->user();
        $class = WaliKelasAccess::resolveHomeroomClass($user, $classId);
        $student = WaliKelasAccess::resolveHomeroomStudent($user, $classId, $studentId);
        if (!$class || !$student) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $note = WaliNote::create([
            'institution_id' => $class->institution_id,
            'class_id' => $class->id,
            'student_id' => $student->id,
            'author_user_id' => $user->id,
            'body' => $data['body'],
        ]);
        $note->load('author:id,name');

        return (new WaliNoteResource($note))->response()->setStatusCode(201);
    }

    public function updateNote(Request $request, int $classId, int $studentId, int $noteId): JsonResponse
    {
        $user = $request->user();
        if (!WaliKelasAccess::resolveHomeroomStudent($user, $classId, $studentId)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $note = WaliNote::where('id', $noteId)
            ->where('class_id', $classId)
            ->where('student_id', $studentId)
            ->firstOrFail();

        if ((int) $note->author_user_id !== (int) $user->id) {
            return response()->json(['message' => 'Hanya penulis catatan yang dapat mengubahnya.'], 403);
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);
        $note->update(['body' => $data['body']]);
        $note->load('author:id,name');

        return (new WaliNoteResource($note))->response();
    }

    public function destroyNote(Request $request, int $classId, int $studentId, int $noteId): JsonResponse
    {
        $user = $request->user();
        if (!WaliKelasAccess::resolveHomeroomStudent($user, $classId, $studentId)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $note = WaliNote::where('id', $noteId)
            ->where('class_id', $classId)
            ->where('student_id', $studentId)
            ->firstOrFail();

        if ((int) $note->author_user_id !== (int) $user->id) {
            return response()->json(['message' => 'Hanya penulis catatan yang dapat menghapusnya.'], 403);
        }

        $note->delete();

        return response()->json(['message' => 'Catatan dihapus.']);
    }

    public function schedule(Request $request, int $classId): JsonResponse
    {
        $user = $request->user();
        $class = WaliKelasAccess::resolveHomeroomClass($user, $classId);
        if (!$class) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $institution = Institution::find($class->institution_id);
        $semesterId = (int) ($request->query('semester_id') ?: $institution?->active_semester_id);
        if (!$semesterId) {
            return response()->json(['message' => 'Semester aktif tidak ditemukan.'], 422);
        }

        $data = $this->lessonScheduleService->getByClass($classId, $semesterId, (int) $class->institution_id);

        return response()->json(['data' => $data]);
    }

    public function exportSchedulePdf(Request $request, int $classId)
    {
        $user = $request->user();
        $class = WaliKelasAccess::resolveHomeroomClass($user, $classId);
        if (!$class) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $institution = Institution::find($class->institution_id);
        $semesterId = (int) ($request->query('semester_id') ?: $institution?->active_semester_id);
        if (!$semesterId) {
            return response()->json(['message' => 'Semester aktif tidak ditemukan.'], 422);
        }

        return $this->lessonScheduleExportService->exportPdf((int) $class->institution_id, [
            'mode' => 'class',
            'semester_id' => $semesterId,
            'class_id' => $classId,
        ]);
    }

    public function indexViolations(Request $request): JsonResponse
    {
        $user = $request->user();
        $classId = (int) $request->query('class_id');
        $class = WaliKelasAccess::resolveHomeroomClass($user, $classId);
        if (!$class) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $studentIds = Student::query()->where('class_id', $classId)->pluck('id');
        $perPage = min(max((int) $request->get('per_page', 15), 1), 100);

        $query = Violation::with([
            'student:id,name,nis,nisn,class_id',
            'violationType:id,name,code,category,point_weight',
            'reporter:id,name',
            'reviewer:id,name',
        ])
            ->where('institution_id', $class->institution_id)
            ->whereIn('student_id', $studentIds)
            ->where(function ($q) use ($user) {
                $q->where('reported_by', $user->id)
                    ->orWhere('status', Violation::STATUS_PENDING);
            })
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('violation_date');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return ViolationResource::collection($query->paginate($perPage))->response();
    }

    public function violationTypes(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!WaliKelasAccess::isHomeroomTeacher($user)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $classId = (int) $request->query('class_id');
        $class = $classId ? WaliKelasAccess::resolveHomeroomClass($user, $classId) : null;
        $institutionId = $class?->institution_id
            ?? $request->attributes->get('current_institution_id')
            ?? $user->institution_id;

        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $types = ViolationType::forInstitution((int) $institutionId)->active()->orderBy('name')->get();

        return ViolationTypeResource::collection($types)->response();
    }

    public function achievementTypes(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!WaliKelasAccess::isHomeroomTeacher($user)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $classId = (int) $request->query('class_id');
        $class = $classId ? WaliKelasAccess::resolveHomeroomClass($user, $classId) : null;
        $institutionId = $class?->institution_id
            ?? $request->attributes->get('current_institution_id')
            ?? $user->institution_id;

        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $types = AchievementType::forInstitution((int) $institutionId)->active()->orderBy('name')->get();

        return AchievementTypeResource::collection($types)->response();
    }

    public function storeViolation(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'class_id' => ['required', 'integer'],
            'student_id' => ['required', 'integer'],
            'violation_type_id' => ['required', 'integer'],
            'violation_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sanction' => ['nullable', 'string', 'max:500'],
        ]);

        $student = WaliKelasAccess::resolveHomeroomStudent($user, (int) $data['class_id'], (int) $data['student_id']);
        if (!$student) {
            return response()->json(['message' => 'Siswa harus berada di kelas yang Anda waliki.'], 403);
        }

        $violation = $this->violationService->create(
            (int) $student->institution_id,
            $data,
            $user->id,
            true
        );

        return (new ViolationResource($violation))->response()->setStatusCode(201);
    }

    public function indexAchievements(Request $request): JsonResponse
    {
        $user = $request->user();
        $classId = (int) $request->query('class_id');
        $class = WaliKelasAccess::resolveHomeroomClass($user, $classId);
        if (!$class) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $perPage = min(max((int) $request->get('per_page', 15), 1), 100);
        $filters = [
            'class_ids' => [$classId],
            'given_by' => $user->id,
        ];
        if ($request->filled('status')) {
            $filters['status'] = $request->status;
        }

        $items = $this->achievementService->listForInstitution(
            (int) $class->institution_id,
            $filters,
            $perPage
        );

        return AchievementResource::collection($items)->response();
    }

    public function storeAchievement(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'class_id' => ['required', 'integer'],
            'student_id' => ['required', 'integer'],
            'achievement_type_id' => ['required', 'integer'],
            'achievement_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'point_value' => ['nullable', 'integer', 'min:0'],
        ]);

        $student = WaliKelasAccess::resolveHomeroomStudent($user, (int) $data['class_id'], (int) $data['student_id']);
        if (!$student) {
            return response()->json(['message' => 'Siswa harus berada di kelas yang Anda waliki.'], 403);
        }

        $achievement = $this->achievementService->create(
            (int) $student->institution_id,
            $data,
            $user->id,
            true
        );

        return (new AchievementResource($achievement))->response()->setStatusCode(201);
    }

    public function indexMutations(Request $request): JsonResponse
    {
        $user = $request->user();
        $classId = (int) $request->query('class_id');
        $class = WaliKelasAccess::resolveHomeroomClass($user, $classId);
        if (!$class) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $studentIds = Student::query()->where('class_id', $classId)->pluck('id');
        $perPage = min(max((int) $request->get('per_page', 15), 1), 100);

        $items = StudentMutation::with(['student', 'originInstitution', 'targetInstitution', 'requester', 'approver'])
            ->where('origin_institution_id', $class->institution_id)
            ->where('source', 'wali')
            ->where('requested_by', $user->id)
            ->whereIn('student_id', $studentIds)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return StudentMutationResource::collection($items)->response();
    }

    public function storeMutation(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'class_id' => ['required', 'integer'],
            'student_id' => ['required', 'integer'],
            'target_npsn' => ['required', 'string', 'max:20'],
            'target_school_name' => ['nullable', 'string', 'max:255'],
            'external' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $student = WaliKelasAccess::resolveHomeroomStudent($user, (int) $data['class_id'], (int) $data['student_id']);
        if (!$student) {
            return response()->json(['message' => 'Siswa harus berada di kelas yang Anda waliki.'], 403);
        }

        try {
            $mutation = $this->studentMutationService->createProposalFromWali(
                (int) $student->institution_id,
                [
                    'student_id' => (int) $data['student_id'],
                    'target_npsn' => $data['target_npsn'],
                    'target_school_name' => $data['target_school_name'] ?? null,
                    'external' => (bool) ($data['external'] ?? false),
                    'notes' => $data['notes'] ?? null,
                ],
                $user->id
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new StudentMutationResource($mutation))->response()->setStatusCode(201);
    }

    public function exportRoster(Request $request, int $classId)
    {
        $user = $request->user();
        $class = WaliKelasAccess::resolveHomeroomClass($user, $classId);
        if (!$class) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $students = Student::query()
            ->where('class_id', $classId)
            ->where('status', 'Aktif')
            ->orderBy('name')
            ->get(['id', 'name', 'nis', 'nisn', 'gender']);

        $institution = Institution::find($class->institution_id);
        $printedAt = now()->locale('id')->isoFormat('D MMMM YYYY HH:mm');

        $pdf = DomPDF::loadView('wali.student_roster', [
            'institution' => $institution,
            'class' => $class,
            'students' => $students,
            'printed_at' => $printedAt,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('Daftar_Siswa_'.$class->name.'_'.date('Y-m-d').'.pdf');
    }

    public function exportContacts(Request $request, int $classId)
    {
        $user = $request->user();
        $class = WaliKelasAccess::resolveHomeroomClass($user, $classId);
        if (!$class) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $format = strtolower((string) $request->query('format', 'pdf'));
        $students = Student::query()
            ->where('class_id', $classId)
            ->where('status', 'Aktif')
            ->orderBy('name')
            ->get(['id', 'name', 'nis', 'nisn', 'phone', 'guardian_name', 'guardian_phone', 'address']);

        $institution = Institution::find($class->institution_id);

        if ($format === 'csv') {
            $filename = 'Kontak_Ortu_'.$class->name.'_'.date('Y-m-d').'.csv';

            return new StreamedResponse(function () use ($students) {
                $out = fopen('php://output', 'w');
                fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
                fputcsv($out, ['No', 'Nama', 'NIS', 'NISN', 'HP Siswa', 'Nama Wali', 'HP Wali', 'Alamat']);
                foreach ($students as $i => $s) {
                    fputcsv($out, [
                        $i + 1,
                        $s->name,
                        $s->nis,
                        $s->nisn,
                        $s->phone,
                        $s->guardian_name,
                        $s->guardian_phone,
                        $s->address,
                    ]);
                }
                fclose($out);
            }, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        }

        $printedAt = now()->locale('id')->isoFormat('D MMMM YYYY HH:mm');
        $pdf = DomPDF::loadView('wali.student_contacts', [
            'institution' => $institution,
            'class' => $class,
            'students' => $students,
            'printed_at' => $printedAt,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Kontak_Ortu_'.$class->name.'_'.date('Y-m-d').'.pdf');
    }

    public function exportAttendance(Request $request, int $classId)
    {
        $user = $request->user();
        $class = WaliKelasAccess::resolveHomeroomClass($user, $classId);
        if (!$class) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        try {
            $format = strtolower((string) $request->query('format', 'pdf'));
            if (!in_array($format, ['pdf', 'csv'], true)) {
                return response()->json(['message' => 'Format harus pdf atau csv.'], 422);
            }

            $filters = $request->only(['semester_id', 'subject_id', 'date_from', 'date_to']);
            $filters['class_id'] = $classId;
            $institutionId = (int) $class->institution_id;
            $rekap = $this->studentAttendanceService->buildRekap($institutionId, $filters, null);
            $institution = Institution::find($institutionId);
            $printedAt = now()->locale('id')->isoFormat('D MMMM YYYY HH:mm');

            if ($format === 'csv') {
                $filename = 'Rekap_Absensi_'.$class->name.'_'.date('Y-m-d_His').'.csv';

                return new StreamedResponse(function () use ($rekap) {
                    $out = fopen('php://output', 'w');
                    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
                    fputcsv($out, [
                        'No', 'NIS', 'NISN', 'Nama', 'Kelas',
                        'Hadir', 'Alpha', 'Izin', 'Sakit', 'Dinas Luar',
                        'Tercatat', 'Sesi Diharapkan', '% Hadir',
                    ]);
                    foreach ($rekap['rows'] as $i => $row) {
                        fputcsv($out, [
                            $i + 1,
                            $row['nis'] ?? '',
                            $row['nisn'] ?? '',
                            $row['name'] ?? '',
                            $row['class_name'] ?? '',
                            $row['counts']['hadir'] ?? 0,
                            $row['counts']['alpha'] ?? 0,
                            $row['counts']['izin'] ?? 0,
                            $row['counts']['sakit'] ?? 0,
                            $row['counts']['dinas_luar'] ?? 0,
                            $row['tercatat'] ?? 0,
                            $row['sesi_diharapkan'] ?? 0,
                            $row['persentase_hadir'] ?? 0,
                        ]);
                    }
                    fclose($out);
                }, 200, [
                    'Content-Type' => 'text/csv; charset=UTF-8',
                    'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                ]);
            }

            $pdf = DomPDF::loadView('attendance.student_rekap', [
                'institution' => $institution,
                'rows' => $rekap['rows'],
                'totals' => $rekap['totals'],
                'meta' => $rekap['meta'],
                'printed_at' => $printedAt,
            ])->setPaper('a4', 'landscape');

            return $pdf->download('Rekap_Absensi_'.$class->name.'_'.date('Y-m-d_His').'.pdf');
        } catch (\Exception $e) {
            Log::error('WaliKelas exportAttendance failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mengekspor rekap absensi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
