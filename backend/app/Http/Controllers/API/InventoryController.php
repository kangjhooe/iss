<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInventoryRequest;
use App\Http\Requests\UpdateInventoryRequest;
use App\Http\Resources\InventoryItemResource;
use App\Models\InventoryItem;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InventoryController extends Controller
{
    public function __construct(
        private InventoryService $service
    ) {}

    /**
     * Display a listing of inventory items.
     */
    public function index(Request $request)
    {
        try {
            $filters = $request->only([
                'category_id', 'status', 'condition', 'room_id', 'building_id', 'search'
            ]);
            $filters['with_trashed'] = filter_var($request->get('with_trashed'), FILTER_VALIDATE_BOOLEAN);
            $filters['only_trashed'] = filter_var($request->get('only_trashed'), FILTER_VALIDATE_BOOLEAN);

            $institutionId = null;
            if ($request->user()->isSuperAdmin()) {
                $institutionId = $request->get('institution_id');
            } elseif ($request->user()->isAdmin()) {
                $institutionId = $request->get('institution_id') ?? $request->user()->institution_id;
            } else {
                $institutionId = $request->user()->institution_id;
            }
            if ($institutionId === null) {
                $perPage = min($request->get('per_page', 15), 100);
                return InventoryItemResource::collection(
                    new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage)
                );
            }

            $perPage = min($request->get('per_page', 15), 100);
            $items = $this->service->list($filters, $institutionId, $perPage);

            return InventoryItemResource::collection($items);
        } catch (\Exception $e) {
            Log::error('Failed to list inventory items', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data inventaris',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Store a newly created inventory item.
     */
    public function store(StoreInventoryRequest $request)
    {
        try {
            $institutionId = $request->user()->isAdminOrSuperAdmin() 
                ? $request->institution_id 
                : $request->user()->institution_id;

            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
            }

            $data = $request->validated();
            $data['institution_id'] = $institutionId;
            $file = $request->hasFile('image') ? $request->file('image') : null;

            $item = $this->service->create($data, $request->user()->id, $file);

            return response()->json([
                'message' => 'Barang inventaris berhasil ditambahkan',
                'data' => new InventoryItemResource($item),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create inventory item', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => $e->getMessage() ?: 'Terjadi kesalahan saat menambahkan barang inventaris',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Display the specified inventory item.
     */
    public function show(Request $request, InventoryItem $item)
    {
        try {
            if (!$request->user()->isAdminOrSuperAdmin() && (int) $item->institution_id !== (int) $request->user()->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            $item->load(['category', 'room', 'building', 'institution', 'transactions', 'maintenances', 'loans', 'creator', 'updater']);
            return new InventoryItemResource($item);
        } catch (\Exception $e) {
            Log::error('Failed to show inventory item', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data barang',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Update the specified inventory item.
     */
    public function update(UpdateInventoryRequest $request, InventoryItem $item)
    {
        try {
            if (!$request->user()->isAdminOrSuperAdmin() && (int) $item->institution_id !== (int) $request->user()->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            $data = $request->validated();
            $file = $request->hasFile('image') ? $request->file('image') : null;

            $item = $this->service->update($item, $data, $request->user()->id, $file);

            return response()->json([
                'message' => 'Barang inventaris berhasil diperbarui',
                'data' => new InventoryItemResource($item),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update inventory item', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => $e->getMessage() ?: 'Terjadi kesalahan saat memperbarui barang inventaris',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Remove the specified inventory item.
     */
    public function destroy(Request $request, InventoryItem $item)
    {
        try {
            if (!$request->user()->isAdminOrSuperAdmin() && (int) $item->institution_id !== (int) $request->user()->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            $this->service->delete($item, $request->user()->id);

            return response()->json([
                'message' => 'Barang inventaris berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete inventory item', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat menghapus barang inventaris',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Restore a soft-deleted inventory item.
     */
    public function restore(Request $request, $id)
    {
        try {
            $item = InventoryItem::withTrashed()->findOrFail($id);

            if (!$request->user()->isAdminOrSuperAdmin() && $item->institution_id !== $request->user()->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if ($item->trashed()) {
                $item->restore();
            }

            return response()->json([
                'message' => 'Barang inventaris berhasil dipulihkan',
                'data' => new InventoryItemResource($item->fresh(['category', 'room', 'building', 'institution'])),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Barang inventaris tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to restore inventory item', [
                'item_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat memulihkan barang inventaris',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
