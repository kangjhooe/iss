<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesActiveInstitution;
use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryAssetOpnameLineResource;
use App\Http\Resources\InventoryStockOpnameLineResource;
use App\Http\Resources\InventoryStockOpnameResource;
use App\Models\InventoryAssetOpnameLine;
use App\Models\InventoryStockOpname;
use App\Models\InventoryStockOpnameLine;
use App\Services\InventoryStockOpnameService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InventoryStockOpnameController extends Controller
{
    use ResolvesActiveInstitution;

    public function __construct(
        private InventoryStockOpnameService $service
    ) {}

    public function index(Request $request)
    {
        try {
            if ($denied = $this->denyUnlessInventoryManage($request)) {
                return $denied;
            }

            $institutionId = $this->resolveInstitutionId($request);
            if (! $institutionId) {
                return InventoryStockOpnameResource::collection(collect());
            }

            $filters = $request->only(['status', 'room_id', 'search', 'opname_type']);
            $perPage = min((int) $request->get('per_page', 15), 100);

            return InventoryStockOpnameResource::collection(
                $this->service->list($filters, $institutionId, $perPage)
            );
        } catch (\Exception $e) {
            Log::error('Failed to list stock opname', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    public function store(Request $request)
    {
        if ($denied = $this->denyUnlessInventoryManage($request)) {
            return $denied;
        }

        $validated = $request->validate([
            'opname_date' => 'required|date',
            'opname_type' => 'nullable|in:stock,asset',
            'room_id' => 'nullable|exists:room,id',
            'building_id' => 'nullable|exists:building,id',
            'notes' => 'nullable|string|max:2000',
        ]);

        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (! $institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 403);
            }

            $opname = $this->service->create($validated, $institutionId, $request->user()->id);
            $label = ($validated['opname_type'] ?? 'stock') === 'asset' ? 'Opname aset' : 'Stock opname';

            return response()->json([
                'message' => "Sesi {$label} berhasil dibuat",
                'data' => new InventoryStockOpnameResource($opname),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage() ?: 'Terjadi kesalahan'], 500);
        }
    }

    public function show(Request $request, InventoryStockOpname $opname)
    {
        if ($denied = $this->denyUnlessInventoryManage($request)) {
            return $denied;
        }

        if (! $this->canAccessInstitutionRecord($request, (int) $opname->institution_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($opname->isAssetOpname()) {
            $opname->load(['assetLines.asset.item', 'assetLines.asset.room', 'room', 'building', 'creator']);
        } else {
            $opname->load(['lines.item.category', 'lines.item.room', 'lines.adjustmentTransaction', 'room', 'building', 'creator']);
        }

        return new InventoryStockOpnameResource($opname);
    }

    public function refreshLines(Request $request, InventoryStockOpname $opname)
    {
        if ($denied = $this->denyUnlessInventoryManage($request)) {
            return $denied;
        }

        if (! $this->canAccessInstitutionRecord($request, (int) $opname->institution_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            if ($opname->isAssetOpname()) {
                $this->service->populateAssetLines($opname, $request->user()->id);
            } else {
                $this->service->populateLines($opname, $request->user()->id);
            }

            if ($opname->isAssetOpname()) {
                $opname->load(['assetLines.asset.item', 'assetLines.asset.room', 'room', 'building']);
            } else {
                $opname->load(['lines.item.category', 'lines.item.room', 'room', 'building']);
            }

            return response()->json([
                'message' => 'Daftar opname diperbarui',
                'data' => new InventoryStockOpnameResource($opname),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage() ?: 'Terjadi kesalahan'], 500);
        }
    }

    public function updateLine(Request $request, InventoryStockOpname $opname, InventoryStockOpnameLine $line)
    {
        if ($denied = $this->denyUnlessInventoryManage($request)) {
            return $denied;
        }

        if ((int) $line->opname_id !== (int) $opname->id) {
            return response()->json(['message' => 'Baris tidak sesuai sesi opname'], 422);
        }
        if (! $this->canAccessInstitutionRecord($request, (int) $opname->institution_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'counted_quantity' => 'nullable|integer|min:0',
            'condition' => 'nullable|in:Baik,Rusak Ringan,Rusak Berat,Habis Pakai',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $line = $this->service->updateLine($line, $validated, $request->user()->id);

            return response()->json([
                'message' => 'Hasil hitung disimpan',
                'data' => new InventoryStockOpnameLineResource($line),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage() ?: 'Terjadi kesalahan'], 500);
        }
    }

    public function updateAssetLine(Request $request, InventoryStockOpname $opname, InventoryAssetOpnameLine $line)
    {
        if ($denied = $this->denyUnlessInventoryManage($request)) {
            return $denied;
        }

        if ((int) $line->opname_id !== (int) $opname->id) {
            return response()->json(['message' => 'Baris tidak sesuai sesi opname'], 422);
        }
        if (! $this->canAccessInstitutionRecord($request, (int) $opname->institution_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'found' => 'nullable|boolean',
            'counted_condition' => 'nullable|in:Baik,Rusak Ringan,Rusak Berat,Habis Pakai',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $line = $this->service->updateAssetLine($line, $validated, $request->user()->id);

            return response()->json([
                'message' => 'Hasil cek aset disimpan',
                'data' => new InventoryAssetOpnameLineResource($line),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage() ?: 'Terjadi kesalahan'], 500);
        }
    }

    public function finalize(Request $request, InventoryStockOpname $opname)
    {
        if ($denied = $this->denyUnlessInventoryManage($request)) {
            return $denied;
        }

        if (! $this->canAccessInstitutionRecord($request, (int) $opname->institution_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $opname = $this->service->finalize($opname, $request->user()->id);
            $message = $opname->isAssetOpname()
                ? 'Opname aset difinalisasi, status/kondisi aset diperbarui'
                : 'Stock opname difinalisasi, penyesuaian stok diterapkan';

            return response()->json([
                'message' => $message,
                'data' => new InventoryStockOpnameResource($opname),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage() ?: 'Terjadi kesalahan'], 500);
        }
    }

    public function cancel(Request $request, InventoryStockOpname $opname)
    {
        if ($denied = $this->denyUnlessInventoryManage($request)) {
            return $denied;
        }

        if (! $this->canAccessInstitutionRecord($request, (int) $opname->institution_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $opname = $this->service->cancel($opname, $request->user()->id);

            return response()->json([
                'message' => 'Opname dibatalkan',
                'data' => new InventoryStockOpnameResource($opname),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage() ?: 'Terjadi kesalahan'], 500);
        }
    }
}
