<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeachingJournalRequest;
use App\Http\Requests\UpdateTeachingJournalRequest;
use App\Http\Resources\TeachingJournalResource;
use App\Models\TeachingJournal;
use App\Services\TeachingJournalService;
use App\Support\InstitutionContext;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeachingJournalController extends Controller
{
    public function __construct(
        protected TeachingJournalService $teachingJournalService
    ) {}

    private function resolveInstitutionId(Request $request): ?int
    {
        return InstitutionContext::resolveForUser(
            $request->user(),
            $request,
            $request->get('institution_id')
        );
    }

    private function canAccessJournal($user, TeachingJournal $journal): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return InstitutionContext::canAccessInstitution($user, (int) $journal->institution_id);
    }

    /**
     * List teaching journals for current institution.
     * Teachers (role teacher/staff) see only their own entries unless admin.
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $filters = $request->only([
                'semester_id', 'class_id', 'employee_id', 'subject_id', 'date_from', 'date_to',
            ]);
            $perPage = min($request->get('per_page', 15), 100);

            $employeeId = null;
            if ($user->isTeacherOrStaff()) {
                $user->load(['teacherProfile', 'employeeProfile']);
                $teacher = $user->teacherProfile ?? $user->employeeProfile;
                if ($teacher) {
                    $employeeId = $teacher->id;
                }
            }

            $journals = $this->teachingJournalService->listForInstitution(
                $institutionId,
                $filters,
                $perPage,
                $employeeId
            );

            return TeachingJournalResource::collection($journals);
        } catch (\Exception $e) {
            Log::error('TeachingJournal index failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil data jurnal mengajar.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Store a new teaching journal entry.
     */
    public function store(StoreTeachingJournalRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $data = $request->validated();
            if ($user->isTeacherOrStaff()) {
                $user->load(['teacherProfile', 'employeeProfile']);
                $teacher = $user->teacherProfile ?? $user->employeeProfile;
                if ($teacher) {
                    $data['employee_id'] = $teacher->id;
                }
            }
            if (empty($data['employee_id'])) {
                return response()->json(['message' => 'Guru wajib dipilih atau Anda harus login sebagai guru.'], 422);
            }

            $journal = $this->teachingJournalService->create($institutionId, $data);
            return (new TeachingJournalResource($journal))
                ->response()
                ->setStatusCode(201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Semester, kelas, mata pelajaran, atau guru tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('TeachingJournal store failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mencatat jurnal mengajar.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Show single teaching journal.
     */
    public function show(Request $request, TeachingJournal $teaching_journal): TeachingJournalResource|JsonResponse
    {
        $user = $request->user();
        if (!$this->canAccessJournal($user, $teaching_journal)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->isTeacherOrStaff()) {
            $user->load(['teacherProfile', 'employeeProfile']);
            $teacher = $user->teacherProfile ?? $user->employeeProfile;
            if ($teacher && (int) $teaching_journal->employee_id !== (int) $teacher->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        $teaching_journal->load(['semester', 'schoolClass', 'subject', 'employee', 'lessonSchedule']);
        return new TeachingJournalResource($teaching_journal);
    }

    /**
     * Update teaching journal.
     */
    public function update(UpdateTeachingJournalRequest $request, TeachingJournal $teaching_journal): TeachingJournalResource|JsonResponse
    {
        try {
            $user = $request->user();
            if (!$this->canAccessJournal($user, $teaching_journal)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $data = $request->validated();
            if ($user->isTeacherOrStaff()) {
                $user->load(['teacherProfile', 'employeeProfile']);
                $teacher = $user->teacherProfile ?? $user->employeeProfile;
                if ($teacher && (int) $teaching_journal->employee_id !== (int) $teacher->id) {
                    return response()->json(['message' => 'Unauthorized'], 403);
                }
                unset($data['employee_id']);
            }

            $journal = $this->teachingJournalService->update($teaching_journal, $data);
            return new TeachingJournalResource($journal);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('TeachingJournal update failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal memperbarui jurnal mengajar.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Delete teaching journal (soft delete).
     */
    public function destroy(Request $request, TeachingJournal $teaching_journal): JsonResponse
    {
        $user = $request->user();
        if (!$this->canAccessJournal($user, $teaching_journal)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->isTeacherOrStaff()) {
            $user->load(['teacherProfile', 'employeeProfile']);
            $teacher = $user->teacherProfile ?? $user->employeeProfile;
            if ($teacher && (int) $teaching_journal->employee_id !== (int) $teacher->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        $teaching_journal->delete();
        return response()->json(['message' => 'Jurnal mengajar berhasil dihapus.'], 200);
    }

    /**
     * Export teaching journals as CSV.
     */
    public function export(Request $request): StreamedResponse|JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $filters = $request->only(['semester_id', 'class_id', 'employee_id', 'subject_id', 'date_from', 'date_to']);
            $employeeId = null;
            if ($user->isTeacherOrStaff()) {
                $user->load(['teacherProfile', 'employeeProfile']);
                $teacher = $user->teacherProfile ?? $user->employeeProfile;
                if ($teacher) {
                    $employeeId = $teacher->id;
                }
            }

            $journals = $this->teachingJournalService->listForExport($institutionId, $filters, 5000, $employeeId);
            $filename = 'jurnal-mengajar-' . date('Y-m-d-His') . '.csv';

            return response()->streamDownload(function () use ($journals) {
                $out = fopen('php://output', 'w');
                fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($out, [
                    'Tanggal', 'Kelas', 'Mapel', 'Guru', 'Jam ke', 'Materi', 'Catatan Kehadiran', 'Catatan',
                ]);
                foreach ($journals as $j) {
                    fputcsv($out, [
                        $j->journal_date?->format('Y-m-d'),
                        $j->schoolClass?->name ?? '',
                        $j->subject?->name ?? '',
                        $j->employee?->name ?? '',
                        $j->period ?? '',
                        $j->material_taught ?? '',
                        $j->attendance_notes ?? '',
                        $j->notes ?? '',
                    ]);
                }
                fclose($out);
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        } catch (\Exception $e) {
            Log::error('TeachingJournal export failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengekspor jurnal mengajar.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
