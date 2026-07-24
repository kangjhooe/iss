<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFinanceFeeTypeRequest;
use App\Http\Requests\UpdateFinanceFeeTypeRequest;
use App\Http\Resources\FinanceFeeTypeResource;
use App\Models\FinanceFeeType;
use App\Support\InstitutionContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class FinanceFeeTypeController extends Controller
{
    protected function institutionId(Request $request): ?int
    {
        return InstitutionContext::resolveForUser(
            $request->user(),
            $request,
            $request->get('institution_id')
        );
    }

    protected function denyIfForeign(Request $request, int $modelInstitutionId): ?JsonResponse
    {
        $institutionId = $this->institutionId($request);
        if ($request->user()->isSuperAdmin()) {
            return null;
        }
        if (!$institutionId || (int) $modelInstitutionId !== (int) $institutionId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return null;
    }

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $this->institutionId($request);
            if (!$institutionId && !$user->isSuperAdmin()) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }
            if (!$institutionId) {
                return response()->json(['message' => 'Pilih institusi.'], 400);
            }

            $query = FinanceFeeType::forInstitution($institutionId)
                ->withCount('invoices')
                ->orderBy('sort_order')
                ->orderBy('name');

            if ($request->boolean('active_only')) {
                $query->active();
            }
            if ($request->filled('frequency')) {
                $query->where('frequency', $request->get('frequency'));
            }
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            }

            $perPage = min((int) $request->get('per_page', 50), 100);
            return FinanceFeeTypeResource::collection($query->paginate($perPage));
        } catch (\Exception $e) {
            Log::error('FinanceFeeType index failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil jenis biaya.'], 500);
        }
    }

    public function store(StoreFinanceFeeTypeRequest $request): JsonResponse
    {
        try {
            $institutionId = $this->institutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $data = $request->validated();
            $data['institution_id'] = $institutionId;
            $data['code'] = !empty($data['code']) ? $data['code'] : null;
            $data['default_amount'] = $data['default_amount'] ?? 0;
            $data['is_active'] = $data['is_active'] ?? true;
            $data['sort_order'] = $data['sort_order'] ?? 0;

            $feeType = FinanceFeeType::create($data);

            return (new FinanceFeeTypeResource($feeType))
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            Log::error('FinanceFeeType store failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal menambah jenis biaya.'], 500);
        }
    }

    public function show(Request $request, FinanceFeeType $fee_type): FinanceFeeTypeResource|JsonResponse
    {
        if ($denied = $this->denyIfForeign($request, (int) $fee_type->institution_id)) {
            return $denied;
        }
        $fee_type->loadCount('invoices');
        return new FinanceFeeTypeResource($fee_type);
    }

    public function update(UpdateFinanceFeeTypeRequest $request, FinanceFeeType $fee_type): FinanceFeeTypeResource|JsonResponse
    {
        try {
            if ($denied = $this->denyIfForeign($request, (int) $fee_type->institution_id)) {
                return $denied;
            }

            $data = $request->validated();
            if (array_key_exists('code', $data)) {
                $data['code'] = $data['code'] ?: null;
            }
            $fee_type->update($data);
            return new FinanceFeeTypeResource($fee_type->fresh());
        } catch (\Exception $e) {
            Log::error('FinanceFeeType update failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal memperbarui jenis biaya.'], 500);
        }
    }

    public function destroy(Request $request, FinanceFeeType $fee_type): JsonResponse
    {
        if ($denied = $this->denyIfForeign($request, (int) $fee_type->institution_id)) {
            return $denied;
        }

        if ($fee_type->invoices()->exists()) {
            return response()->json([
                'message' => 'Jenis biaya tidak dapat dihapus karena sudah dipakai tagihan. Nonaktifkan saja.',
            ], 422);
        }

        $fee_type->delete();
        return response()->json(['message' => 'Jenis biaya dihapus.']);
    }
}
