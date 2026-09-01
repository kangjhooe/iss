<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PayrollSlipResource;
use App\Models\PayrollSlip;
use App\Services\PayrollSlipPdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class EmployeePayrollPortalController extends Controller
{
    public function __construct(protected PayrollSlipPdfService $pdfService)
    {
    }

    protected function resolveEmployee(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->isTeacherOrStaff()) {
            return null;
        }

        return $user->employeeProfile ?? $user->teacherProfile;
    }

    public function slips(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $employee = $this->resolveEmployee($request);
            if (! $employee) {
                return response()->json(['message' => 'Profil pegawai tidak ditemukan.'], 404);
            }

            $query = PayrollSlip::query()
                ->where('employee_id', $employee->id)
                ->whereIn('status', ['final', 'paid'])
                ->with(['period', 'run:id,status,label'])
                ->orderByDesc('created_at');

            $perPage = min(24, max(6, (int) $request->get('per_page', 12)));

            return PayrollSlipResource::collection($query->paginate($perPage));
        } catch (\Exception $e) {
            Log::error('EmployeePayrollPortal slips failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal memuat slip gaji.'], 500);
        }
    }

    public function show(Request $request, PayrollSlip $slip): PayrollSlipResource|JsonResponse
    {
        $employee = $this->resolveEmployee($request);
        if (! $employee || (int) $slip->employee_id !== (int) $employee->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (! in_array($slip->status, ['final', 'paid'], true)) {
            return response()->json(['message' => 'Slip gaji belum tersedia.'], 403);
        }

        $slip->load([
            'period',
            'lines' => fn ($q) => $q->orderBy('sort_order'),
        ]);

        return new PayrollSlipResource($slip);
    }

    public function pdf(Request $request, PayrollSlip $slip)
    {
        try {
            $employee = $this->resolveEmployee($request);
            if (! $employee || (int) $slip->employee_id !== (int) $employee->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if (! in_array($slip->status, ['final', 'paid'], true)) {
                return response()->json(['message' => 'Slip gaji belum tersedia.'], 403);
            }

            return $this->pdfService->stream($slip);
        } catch (\Exception $e) {
            Log::error('EmployeePayrollPortal pdf failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal mengunduh slip gaji.'], 500);
        }
    }
}
