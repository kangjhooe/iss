<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryLoanResource;
use App\Models\InventoryLoan;
use App\Models\Room;
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
            } elseif ($request->user()->isAdmin() || $request->user()->isInstitutionAdmin()) {
                $institutionId = $request->get('institution_id') ?? $request->user()->institution_id;
            } else {
                $institutionId = $request->user()->institution_id;
            }

            $query = InventoryLoan::with(['item.category', 'item.room', 'borrowerEmployee', 'borrowerStudent', 'creator']);

            if ($institutionId) {
                $query->where('institution_id', $institutionId);
            } else {
                $query->whereRaw('1 = 0');
            }

            if ($request->has('item_id')) {
                $query->where('item_id', $request->item_id);
            }

            if ($request->filled('room_id')) {
                $query->whereHas('item', fn ($q) => $q->where('room_id', $request->room_id));
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
            $user = $request->user();

            if (!$user->isAdminOrSuperAdmin() && !$user->isInstitutionAdmin() && (int) $item->institution_id !== (int) $user->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Lab-scoped items: only admin or PJ of that lab
            if ($item->room_id) {
                $room = Room::find($item->room_id);
                if ($room && $room->type === 'Laboratorium' && !$user->canManageLab($room)) {
                    return response()->json(['message' => 'Anda tidak berwenang meminjamkan barang lab ini'], 403);
                }
            }

            if (!$item->isAvailable() || $item->getAvailableQuantity() < $request->quantity) {
                return response()->json([
                    'message' => 'Barang tidak tersedia atau jumlah tidak mencukupi',
                ], 422);
            }

            $institutionId = ($user->isAdminOrSuperAdmin() || $user->isInstitutionAdmin())
                ? ($request->institution_id ?? $item->institution_id)
                : $user->institution_id;

            $data = $validator->validated();
            $data['institution_id'] = $institutionId;
            $data['status'] = 'Dipinjam';
            $data['created_by'] = $user->id;

            $loan = InventoryLoan::create($data);

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
        $user = $request->user();
        if (!$user->isAdminOrSuperAdmin() && !$user->isInstitutionAdmin() && (int) $loan->institution_id !== (int) $user->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $loan->load('item.room');
        if ($loan->item?->room_id) {
            $room = $loan->item->room ?: Room::find($loan->item->room_id);
            if ($room && $room->type === 'Laboratorium' && !$user->canManageLab($room)) {
                return response()->json(['message' => 'Anda tidak berwenang mengembalikan barang lab ini'], 403);
            }
        }

        $validator = Validator::make($request->all(), [
            'actual_return_date' => 'required|date',
            'notes' => 'nullable|string',
            'return_condition' => 'nullable|in:Baik,Rusak Ringan,Rusak Berat,Habis Pakai',
            'return_item_status' => 'nullable|in:Tersedia,Rusak,Hilang',
            'mark_as' => 'nullable|in:Dikembalikan,Hilang',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            if (in_array($loan->status, ['Dikembalikan', 'Hilang'], true)) {
                return response()->json(['message' => 'Peminjaman sudah ditutup'], 422);
            }

            $data = $validator->validated();
            $markAs = $data['mark_as'] ?? 'Dikembalikan';
            unset($data['mark_as']);

            $data['status'] = $markAs === 'Hilang' ? 'Hilang' : 'Dikembalikan';
            $data['updated_by'] = $user->id;

            $loan->update($data);

            $item = $loan->item;
            if ($item) {
                $itemUpdates = [];
                if (!empty($data['return_condition'])) {
                    $itemUpdates['condition'] = $data['return_condition'];
                }
                if ($markAs === 'Hilang' || ($data['return_item_status'] ?? null) === 'Hilang') {
                    $itemUpdates['status'] = 'Hilang';
                } elseif (($data['return_item_status'] ?? null) === 'Rusak') {
                    $itemUpdates['status'] = 'Rusak';
                } else {
                    $itemUpdates['status'] = 'Tersedia';
                }
                $item->update($itemUpdates);
            }

            $loan->load(['item.category', 'borrowerEmployee', 'borrowerStudent', 'updater']);
            return response()->json([
                'message' => $markAs === 'Hilang' ? 'Barang ditandai hilang' : 'Pengembalian berhasil dicatat',
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
            if (!$request->user()->isAdminOrSuperAdmin() && !$request->user()->isInstitutionAdmin() && (int) $loan->institution_id !== (int) $request->user()->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            $loan->load(['item.category', 'borrowerEmployee', 'borrowerStudent', 'creator', 'updater']);
            return new InventoryLoanResource($loan);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }
}
