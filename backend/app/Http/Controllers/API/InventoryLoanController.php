<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryLoanResource;
use App\Models\InventoryLoan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class InventoryLoanController extends Controller
{
    /**
     * Display a listing of loans.
     */
    public function index(Request $request)
    {
        try {
            $institutionId = null;
            if ($request->user()->isSuperAdmin()) {
                $institutionId = $request->get('institution_id');
            } elseif ($request->user()->isAdmin()) {
                $institutionId = $request->get('institution_id') ?? $request->user()->institution_id;
            } else {
                $institutionId = $request->user()->institution_id;
            }

            $query = InventoryLoan::with(['item.category', 'borrowerEmployee', 'borrowerStudent', 'creator']);

            if ($institutionId) {
                $query->where('institution_id', $institutionId);
            } else {
                $query->whereRaw('1 = 0');
            }

            if ($request->has('item_id')) {
                $query->where('item_id', $request->item_id);
            }

            if ($request->has('borrower_type')) {
                $query->where('borrower_type', $request->borrower_type);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $perPage = min($request->get('per_page', 15), 100);
            $loans = $query->orderBy('loan_date', 'desc')->paginate($perPage);

            return InventoryLoanResource::collection($loans);
        } catch (\Exception $e) {
            Log::error('Failed to list loans', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    /**
     * Store a newly created loan.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:inventory_item,id',
            'borrower_type' => 'required|in:Employee,Student,External',
            'borrower_id' => 'nullable|integer',
            'borrower_name' => 'required|string|max:255',
            'borrower_phone' => 'nullable|string|max:50',
            'loan_date' => 'required|date',
            'expected_return_date' => 'required|date|after:loan_date',
            'quantity' => 'required|integer|min:1',
            'purpose' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $item = \App\Models\InventoryItem::findOrFail($request->item_id);

            if (!$request->user()->isAdminOrSuperAdmin() && (int) $item->institution_id !== (int) $request->user()->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Check availability
            if (!$item->isAvailable() || $item->getAvailableQuantity() < $request->quantity) {
                return response()->json([
                    'message' => 'Barang tidak tersedia atau jumlah tidak mencukupi',
                ], 422);
            }

            $institutionId = $request->user()->isAdminOrSuperAdmin() 
                ? ($request->institution_id ?? $item->institution_id)
                : $request->user()->institution_id;

            $data = $validator->validated();
            $data['institution_id'] = $institutionId;
            $data['status'] = 'Dipinjam';
            $data['created_by'] = $request->user()->id;

            $loan = InventoryLoan::create($data);

            // Update item status if all quantity is loaned
            if ($item->getAvailableQuantity() === 0) {
                $item->update(['status' => 'Dipinjam']);
            }

            $loan->load(['item.category', 'borrowerEmployee', 'borrowerStudent', 'creator']);
            return response()->json([
                'message' => 'Peminjaman berhasil dicatat',
                'data' => new InventoryLoanResource($loan),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create loan', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    /**
     * Return loaned item.
     */
    public function return(Request $request, InventoryLoan $loan)
    {
        if (!$request->user()->isAdminOrSuperAdmin() && (int) $loan->institution_id !== (int) $request->user()->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $validator = Validator::make($request->all(), [
            'actual_return_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            if ($loan->status === 'Dikembalikan') {
                return response()->json(['message' => 'Barang sudah dikembalikan'], 422);
            }

            $data = $validator->validated();
            $data['status'] = 'Dikembalikan';
            $data['updated_by'] = $request->user()->id;

            $loan->update($data);

            // Update item status
            $item = $loan->item;
            $item->update(['status' => 'Tersedia']);

            $loan->load(['item.category', 'borrowerEmployee', 'borrowerStudent', 'updater']);
            return response()->json([
                'message' => 'Pengembalian berhasil dicatat',
                'data' => new InventoryLoanResource($loan),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to return loan', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    /**
     * Display the specified loan.
     */
    public function show(Request $request, InventoryLoan $loan)
    {
        try {
            if (!$request->user()->isAdminOrSuperAdmin() && (int) $loan->institution_id !== (int) $request->user()->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            $loan->load(['item.category', 'borrowerEmployee', 'borrowerStudent', 'creator', 'updater']);
            return new InventoryLoanResource($loan);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }
}
