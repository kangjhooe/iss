<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateFinanceInvoiceRequest;
use App\Http\Resources\FinanceInvoiceResource;
use App\Models\FinanceInvoice;
use App\Services\FinanceService;
use App\Support\InstitutionContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceInvoiceController extends Controller
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
        $query = FinanceInvoice::forInstitution($institutionId)
            ->with(['feeType:id,name,code,frequency', 'student:id,name,nis,class_id', 'schoolClass:id,name,code'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            if ($request->get('status') === 'outstanding') {
                $query->outstanding();
            } else {
                $query->where('status', $request->get('status'));
            }
        }
        if ($request->filled('fee_type_id')) {
            $query->where('fee_type_id', $request->get('fee_type_id'));
        }
        if ($request->filled('frequency')) {
            $query->whereHas('feeType', fn ($q) => $q->where('frequency', $request->get('frequency')));
        }
        if ($request->filled('exclude_frequency')) {
            $query->whereHas('feeType', fn ($q) => $q->where('frequency', '!=', $request->get('exclude_frequency')));
        }
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->get('class_id'));
        }
        if ($request->filled('student_id')) {
            $query->where('student_id', $request->get('student_id'));
        }
        if ($request->filled('period_label')) {
            $query->where('period_label', $request->get('period_label'));
        }
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('student', fn ($sq) => $sq->where('name', 'like', "%{$search}%")
                        ->orWhere('nis', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    protected function cancelInvoice(FinanceInvoice $invoice): void
    {
        $updates = ['status' => 'cancelled'];
        // Free the active unique key (institution, fee_type, student, period) by suffixing invoice id.
        if ($invoice->period_label) {
            $suffix = '-c' . $invoice->id;
            if (!str_ends_with($invoice->period_label, $suffix)) {
                $base = preg_replace('/-c\d+$/', '', $invoice->period_label) ?: $invoice->period_label;
                $updates['period_label'] = $base . $suffix;
            }
        }
        $invoice->update($updates);
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
            return FinanceInvoiceResource::collection(
                $this->filteredQuery($request, $institutionId)->paginate($perPage)
            );
        } catch (\Exception $e) {
            Log::error('FinanceInvoice index failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil tagihan.'], 500);
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
            $filename = 'keuangan-tagihan-' . now()->format('Ymd-His') . '.csv';

            return new StreamedResponse(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($out, [
                    'ID', 'Siswa', 'NIS', 'Kelas', 'Jenis Biaya', 'Judul', 'Periode',
                    'Nominal', 'Dibayar', 'Sisa', 'Jatuh Tempo', 'Status', 'Dibuat',
                ]);
                foreach ($rows as $row) {
                    fputcsv($out, [
                        $row->id,
                        $row->student?->name,
                        $row->student?->nis,
                        $row->schoolClass?->name,
                        $row->feeType?->name,
                        $row->title,
                        $row->period_label,
                        (float) $row->amount,
                        (float) $row->amount_paid,
                        (float) $row->remaining,
                        $row->due_date?->format('Y-m-d'),
                        $row->status,
                        $row->created_at?->format('Y-m-d H:i'),
                    ]);
                }
                fclose($out);
            }, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        } catch (\Exception $e) {
            Log::error('FinanceInvoice export failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengekspor tagihan.'], 500);
        }
    }

    public function generate(GenerateFinanceInvoiceRequest $request): JsonResponse
    {
        try {
            $institutionId = $this->institutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $result = $this->financeService->generateInvoices(
                $institutionId,
                (int) $request->user()->id,
                $request->validated()
            );

            $loaded = $result['invoices']->load(['feeType', 'student', 'schoolClass']);
            $status = $result['created'] > 0 ? 201 : 200;

            return response()->json([
                'message' => "Berhasil membuat {$result['created']} tagihan"
                    . ($result['skipped'] ? ", {$result['skipped']} dilewati (sudah ada)." : '.'),
                'created' => $result['created'],
                'skipped' => $result['skipped'],
                'batch_key' => $result['batch_key'],
                'data' => FinanceInvoiceResource::collection($loaded)->resolve(),
            ], $status);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('FinanceInvoice generate failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal membuat tagihan.'], 500);
        }
    }

    public function show(Request $request, FinanceInvoice $invoice): FinanceInvoiceResource|JsonResponse
    {
        if ($denied = $this->denyIfForeign($request, (int) $invoice->institution_id)) {
            return $denied;
        }
        $invoice->load(['feeType', 'student', 'schoolClass', 'payments']);
        return new FinanceInvoiceResource($invoice);
    }

    public function update(Request $request, FinanceInvoice $invoice): FinanceInvoiceResource|JsonResponse
    {
        try {
            if ($denied = $this->denyIfForeign($request, (int) $invoice->institution_id)) {
                return $denied;
            }

            $data = $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'amount' => 'sometimes|required|numeric|min:0.01',
                'due_date' => 'nullable|date',
                'notes' => 'nullable|string',
                'status' => 'sometimes|in:cancelled',
            ]);

            if (isset($data['amount']) && (float) $data['amount'] < (float) $invoice->amount_paid) {
                return response()->json([
                    'message' => 'Nominal tagihan tidak boleh lebih kecil dari yang sudah dibayar.',
                ], 422);
            }

            if (($data['status'] ?? null) === 'cancelled') {
                $this->cancelInvoice($invoice);
            } else {
                $invoice->update($data);
                if (isset($data['amount'])) {
                    $invoice->refreshPaymentStatus();
                }
            }

            return new FinanceInvoiceResource($invoice->fresh(['feeType', 'student', 'schoolClass']));
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('FinanceInvoice update failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal memperbarui tagihan.'], 500);
        }
    }

    public function destroy(Request $request, FinanceInvoice $invoice): JsonResponse
    {
        if ($denied = $this->denyIfForeign($request, (int) $invoice->institution_id)) {
            return $denied;
        }

        if ($invoice->payments()->exists()) {
            $this->cancelInvoice($invoice);
            return response()->json(['message' => 'Tagihan dibatalkan karena sudah ada pembayaran.']);
        }

        $invoice->delete();
        return response()->json(['message' => 'Tagihan dihapus.']);
    }
}
