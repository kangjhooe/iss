<?php

namespace App\Http\Controllers\API;

use App\Exports\PayrollRunExport;
use App\Http\Controllers\API\Concerns\ResolvesPayrollInstitution;
use App\Http\Controllers\Controller;
use App\Http\Resources\PayrollRunResource;
use App\Http\Resources\PayrollSlipResource;
use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\PayrollSlip;
use App\Services\PayrollRunExportService;
use App\Services\PayrollRunReportPdfService;
use App\Services\PayrollService;
use App\Services\PayrollSlipPdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Excel as ExcelManager;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
class PayrollRunController extends Controller
{
    use ResolvesPayrollInstitution;

    public function __construct(
        protected PayrollService $payrollService,
        protected PayrollSlipPdfService $pdfService,
        protected PayrollRunExportService $exportService,
        protected PayrollRunReportPdfService $reportPdfService
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $this->payrollInstitutionId($request);
            if (! $institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $query = PayrollRun::forInstitution($institutionId)
                ->with(['period', 'creator:id,name', 'financeExpense'])
                ->withCount('slips')
                ->withSum('slips as total_net', 'net')
                ->orderByDesc('generated_at');

            if ($request->filled('period_id')) {
                $query->where('period_id', $request->get('period_id'));
            }
            if ($request->filled('status')) {
                $query->where('status', $request->get('status'));
            }

            $perPage = min(50, max(10, (int) $request->get('per_page', 15)));

            return PayrollRunResource::collection($query->paginate($perPage));
        } catch (\Exception $e) {
            Log::error('PayrollRun index failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal memuat proses gaji.'], 500);
        }
    }

    public function generate(Request $request): JsonResponse
    {
        try {
            $institutionId = $this->payrollInstitutionId($request);
            if (! $institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $data = $request->validate([
                'period_id' => [
                    'required',
                    'integer',
                    Rule::exists('payroll_periods', 'id')->where('institution_id', $institutionId),
                ],
                'label' => ['nullable', 'string', 'max:120'],
                'notes' => ['nullable', 'string'],
                'employee_ids' => ['nullable', 'array'],
                'employee_ids.*' => ['integer'],
                'employee_types' => ['nullable', 'array'],
                'employee_types.*' => ['string', 'max:40'],
                'include_thr' => ['nullable', 'boolean'],
            ]);

            $result = $this->payrollService->generateRun(
                $institutionId,
                (int) $request->user()->id,
                $data
            );

            return response()->json([
                'message' => "Berhasil membuat {$result['created']} slip gaji.",
                'data' => new PayrollRunResource($result['run']),
                'meta' => [
                    'created' => $result['created'],
                    'skipped' => $result['skipped'],
                    'skipped_employees' => $result['skipped_employees'],
                ],
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('PayrollRun generate failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal memproses gaji.'], 500);
        }
    }

    public function show(Request $request, PayrollRun $run): PayrollRunResource|JsonResponse
    {
        if ($denied = $this->denyPayrollForeign($request, (int) $run->institution_id)) {
            return $denied;
        }

        $run->load(['period', 'creator:id,name', 'financeExpense'])->loadCount('slips')->loadSum('slips as total_net', 'net');

        return new PayrollRunResource($run);
    }

    public function slips(Request $request, PayrollRun $run): AnonymousResourceCollection|JsonResponse
    {
        try {
            if ($denied = $this->denyPayrollForeign($request, (int) $run->institution_id)) {
                return $denied;
            }

            $query = PayrollSlip::query()
                ->where('run_id', $run->id)
                ->with(['employee:id,nip,name,type,employment_status', 'period'])
                ->join('employee', 'employee.id', '=', 'payroll_slips.employee_id')
                ->orderBy('employee.name')
                ->select('payroll_slips.*');

            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->whereHas('employee', fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%"));
            }

            $perPage = min(100, max(10, (int) $request->get('per_page', 25)));

            return PayrollSlipResource::collection($query->paginate($perPage));
        } catch (\Exception $e) {
            Log::error('PayrollRun slips failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal memuat slip gaji.'], 500);
        }
    }

    public function finalize(Request $request, PayrollRun $run): PayrollRunResource|JsonResponse
    {
        try {
            if ($denied = $this->denyPayrollForeign($request, (int) $run->institution_id)) {
                return $denied;
            }

            $run = $this->payrollService->finalizeRun($run);

            return new PayrollRunResource($run);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('PayrollRun finalize failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal memfinalisasi gaji.'], 500);
        }
    }

    public function markPaid(Request $request, PayrollRun $run): PayrollRunResource|JsonResponse
    {
        try {
            if ($denied = $this->denyPayrollForeign($request, (int) $run->institution_id)) {
                return $denied;
            }

            $run = $this->payrollService->markRunPaid($run, (int) $request->user()->id);

            return new PayrollRunResource($run->load(['period', 'creator:id,name', 'financeExpense']));
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('PayrollRun markPaid failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal menandai gaji dibayar.'], 500);
        }
    }

    public function unpay(Request $request, PayrollRun $run): PayrollRunResource|JsonResponse
    {
        try {
            if ($denied = $this->denyPayrollForeign($request, (int) $run->institution_id)) {
                return $denied;
            }

            $run = $this->payrollService->unpayRun($run);

            return new PayrollRunResource($run->load(['period', 'creator:id,name', 'financeExpense']));
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('PayrollRun unpay failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal membatalkan pembayaran gaji.'], 500);
        }
    }

    public function reopen(Request $request, PayrollRun $run): PayrollRunResource|JsonResponse
    {
        try {
            if ($denied = $this->denyPayrollForeign($request, (int) $run->institution_id)) {
                return $denied;
            }

            $run = $this->payrollService->reopenRun($run);

            return new PayrollRunResource($run->load(['period', 'creator:id,name', 'financeExpense']));
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('PayrollRun reopen failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal membuka kembali proses gaji.'], 500);
        }
    }

    public function destroy(Request $request, PayrollRun $run): JsonResponse
    {
        try {
            if ($denied = $this->denyPayrollForeign($request, (int) $run->institution_id)) {
                return $denied;
            }

            $this->payrollService->deleteRun($run);

            return response()->json(['message' => 'Proses gaji dihapus.']);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('PayrollRun destroy failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal menghapus proses gaji.'], 500);
        }
    }

    public function exportExcel(Request $request, PayrollRun $run): BinaryFileResponse|JsonResponse
    {
        try {
            if ($denied = $this->denyPayrollForeign($request, (int) $run->institution_id)) {
                return $denied;
            }

            $report = $this->exportService->buildReport($run);
            $filename = $this->exportService->buildFilename($run, 'xlsx');
            $export = new PayrollRunExport($report);

            return app(ExcelManager::class)->download($export, $filename, ExcelManager::XLSX);
        } catch (\Exception $e) {
            Log::error('PayrollRun exportExcel failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal mengekspor Excel.'], 500);
        }
    }

    public function exportPdf(Request $request, PayrollRun $run)
    {
        try {
            if ($denied = $this->denyPayrollForeign($request, (int) $run->institution_id)) {
                return $denied;
            }

            return $this->reportPdfService->stream($run);
        } catch (\Exception $e) {
            Log::error('PayrollRun exportPdf failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mencetak rekap PDF.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
