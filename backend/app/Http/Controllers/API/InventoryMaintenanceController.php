<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesActiveInstitution;
use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryMaintenanceResource;
use App\Models\InventoryMaintenance;
use App\Support\InventoryAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class InventoryMaintenanceController extends Controller
{
    use ResolvesActiveInstitution;

    /**
     * Display a listing of maintenances.
     */
    public function index(Request $request)
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);

            $query = InventoryMaintenance::with(['item.category', 'asset', 'creator', 'updater']);

            if ($institutionId) {
                $query->where('institution_id', $institutionId);
                InventoryAccess::scopeMaintenances($query, $request->user(), $institutionId);
            } else {
                $query->whereRaw('1 = 0');
            }

            if ($request->has('item_id')) {
                $query->where('item_id', $request->item_id);
            }

            if ($request->filled('asset_id')) {
                $query->where('asset_id', $request->asset_id);
            }

            if ($request->has('maintenance_type')) {
                $query->where('maintenance_type', $request->maintenance_type);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('room_id')) {
                $roomId = (int) $request->room_id;
                $query->where(function ($q) use ($roomId) {
                    $q->whereHas('item', fn ($iq) => $iq->where('room_id', $roomId))
                        ->orWhereHas('asset', fn ($aq) => $aq->where('room_id', $roomId));
                });
            }

            $perPage = min($request->get('per_page', 15), 100);
            $maintenances = $query->orderBy('scheduled_date', 'desc')->paginate($perPage);

            return InventoryMaintenanceResource::collection($maintenances);
        } catch (\Exception $e) {
            Log::error('Failed to list maintenances', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    /**
     * Store a newly created maintenance.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:inventory_item,id',
            'asset_id' => 'nullable|exists:inventory_asset,id',
            'maintenance_type' => 'required|in:Perawatan,Perbaikan,Kalibrasi,Inspeksi',
            'scheduled_date' => 'required|date',
            'completed_date' => 'nullable|date',
            'cost' => 'nullable|numeric|min:0',
            'vendor' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:Terjadwal,Dalam Proses,Selesai,Dibatalkan',
            'technician_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $item = \App\Models\InventoryItem::findOrFail($request->item_id);
            if (!$this->userCanAccessInventoryItem($request, $item)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if ($item->isIndividualTracked()) {
                if (! $request->filled('asset_id')) {
                    return response()->json([
                        'message' => 'Master aset individual memerlukan pemilihan unit aset (asset_id).',
                    ], 422);
                }
                $asset = \App\Models\InventoryAsset::findOrFail($request->asset_id);
                if ((int) $asset->item_id !== (int) $item->id) {
                    return response()->json(['message' => 'Aset tidak sesuai dengan master barang'], 422);
                }
            }

            $institutionId = $request->user()->isAdminOrSuperAdmin()
                ? ($request->institution_id ?? $item->institution_id)
                : ($this->resolveInstitutionId($request) ?? $item->institution_id);

            $data = $validator->validated();
            $data['institution_id'] = $institutionId;
            $data['status'] = $data['status'] ?? 'Terjadwal';
            $data['created_by'] = $request->user()->id;

            $maintenance = InventoryMaintenance::create($data);

            $maintenance->load(['item.category', 'asset', 'creator']);
            return response()->json([
                'message' => 'Pemeliharaan berhasil ditambahkan',
                'data' => new InventoryMaintenanceResource($maintenance),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create maintenance', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    /**
     * Update the specified maintenance.
     */
    public function update(Request $request, InventoryMaintenance $maintenance)
    {
        if (!$this->canAccessInstitutionRecord($request, (int) $maintenance->institution_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $validator = Validator::make($request->all(), [
            'maintenance_type' => 'sometimes|in:Perawatan,Perbaikan,Kalibrasi,Inspeksi',
            'scheduled_date' => 'sometimes|date',
            'completed_date' => 'nullable|date',
            'cost' => 'nullable|numeric|min:0',
            'vendor' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:Terjadwal,Dalam Proses,Selesai,Dibatalkan',
            'technician_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $validator->validated();
            $data['updated_by'] = $request->user()->id;

            $maintenance->update($data);

            $maintenance->load(['item.category', 'asset', 'updater']);
            return response()->json([
                'message' => 'Pemeliharaan berhasil diperbarui',
                'data' => new InventoryMaintenanceResource($maintenance),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update maintenance', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    /**
     * Display the specified maintenance.
     */
    public function show(Request $request, InventoryMaintenance $maintenance)
    {
        try {
            if (!$this->canAccessInstitutionRecord($request, (int) $maintenance->institution_id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            $maintenance->load(['item.category', 'asset', 'creator', 'updater']);
            return new InventoryMaintenanceResource($maintenance);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }
}
