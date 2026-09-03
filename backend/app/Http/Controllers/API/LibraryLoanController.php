<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Requests\StoreLibraryLoanRequest;
use App\Http\Resources\LibraryLoanResource;
use App\Models\LibraryLoan;
use App\Services\LibraryLoanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LibraryLoanController extends Controller
{
    use ResolvesInstitution;

    public function __construct(
        private LibraryLoanService $service
    ) {}

    public function index(Request $request)
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$request->user()->isAdminOrSuperAdmin() && $institutionId === null) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }
            $filters = $request->only(['status', 'borrower_type', 'copy_id', 'search']);
            $perPage = min($request->get('per_page', 15), 100);
            $loans = $this->service->listLoans($filters, $institutionId, $perPage);
            return LibraryLoanResource::collection($loans);
        } catch (\Exception $e) {
            Log::error('Library loans index', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil data peminjaman.'], 500);
        }
    }

    public function store(StoreLibraryLoanRequest $request)
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 400);
            }
            $loan = $this->service->createLoan($request->validated(), $institutionId, $request->user()->id);
            $loan->load(['copy.book', 'finePayments', 'creator']);
            return response()->json([
                'message' => 'Peminjaman berhasil dicatat.',
                'data' => new LibraryLoanResource($loan),
            ], 201);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Library loan store', ['error' => $e->getMessage()]);
            return response()->json(['message' => $e->getMessage() ?: 'Gagal mencatat peminjaman.'], 500);
        }
    }

    public function show(Request $request, LibraryLoan $loan)
    {
        try {
            if ($resp = $this->denyUnlessCanAccessInstitution($request, (int) $loan->institution_id)) {
                return $resp;
            }
            $loan->load(['copy.book', 'finePayments', 'creator']);
            return response()->json(['data' => new LibraryLoanResource($loan)]);
        } catch (\Exception $e) {
            Log::error('Library loan show', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil data peminjaman.'], 500);
        }
    }

    public function returnLoan(Request $request, LibraryLoan $loan)
    {
        try {
            if ($resp = $this->denyUnlessCanAccessInstitution($request, (int) $loan->institution_id)) {
                return $resp;
            }
            $fineAmount = $request->has('fine_amount') ? (float) $request->fine_amount : null;
            $notes = $request->input('notes');
            $updated = $this->service->returnLoan($loan, $fineAmount, $notes);
            $updated->load(['copy.book', 'finePayments', 'creator']);
            return response()->json([
                'message' => 'Buku berhasil dikembalikan.',
                'data' => new LibraryLoanResource($updated),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Library loan return', ['error' => $e->getMessage()]);
            return response()->json(['message' => $e->getMessage() ?: 'Gagal mengembalikan.'], 500);
        }
    }

    public function calculateFine(Request $request, LibraryLoan $loan)
    {
        try {
            if ($resp = $this->denyUnlessCanAccessInstitution($request, (int) $loan->institution_id)) {
                return $resp;
            }
            $amount = $this->service->calculateLateFine($loan);
            return response()->json(['fine_amount' => $amount]);
        } catch (\Exception $e) {
            Log::error('Library loan calculate fine', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal menghitung denda.'], 500);
        }
    }

    public function renewLoan(Request $request, LibraryLoan $loan)
    {
        try {
            if ($resp = $this->denyUnlessCanAccessInstitution($request, (int) $loan->institution_id)) {
                return $resp;
            }
            $extraDays = (int) $request->input('extra_days', 7);
            $extraDays = max(1, min($extraDays, 30));
            $updated = $this->service->renewLoan($loan, $extraDays);
            return response()->json([
                'message' => 'Peminjaman berhasil diperpanjang.',
                'data' => new LibraryLoanResource($updated),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Library loan renew', ['error' => $e->getMessage()]);
            return response()->json(['message' => $e->getMessage() ?: 'Gagal memperpanjang.'], 500);
        }
    }
}
