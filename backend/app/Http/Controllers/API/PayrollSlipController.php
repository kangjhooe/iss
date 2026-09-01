<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesPayrollInstitution;
use App\Http\Controllers\Controller;
use App\Http\Resources\PayrollSlipResource;
use App\Models\PayrollSlip;
use App\Services\PayrollService;
use App\Services\PayrollSlipPdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PayrollSlipController extends Controller
{
    use ResolvesPayrollInstitution;

    public function __construct(
        protected PayrollService $payrollService,
        protected PayrollSlipPdfService $pdfService
    ) {
    }

    public function show(Request $request, PayrollSlip $slip): PayrollSlipResource|JsonResponse
    {
        if ($denied = $this->denyPayrollForeign($request, (int) $slip->institution_id)) {
            return $denied;
        }

        $slip->load([
            'employee:id,nip,name,type,employment_status',
            'period',
            'run',
            'lines' => fn ($q) => $q->orderBy('sort_order'),
        ]);

        return new PayrollSlipResource($slip);
    }

    public function update(Request $request, PayrollSlip $slip): PayrollSlipResource|JsonResponse
    {
        try {
            if ($denied = $this->denyPayrollForeign($request, (int) $slip->institution_id)) {
                return $denied;
            }

            $data = $request->validate([
                'notes' => ['nullable', 'string'],
                'lines' => ['nullable', 'array'],
                'lines.*.id' => ['nullable', 'integer'],
                'lines.*.label' => ['nullable', 'string', 'max:120'],
                'lines.*.type' => ['nullable', Rule::in(['earning', 'deduction'])],
                'lines.*.amount' => ['nullable', 'numeric', 'min:0'],
                'lines.*.component_id' => ['nullable', 'integer'],
                'remove_line_ids' => ['nullable', 'array'],
                'remove_line_ids.*' => ['integer'],
            ]);

            $slip = $this->payrollService->updateSlip($slip, $data);

            return new PayrollSlipResource($slip);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('PayrollSlip update failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal memperbarui slip gaji.'], 500);
        }
    }

    public function pdf(Request $request, PayrollSlip $slip)
    {
        try {
            if ($denied = $this->denyPayrollForeign($request, (int) $slip->institution_id)) {
                return $denied;
            }

            if (! in_array($slip->status, ['final', 'paid'], true) && ! $slip->run?->isEditable()) {
                // Allow PDF in draft for TU preview
            }

            return $this->pdfService->stream($slip);
        } catch (\Exception $e) {
            Log::error('PayrollSlip pdf failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mencetak slip gaji.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
