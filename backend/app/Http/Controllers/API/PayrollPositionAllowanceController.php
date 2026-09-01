<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesPayrollInstitution;
use App\Http\Controllers\Controller;
use App\Services\PayrollService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PayrollPositionAllowanceController extends Controller
{
    use ResolvesPayrollInstitution;

    public function __construct(protected PayrollService $payrollService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $institutionId = $this->payrollInstitutionId($request);
            if (! $institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $items = $this->payrollService->listPositionAllowances($institutionId);

            return response()->json(['data' => $items->values()]);
        } catch (\Exception $e) {
            Log::error('PayrollPositionAllowance index failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal memuat tunjangan jabatan.'], 500);
        }
    }

    public function sync(Request $request): JsonResponse
    {
        try {
            $institutionId = $this->payrollInstitutionId($request);
            if (! $institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $data = $request->validate([
                'items' => ['required', 'array'],
                'items.*.structural_position_id' => ['required', 'integer'],
                'items.*.amount' => ['nullable', 'numeric', 'min:0'],
                'items.*.is_active' => ['nullable', 'boolean'],
            ]);

            $items = $this->payrollService->syncPositionAllowances($institutionId, $data['items']);

            return response()->json([
                'message' => 'Tunjangan jabatan disimpan.',
                'data' => $items->values(),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('PayrollPositionAllowance sync failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal menyimpan tunjangan jabatan.'], 500);
        }
    }
}
