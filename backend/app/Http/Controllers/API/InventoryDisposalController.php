<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesActiveInstitution;
use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryDisposalResource;
use App\Models\InventoryDisposal;
use App\Services\InventoryService;
use App\Support\InventoryAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InventoryDisposalController extends Controller
{
    use ResolvesActiveInstitution;

    public function __construct(
        private InventoryService $service
    ) {}

    public function index(Request $request)
    {
        try {
            if ($denied = $this->denyUnlessInventoryManage($request)) {
                return $denied;
            }

            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['data' => [], 'meta' => ['total' => 0]]);
            }

            $query = InventoryDisposal::with(['item.category', 'item.room', 'asset'])
                ->where('institution_id', $institutionId);

            if ($request->filled('item_id')) {
                $query->where('item_id', $request->item_id);
            }
            if ($request->filled('asset_id')) {
                $query->where('asset_id', $request->asset_id);
            }
            if ($request->filled('date_from')) {
                $query->whereDate('disposal_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('disposal_date', '<=', $request->date_to);
            }
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('disposal_reason', 'like', "%{$search}%")
                        ->orWhere('disposal_document_number', 'like', "%{$search}%")
                        ->orWhereHas('item', function ($iq) use ($search) {
                            $iq->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%");
                        });
                });
            }

            $perPage = min((int) $request->get('per_page', 15), 100);

            return InventoryDisposalResource::collection(
                $query->orderByDesc('disposal_date')->orderByDesc('id')->paginate($perPage)
            );
        } catch (\Exception $e) {
            Log::error('Failed to list inventory disposals', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }

    public function update(Request $request, InventoryDisposal $disposal)
    {
        if ($denied = $this->denyUnlessInventoryManage($request)) {
            return $denied;
        }

        $validated = $request->validate([
            'disposal_date' => 'sometimes|required|date',
            'disposal_reason' => 'sometimes|required|string|max:2000',
            'disposal_document_number' => 'nullable|string|max:100',
            'status' => 'sometimes|required|in:Dijual,Hilang,Rusak',
            'quantity' => 'sometimes|required|integer|min:1',
        ]);

        try {
            if (!$this->canAccess($request, $disposal)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $disposal = $this->service->updateDisposal($disposal, $validated, $request->user()->id);

            return response()->json([
                'message' => 'Data penghapusan berhasil diperbarui',
                'data' => new InventoryDisposalResource($disposal->load(['item.category', 'item.room', 'asset'])),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update inventory disposal', [
                'disposal_id' => $disposal->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => $e->getMessage() ?: 'Terjadi kesalahan saat memperbarui penghapusan',
            ], 500);
        }
    }

    public function uploadDocument(Request $request, InventoryDisposal $disposal)
    {
        if ($denied = $this->denyUnlessInventoryManage($request)) {
            return $denied;
        }

        $rules = \App\Helpers\FileUploadRules::inventoryDisposalDocument();
        $messages = \App\Helpers\FileUploadRules::messages(
            \App\Helpers\FileUploadRules::TYPE_PDF_ONLY,
            \App\Helpers\FileUploadRules::SIZE_SMALL,
            'file',
            false
        );

        $request->validate($rules, $messages);

        try {
            if (!$this->canAccess($request, $disposal)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $disposal = $this->service->storeDisposalDocument(
                $disposal,
                $request->file('file'),
                $request->user()->id
            );

            return response()->json([
                'message' => 'Dokumen SK/BA berhasil diunggah',
                'data' => new InventoryDisposalResource($disposal->load(['item.category', 'item.room', 'asset'])),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to upload disposal document', [
                'disposal_id' => $disposal->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => $e->getMessage() ?: 'Gagal mengunggah dokumen',
            ], 500);
        }
    }

    public function deleteDocument(Request $request, InventoryDisposal $disposal)
    {
        try {
            if ($denied = $this->denyUnlessInventoryManage($request)) {
                return $denied;
            }

            if (!$this->canAccess($request, $disposal)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if (!$disposal->document_path) {
                return response()->json(['message' => 'Tidak ada dokumen untuk dihapus'], 404);
            }

            $disposal = $this->service->deleteDisposalDocument($disposal, $request->user()->id);

            return response()->json([
                'message' => 'Dokumen SK/BA berhasil dihapus',
                'data' => new InventoryDisposalResource($disposal->load(['item.category', 'item.room', 'asset'])),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete disposal document', [
                'disposal_id' => $disposal->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Gagal menghapus dokumen'], 500);
        }
    }

    public function destroy(Request $request, InventoryDisposal $disposal)
    {
        try {
            if ($denied = $this->denyUnlessInventoryManage($request)) {
                return $denied;
            }

            if (!$this->canAccess($request, $disposal)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $this->service->undoDisposal($disposal, $request->user()->id);

            return response()->json([
                'message' => $disposal->asset_id
                    ? 'Penghapusan dibatalkan dan aset dikembalikan'
                    : 'Penghapusan dibatalkan dan stok barang dikembalikan',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to undo inventory disposal', [
                'disposal_id' => $disposal->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => $e->getMessage() ?: 'Terjadi kesalahan saat membatalkan penghapusan',
            ], 500);
        }
    }

    protected function canAccess(Request $request, InventoryDisposal $disposal): bool
    {
        return InventoryAccess::canAccessDisposal($request->user(), $disposal);
    }
}
