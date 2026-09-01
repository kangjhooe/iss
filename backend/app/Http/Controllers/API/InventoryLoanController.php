<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesActiveInstitution;
use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryLoanResource;
use App\Models\InventoryAsset;
use App\Models\InventoryLoan;
use App\Services\InventoryAssetService;
use App\Support\InstitutionContext;
use App\Support\InventoryAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class InventoryLoanController extends Controller
{
    use ResolvesActiveInstitution;

    public function __construct(
        private InventoryAssetService $assetService
    ) {}

    /**
     * Display a listing of loans.
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            if ($user->isSuperAdmin()) {
                $institutionId = $request->get('institution_id');
            } elseif ($user->isAdmin() || $user->isInstitutionAdmin()) {
                $institutionId = InstitutionContext::resolveForUser(
                    $user,
                    $request,
                    $request->get('institution_id')
                ) ?? $user->institution_id;
            } else {
                $institutionId = InstitutionContext::resolveForUser($user, $request, null);
            }

            $query = InventoryLoan::with(['item.category', 'item.room', 'asset', 'borrowerEmployee', 'borrowerStudent', 'creator']);

            if ($institutionId) {
                $query->where('institution_id', $institutionId);
                InventoryAccess::scopeLoans($query, $user, (int) $institutionId);
            } else {
                $query->whereRaw('1 = 0');
            }

            if ($request->has('item_id')) {
                $query->where('item_id', $request->item_id);
            }

            if ($request->filled('room_id')) {
                $roomId = (int) $request->room_id;
                $query->where(function ($q) use ($roomId) {
                    $q->whereHas('item', fn ($iq) => $iq->where('room_id', $roomId))
                        ->orWhereHas('asset', fn ($aq) => $aq->where('room_id', $roomId));
                });
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
            'item_id' => 'required_without:asset_id|nullable|exists:inventory_item,id',
            'asset_id' => 'nullable|exists:inventory_asset,id',
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
            $user = $request->user();
            $asset = null;

            if ($request->filled('asset_id')) {
                $asset = InventoryAsset::findOrFail($request->asset_id);
                $item = $asset->item;
                if (! $item) {
                    return response()->json(['message' => 'Master barang aset tidak ditemukan'], 422);
                }
                if ((int) $request->quantity !== 1) {
                    return response()->json(['message' => 'Peminjaman aset individual hanya 1 unit'], 422);
                }
                if (! $asset->isAvailableForLoan()) {
                    return response()->json(['message' => 'Aset tidak tersedia untuk dipinjam'], 422);
                }
            } else {
                $item = \App\Models\InventoryItem::findOrFail($request->item_id);
                if ($item->isIndividualTracked()) {
                    return response()->json([
                        'message' => 'Master aset individual memerlukan pemilihan unit aset (asset_id).',
                    ], 422);
                }
            }

            if (! $this->canAccessInstitutionRecord($request, (int) $item->institution_id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if (! $this->userCanAccessInventoryItem($request, $item)) {
                return response()->json(['message' => 'Anda tidak berwenang mengelola inventaris barang ini'], 403);
            }

            if (! $asset && (! $item->isAvailable() || $item->getAvailableQuantity() < $request->quantity)) {
                return response()->json([
                    'message' => 'Barang tidak tersedia atau jumlah tidak mencukupi',
                ], 422);
            }

            $institutionId = $user->isAdminOrSuperAdmin()
                ? ($request->institution_id ?? $item->institution_id)
                : (InstitutionContext::resolveForUser($user, $request, null) ?? (int) $item->institution_id);

            $data = $validator->validated();
            $data['item_id'] = $item->id;
            $data['asset_id'] = $asset?->id;
            $data['institution_id'] = $institutionId;
            $data['status'] = 'Dipinjam';
            $data['created_by'] = $user->id;

            $loan = InventoryLoan::create($data);

            if ($asset) {
                $asset->update(['status' => 'Dipinjam', 'updated_by' => $user->id]);
            } elseif ($item->getAvailableQuantity() === 0) {
                $item->update(['status' => 'Dipinjam']);
            }

            $loan->load(['item.category', 'asset', 'borrowerEmployee', 'borrowerStudent', 'creator']);
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
     * Update an active loan (dates, borrower info, etc.).
     */
    public function update(Request $request, InventoryLoan $loan)
    {
        $user = $request->user();
        if (!$this->canAccessInstitutionRecord($request, (int) $loan->institution_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $loan->load(['item', 'item.room', 'asset']);
        if ($loan->item && ! $this->userCanAccessInventoryItem($request, $loan->item)) {
            return response()->json(['message' => 'Anda tidak berwenang mengubah peminjaman barang ini'], 403);
        }

        if (!in_array($loan->status, ['Dipinjam', 'Terlambat'], true)) {
            return response()->json(['message' => 'Hanya peminjaman aktif yang dapat diubah'], 422);
        }

        $validator = Validator::make($request->all(), [
            'borrower_type' => 'sometimes|in:Employee,Student,External',
            'borrower_name' => 'sometimes|string|max:255',
            'borrower_phone' => 'nullable|string|max:50',
            'loan_date' => 'sometimes|date',
            'expected_return_date' => 'sometimes|date',
            'quantity' => 'sometimes|integer|min:1',
            'purpose' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $loanDate = $data['loan_date'] ?? $loan->loan_date?->format('Y-m-d');
        $returnDate = $data['expected_return_date'] ?? $loan->expected_return_date?->format('Y-m-d');
        if ($loanDate && $returnDate && $returnDate <= $loanDate) {
            return response()->json([
                'errors' => ['expected_return_date' => ['Tanggal jatuh tempo harus setelah tanggal pinjam.']],
            ], 422);
        }

        if (isset($data['quantity']) && $loan->item) {
            $available = $loan->item->getAvailableQuantity() + $loan->quantity;
            if ($data['quantity'] > $available) {
                return response()->json(['message' => 'Jumlah melebihi stok tersedia'], 422);
            }
        }

        try {
            $data['updated_by'] = $user->id;
            $loan->update($data);

            if ($loan->expected_return_date && $loan->expected_return_date->isFuture() && $loan->status === 'Terlambat') {
                $loan->update(['status' => 'Dipinjam']);
            }

            $loan->load(['item.category', 'borrowerEmployee', 'borrowerStudent', 'updater']);
            return response()->json([
                'message' => 'Peminjaman berhasil diperbarui',
                'data' => new InventoryLoanResource($loan),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update loan', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    /**
     * Return loaned item.
     */
    public function return(Request $request, InventoryLoan $loan)
    {
        $user = $request->user();
        if (!$this->canAccessInstitutionRecord($request, (int) $loan->institution_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $loan->load(['item', 'item.room', 'asset']);
        if ($loan->item && ! $this->userCanAccessInventoryItem($request, $loan->item)) {
            return response()->json(['message' => 'Anda tidak berwenang mengembalikan barang ini'], 403);
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
            $asset = $loan->asset;

            if ($asset) {
                $assetUpdates = ['updated_by' => $user->id];
                if (! empty($data['return_condition'])) {
                    $assetUpdates['condition'] = $data['return_condition'];
                }
                if ($markAs === 'Hilang' || ($data['return_item_status'] ?? null) === 'Hilang') {
                    $assetUpdates['status'] = 'Hilang';
                } elseif (($data['return_item_status'] ?? null) === 'Rusak') {
                    $assetUpdates['status'] = 'Rusak';
                } else {
                    $assetUpdates['status'] = 'Tersedia';
                }
                $asset->update($assetUpdates);
                $this->assetService->syncItemQuantityFromAssets($item);
            }

            if ($item && ! $item->isIndividualTracked()) {
                $itemUpdates = ['updated_by' => $user->id];
                if (! empty($data['return_condition'])) {
                    $itemUpdates['condition'] = $data['return_condition'];
                }

                $activeLoans = $item->loans()
                    ->whereIn('status', ['Dipinjam', 'Terlambat'])
                    ->where('id', '!=', $loan->id)
                    ->exists();

                if ($activeLoans) {
                    $itemUpdates['status'] = 'Dipinjam';
                } elseif ($markAs === 'Hilang' || ($data['return_item_status'] ?? null) === 'Hilang') {
                    $itemUpdates['status'] = 'Hilang';
                } elseif (($data['return_item_status'] ?? null) === 'Rusak') {
                    $itemUpdates['status'] = 'Rusak';
                } else {
                    $itemUpdates['status'] = 'Tersedia';
                }
                $item->update($itemUpdates);
            }

            $loan->load(['item.category', 'asset', 'borrowerEmployee', 'borrowerStudent', 'updater']);
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
            if (!$this->canAccessInstitutionRecord($request, (int) $loan->institution_id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            $loan->load(['item.category', 'asset', 'borrowerEmployee', 'borrowerStudent', 'creator', 'updater']);
            return new InventoryLoanResource($loan);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }
}
