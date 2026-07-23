<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAlumniDestinationRequest;
use App\Http\Requests\UpdateAlumniDestinationRequest;
use App\Http\Resources\AlumniDestinationResource;
use App\Models\AlumniDestination;
use App\Models\Student;
use App\Services\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class AlumniDestinationController extends Controller
{
    use ResolvesInstitution;

    public function __construct(
        protected StudentService $studentService
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
            $institutionId = $this->resolveInstitutionId($request);
            $user = $request->user();
            if (
                !$user->isAdminOrSuperAdmin()
                && $institutionId
                && (int) $alumni_destination->institution_id !== (int) $institutionId
            ) {
                return response()->json(['message' => 'Anda tidak berwenang mengubah data ini.'], 403);
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
            $institutionId = $this->resolveInstitutionId($request);
            $user = $request->user();
            if (
                !$user->isAdminOrSuperAdmin()
                && $institutionId
                && (int) $alumni_destination->institution_id !== (int) $institutionId
            ) {
                return response()->json(['message' => 'Anda tidak berwenang menghapus data ini.'], 403);
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

    /**
     * Daftar jenis destinasi (untuk dropdown).
     */
    public function types(): JsonResponse
    {
        return response()->json(['data' => AlumniDestination::DESTINATION_TYPES]);
    }
}
