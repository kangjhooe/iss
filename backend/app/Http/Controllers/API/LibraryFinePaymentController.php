<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Requests\StoreLibraryFinePaymentRequest;
use App\Http\Resources\LibraryFinePaymentResource;
use App\Models\LibraryFinePayment;
use App\Models\LibraryLoan;
use App\Services\LibraryLoanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LibraryFinePaymentController extends Controller
{
    use ResolvesInstitution;

    public function __construct(
        private LibraryLoanService $loanService
    ) {}

    public function index(Request $request)
    {
        try {
            $query = LibraryFinePayment::query()->with(['loan.copy.book', 'creator']);
            if ($request->has('loan_id')) {
                $query->where('loan_id', $request->loan_id);
            }
            $institutionId = $this->resolveInstitutionId($request);
            if (!$request->user()->isAdminOrSuperAdmin()) {
                if ($institutionId === null) {
                    return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
                }
                $query->whereHas('loan', fn ($q) => $q->where('institution_id', $institutionId));
            } elseif ($institutionId !== null) {
                $query->whereHas('loan', fn ($q) => $q->where('institution_id', $institutionId));
            }
            $perPage = min($request->get('per_page', 15), 100);
            $payments = $query->orderBy('paid_at', 'desc')->paginate($perPage);
            return LibraryFinePaymentResource::collection($payments);
        } catch (\Exception $e) {
            Log::error('Library fine payments index', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil data pembayaran denda.'], 500);
        }
    }

    public function store(StoreLibraryFinePaymentRequest $request)
    {
        try {
            $loan = LibraryLoan::findOrFail($request->loan_id);
            if ($resp = $this->denyUnlessCanAccessInstitution($request, (int) $loan->institution_id)) {
                return $resp;
            }
            $data = $request->validated();
            $payment = $this->loanService->payFine(
                $loan,
                (float) $data['amount'],
                $data['paid_at'],
                $data['payment_method'] ?? null,
                $data['notes'] ?? null,
                $request->user()->id
            );
            return response()->json([
                'message' => 'Pembayaran denda berhasil dicatat.',
                'data' => new LibraryFinePaymentResource($payment),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Library fine payment store', ['error' => $e->getMessage()]);
            return response()->json(['message' => $e->getMessage() ?: 'Gagal mencatat pembayaran.'], 500);
        }
    }
}
