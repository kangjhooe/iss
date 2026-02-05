<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExtracurricularRequest;
use App\Http\Requests\StoreExtracurricularStudentRequest;
use App\Http\Requests\UpdateExtracurricularRequest;
use App\Http\Requests\UpdateExtracurricularStudentRequest;
use App\Http\Resources\ExtracurricularResource;
use App\Http\Resources\ExtracurricularStudentResource;
use App\Http\Resources\StudentResource;
use App\Models\Extracurricular;
use App\Models\ExtracurricularStudent;
use App\Models\Institution;
use App\Models\Student;
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
            $institutionId = $user->institution_id;
            if (!$institutionId && !$user->isSuperAdmin()) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }
            if ($user->isSuperAdmin() && $request->has('institution_id')) {
                $institutionId = $request->get('institution_id');
            }
            if (!$institutionId) {
                return response()->json(['message' => 'Pilih institusi.'], 400);
            }

            $query = Extracurricular::forInstitution($institutionId)
                ->withCount('extracurricularStudents as participants_count')
                ->with(['supervisor:id,name,nip,email', 'academicYear:id,name,code', 'semester:id,name', 'room:id,name,code']);

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

            $query->orderBy('name');
            $perPage = min($request->get('per_page', 15), 100);
            $items = $query->paginate($perPage);

            return ExtracurricularResource::collection($items);
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
            $institutionId = $user->institution_id;
            if (!$institutionId && !$user->isSuperAdmin()) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }
            if ($user->isSuperAdmin() && $request->filled('institution_id')) {
                $institutionId = $request->get('institution_id');
            }
            if (!$institutionId) {
                return response()->json(['message' => 'Pilih institusi.'], 400);
            }

            $data = $request->validated();
            $data['institution_id'] = $institutionId;
            if (!isset($data['status'])) {
                $data['status'] = 'Aktif';
            }
            $extracurricular = Extracurricular::create($data);

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
        if (!$user->isSuperAdmin() && $user->institution_id !== $extracurricular->institution_id) {
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

        return new ExtracurricularResource($extracurricular);
    }

    /**
     * Update extracurricular.
     */
    public function update(UpdateExtracurricularRequest $request, Extracurricular $extracurricular): ExtracurricularResource|JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user->isSuperAdmin() && $user->institution_id !== $extracurricular->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $extracurricular->update($request->validated());
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
        if (!$user->isSuperAdmin() && $user->institution_id !== $extracurricular->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
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
        if (!$user->isSuperAdmin() && $user->institution_id !== $extracurricular->institution_id) {
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

        return response()->json(['data' => ExtracurricularStudentResource::collection($participants)]);
    }

    /**
     * List students available to add (same institution, not already in this ekskul for the semester).
     */
    public function getAvailableStudents(Request $request, Extracurricular $extracurricular): JsonResponse
    {
        $user = $request->user();
        if (!$user->isSuperAdmin() && $user->institution_id !== $extracurricular->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $semesterId = $request->get('semester_id') ?? $this->getActiveSemesterId($extracurricular->institution_id);
        if (!$semesterId) {
            return response()->json(['message' => 'Semester aktif tidak ditemukan. Tetapkan semester aktif di profil instansi.'], 400);
        }

        $alreadyEnrolledIds = $extracurricular->extracurricularStudents()
            ->where('semester_id', $semesterId)
            ->pluck('student_id');

        $query = Student::where('institution_id', $extracurricular->institution_id)
            ->where('status', 'Aktif')
            ->whereNotIn('id', $alreadyEnrolledIds);

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('nis', 'like', '%' . $search . '%')
                    ->orWhere('nisn', 'like', '%' . $search . '%');
            });
        }

        $perPage = min($request->get('per_page', 20), 100);
        $students = $query->with('class')->orderBy('name')->paginate($perPage);

        return response()->json(StudentResource::collection($students));
    }

    /**
     * Add students as participants.
     */
    public function addStudents(StoreExtracurricularStudentRequest $request, Extracurricular $extracurricular): JsonResponse
    {
        $user = $request->user();
        if (!$user->isSuperAdmin() && $user->institution_id !== $extracurricular->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $semesterId = $request->get('semester_id') ?? $this->getActiveSemesterId($extracurricular->institution_id);
        $academicYearId = $request->get('academic_year_id');
        if (!$academicYearId && $extracurricular->academic_year_id) {
            $academicYearId = $extracurricular->academic_year_id;
        }
        if (!$academicYearId && $semesterId) {
            $semester = \App\Models\Semester::find($semesterId);
            if ($semester) {
                $academicYearId = $semester->academic_year_id;
            }
        }
        if (!$semesterId) {
            return response()->json(['message' => 'Semester aktif tidak ditemukan.'], 400);
        }

        $joinedAt = $request->get('joined_at') ?? now()->format('Y-m-d');
        $studentIds = $request->validated()['student_ids'];

        // Filter: only students from same institution and not already enrolled this semester
        $alreadyEnrolledIds = $extracurricular->extracurricularStudents()
            ->where('semester_id', $semesterId)
            ->pluck('student_id')
            ->toArray();

        $validStudents = Student::where('institution_id', $extracurricular->institution_id)
            ->whereIn('id', $studentIds)
            ->whereNotIn('id', $alreadyEnrolledIds)
            ->pluck('id')
            ->toArray();

        $added = 0;
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
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Extracurricular addStudents failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal menambahkan peserta.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
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
        if (!$user->isSuperAdmin() && $user->institution_id !== $extracurricular->institution_id) {
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
            $institutionId = $user->institution_id;
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
                ->with(['extracurricular:id,name,institution_id', 'semester:id,name', 'academicYear:id,name'])
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
        if (!$user->isSuperAdmin() && $user->institution_id !== $extracurricular->institution_id) {
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
                    $p->student?->class?->name ?? '-',
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
        if (!$user->isSuperAdmin() && $user->institution_id !== $extracurricular->institution_id) {
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
}
