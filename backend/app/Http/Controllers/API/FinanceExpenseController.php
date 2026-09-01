<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\FinanceExpenseResource;
use App\Models\FinanceExpense;
use App\Services\FinanceExpenseService;
use App\Support\InstitutionContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceExpenseController extends Controller
{
    public function __construct(protected FinanceExpenseService $expenseService)
    {
    }

    protected function institutionId(Request $request): ?int
    {
        return InstitutionContext::resolveForUser(
            $request->user(),
            $request,
            $request->get('institution_id')
        );
    }

    protected function denyIfForeign(Request $request, int $modelInstitutionId): ?JsonResponse
    {
        $institutionId = $this->institutionId($request);
        if ($request->user()->isSuperAdmin()) {
            return null;
        }
        if (! $institutionId || (int) $modelInstitutionId !== (int) $institutionId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return null;
    }

    protected function validatedFilters(Request $request): ?JsonResponse
    {
        if ($request->filled('category')) {
            $category = $request->get('category');
            if (! in_array($category, [FinanceExpense::CATEGORY_PAYROLL, FinanceExpense::CATEGORY_OTHER], true)) {
                return response()->json(['message' => 'Kategori tidak valid.'], 422);
            }
        }
        if ($request->filled('source')) {
            $source = $request->get('source');
            if (! in_array($source, [FinanceExpense::SOURCE_AUTO, FinanceExpense::SOURCE_MANUAL], true)) {
                return response()->json(['message' => 'Sumber tidak valid.'], 422);
            }
        }

        return null;
    }

    protected function filteredQuery(Request $request, int $institutionId): Builder
    {
        $query = FinanceExpense::forInstitution($institutionId)
            ->with(['payrollRun.period', 'recorder:id,name'])
            ->orderByDesc('expense_date');

        if ($request->filled('category')) {
            $query->where('category', $request->get('category'));
        }
        if ($request->filled('source')) {
            $query->where('source', $request->get('source'));
        }
        if ($request->filled('from')) {
            $query->whereDate('expense_date', '>=', $request->get('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('expense_date', '<=', $request->get('to'));
        }
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $this->institutionId($request);
            if (! $institutionId && ! $request->user()->isSuperAdmin()) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }
            if (! $institutionId) {
                return response()->json(['message' => 'Pilih institusi.'], 400);
            }

            if ($invalid = $this->validatedFilters($request)) {
                return $invalid;
            }

            $perPage = min((int) $request->get('per_page', 20), 100);

            return FinanceExpenseResource::collection(
                $this->filteredQuery($request, $institutionId)->paginate($perPage)
            );
        } catch (\Exception $e) {
            Log::error('FinanceExpense index failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal memuat pengeluaran.'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $institutionId = $this->institutionId($request);
            if (! $institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $data = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'amount' => ['required', 'numeric', 'min:0.01'],
                'expense_date' => ['nullable', 'date'],
                'method' => ['nullable', Rule::in(FinanceExpense::METHODS)],
                'reference' => ['nullable', 'string', 'max:100'],
                'notes' => ['nullable', 'string'],
            ]);

            $expense = $this->expenseService->recordManualExpense(
                $institutionId,
                (int) $request->user()->id,
                $data
            );

            return (new FinanceExpenseResource($expense->load('recorder:id,name')))
                ->response()
                ->setStatusCode(201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('FinanceExpense store failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal mencatat pengeluaran.'], 500);
        }
    }

    public function show(Request $request, FinanceExpense $expense): FinanceExpenseResource|JsonResponse
    {
        if ($denied = $this->denyIfForeign($request, (int) $expense->institution_id)) {
            return $denied;
        }

        $expense->load(['payrollRun.period', 'recorder:id,name']);

        return new FinanceExpenseResource($expense);
    }

    public function destroy(Request $request, FinanceExpense $expense): JsonResponse
    {
        try {
            if ($denied = $this->denyIfForeign($request, (int) $expense->institution_id)) {
                return $denied;
            }

            $this->expenseService->deleteManualExpense($expense);

            return response()->json(['message' => 'Pengeluaran dihapus.']);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('FinanceExpense destroy failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal menghapus pengeluaran.'], 500);
        }
    }

    public function export(Request $request): StreamedResponse|JsonResponse
    {
        try {
            $institutionId = $this->institutionId($request);
            if (! $institutionId && ! $request->user()->isSuperAdmin()) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }
            if (! $institutionId) {
                return response()->json(['message' => 'Pilih institusi.'], 400);
            }

            if ($invalid = $this->validatedFilters($request)) {
                return $invalid;
            }

            $rows = $this->filteredQuery($request, $institutionId)->limit(10000)->get();
            $filename = 'keuangan-pengeluaran-' . now()->format('Ymd-His') . '.csv';

            return new StreamedResponse(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($out, [
                    'ID', 'Tanggal', 'Judul', 'Kategori', 'Sumber', 'Nominal', 'Metode', 'Referensi', 'Proses Gaji', 'Periode Gaji', 'Catatan',
                ]);
                foreach ($rows as $row) {
                    fputcsv($out, [
                        $row->id,
                        $row->expense_date?->format('Y-m-d'),
                        $row->title,
                        $row->category,
                        $row->source,
                        (float) $row->amount,
                        $row->method,
                        $row->reference,
                        $row->payrollRun?->label,
                        $row->payrollRun?->period?->label,
                        $row->notes,
                    ]);
                }
                fclose($out);
            }, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        } catch (\Exception $e) {
            Log::error('FinanceExpense export failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal mengekspor pengeluaran.'], 500);
        }
    }
}
