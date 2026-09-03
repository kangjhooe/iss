<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAlumniDestinationRequest;
use App\Http\Requests\UpdateAlumniDestinationRequest;
use App\Http\Resources\AlumniDestinationResource;
use App\Models\AlumniDestination;
use App\Models\Student;
use App\Services\AlumniDestinationSync;
use App\Services\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class AlumniDestinationController extends Controller
{
    use ResolvesInstitution;

    public function __construct(
        protected StudentService $studentService,
        protected AlumniDestinationSync $alumniDestinationSync
    ) {}

    /**
     * Daftar destinasi untuk satu alumni (student_id).
     */
    public function indexByStudent(Request $request, int $studentId): AnonymousResourceCollection|JsonResponse
    {
        try {
            $student = Student::findOrFail($studentId);
            if ($student->status !== 'Lulus') {
                return response()->json(['message' => 'Hanya alumni (siswa lulus) yang dapat memiliki data destinasi.'], 422);
            }

            $institutionId = $this->resolveInstitutionId($request);
            $user = $request->user();
            if (!$this->studentService->canAccess($student, $institutionId, $user->isAdminOrSuperAdmin())) {
                return response()->json(['message' => 'Anda tidak berwenang mengakses data alumni ini.'], 403);
            }

            $destinations = AlumniDestination::where('student_id', $studentId)
                ->with('relatedInstitution:id,name')
                ->orderByDesc('year_entered')
                ->orderByDesc('id')
                ->get();

            return AlumniDestinationResource::collection($destinations);
        } catch (\Exception $e) {
            Log::error('AlumniDestination indexByStudent failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil data destinasi alumni.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Simpan destinasi baru untuk alumni.
     */
    public function store(StoreAlumniDestinationRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $student = Student::findOrFail($request->input('student_id'));
            if ($student->status !== 'Lulus') {
                return response()->json(['message' => 'Hanya alumni (siswa lulus) yang dapat memiliki data destinasi.'], 422);
            }

            $institutionId = $this->resolveInstitutionId($request);

            // Super/admin tanpa scope institusi: ikuti institusi siswa.
            if (!$institutionId && $user->isAdminOrSuperAdmin()) {
                $institutionId = (int) $student->institution_id;
            }

            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            if (!$this->studentService->canAccess($student, $institutionId, $user->isAdminOrSuperAdmin())) {
                return response()->json(['message' => 'Siswa tidak berada di institusi Anda.'], 403);
            }

            // Simpan destinasi di institusi milik siswa (bukan sekadar konteks request).
            $data = $request->validated();
            $data['institution_id'] = (int) $student->institution_id;
            $data['status'] = AlumniDestination::STATUS_APPROVED;
            $data['source'] = AlumniDestination::SOURCE_MANUAL;

            $destination = AlumniDestination::create($data);
            $destination->load('student');

            return response()->json([
                'message' => 'Destinasi alumni berhasil ditambahkan.',
                'data' => new AlumniDestinationResource($destination),
            ], 201);
        } catch (\Exception $e) {
            Log::error('AlumniDestination store failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal menambah destinasi alumni.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Update destinasi.
     */
    public function update(UpdateAlumniDestinationRequest $request, AlumniDestination $alumni_destination): JsonResponse
    {
        try {
            if ($forbidden = $this->forbidIfOutsideInstitution($request, $alumni_destination)) {
                return $forbidden;
            }

            if ($alumni_destination->isPending()) {
                return response()->json([
                    'message' => 'Destinasi otomatis masih menunggu persetujuan. Setujui atau tolak terlebih dahulu.',
                ], 422);
            }

            $alumni_destination->update($request->validated());
            $alumni_destination->load('student');

            return response()->json([
                'message' => 'Destinasi alumni berhasil diperbarui.',
                'data' => new AlumniDestinationResource($alumni_destination),
            ]);
        } catch (\Exception $e) {
            Log::error('AlumniDestination update failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal memperbarui destinasi alumni.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Hapus destinasi.
     */
    public function destroy(Request $request, AlumniDestination $alumni_destination): JsonResponse
    {
        try {
            if ($forbidden = $this->forbidIfOutsideInstitution($request, $alumni_destination)) {
                return $forbidden;
            }

            $alumni_destination->delete();

            return response()->json(['message' => 'Destinasi alumni berhasil dihapus.']);
        } catch (\Exception $e) {
            Log::error('AlumniDestination destroy failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal menghapus destinasi alumni.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function approve(Request $request, AlumniDestination $alumni_destination): JsonResponse
    {
        return $this->review($request, $alumni_destination, AlumniDestination::STATUS_APPROVED);
    }

    public function reject(Request $request, AlumniDestination $alumni_destination): JsonResponse
    {
        return $this->review($request, $alumni_destination, AlumniDestination::STATUS_REJECTED);
    }

    /**
     * Daftar jenis destinasi (untuk dropdown).
     */
    public function types(): JsonResponse
    {
        return response()->json(['data' => AlumniDestination::DESTINATION_TYPES]);
    }

    private function review(Request $request, AlumniDestination $destination, string $status): JsonResponse
    {
        try {
            if ($forbidden = $this->forbidIfOutsideInstitution($request, $destination)) {
                return $forbidden;
            }

            if (! $destination->isPending()) {
                return response()->json(['message' => 'Destinasi ini sudah ditinjau.'], 422);
            }

            $update = [
                'status' => $status,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ];

            if ($status === AlumniDestination::STATUS_APPROVED) {
                $update['notes'] = $destination->cleanApprovedNotes();
            }

            $destination->update($update);
            $destination->load(['student', 'relatedInstitution:id,name']);

            $action = $status === AlumniDestination::STATUS_APPROVED ? 'approved' : 'rejected';
            $this->alumniDestinationSync->notifyDecision($destination, $action);

            return response()->json([
                'message' => $status === AlumniDestination::STATUS_APPROVED
                    ? 'Destinasi alumni disetujui.'
                    : 'Destinasi alumni ditolak.',
                'data' => new AlumniDestinationResource($destination),
            ]);
        } catch (\Exception $e) {
            Log::error('AlumniDestination review failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal meninjau destinasi alumni.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    private function forbidIfOutsideInstitution(Request $request, AlumniDestination $destination): ?JsonResponse
    {
        return $this->denyUnlessCanAccessInstitution(
            $request,
            (int) $destination->institution_id,
            'Anda tidak berwenang mengubah data ini.'
        );
    }
}
