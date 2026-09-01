<?php

namespace App\Http\Controllers\API;

use App\Exports\InventoryItemsExport;
use App\Http\Controllers\API\Concerns\ResolvesActiveInstitution;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInventoryRequest;
use App\Http\Requests\UpdateInventoryRequest;
use App\Http\Resources\InventoryItemResource;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Services\InventoryItemImportService;
use App\Services\InventoryService;
use App\Services\InventoryAssetService;
use App\Support\InstitutionContext;
use App\Support\InventoryAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Excel as ExcelManager;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InventoryController extends Controller
{
    use ResolvesActiveInstitution;

    public function __construct(
        private InventoryService $service,
        private InventoryAssetService $assetService,
        private InventoryItemImportService $importService
    ) {}

    /**
     * Display a listing of inventory items.
     */
    public function index(Request $request)
    {
        try {
            $filters = $request->only([
                'category_id', 'status', 'condition', 'room_id', 'building_id', 'search', 'disposed', 'tracking_type'
            ]);
            $filters['with_trashed'] = filter_var($request->get('with_trashed'), FILTER_VALIDATE_BOOLEAN);
            $filters['only_trashed'] = filter_var($request->get('only_trashed'), FILTER_VALIDATE_BOOLEAN);
            $filters['disposed'] = filter_var($request->get('disposed'), FILTER_VALIDATE_BOOLEAN);

            $user = $request->user();
            $institutionId = $this->resolveInstitutionId($request);
            if ($institutionId === null) {
                $perPage = min($request->get('per_page', 15), 100);
                return InventoryItemResource::collection(
                    new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage)
                );
            }

            // Kepala lab tanpa modul inventory: scope ke ruangan lab yang ditanggungjawabi
            InventoryAccess::applyRoomFilter($filters, $user, $institutionId);

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
            $institutionId = $this->resolveInstitutionId($request);

            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
            }

            $data = $request->validated();
            $data['institution_id'] = $institutionId;

            $roomId = isset($data['room_id']) && $data['room_id'] !== '' ? (int) $data['room_id'] : null;
            if (! InventoryAccess::canAssignRoom($request->user(), $institutionId, $roomId)) {
                return response()->json([
                    'message' => 'Ruangan wajib dipilih dan harus termasuk ruangan yang Anda tanggung jawabi',
                ], 403);
            }

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
            if (!$this->userCanAccessInventoryItem($request, $item)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            $item->load([
                'category', 'room', 'building', 'institution', 'responsibleEmployee',
                'transactions.fromLocation', 'transactions.toLocation',
                'maintenances', 'loans', 'assets.room', 'creator', 'updater',
            ]);
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
            if (!$this->userCanAccessInventoryItem($request, $item)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            $data = $request->validated();

            if (array_key_exists('room_id', $data)) {
                $roomId = $data['room_id'] !== null && $data['room_id'] !== '' ? (int) $data['room_id'] : null;
                if (! InventoryAccess::canAssignRoom($request->user(), (int) $item->institution_id, $roomId)) {
                    return response()->json([
                        'message' => 'Ruangan tidak valid atau di luar tanggung jawab Anda',
                    ], 403);
                }
            }

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
            if ($denied = $this->denyUnlessInventoryManage($request)) {
                return $denied;
            }
            if (!$this->userCanAccessInventoryItem($request, $item)) {
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
     * Record formal disposal (penghapusan) with audit trail.
     */
    public function dispose(Request $request, InventoryItem $item)
    {
        $validated = $request->validate([
            'disposal_date' => 'required|date',
            'disposal_reason' => 'required|string|max:2000',
            'disposal_document_number' => 'nullable|string|max:100',
            'status' => 'required|in:Dijual,Hilang,Rusak',
            'condition' => 'nullable|in:Baik,Rusak Ringan,Rusak Berat,Habis Pakai',
            'quantity' => 'nullable|integer|min:1',
        ]);

        try {
            if ($denied = $this->denyUnlessInventoryManage($request)) {
                return $denied;
            }
            if (!$this->userCanAccessInventoryItem($request, $item)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $item = $this->service->dispose($item, $validated, $request->user()->id);

            return response()->json([
                'message' => 'Penghapusan barang berhasil dicatat',
                'data' => new InventoryItemResource($item),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to dispose inventory item', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => $e->getMessage() ?: 'Terjadi kesalahan saat mencatat penghapusan',
            ], 500);
        }
    }

    /**
     * Pecah master stok menjadi aset individual (manual, tidak otomatis).
     */
    public function splitAssets(Request $request, InventoryItem $item)
    {
        $validated = $request->validate([
            'count' => 'required|integer|min:1|max:500',
        ]);

        try {
            if (! $this->userCanAccessInventoryItem($request, $item)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $assets = $this->assetService->splitFromStockItem($item, (int) $validated['count'], $request->user()->id);

            return response()->json([
                'message' => count($assets) . ' aset individual berhasil dibuat dari master barang',
                'data' => [
                    'item' => new InventoryItemResource($item->fresh(['category', 'assets'])),
                    'assets' => \App\Http\Resources\InventoryAssetResource::collection(collect($assets)),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to split inventory assets', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => $e->getMessage() ?: 'Terjadi kesalahan saat memecah aset',
            ], 500);
        }
    }

    /**
     * Restore a soft-deleted inventory item.
     */
    public function restore(Request $request, $id)
    {
        try {
            if ($denied = $this->denyUnlessInventoryManage($request)) {
                return $denied;
            }
            $item = InventoryItem::withTrashed()->findOrFail($id);

            if (!$this->userCanAccessInventoryItem($request, $item)) {
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

    /**
     * Meta untuk template import Excel (kategori contoh).
     */
    public function importTemplate(Request $request)
    {
        try {
            if ($denied = $this->denyUnlessInventoryManage($request)) {
                return $denied;
            }
            $institutionId = $this->resolveInstitutionId($request);
            if (! $institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 403);
            }

            $sampleCategory = InventoryCategory::where('institution_id', $institutionId)
                ->orderBy('code')
                ->value('code');

            return response()->json([
                'data' => [
                    'headers' => InventoryItemImportService::HEADERS,
                    'sample_kode_kategori' => $sampleCategory ?: 'ELK',
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Inventory import template meta failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal mengambil data template'], 500);
        }
    }

    /**
     * Import master barang dari baris Excel (diparse di frontend).
     */
    public function import(Request $request)
    {
        $validated = $request->validate([
            'rows' => 'required|array|min:1|max:2000',
            'rows.*' => 'array',
        ]);

        try {
            if ($denied = $this->denyUnlessInventoryManage($request)) {
                return $denied;
            }
            $institutionId = $this->resolveInstitutionId($request);
            if (! $institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 403);
            }

            $results = $this->importService->importFromRows(
                $validated['rows'],
                $institutionId,
                $request->user()->id
            );

            return response()->json([
                'message' => 'Import selesai',
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            Log::error('Inventory import failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => $e->getMessage() ?: 'Gagal mengimpor data inventaris',
            ], 500);
        }
    }

    /**
     * Export master barang ke Excel (.xlsx) sesuai filter.
     */
    public function exportExcel(Request $request): BinaryFileResponse
    {
        if ($denied = $this->denyUnlessInventoryManage($request)) {
            abort(403, $denied->getData(true)['message'] ?? 'Forbidden');
        }

        $institutionId = $this->resolveInstitutionId($request);
        if (! $institutionId) {
            abort(403, 'Institusi tidak ditemukan');
        }

        $filters = $request->only([
            'category_id', 'status', 'condition', 'room_id', 'building_id', 'search', 'tracking_type',
        ]);
        $filters['disposed'] = filter_var($request->get('disposed'), FILTER_VALIDATE_BOOLEAN);
        InventoryAccess::applyRoomFilter($filters, $request->user(), $institutionId);

        $items = $this->service->listForExport($filters, $institutionId);
        $rows = $this->importService->rowsForExport($items);
        $filename = 'Master_Barang_Inventaris_' . now()->format('Ymd_His') . '.xlsx';

        return app(ExcelManager::class)->download(
            new InventoryItemsExport($rows),
            $filename,
            ExcelManager::XLSX
        );
    }
}
