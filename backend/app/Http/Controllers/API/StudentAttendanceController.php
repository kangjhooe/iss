<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentAttendanceRequest;
use App\Http\Requests\UpdateStudentAttendanceRequest;
use App\Http\Resources\StudentAttendanceResource;
use App\Models\StudentAttendance;
use App\Services\StudentAttendanceService;
use App\Support\InstitutionContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StudentAttendanceController extends Controller
{
    public function __construct(
        protected StudentAttendanceService $studentAttendanceService
    ) {}

    private function resolveInstitutionId(Request $request): ?int
    {
        return InstitutionContext::resolveForUser(
            $request->user(),
            $request,
            $request->get('institution_id')
        );
    }

    /**
     * List attendances for a teaching journal (students in that class with current/default status).
     */
    public function index(Request $request, int $teachingJournalId): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $list = $this->studentAttendanceService->listByTeachingJournal($teachingJournalId, $institutionId);
            return response()->json(['data' => $list]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Jurnal mengajar tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('StudentAttendance index failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil data absensi siswa.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * List current student's attendance history (for student portal).
     */
    public function my(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user || !$user->isStudent()) {
                return response()->json(['message' => 'Hanya siswa yang dapat mengakses data ini.'], 403);
            }

            $institutionId = $user->institution_id;
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $student = $user->studentProfile;
            if (!$student) {
                return response()->json(['message' => 'Profil siswa tidak ditemukan untuk akun ini.'], 404);
            }

            $semesterId = $request->integer('semester_id') ?: null;
            $dateFrom = $request->query('date_from');
            $dateTo = $request->query('date_to');

            $history = $this->studentAttendanceService->historyForStudent(
                $student->id,
                (int) $institutionId,
                $semesterId,
                $dateFrom ?: null,
                $dateTo ?: null
            );

            return response()->json([
                'data' => $history,
                'meta' => [
                    'total' => $history->count(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('StudentAttendance my history failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil riwayat absensi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Store/update bulk attendances for a teaching journal.
     */
    public function store(StoreStudentAttendanceRequest $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $data = $request->validated();
            $teachingJournalId = (int) $data['teaching_journal_id'];
            $attendances = $data['attendances'] ?? [];

            $saved = $this->studentAttendanceService->upsertForJournal(
                $teachingJournalId,
                $institutionId,
                $attendances
            );

            return response()->json([
                'message' => 'Absensi siswa berhasil disimpan.',
                'data' => StudentAttendanceResource::collection($saved),
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Jurnal mengajar tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('StudentAttendance store failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal menyimpan absensi siswa.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Update a single student attendance record.
     */
    public function update(UpdateStudentAttendanceRequest $request, StudentAttendance $studentAttendance): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId || $studentAttendance->institution_id !== (int) $institutionId) {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }

            $attendance = $this->studentAttendanceService->update($studentAttendance, $request->validated());
            return response()->json([
                'message' => 'Absensi berhasil diperbarui.',
                'data' => new StudentAttendanceResource($attendance),
            ]);
        } catch (\Exception $e) {
            Log::error('StudentAttendance update failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal memperbarui absensi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
