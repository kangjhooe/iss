<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesPayrollInstitution;
use App\Http\Controllers\Controller;
use App\Http\Resources\PayrollPeriodResource;
use App\Models\PayrollPeriod;
use App\Services\PayrollService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PayrollPeriodController extends Controller
{
    use ResolvesPayrollInstitution;

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

            $query = PayrollPeriod::forInstitution($institutionId)
                ->withCount('runs')
                ->orderByDesc('year')
                ->orderByDesc('month');

            if ($request->filled('status')) {
                $query->where('status', $request->get('status'));
            }

            $perPage = min(50, max(10, (int) $request->get('per_page', 12)));

            return PayrollPeriodResource::collection($query->paginate($perPage));
        } catch (\Exception $e) {
            Log::error('PayrollPeriod index failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal memuat periode gaji.'], 500);
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
                'year' => ['required', 'integer', 'min:2000', 'max:2100'],
                'month' => ['required', 'integer', 'min:1', 'max:12'],
                'label' => ['nullable', 'string', 'max:80'],
                'working_days' => ['nullable', 'integer', 'min:1', 'max:31'],
            ]);

            $period = $this->payrollService->createPeriod($institutionId, $data);

            return (new PayrollPeriodResource($period))
                ->response()
                ->setStatusCode(201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('PayrollPeriod store failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal membuat periode gaji.'], 500);
        }
    }

    public function close(Request $request, PayrollPeriod $period): PayrollPeriodResource|JsonResponse
    {
        try {
            if ($denied = $this->denyPayrollForeign($request, (int) $period->institution_id)) {
                return $denied;
            }

            $period = $this->payrollService->closePeriod($period);

            return new PayrollPeriodResource($period);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('PayrollPeriod close failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal menutup periode gaji.'], 500);
        }
    }
}
