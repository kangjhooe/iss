<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Controllers\Controller;
use App\Models\PklJournal;
use App\Models\PklPlacement;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class PklJournalController extends Controller
{
    use ResolvesInstitution;

    /**
     * Portal siswa: daftar penempatan PKL milik sendiri.
     */
    public function myPlacements(Request $request): JsonResponse
    {
        try {
            $ctx = $this->resolveStudentContext($request);
            if ($ctx instanceof JsonResponse) {
                return $ctx;
            }
            [, $student, $institutionId] = $ctx;

            $placements = PklPlacement::forInstitution($institutionId)
                ->where('student_id', $student->id)
                ->with([
                    'period:id,name,status,start_date,end_date',
                    'industryPartner:id,name,city,address,pic_name,pic_phone',
                    'supervisor:id,name',
                ])
                ->withCount(['journals', 'monitoringLogs'])
                ->orderByDesc('id')
                ->get();

            return response()->json(['data' => $placements]);
        } catch (\Exception $e) {
            Log::error('PKL myPlacements failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal mengambil penempatan PKL.'], 500);
        }
    }

    /**
     * Portal siswa: detail satu penempatan + ringkasan.
     */
    public function myPlacementShow(Request $request, PklPlacement $pkl_placement): JsonResponse
    {
        try {
            $ctx = $this->resolveStudentContext($request);
            if ($ctx instanceof JsonResponse) {
                return $ctx;
            }
            [, $student, $institutionId] = $ctx;

            if ($denied = $this->denyUnlessOwnPlacement($pkl_placement, $student, $institutionId)) {
                return $denied;
            }

            $pkl_placement->load([
                'period:id,name,status,start_date,end_date',
                'industryPartner:id,name,city,address,pic_name,pic_phone,phone',
                'supervisor:id,name',
            ]);
            $pkl_placement->loadCount(['journals', 'monitoringLogs']);

            $monitoring = $pkl_placement->monitoringLogs()
                ->with('loggedBy:id,name')
                ->orderByDesc('visit_date')
                ->get(['id', 'pkl_placement_id', 'logged_by_employee_id', 'visit_date', 'method', 'notes', 'created_at']);

            return response()->json([
                'data' => $pkl_placement,
                'monitoring_logs' => $monitoring,
            ]);
        } catch (\Exception $e) {
            Log::error('PKL myPlacementShow failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal mengambil detail penempatan PKL.'], 500);
        }
    }

    /**
     * Portal siswa: daftar jurnal penempatan sendiri.
     */
    public function myJournalsIndex(Request $request, PklPlacement $pkl_placement): JsonResponse
    {
        try {
            $ctx = $this->resolveStudentContext($request);
            if ($ctx instanceof JsonResponse) {
                return $ctx;
            }
            [, $student, $institutionId] = $ctx;

            if ($denied = $this->denyUnlessOwnPlacement($pkl_placement, $student, $institutionId)) {
                return $denied;
            }

            $journals = $pkl_placement->journals()
                ->when($request->filled('date_from'), fn ($q) => $q->whereDate('journal_date', '>=', $request->get('date_from')))
                ->when($request->filled('date_to'), fn ($q) => $q->whereDate('journal_date', '<=', $request->get('date_to')))
                ->orderByDesc('journal_date')
                ->get();

            return response()->json(['data' => $journals]);
        } catch (\Exception $e) {
            Log::error('PKL myJournalsIndex failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal mengambil jurnal PKL.'], 500);
        }
    }

    /**
     * Portal siswa: buat jurnal harian.
     */
    public function myJournalsStore(Request $request, PklPlacement $pkl_placement): JsonResponse
    {
        try {
            $ctx = $this->resolveStudentContext($request);
            if ($ctx instanceof JsonResponse) {
                return $ctx;
            }
            [, $student, $institutionId] = $ctx;

            if ($denied = $this->denyUnlessOwnPlacement($pkl_placement, $student, $institutionId)) {
                return $denied;
            }

            if ($blocked = $this->denyUnlessWritablePlacement($pkl_placement)) {
                return $blocked;
            }

            $data = $request->validate([
                'journal_date' => 'required|date',
                'activities' => 'required|string|max:10000',
                'hours' => 'nullable|numeric|min:0|max:24',
                'status' => ['nullable', Rule::in(PklJournal::STATUSES)],
            ]);

            if ($this->dateOutsidePlacement($pkl_placement, $data['journal_date'])) {
                return response()->json([
                    'message' => 'Tanggal jurnal harus berada dalam rentang penempatan PKL.',
                ], 422);
            }

            $exists = PklJournal::where('pkl_placement_id', $pkl_placement->id)
                ->whereDate('journal_date', $data['journal_date'])
                ->exists();
            if ($exists) {
                return response()->json([
                    'message' => 'Jurnal untuk tanggal tersebut sudah ada. Silakan edit jurnal yang ada.',
                ], 422);
            }

            $journal = PklJournal::create([
                'institution_id' => $institutionId,
                'pkl_placement_id' => $pkl_placement->id,
                'student_id' => $student->id,
                'journal_date' => $data['journal_date'],
                'activities' => $data['activities'],
                'hours' => $data['hours'] ?? null,
                'status' => $data['status'] ?? 'submitted',
            ]);

            return response()->json([
                'message' => 'Jurnal PKL berhasil disimpan.',
                'data' => $journal,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('PKL myJournalsStore failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal menyimpan jurnal PKL.'], 500);
        }
    }

    /**
     * Portal siswa: update jurnal sendiri.
     */
    public function myJournalsUpdate(Request $request, PklPlacement $pkl_placement, PklJournal $journal): JsonResponse
    {
        try {
            $ctx = $this->resolveStudentContext($request);
            if ($ctx instanceof JsonResponse) {
                return $ctx;
            }
            [, $student, $institutionId] = $ctx;

            if ($denied = $this->denyUnlessOwnPlacement($pkl_placement, $student, $institutionId)) {
                return $denied;
            }

            if ((int) $journal->pkl_placement_id !== (int) $pkl_placement->id
                || (int) $journal->student_id !== (int) $student->id) {
                return response()->json(['message' => 'Jurnal tidak ditemukan.'], 404);
            }

            if ($blocked = $this->denyUnlessWritablePlacement($pkl_placement)) {
                return $blocked;
            }

            $data = $request->validate([
                'journal_date' => 'sometimes|required|date',
                'activities' => 'sometimes|required|string|max:10000',
                'hours' => 'nullable|numeric|min:0|max:24',
                'status' => ['nullable', Rule::in(PklJournal::STATUSES)],
            ]);

            if (isset($data['journal_date'])) {
                if ($this->dateOutsidePlacement($pkl_placement, $data['journal_date'])) {
                    return response()->json([
                        'message' => 'Tanggal jurnal harus berada dalam rentang penempatan PKL.',
                    ], 422);
                }

                $dup = PklJournal::where('pkl_placement_id', $pkl_placement->id)
                    ->whereDate('journal_date', $data['journal_date'])
                    ->where('id', '!=', $journal->id)
                    ->exists();
                if ($dup) {
                    return response()->json([
                        'message' => 'Jurnal untuk tanggal tersebut sudah ada.',
                    ], 422);
                }
            }

            $journal->fill($data);
            $journal->save();

            return response()->json([
                'message' => 'Jurnal PKL berhasil diperbarui.',
                'data' => $journal->fresh(),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('PKL myJournalsUpdate failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal memperbarui jurnal PKL.'], 500);
        }
    }

    /**
     * Portal siswa: hapus jurnal sendiri.
     */
    public function myJournalsDestroy(Request $request, PklPlacement $pkl_placement, PklJournal $journal): JsonResponse
    {
        try {
            $ctx = $this->resolveStudentContext($request);
            if ($ctx instanceof JsonResponse) {
                return $ctx;
            }
            [, $student, $institutionId] = $ctx;

            if ($denied = $this->denyUnlessOwnPlacement($pkl_placement, $student, $institutionId)) {
                return $denied;
            }

            if ((int) $journal->pkl_placement_id !== (int) $pkl_placement->id
                || (int) $journal->student_id !== (int) $student->id) {
                return response()->json(['message' => 'Jurnal tidak ditemukan.'], 404);
            }

            if ($blocked = $this->denyUnlessWritablePlacement($pkl_placement)) {
                return $blocked;
            }

            $journal->delete();

            return response()->json(['message' => 'Jurnal PKL dihapus.']);
        } catch (\Exception $e) {
            Log::error('PKL myJournalsDestroy failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal menghapus jurnal PKL.'], 500);
        }
    }

    /**
     * Staff (modul pkl): daftar jurnal siswa pada penempatan.
     */
    public function indexForPlacement(Request $request, PklPlacement $pkl_placement): JsonResponse
    {
        if ($denied = $this->denyOutside($request, $pkl_placement->institution_id)) {
            return $denied;
        }

        $journals = $pkl_placement->journals()
            ->orderByDesc('journal_date')
            ->get();

        return response()->json(['data' => $journals]);
    }

    /**
     * Staff: catatan pembimbing pada jurnal siswa.
     */
    public function updateSupervisorNotes(Request $request, PklPlacement $pkl_placement, PklJournal $journal): JsonResponse
    {
        if ($denied = $this->denyOutside($request, $pkl_placement->institution_id)) {
            return $denied;
        }

        if ((int) $journal->pkl_placement_id !== (int) $pkl_placement->id) {
            return response()->json(['message' => 'Jurnal tidak ditemukan.'], 404);
        }

        $data = $request->validate([
            'supervisor_notes' => 'nullable|string|max:5000',
        ]);

        $journal->supervisor_notes = $data['supervisor_notes'] ?? null;
        $journal->save();

        return response()->json([
            'message' => 'Catatan pembimbing disimpan.',
            'data' => $journal->fresh(),
        ]);
    }

    /**
     * @return array{0: User, 1: Student, 2: int}|JsonResponse
     */
    protected function resolveStudentContext(Request $request): array|JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isStudent()) {
            return response()->json(['message' => 'Hanya siswa yang dapat mengakses data ini.'], 403);
        }

        $student = $user->studentProfile;
        if (!$student) {
            return response()->json(['message' => 'Profil siswa tidak ditemukan untuk akun ini.'], 404);
        }

        $institutionId = (int) ($student->institution_id ?: $user->institution_id);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        return [$user, $student, $institutionId];
    }

    protected function denyUnlessOwnPlacement(PklPlacement $placement, Student $student, int $institutionId): ?JsonResponse
    {
        if ((int) $placement->institution_id !== $institutionId
            || (int) $placement->student_id !== (int) $student->id) {
            return response()->json(['message' => 'Anda hanya dapat mengakses penempatan PKL sendiri.'], 403);
        }

        return null;
    }

    protected function denyUnlessWritablePlacement(PklPlacement $placement): ?JsonResponse
    {
        if (!in_array($placement->status, ['berlangsung', 'draft'], true)) {
            return response()->json([
                'message' => 'Jurnal hanya dapat diubah saat penempatan masih draft atau berlangsung.',
            ], 422);
        }

        return null;
    }

    protected function dateOutsidePlacement(PklPlacement $placement, string $date): bool
    {
        if ($placement->start_date && $date < $placement->start_date->format('Y-m-d')) {
            return true;
        }
        if ($placement->end_date && $date > $placement->end_date->format('Y-m-d')) {
            return true;
        }

        return false;
    }

    private function denyOutside(Request $request, int $recordInstitutionId): ?JsonResponse
    {
        $user = $request->user();
        if ($user->isAdminOrSuperAdmin()) {
            return null;
        }
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId || (int) $recordInstitutionId !== (int) $institutionId) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return null;
    }
}
