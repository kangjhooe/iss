<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUksMedicineRequest;
use App\Http\Requests\StoreUksMedicineTransactionRequest;
use App\Http\Requests\UpdateUksMedicineRequest;
use App\Http\Resources\UksMedicineResource;
use App\Http\Resources\UksMedicineTransactionResource;
use App\Models\UksMedicine;
use App\Services\UksMedicineService;
use App\Support\InstitutionContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class UksMedicineController extends Controller
{
    use ResolvesInstitution;

    public function __construct(
        protected UksMedicineService $uksMedicineService
    ) {}

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $filters = $request->only(['search', 'is_active', 'low_stock', 'expired']);
            $perPage = min((int) $request->get('per_page', 50), 100);
            $items = $this->uksMedicineService->listForInstitution($institutionId, $filters, $perPage);

            return UksMedicineResource::collection($items);
        } catch (\Exception $e) {
            Log::error('UKS medicine index failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mengambil stok obat UKS.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function summary(Request $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            return response()->json([
                'data' => $this->uksMedicineService->getStockSummary($institutionId),
            ]);
        } catch (\Exception $e) {
            Log::error('UKS medicine summary failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mengambil ringkasan stok obat UKS.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function store(StoreUksMedicineRequest $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $medicine = $this->uksMedicineService->create($institutionId, $request->validated());

            return (new UksMedicineResource($medicine))->response()->setStatusCode(201);
        } catch (\Exception $e) {
            Log::error('UKS medicine store failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal menambahkan obat UKS.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function show(Request $request, UksMedicine $uks_medicine): UksMedicineResource|JsonResponse
    {
        $user = $request->user();
        if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $uks_medicine->institution_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return new UksMedicineResource($uks_medicine);
    }

    public function update(UpdateUksMedicineRequest $request, UksMedicine $uks_medicine): UksMedicineResource|JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $uks_medicine->institution_id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $medicine = $this->uksMedicineService->update($uks_medicine, $request->validated());

            return new UksMedicineResource($medicine);
        } catch (\Exception $e) {
            Log::error('UKS medicine update failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal memperbarui obat UKS.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function destroy(Request $request, UksMedicine $uks_medicine): JsonResponse
    {
        $user = $request->user();
        if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $uks_medicine->institution_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->uksMedicineService->delete($uks_medicine);

        return response()->json(['message' => 'Obat UKS berhasil dihapus atau dinonaktifkan.']);
    }

    public function transactions(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $filters = $request->only(['uks_medicine_id', 'type', 'date_from', 'date_to']);
            $perPage = min((int) $request->get('per_page', 30), 100);
            $rows = $this->uksMedicineService->listTransactions($institutionId, $filters, $perPage);

            return UksMedicineTransactionResource::collection($rows);
        } catch (\Exception $e) {
            Log::error('UKS medicine transactions failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mengambil riwayat transaksi obat UKS.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function storeTransaction(StoreUksMedicineTransactionRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $tx = $this->uksMedicineService->recordTransaction($institutionId, $request->validated(), $user->id);

            return (new UksMedicineTransactionResource($tx))->response()->setStatusCode(201);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Obat atau kunjungan tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('UKS medicine transaction store failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mencatat transaksi obat UKS.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
