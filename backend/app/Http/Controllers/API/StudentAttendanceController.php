<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\PrepareStudentAttendanceFromScheduleRequest;
use App\Http\Requests\StoreStudentAttendanceFromScheduleRequest;
use App\Http\Requests\StoreStudentAttendanceRequest;
use App\Http\Requests\UpdateStudentAttendanceRequest;
use App\Http\Resources\StudentAttendanceResource;
use App\Http\Resources\TeachingJournalResource;
use App\Models\Institution;
use App\Models\Semester;
use App\Models\StudentAttendance;
use App\Services\StudentAttendanceService;
use App\Support\InstitutionContext;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    private function resolveTeacherEmployeeId(Request $request): ?int
    {
        $user = $request->user();
        if (!$user || !$user->isTeacherOrStaff()) {
            return null;
        }
        $user->loadMissing(['teacherProfile', 'employeeProfile']);
        $teacher = $user->teacherProfile ?? $user->employeeProfile;

        return $teacher?->id ? (int) $teacher->id : null;
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
     * Export current student's attendance history as PDF (kop + TTD standar).
     */
    public function exportMy(Request $request)
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

            $student->loadMissing('class:id,name');
            $institution = Institution::find($institutionId);
            $semesterId = $request->integer('semester_id') ?: null;
            $dateFrom = $request->query('date_from') ?: null;
            $dateTo = $request->query('date_to') ?: null;

            $history = $this->studentAttendanceService->historyForStudent(
                $student->id,
                (int) $institutionId,
                $semesterId,
                $dateFrom,
                $dateTo
            );

            $semesterName = $semesterId
                ? Semester::where('id', $semesterId)->value('name')
                : null;

            $printedAt = now()->locale('id')->isoFormat('D MMMM YYYY HH:mm');
            $pdf = DomPDF::loadView('attendance.student_my_history', [
                'institution' => $institution,
                'student' => $student,
                'rows' => $history->values()->all(),
                'meta' => [
                    'semester_name' => $semesterName,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                ],
                'printed_at' => $printedAt,
            ])->setPaper('a4', 'portrait');

            $filename = 'Riwayat_Absensi_' . preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $student->name) . '_' . date('Y-m-d') . '.pdf';

            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error('StudentAttendance exportMy failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengekspor riwayat absensi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * JSON rekap absensi siswa (agregat per siswa).
     */
    public function rekap(Request $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $filters = $request->only(['semester_id', 'class_id', 'subject_id', 'date_from', 'date_to']);
            $scopeEmployeeId = $this->resolveTeacherEmployeeId($request);
            $rekap = $this->studentAttendanceService->buildRekap($institutionId, $filters, $scopeEmployeeId);

            return response()->json([
                'data' => $rekap['rows'],
                'totals' => $rekap['totals'],
                'meta' => $rekap['meta'],
            ]);
        } catch (\Exception $e) {
            Log::error('StudentAttendance rekap failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil rekap absensi siswa.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Export rekap absensi siswa (PDF / CSV) dengan kop & TTD standar.
     */
    public function exportRekap(Request $request)
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $format = strtolower((string) $request->query('format', 'pdf'));
            if (!in_array($format, ['pdf', 'csv'], true)) {
                return response()->json(['message' => 'Format harus pdf atau csv.'], 422);
            }

            $filters = $request->only(['semester_id', 'class_id', 'subject_id', 'date_from', 'date_to']);
            $scopeEmployeeId = $this->resolveTeacherEmployeeId($request);
            $rekap = $this->studentAttendanceService->buildRekap($institutionId, $filters, $scopeEmployeeId);
            $institution = Institution::find($institutionId);
            $printedAt = now()->locale('id')->isoFormat('D MMMM YYYY HH:mm');

            if ($format === 'csv') {
                $filename = 'Rekap_Absensi_Siswa_' . date('Y-m-d_His') . '.csv';

                return new StreamedResponse(function () use ($rekap) {
                    $out = fopen('php://output', 'w');
                    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
                    fputcsv($out, [
                        'No', 'NIS', 'NISN', 'Nama', 'Kelas',
                        'Hadir', 'Alpha', 'Izin', 'Sakit', 'Dinas Luar',
                        'Tercatat', 'JP', 'Pertemuan', '% Hadir (tercatat)', '% Hadir (JP)',
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
                            $row['jp_diharapkan'] ?? ($row['sesi_diharapkan'] ?? 0),
                            $row['pertemuan_diharapkan'] ?? 0,
                            $row['persentase_hadir'] ?? 0,
                            $row['persentase_hadir_jp'] ?? 0,
                        ]);
                    }
                    fclose($out);
                }, 200, [
                    'Content-Type' => 'text/csv; charset=UTF-8',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                ]);
            }

            $pdf = DomPDF::loadView('attendance.student_rekap', [
                'institution' => $institution,
                'rows' => $rekap['rows'],
                'totals' => $rekap['totals'],
                'meta' => $rekap['meta'],
                'printed_at' => $printedAt,
            ])->setPaper('a4', 'landscape');

            return $pdf->download('Rekap_Absensi_Siswa_' . date('Y-m-d_His') . '.pdf');
        } catch (\Exception $e) {
            Log::error('StudentAttendance exportRekap failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengekspor rekap absensi siswa.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Prepare attendance from lesson schedule + date (auto-create empty journal).
     * Returns day-mismatch warning (soft) so UI can alert but still continue.
     */
    public function prepareFromSchedule(PrepareStudentAttendanceFromScheduleRequest $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $data = $request->validated();
            $scopeEmployeeId = $this->resolveTeacherEmployeeId($request);
            $prepared = $this->studentAttendanceService->prepareFromSchedule(
                array_map('intval', $data['lesson_schedule_ids'] ?? []),
                (string) $data['date'],
                $institutionId,
                $scopeEmployeeId
            );

            return response()->json([
                'data' => [
                    'day_mismatch' => $prepared['day_mismatch'],
                    'day_mismatch_message' => $prepared['day_mismatch_message'],
                    'schedule' => $prepared['schedule'],
                    'schedules' => $prepared['schedules'],
                    'periods' => $prepared['periods'],
                    'teaching_journal' => (new TeachingJournalResource($prepared['teaching_journal']))->resolve(),
                    'attendances' => $prepared['attendances'],
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Slot jadwal tidak ditemukan.'], 404);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('StudentAttendance prepareFromSchedule failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal menyiapkan absensi dari jadwal.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Store/update bulk attendances from lesson schedule + date (auto journal).
     */
    public function storeFromSchedule(StoreStudentAttendanceFromScheduleRequest $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $data = $request->validated();
            $scopeEmployeeId = $this->resolveTeacherEmployeeId($request);
            $result = $this->studentAttendanceService->upsertFromSchedule(
                array_map('intval', $data['lesson_schedule_ids'] ?? []),
                (string) $data['date'],
                $institutionId,
                $data['attendances'] ?? [],
                $scopeEmployeeId
            );

            $periodCount = count($result['periods'] ?? []);
            $message = $periodCount > 1
                ? "Absensi siswa berhasil disimpan untuk {$periodCount} jam pelajaran."
                : 'Absensi siswa berhasil disimpan.';

            return response()->json([
                'message' => $message,
                'data' => [
                    'day_mismatch' => $result['day_mismatch'],
                    'day_mismatch_message' => $result['day_mismatch_message'],
                    'schedule' => $result['schedule'],
                    'schedules' => $result['schedules'],
                    'periods' => $result['periods'],
                    'teaching_journal' => (new TeachingJournalResource($result['teaching_journal']))->resolve(),
                    'attendances' => StudentAttendanceResource::collection($result['attendances'])->resolve(),
                ],
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Slot jadwal tidak ditemukan.'], 404);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('StudentAttendance storeFromSchedule failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal menyimpan absensi dari jadwal.',
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
