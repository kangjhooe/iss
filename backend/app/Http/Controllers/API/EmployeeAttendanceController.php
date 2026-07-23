<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkEmployeeAttendanceRequest;
use App\Http\Requests\StoreEmployeeAttendanceRequest;
use App\Http\Requests\UpdateEmployeeAttendanceRequest;
use App\Http\Resources\EmployeeAttendanceResource;
use App\Models\EmployeeAttendance;
use App\Models\Institution;
use App\Services\EmployeeAttendanceService;
use App\Support\InstitutionContext;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeAttendanceController extends Controller
{
    public function __construct(
        protected EmployeeAttendanceService $employeeAttendanceService
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
     * List employee attendances for current institution.
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $filters = $request->only(['employee_id', 'date_from', 'date_to', 'status']);
            $perPage = min($request->get('per_page', 15), 100);

            $attendances = $this->employeeAttendanceService->listForInstitution(
                $institutionId,
                $filters,
                $perPage
            );

            return EmployeeAttendanceResource::collection($attendances);
        } catch (\Exception $e) {
            Log::error('EmployeeAttendance index failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil data absensi pegawai.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * JSON rekap absensi pegawai (agregat per pegawai).
     */
    public function rekap(Request $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $filters = $request->only(['employee_id', 'date_from', 'date_to', 'status']);
            $rekap = $this->employeeAttendanceService->buildRekap($institutionId, $filters);

            return response()->json([
                'data' => $rekap['rows'],
                'totals' => $rekap['totals'],
                'meta' => $rekap['meta'],
            ]);
        } catch (\Exception $e) {
            Log::error('EmployeeAttendance rekap failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil rekap absensi pegawai.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Export rekap absensi pegawai (PDF / CSV) dengan kop & TTD standar.
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

            $filters = $request->only(['employee_id', 'date_from', 'date_to', 'status']);
            $rekap = $this->employeeAttendanceService->buildRekap($institutionId, $filters);
            $institution = Institution::find($institutionId);
            $printedAt = now()->locale('id')->isoFormat('D MMMM YYYY HH:mm');

            if ($format === 'csv') {
                $filename = 'Rekap_Absensi_Pegawai_' . date('Y-m-d_His') . '.csv';

                return new StreamedResponse(function () use ($rekap) {
                    $out = fopen('php://output', 'w');
                    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
                    fputcsv($out, [
                        'No', 'NIP', 'Nama', 'Tipe',
                        'Hadir', 'Alpha', 'Izin', 'Sakit', 'Cuti', 'Dinas Luar', 'WFH',
                        'Tercatat', '% Hadir',
                    ]);
                    foreach ($rekap['rows'] as $i => $row) {
                        fputcsv($out, [
                            $i + 1,
                            $row['nip'] ?? '',
                            $row['name'] ?? '',
                            $row['type'] ?? '',
                            $row['counts']['hadir'] ?? 0,
                            $row['counts']['alpha'] ?? 0,
                            $row['counts']['izin'] ?? 0,
                            $row['counts']['sakit'] ?? 0,
                            $row['counts']['cuti'] ?? 0,
                            $row['counts']['dinas_luar'] ?? 0,
                            $row['counts']['wfh'] ?? 0,
                            $row['tercatat'] ?? 0,
                            $row['persentase_hadir'] ?? 0,
                        ]);
                    }
                    fclose($out);
                }, 200, [
                    'Content-Type' => 'text/csv; charset=UTF-8',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                ]);
            }

            $pdf = DomPDF::loadView('attendance.employee_rekap', [
                'institution' => $institution,
                'rows' => $rekap['rows'],
                'totals' => $rekap['totals'],
                'meta' => $rekap['meta'],
                'printed_at' => $printedAt,
            ])->setPaper('a4', 'landscape');

            return $pdf->download('Rekap_Absensi_Pegawai_' . date('Y-m-d_His') . '.pdf');
        } catch (\Exception $e) {
            Log::error('EmployeeAttendance exportRekap failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengekspor rekap absensi pegawai.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Store a single employee attendance (or update if exists for same date).
     */
    public function store(StoreEmployeeAttendanceRequest $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $data = $request->validated();
            $attendance = $this->employeeAttendanceService->upsert($institutionId, $data);

            return (new EmployeeAttendanceResource($attendance))
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            Log::error('EmployeeAttendance store failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal menyimpan absensi pegawai.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Bulk store/update attendances for one date.
     */
    public function bulkStore(BulkEmployeeAttendanceRequest $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $data = $request->validated();
            $saved = $this->employeeAttendanceService->bulkUpsert(
                $institutionId,
                $data['date'],
                $data['attendances'] ?? []
            );

            return response()->json([
                'message' => 'Absensi pegawai berhasil disimpan.',
                'data' => EmployeeAttendanceResource::collection($saved),
            ], 201);
        } catch (\Exception $e) {
            Log::error('EmployeeAttendance bulkStore failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal menyimpan absensi massal.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Update a single employee attendance record.
     */
    public function update(UpdateEmployeeAttendanceRequest $request, EmployeeAttendance $employeeAttendance): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId || $employeeAttendance->institution_id !== (int) $institutionId) {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }

            $attendance = $this->employeeAttendanceService->update($employeeAttendance, $request->validated());

            return response()->json([
                'message' => 'Absensi berhasil diperbarui.',
                'data' => new EmployeeAttendanceResource($attendance),
            ]);
        } catch (\Exception $e) {
            Log::error('EmployeeAttendance update failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal memperbarui absensi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Status options for employee attendance.
     */
    public function statusOptions(): JsonResponse
    {
        return response()->json(['data' => EmployeeAttendance::STATUSES]);
    }
}
