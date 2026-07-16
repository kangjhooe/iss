<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkEmployeeAttendanceRequest;
use App\Http\Requests\StoreEmployeeAttendanceRequest;
use App\Http\Requests\UpdateEmployeeAttendanceRequest;
use App\Http\Resources\EmployeeAttendanceResource;
use App\Models\EmployeeAttendance;
use App\Services\EmployeeAttendanceService;
use App\Support\InstitutionContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

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
     * Bulk store/update employee attendances for one date.
     */
    public function bulkStore(BulkEmployeeAttendanceRequest $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $date = $request->validated()['date'];
            $attendances = $request->validated()['attendances'] ?? [];

            $saved = $this->employeeAttendanceService->bulkUpsert($institutionId, $date, $attendances);

            return response()->json([
                'message' => 'Absensi pegawai berhasil disimpan.',
                'data' => EmployeeAttendanceResource::collection($saved),
            ], 201);
        } catch (\Exception $e) {
            Log::error('EmployeeAttendance bulkStore failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal menyimpan absensi pegawai.',
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
     * Get status options for employee attendance.
     */
    public function statusOptions(): JsonResponse
    {
        return response()->json(['data' => EmployeeAttendance::STATUSES]);
    }
}
