<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesActiveInstitution;
use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryAssetMovementResource;
use App\Models\InventoryAssetMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InventoryAssetMovementController extends Controller
{
    use ResolvesActiveInstitution;

    public function index(Request $request)
    {
        try {
            if ($denied = $this->denyUnlessInventoryManage($request)) {
                return $denied;
            }

            $institutionId = $this->resolveInstitutionId($request);
            if (! $institutionId) {
                return InventoryAssetMovementResource::collection(collect());
            }

            $query = InventoryAssetMovement::with(['asset', 'item', 'fromRoom', 'toRoom', 'creator'])
                ->where('institution_id', $institutionId);

            if ($request->filled('asset_id')) {
                $query->where('asset_id', $request->asset_id);
            }
            if ($request->filled('item_id')) {
                $query->where('item_id', $request->item_id);
            }
            if ($request->filled('room_id')) {
                $roomId = (int) $request->room_id;
                $query->where(function ($q) use ($roomId) {
                    $q->where('from_room_id', $roomId)->orWhere('to_room_id', $roomId);
                });
            }
            if ($request->filled('date_from')) {
                $query->whereDate('movement_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('movement_date', '<=', $request->date_to);
            }

            $perPage = min((int) $request->get('per_page', 15), 100);

            return InventoryAssetMovementResource::collection(
                $query->orderByDesc('movement_date')->orderByDesc('id')->paginate($perPage)
            );
        } catch (\Exception $e) {
            Log::error('Failed to list asset movements', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }
}
