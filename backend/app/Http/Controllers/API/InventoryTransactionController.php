<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryTransactionResource;
use App\Models\InventoryTransaction;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class InventoryTransactionController extends Controller
{
    public function __construct(
        private InventoryService $service
    ) {}

    /**
     * Display a listing of transactions.
     */
    public function index(Request $request)
    {
        try {
            $institutionId = null;
            if (!$request->user()->isAdminOrSuperAdmin()) {
                $institutionId = $request->user()->institution_id;
            } elseif ($request->has('institution_id')) {
                $institutionId = $request->institution_id;
            }

            $query = InventoryTransaction::with(['item.category', 'creator', 'fromLocation', 'toLocation']);

            if ($institutionId) {
                $query->where('institution_id', $institutionId);
            }

            if ($request->has('item_id')) {
                $query->where('item_id', $request->item_id);
            }

            if ($request->has('transaction_type')) {
                $query->where('transaction_type', $request->transaction_type);
            }

            if ($request->has('date_from')) {
                $query->where('transaction_date', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->where('transaction_date', '<=', $request->date_to);
            }

            $perPage = min($request->get('per_page', 15), 100);
            $transactions = $query->orderBy('transaction_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return InventoryTransactionResource::collection($transactions);
        } catch (\Exception $e) {
            Log::error('Failed to list transactions', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    /**
     * Store a newly created transaction.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:inventory_item,id',
            'transaction_type' => 'required|in:Masuk,Keluar,Mutasi,Penyesuaian',
            'transaction_date' => 'required|date',
            'quantity' => 'required|integer|min:1',
            'reference_number' => 'nullable|string|max:100',
            'from_location_id' => 'nullable|exists:room,id',
            'to_location_id' => 'nullable|exists:room,id',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $item = \App\Models\InventoryItem::findOrFail($request->item_id);
            $institutionId = $request->user()->isAdminOrSuperAdmin() 
                ? ($request->institution_id ?? $item->institution_id)
                : $request->user()->institution_id;

            $data = $validator->validated();
            $data['institution_id'] = $institutionId;

            $transaction = $this->service->recordTransaction($data, $request->user()->id);

            return response()->json([
                'message' => 'Transaksi berhasil dicatat',
                'data' => new InventoryTransactionResource($transaction),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create transaction', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => $e->getMessage() ?: 'Terjadi kesalahan',
            ], 500);
        }
    }

    /**
     * Display the specified transaction.
     */
    public function show(InventoryTransaction $transaction)
    {
        try {
            $transaction->load(['item.category', 'creator', 'fromLocation', 'toLocation']);
            return new InventoryTransactionResource($transaction);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }
}
