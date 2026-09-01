<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFinancePaymentRequest;
use App\Http\Resources\FinancePaymentResource;
use App\Models\FinancePayment;
use App\Models\Institution;
use App\Services\FinanceService;
use App\Support\InstitutionContext;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancePaymentController extends Controller
{
    public function __construct(protected FinanceService $financeService)
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
        if (!$institutionId || (int) $modelInstitutionId !== (int) $institutionId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return null;
    }

    protected function filteredQuery(Request $request, int $institutionId): Builder
    {
        $query = FinancePayment::forInstitution($institutionId)
            ->with(['invoice.student:id,name,nis', 'invoice.feeType:id,name', 'invoice.schoolClass:id,name'])
            ->orderByDesc('paid_at');

        // Align with dashboard: payments on cancelled invoices are excluded unless explicitly requested.
        if ($request->boolean('include_cancelled')) {
            // no filter
        } else {
            $query->whereHas('invoice', fn ($q) => $q->where('status', '!=', 'cancelled'));
        }

        if ($request->filled('invoice_id')) {
            $query->where('invoice_id', $request->get('invoice_id'));
        }
        if ($request->filled('method')) {
            $query->where('method', $request->get('method'));
        }
        if ($request->filled('from')) {
            $query->whereDate('paid_at', '>=', $request->get('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('paid_at', '<=', $request->get('to'));
        }
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('invoice.student', fn ($sq) => $sq->where('name', 'like', "%{$search}%")
                        ->orWhere('nis', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $this->institutionId($request);
            if (!$institutionId && !$user->isSuperAdmin()) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }
            if (!$institutionId) {
                return response()->json(['message' => 'Pilih institusi.'], 400);
            }

            $perPage = min((int) $request->get('per_page', 20), 100);
            return FinancePaymentResource::collection(
                $this->filteredQuery($request, $institutionId)->paginate($perPage)
            );
        } catch (\Exception $e) {
            Log::error('FinancePayment index failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil pembayaran.'], 500);
        }
    }

    public function export(Request $request): StreamedResponse|JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $this->institutionId($request);
            if (!$institutionId && !$user->isSuperAdmin()) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }
            if (!$institutionId) {
                return response()->json(['message' => 'Pilih institusi.'], 400);
            }

            $rows = $this->filteredQuery($request, $institutionId)->limit(10000)->get();
            $filename = 'keuangan-pembayaran-' . now()->format('Ymd-His') . '.csv';

            return new StreamedResponse(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($out, [
                    'ID', 'Tanggal', 'Siswa', 'NIS', 'Tagihan', 'Jenis Biaya',
                    'Nominal', 'Metode', 'Referensi', 'Catatan',
                ]);
                foreach ($rows as $row) {
                    fputcsv($out, [
                        $row->id,
                        $row->paid_at?->format('Y-m-d H:i'),
                        $row->invoice?->student?->name,
                        $row->invoice?->student?->nis,
                        $row->invoice?->title,
                        $row->invoice?->feeType?->name,
                        (float) $row->amount,
                        $row->method,
                        $row->reference,
                        $row->notes,
                    ]);
                }
                fclose($out);
            }, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        } catch (\Exception $e) {
            Log::error('FinancePayment export failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengekspor pembayaran.'], 500);
        }
    }

    public function store(StoreFinancePaymentRequest $request): JsonResponse
    {
        try {
            $institutionId = $this->institutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $payment = $this->financeService->recordPayment(
                $institutionId,
                (int) $request->user()->id,
                $request->validated()
            );

            return (new FinancePaymentResource($payment))
                ->response()
                ->setStatusCode(201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('FinancePayment store failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mencatat pembayaran.'], 500);
        }
    }

    public function show(Request $request, FinancePayment $payment): FinancePaymentResource|JsonResponse
    {
        if ($denied = $this->denyIfForeign($request, (int) $payment->institution_id)) {
            return $denied;
        }
        $payment->load(['invoice.student', 'invoice.feeType', 'invoice.schoolClass', 'recorder:id,name']);
        return new FinancePaymentResource($payment);
    }

    /**
     * Cetak kwitansi PDF (kop resmi + tanda tangan), selaras laporan DomPDF lain.
     */
    public function receipt(Request $request, FinancePayment $payment)
    {
        try {
            if ($denied = $this->denyIfForeign($request, (int) $payment->institution_id)) {
                return $denied;
            }

            $payment->load([
                'invoice.student:id,name,nis',
                'invoice.feeType:id,name',
                'invoice.schoolClass:id,name',
                'recorder:id,name',
            ]);

            $institution = Institution::find($payment->institution_id);
            if (!$institution) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 404);
            }

            $printedAt = now()->locale('id')->isoFormat('D MMMM YYYY HH:mm');
            $asOfDate = $payment->paid_at ?? now();
            $pdf = DomPDF::loadView('finance.receipt', [
                'institution' => $institution,
                'payment' => $payment,
                'printed_at' => $printedAt,
                'as_of_date' => $asOfDate,
            ])->setPaper('a4', 'portrait');

            $filename = 'Kwitansi_' . $payment->id . '_' . date('Ymd_His') . '.pdf';

            return $pdf->stream($filename, ['Attachment' => false]);
        } catch (\Exception $e) {
            Log::error('FinancePayment receipt failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mencetak kwitansi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function destroy(Request $request, FinancePayment $payment): JsonResponse
    {
        try {
            if ($denied = $this->denyIfForeign($request, (int) $payment->institution_id)) {
                return $denied;
            }

            $this->financeService->deletePayment($payment);
            return response()->json(['message' => 'Pembayaran dihapus.']);
        } catch (\Exception $e) {
            Log::error('FinancePayment destroy failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal menghapus pembayaran.'], 500);
        }
    }
}
