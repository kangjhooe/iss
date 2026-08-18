<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUksVisitRequest;
use App\Http\Requests\UpdateUksVisitRequest;
use App\Http\Resources\UksVisitResource;
use App\Models\Institution;
use App\Models\UksVisit;
use App\Models\User;
use App\Services\UksService;
use App\Support\InstitutionContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UksVisitController extends Controller
{
    use ResolvesInstitution;

    public function __construct(
        protected UksService $uksService
    ) {}

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $filters = $request->only([
                'student_id', 'recorded_by', 'uks_visit_type_id', 'status',
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
            $perPage = min((int) $request->get('per_page', 15), 100);
            $visits = $this->uksService->listForInstitution($institutionId, $filters, $perPage);

            return UksVisitResource::collection($visits);
        } catch (\Exception $e) {
            Log::error('UKS index failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mengambil data kunjungan UKS.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function store(StoreUksVisitRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $visit = $this->uksService->create($institutionId, $request->validated(), $user->id);

            return (new UksVisitResource($visit))->response()->setStatusCode(201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Siswa atau jenis kunjungan tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('UKS store failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mencatat kunjungan UKS.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function show(Request $request, UksVisit $uks_visit): UksVisitResource|JsonResponse
    {
        $user = $request->user();
        if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $uks_visit->institution_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $uks_visit->load(['student.class:id,name', 'recorder', 'visitType']);

        return new UksVisitResource($uks_visit);
    }

    public function update(UpdateUksVisitRequest $request, UksVisit $uks_visit): UksVisitResource|JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $uks_visit->institution_id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $visit = $this->uksService->update($uks_visit, $request->validated());

            return new UksVisitResource($visit);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Siswa atau jenis kunjungan tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('UKS update failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal memperbarui kunjungan UKS.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function destroy(Request $request, UksVisit $uks_visit): JsonResponse
    {
        $user = $request->user();
        if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $uks_visit->institution_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $uks_visit->delete();

        return response()->json(['message' => 'Kunjungan UKS berhasil dihapus.']);
    }

    public function recorders(Request $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $list = User::where('institution_id', $institutionId)
                ->select('id', 'name', 'email')
                ->orderBy('name')
                ->get()
                ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email]);

            return response()->json(['data' => $list]);
        } catch (\Exception $e) {
            Log::error('UKS recorders failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mengambil daftar petugas.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function export(Request $request): StreamedResponse|JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $filters = $request->only([
                'student_id', 'recorded_by', 'uks_visit_type_id', 'status',
                'date_from', 'date_to', 'class_id', 'academic_year_id', 'semester_id', 'search',
            ]);
            $visits = $this->uksService->listForExport($institutionId, $filters);
            $filename = 'laporan-uks-kunjungan-'.date('Y-m-d-His').'.csv';

            return response()->streamDownload(function () use ($visits) {
                $out = fopen('php://output', 'w');
                fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
                fputcsv($out, [
                    'Tanggal', 'NIS', 'NISN', 'Nama Siswa', 'Kelas', 'Jenis', 'Status',
                    'Keluhan', 'Tindakan', 'Catatan', 'TB (cm)', 'BB (kg)', 'Suhu (°C)', 'Tekanan Darah', 'Petugas',
                ]);
                foreach ($visits as $v) {
                    fputcsv($out, [
                        $v->visit_date?->format('Y-m-d'),
                        $v->student?->nis ?? '',
                        $v->student?->nisn ?? '',
                        $v->student?->name ?? '',
                        $v->student?->class?->name ?? '',
                        $v->visitType?->name ?? '',
                        $v->status ?? '',
                        $v->complaint ?? '',
                        $v->action_taken ?? '',
                        $v->notes ?? '',
                        $v->height_cm ?? '',
                        $v->weight_kg ?? '',
                        $v->temperature_c ?? '',
                        $v->blood_pressure ?? '',
                        $v->recorder?->name ?? '',
                    ]);
                }
                fclose($out);
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        } catch (\Exception $e) {
            Log::error('UKS export failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mengekspor data UKS.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Portal siswa: ringkasan + riwayat kunjungan UKS sendiri (tanpa permission modul uks).
     */
    public function my(Request $request): JsonResponse
    {
        try {
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

            $summary = $this->uksService->summaryForStudent((int) $student->id, $institutionId);
            $visits = $this->uksService->listForStudentPortal((int) $student->id, $institutionId);

            return response()->json([
                'data' => UksVisitResource::collection($visits)->resolve(),
                'summary' => $summary,
                'meta' => [
                    'total' => $summary['total'],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('UKS my visits failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mengambil riwayat kunjungan UKS.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

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

            $visits = $this->uksService->listByStudent($studentId, $institutionId);

            return UksVisitResource::collection($visits);
        } catch (\Exception $e) {
            Log::error('UKS byStudent failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mengambil riwayat UKS siswa.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function stats(Request $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $year = $request->get('year') ? (int) $request->get('year') : null;
            $data = $this->uksService->getStats($institutionId, $year);

            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('UKS stats failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mengambil statistik UKS.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
