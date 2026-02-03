<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLibraryLoanRequest;
use App\Http\Resources\LibraryLoanResource;
use App\Models\LibraryLoan;
use App\Services\LibraryLoanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LibraryLoanController extends Controller
{
    public function __construct(
        private LibraryLoanService $service
    ) {}

    public function index(Request $request)
    {
        try {
            $institutionId = null;
            if (!$request->user()->isAdminOrSuperAdmin()) {
                $institutionId = $request->user()->institution_id;
            } elseif ($request->has('institution_id')) {
                $institutionId = $request->institution_id;
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
            $institutionId = $request->user()->isAdminOrSuperAdmin() ? $request->input('institution_id') : $request->user()->institution_id;
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

    public function show(LibraryLoan $loan)
    {
        try {
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

    public function calculateFine(LibraryLoan $loan)
    {
        try {
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
