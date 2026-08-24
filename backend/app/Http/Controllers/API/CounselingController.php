<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCounselingSessionRequest;
use App\Http\Requests\UpdateCounselingSessionRequest;
use App\Http\Resources\CounselingSessionResource;
use App\Models\CounselingSession;
use App\Models\Institution;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\CounselingService;
use App\Support\InstitutionContext;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CounselingController extends Controller
{
    use ResolvesInstitution;

    public function __construct(
        protected CounselingService $counselingService
    ) {}

    /**
     * List counseling sessions for current institution.
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $filters = $request->only([
                'student_id', 'counselor_id', 'counseling_type_id', 'status',
                'date_from', 'date_to', 'class_id', 'academic_year_id', 'semester_id', 'search',
            ]);
            $institution = Institution::find($institutionId);
            if ($institution) {
                if (!isset($filters['academic_year_id']) && $institution->active_academic_year_id) {
                    $filters['academic_year_id'] = $institution->active_academic_year_id;
                }
                if (!isset($filters['semester_id']) && $institution->active_semester_id) {
                    $filters['semester_id'] = $institution->active_semester_id;
                }
            }
            $perPage = min($request->get('per_page', 15), 100);
            $sessions = $this->counselingService->listForInstitution($institutionId, $filters, $perPage);

            return CounselingSessionResource::collection($sessions);
        } catch (\Exception $e) {
            Log::error('Counseling index failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil data konseling.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Store a new counseling session.
     */
    public function store(StoreCounselingSessionRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $session = $this->counselingService->create(
                $institutionId,
                $request->validated(),
                $user->id
            );

            return (new CounselingSessionResource($session))
                ->response()
                ->setStatusCode(201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Siswa atau jenis konseling tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('Counseling store failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mencatat sesi konseling.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Show single counseling session.
     */
    public function show(Request $request, CounselingSession $counseling_session): CounselingSessionResource|JsonResponse
    {
        $user = $request->user();
        if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $counseling_session->institution_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $counseling_session->load(['student.class:id,name', 'counselor', 'counselingType']);
        return new CounselingSessionResource($counseling_session);
    }

    /**
     * Update counseling session.
     */
    public function update(UpdateCounselingSessionRequest $request, CounselingSession $counseling_session): CounselingSessionResource|JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $counseling_session->institution_id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $counseling_session = $this->counselingService->update($counseling_session, $request->validated());
            return new CounselingSessionResource($counseling_session);
        } catch (\Exception $e) {
            Log::error('Counseling update failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal memperbarui sesi konseling.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Delete counseling session.
     */
    public function destroy(Request $request, CounselingSession $counseling_session): JsonResponse
    {
        $user = $request->user();
        if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $counseling_session->institution_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $counseling_session->delete();
        return response()->json(['message' => 'Sesi konseling berhasil dihapus.']);
    }

    /**
     * List available counselors (teachers and staff only — never students/parents).
     */
    public function counselors(Request $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $list = User::where('institution_id', $institutionId)
                ->eligibleCounselors()
                ->select('id', 'name', 'email', 'role')
                ->orderBy('name')
                ->get()
                ->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'role' => $u->role,
                ]);

            return response()->json(['data' => $list]);
        } catch (\Exception $e) {
            Log::error('Counseling counselors failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil daftar konselor.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Lightweight class list for the counseling student picker.
     * Does not require the student/class modules.
     */
    public function classesLite(Request $request): JsonResponse
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $institution = Institution::find($institutionId);
        $query = SchoolClass::query()
            ->where('institution_id', $institutionId)
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
     * Lightweight student list for the counseling student picker.
     * Filter class_id and/or q (nama/NIS/NISN/NIK). Does not require the student module.
     */
    public function studentsLite(Request $request): JsonResponse
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $q = trim((string) $request->get('q', ''));
        $classId = $request->get('class_id');
        $studentId = $request->get('student_id');

        if (!$classId && $q === '' && !$studentId) {
            return response()->json([
                'message' => 'Pilih kelas atau ketik nama/NIS siswa.',
                'data' => [],
            ]);
        }

        $query = Student::query()
            ->leftJoin('class', 'student.class_id', '=', 'class.id')
            ->where('student.institution_id', $institutionId)
            ->where(function ($w) {
                $w->where('student.status', 'Aktif')->orWhereNull('student.status');
            })
            ->orderBy('student.name')
            ->select([
                'student.id',
                'student.name',
                'student.nis',
                'student.nisn',
                'student.nik',
                'student.class_id',
                'class.name as class_name',
            ]);

        if ($studentId) {
            $query->where('student.id', (int) $studentId)->limit(1);
        } elseif ($classId) {
            $query->where('student.class_id', (int) $classId)->limit(200);
        } else {
            $query->limit(50);
        }

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('student.name', 'like', "%{$q}%")
                    ->orWhere('student.nis', 'like', "%{$q}%")
                    ->orWhere('student.nisn', 'like', "%{$q}%")
                    ->orWhere('student.nik', 'like', "%{$q}%");
            });
        }

        return response()->json([
            'data' => $query->get(),
        ]);
    }

    /**
     * Export counseling sessions as CSV.
     */
    public function export(Request $request): StreamedResponse|JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $filters = $request->only([
                'student_id', 'counselor_id', 'counseling_type_id', 'status',
                'date_from', 'date_to', 'class_id', 'academic_year_id', 'search',
            ]);
            $sessions = $this->counselingService->listForExport($institutionId, $filters);

            $filename = 'laporan-konseling-' . date('Y-m-d-His') . '.csv';

            return response()->streamDownload(function () use ($sessions) {
                $out = fopen('php://output', 'w');
                fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM for Excel
                fputcsv($out, [
                    'Tanggal', 'NIS', 'NISN', 'Nama Siswa', 'Kelas', 'Konselor', 'Jenis Konseling', 'Status', 'Ringkasan', 'Tindak Lanjut',
                ]);
                foreach ($sessions as $s) {
                    fputcsv($out, [
                        $s->session_date?->format('Y-m-d'),
                        $s->student?->nis ?? '',
                        $s->student?->nisn ?? '',
                        $s->student?->name ?? '',
                        $s->student?->class?->name ?? '',
                        $s->counselor?->name ?? '',
                        $s->counselingType?->name ?? '',
                        $s->status ?? '',
                        $s->summary ?? '',
                        $s->follow_up_notes ?? '',
                    ]);
                }
                fclose($out);
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        } catch (\Exception $e) {
            Log::error('Counseling export failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengekspor data konseling.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * List counseling sessions by student.
     */
    public function byStudent(Request $request, int $studentId): AnonymousResourceCollection|JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $this->resolveInstitutionId($request);
            if ($user->isStudent()) {
                $profile = $user->studentProfile;
                if (!$profile || (int) $profile->id !== $studentId) {
                    return response()->json(['message' => 'Anda hanya dapat melihat data sendiri.'], 403);
                }
                $institutionId = $profile->institution_id;
            }
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $sessions = $this->counselingService->listByStudent($studentId, $institutionId);
            return CounselingSessionResource::collection($sessions);
        } catch (\Exception $e) {
            Log::error('Counseling byStudent failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil riwayat konseling siswa.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Dashboard stats: sessions per month and per type.
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $year = $request->get('year') ? (int) $request->get('year') : null;
            $data = $this->counselingService->getStats($institutionId, $year);
            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('Counseling stats failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil statistik konseling.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Upcoming scheduled sessions (reminder).
     */
    public function upcoming(Request $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $limit = min($request->get('limit', 10), 20);
            $sessions = $this->counselingService->getUpcoming($institutionId, $limit);
            return CounselingSessionResource::collection($sessions);
        } catch (\Exception $e) {
            Log::error('Counseling upcoming failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil jadwal konseling mendatang.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
