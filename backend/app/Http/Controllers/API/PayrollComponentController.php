<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesPayrollInstitution;
use App\Http\Controllers\Controller;
use App\Http\Resources\PayrollComponentResource;
use App\Models\PayrollComponent;
use App\Services\PayrollService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PayrollComponentController extends Controller
{
    use ResolvesPayrollInstitution;

    protected const CALC_MODES = ['fixed', 'per_alpha_day', 'manual', 'structural_position', 'thr'];

    public function __construct(protected PayrollService $payrollService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $this->payrollInstitutionId($request);
            if (! $institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $this->payrollService->ensureDefaultComponents($institutionId);

            $query = PayrollComponent::forInstitution($institutionId)->orderBy('sort_order')->orderBy('name');
            if ($request->filled('active_only')) {
                $query->active();
            }

            return PayrollComponentResource::collection($query->get());
        } catch (\Exception $e) {
            Log::error('PayrollComponent index failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal memuat komponen gaji.'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $institutionId = $this->payrollInstitutionId($request);
            if (! $institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $data = $request->validate([
                'code' => [
                    'required', 'string', 'max:40',
                    Rule::unique('payroll_components', 'code')->where('institution_id', $institutionId),
                ],
                'name' => ['required', 'string', 'max:120'],
                'description' => ['nullable', 'string'],
                'type' => ['required', Rule::in(['earning', 'deduction'])],
                'calc_mode' => ['nullable', Rule::in(self::CALC_MODES)],
                'default_amount' => ['nullable', 'numeric', 'min:0'],
                'is_active' => ['nullable', 'boolean'],
                'sort_order' => ['nullable', 'integer', 'min:0'],
            ]);

            $component = $this->payrollService->createComponent($institutionId, $data);

            return (new PayrollComponentResource($component))
                ->response()
                ->setStatusCode(201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('PayrollComponent store failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal menambah komponen gaji.'], 500);
        }
    }

    public function update(Request $request, PayrollComponent $component): PayrollComponentResource|JsonResponse
    {
        try {
            if ($denied = $this->denyPayrollForeign($request, (int) $component->institution_id)) {
                return $denied;
            }

            $data = $request->validate([
                'code' => ['sometimes', 'string', 'max:40'],
                'name' => ['sometimes', 'string', 'max:120'],
                'description' => ['nullable', 'string'],
                'type' => ['sometimes', Rule::in(['earning', 'deduction'])],
                'calc_mode' => ['sometimes', Rule::in(self::CALC_MODES)],
                'default_amount' => ['nullable', 'numeric', 'min:0'],
                'is_active' => ['nullable', 'boolean'],
                'sort_order' => ['nullable', 'integer', 'min:0'],
            ]);

            $component = $this->payrollService->updateComponent($component, $data);

            return new PayrollComponentResource($component);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('PayrollComponent update failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal memperbarui komponen gaji.'], 500);
        }
    }

    public function destroy(Request $request, PayrollComponent $component): JsonResponse
    {
        try {
            if ($denied = $this->denyPayrollForeign($request, (int) $component->institution_id)) {
                return $denied;
            }

            $this->payrollService->deleteComponent($component);

            return response()->json(['message' => 'Komponen gaji dihapus.']);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('PayrollComponent destroy failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal menghapus komponen gaji.'], 500);
        }
    }
}
