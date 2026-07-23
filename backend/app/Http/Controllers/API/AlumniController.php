<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Controllers\Controller;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use App\Services\StudentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AlumniController extends Controller
{
    use ResolvesInstitution;

    public function __construct(
        protected StudentService $studentService
    ) {}

    /**
     * Daftar alumni (siswa status Lulus).
     */
    public function index(Request $request)
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);

            $filters = $request->only(['search', 'graduation_year', 'class_id']);
            $perPage = min($request->get('per_page', 15), 100);

            $alumni = $this->studentService->listAlumni($filters, $institutionId, $perPage);

            return StudentResource::collection($alumni);
        } catch (\Exception $e) {
            Log::error('Failed to list alumni', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data alumni',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Daftar tahun lulus (untuk filter dropdown).
     */
    public function graduationYears(Request $request)
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            $years = $this->studentService->getGraduationYears($institutionId);

            return response()->json(['data' => $years]);
        } catch (\Exception $e) {
            Log::error('Failed to get graduation years', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Terjadi kesalahan',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Luluskan satu siswa.
     */
    public function graduate(Request $request, int $id)
    {
        try {
            $student = Student::findOrFail($id);
            $user = $request->user();
            $institutionId = $user->isAdminOrSuperAdmin()
                ? null
                : $this->resolveInstitutionId($request);

            if (!$this->studentService->canAccess($student, $institutionId, $user->isAdminOrSuperAdmin())) {
                return response()->json(['message' => 'Anda tidak berwenang meluluskan siswa ini.'], 403);
            }

            $graduationYear = $request->input('graduation_year') ? (int) $request->input('graduation_year') : null;

            $student = $this->studentService->graduateSingle($student, $graduationYear);

            return response()->json([
                'message' => 'Siswa berhasil diluluskan.',
                'data' => new StudentResource($student->load(['institution', 'class', 'academicYear', 'semester', 'documents'])),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to graduate student', ['student_id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat meluluskan siswa',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Luluskan banyak siswa sekaligus.
     */
    public function graduateBulk(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'integer|exists:student,id',
            'graduation_year' => 'nullable|integer|min:1900|max:' . (date('Y') + 2),
        ]);

        try {
            $studentIds = $request->input('student_ids');
            $graduationYear = $request->input('graduation_year') ? (int) $request->input('graduation_year') : null;
            $user = $request->user();

            $institutionId = $user->isAdminOrSuperAdmin()
                ? null
                : $this->resolveInstitutionId($request);

            $filteredIds = [];
            foreach ($studentIds as $id) {
                $student = Student::find($id);
                if ($student && $this->studentService->canAccess($student, $institutionId, $user->isAdminOrSuperAdmin())) {
                    $filteredIds[] = $id;
                }
            }

            if (count($studentIds) > 0 && count($filteredIds) === 0) {
                return response()->json([
                    'message' => 'Tidak ada siswa yang dapat diluluskan. Pastikan siswa berasal dari institusi Anda.',
                ], 403);
            }

            $result = $this->studentService->graduateBulk($filteredIds, $graduationYear);

            return response()->json([
                'message' => $result['success'] . ' siswa berhasil diluluskan.' . (count($result['failed']) > 0 ? ' ' . count($result['failed']) . ' gagal.' : ''),
                'success' => $result['success'],
                'failed' => $result['failed'],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to bulk graduate', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat meluluskan siswa',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Batalkan kelulusan satu siswa (kembalikan ke Aktif).
     */
    public function revokeGraduation(Request $request, int $id)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $student = Student::findOrFail($id);
            $user = $request->user();
            $institutionId = $user->isAdminOrSuperAdmin()
                ? null
                : $this->resolveInstitutionId($request);

            if (!$this->studentService->canAccess($student, $institutionId, $user->isAdminOrSuperAdmin())) {
                return response()->json(['message' => 'Anda tidak berwenang membatalkan kelulusan siswa ini.'], 403);
            }

            $student = $this->studentService->revokeGraduation(
                $student,
                $request->input('reason')
            );

            return response()->json([
                'message' => 'Kelulusan berhasil dibatalkan. Siswa dikembalikan ke status Aktif.',
                'data' => new StudentResource($student->load(['institution', 'class', 'academicYear', 'semester', 'documents'])),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to revoke graduation', ['student_id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat membatalkan kelulusan',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Batalkan kelulusan banyak siswa sekaligus.
     */
    public function revokeGraduationBulk(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'integer|exists:student,id',
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $studentIds = $request->input('student_ids');
            $reason = $request->input('reason');
            $user = $request->user();

            $institutionId = $user->isAdminOrSuperAdmin()
                ? null
                : $this->resolveInstitutionId($request);

            $filteredIds = [];
            foreach ($studentIds as $id) {
                $student = Student::find($id);
                if ($student && $this->studentService->canAccess($student, $institutionId, $user->isAdminOrSuperAdmin())) {
                    $filteredIds[] = $id;
                }
            }

            if (count($studentIds) > 0 && count($filteredIds) === 0) {
                return response()->json([
                    'message' => 'Tidak ada siswa yang dapat dibatalkan kelulusannya. Pastikan siswa berasal dari institusi Anda.',
                ], 403);
            }

            $result = $this->studentService->revokeGraduationBulk($filteredIds, $reason);

            return response()->json([
                'message' => $result['success'] . ' kelulusan berhasil dibatalkan.' . (count($result['failed']) > 0 ? ' ' . count($result['failed']) . ' gagal.' : ''),
                'success' => $result['success'],
                'failed' => $result['failed'],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to bulk revoke graduation', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat membatalkan kelulusan',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
