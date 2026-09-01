<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesActiveInstitution;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInventoryAssetRequest;
use App\Http\Requests\UpdateInventoryAssetRequest;
use App\Http\Resources\InventoryAssetResource;
use App\Models\InventoryAsset;
use App\Models\InventoryItem;
use App\Services\InventoryAssetService;
use App\Support\InventoryAccess;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InventoryAssetController extends Controller
{
    use ResolvesActiveInstitution;

    public function __construct(
        private InventoryAssetService $service
    ) {}

    public function index(Request $request)
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (! $institutionId) {
                $perPage = min((int) $request->get('per_page', 15), 100);

                return InventoryAssetResource::collection(
                    new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage)
                );
            }

            $filters = $request->only([
                'item_id', 'status', 'condition', 'room_id', 'building_id', 'search', 'disposal_status',
            ]);
            $filters['include_disposed'] = filter_var($request->get('include_disposed'), FILTER_VALIDATE_BOOLEAN);

            InventoryAccess::applyRoomFilter($filters, $request->user(), $institutionId);

            $perPage = min((int) $request->get('per_page', 15), 100);
            $assets = $this->service->list($filters, $institutionId, $perPage);

            return InventoryAssetResource::collection($assets);
        } catch (\Exception $e) {
            Log::error('Failed to list inventory assets', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    public function store(StoreInventoryAssetRequest $request)
    {
        try {
            $item = InventoryItem::findOrFail($request->item_id);
            if (! $this->userCanAccessInventoryItem($request, $item)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if (! $item->isIndividualTracked()) {
                return response()->json([
                    'message' => 'Hanya master barang tipe aset individual yang dapat memiliki unit aset.',
                ], 422);
            }

            $count = (int) ($request->input('count') ?? 1);
            $defaults = $request->safe()->except(['item_id', 'count']);
            $assets = $this->service->createForItem($item, $count, $request->user()->id, $defaults);

            return response()->json([
                'message' => $count > 1 ? "{$count} aset berhasil ditambahkan" : 'Aset berhasil ditambahkan',
                'data' => InventoryAssetResource::collection(collect($assets)),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create inventory asset', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => $e->getMessage() ?: 'Terjadi kesalahan',
            ], 500);
        }
    }

    public function show(Request $request, InventoryAsset $asset)
    {
        try {
            if (! $this->canAccessInstitutionRecord($request, (int) $asset->institution_id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if (! $this->userCanAccessInventoryAsset($request, $asset)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $asset->load(['item.category', 'room', 'building', 'responsibleEmployee', 'creator', 'updater']);

            return new InventoryAssetResource($asset);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    public function update(UpdateInventoryAssetRequest $request, InventoryAsset $asset)
    {
        try {
            if (! $this->canAccessInstitutionRecord($request, (int) $asset->institution_id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if (! $this->userCanAccessInventoryAsset($request, $asset)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $asset = $this->service->update($asset, $request->validated(), $request->user()->id);

            return response()->json([
                'message' => 'Aset berhasil diperbarui',
                'data' => new InventoryAssetResource($asset),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update inventory asset', ['error' => $e->getMessage()]);

            return response()->json(['message' => $e->getMessage() ?: 'Terjadi kesalahan'], 500);
        }
    }

    public function qrImage(Request $request, InventoryAsset $asset)
    {
        try {
            if (! $this->canAccessInstitutionRecord($request, (int) $asset->institution_id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            return response()->json([
                'data' => [
                    'asset_id' => $asset->id,
                    'asset_number' => $asset->asset_number,
                    'qr_code' => $this->service->generateQrImageBase64($asset),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal membuat QR'], 500);
        }
    }

    public function resolveByQr(Request $request)
    {
        $request->validate(['token' => 'required|string']);

        try {
            $institutionId = $this->resolveInstitutionId($request);
            $asset = $this->service->resolveByQrToken($request->token, $institutionId);

            if (! $asset) {
                return response()->json(['message' => 'QR aset tidak valid atau tidak ditemukan'], 404);
            }

            if ($institutionId && (int) $asset->institution_id !== (int) $institutionId) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            return new InventoryAssetResource($asset);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    public function dispose(Request $request, InventoryAsset $asset)
    {
        $validated = $request->validate([
            'disposal_date' => 'required|date',
            'disposal_reason' => 'required|string|max:2000',
            'disposal_document_number' => 'nullable|string|max:100',
            'status' => 'required|in:Dijual,Hilang,Rusak',
            'condition' => 'nullable|in:Baik,Rusak Ringan,Rusak Berat,Habis Pakai',
        ]);

        try {
            if ($denied = $this->denyUnlessInventoryManage($request)) {
                return $denied;
            }
            if (! $this->canAccessInstitutionRecord($request, (int) $asset->institution_id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $asset = $this->service->dispose($asset, $validated, $request->user()->id);

            return response()->json([
                'message' => 'Penghapusan aset berhasil dicatat',
                'data' => new InventoryAssetResource($asset),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to dispose inventory asset', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => $e->getMessage() ?: 'Terjadi kesalahan saat penghapusan aset',
            ], 500);
        }
    }

    public function transfer(Request $request, InventoryAsset $asset)
    {
        $validated = $request->validate([
            'to_room_id' => 'required|exists:room,id',
            'movement_date' => 'nullable|date',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:2000',
        ]);

        try {
            if ($denied = $this->denyUnlessInventoryManage($request)) {
                return $denied;
            }
            if (! $this->canAccessInstitutionRecord($request, (int) $asset->institution_id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $asset = $this->service->transfer(
                $asset,
                (int) $validated['to_room_id'],
                $request->user()->id,
                $validated['movement_date'] ?? null,
                $validated['reference_number'] ?? null,
                $validated['notes'] ?? null
            );

            return response()->json([
                'message' => 'Mutasi aset berhasil dicatat',
                'data' => new InventoryAssetResource($asset),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage() ?: 'Terjadi kesalahan'], 500);
        }
    }

    public function qrBulk(Request $request)
    {
        $validated = $request->validate([
            'asset_ids' => 'nullable|array',
            'asset_ids.*' => 'integer|exists:inventory_asset,id',
            'item_id' => 'nullable|exists:inventory_item,id',
            'room_id' => 'nullable|exists:room,id',
        ]);

        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (! $institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 403);
            }

            $assets = $this->resolveAssetsForBulk($validated, $institutionId);
            if ($assets->isEmpty()) {
                return response()->json(['message' => 'Tidak ada aset yang sesuai'], 422);
            }

            $cards = $this->service->buildQrCardsForAssets($assets);

            return response()->json(['data' => $cards]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    public function printQrPdf(Request $request)
    {
        $validated = $request->validate([
            'asset_ids' => 'nullable|array',
            'asset_ids.*' => 'integer|exists:inventory_asset,id',
            'item_id' => 'nullable|exists:inventory_item,id',
            'room_id' => 'nullable|exists:room,id',
        ]);

        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (! $institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 403);
            }

            $assets = $this->resolveAssetsForBulk($validated, $institutionId);
            if ($assets->isEmpty()) {
                return response()->json(['message' => 'Tidak ada aset yang sesuai'], 422);
            }

            $institution = \App\Models\Institution::find($institutionId);
            $cards = $this->service->buildQrCardsForAssets($assets);
            $pages = array_chunk($cards, 8);

            $pdf = DomPDF::loadView('inventory.qr_labels', [
                'institutionName' => $institution?->name ?? 'Institusi',
                'title' => 'Label QR Aset Inventaris',
                'pages' => $pages,
            ])->setPaper('a4', 'portrait');

            return $pdf->stream('label-qr-aset-' . now()->format('Ymd') . '.pdf', ['Attachment' => false]);
        } catch (\Exception $e) {
            Log::error('Failed to print asset QR PDF', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal mencetak label QR'], 500);
        }
    }

    protected function resolveAssetsForBulk(array $validated, int $institutionId)
    {
        $query = InventoryAsset::with(['item', 'room'])
            ->where('institution_id', $institutionId)
            ->where('disposal_status', 'active')
            ->whereNull('disposed_at');

        if (! empty($validated['asset_ids'])) {
            $query->whereIn('id', $validated['asset_ids']);
        } else {
            if (! empty($validated['item_id'])) {
                $query->where('item_id', $validated['item_id']);
            }
            if (! empty($validated['room_id'])) {
                $query->where('room_id', $validated['room_id']);
            }
        }

        return $query->orderBy('asset_number')->limit(500)->get();
    }
}
