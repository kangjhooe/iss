<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Controllers\Controller;
use App\Models\IndustryPartner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class IndustryPartnerController extends Controller
{
    use ResolvesInstitution;

    public function index(Request $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $query = IndustryPartner::forInstitution($institutionId)
                ->when($request->filled('search'), function ($q) use ($request) {
                    $s = '%' . $request->get('search') . '%';
                    $q->where(function ($inner) use ($s) {
                        $inner->where('name', 'like', $s)
                            ->orWhere('business_field', 'like', $s)
                            ->orWhere('city', 'like', $s)
                            ->orWhere('pic_name', 'like', $s);
                    });
                })
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->get('status')))
                ->orderBy('name');

            $perPage = min((int) $request->get('per_page', 20), 100);
            $list = $query->paginate($perPage);

            return response()->json($list);
        } catch (\Exception $e) {
            Log::error('IndustryPartner index failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil data mitra DU/DI.'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $data = $this->validatePartner($request);
            $data['institution_id'] = $institutionId;
            $partner = IndustryPartner::create($data);

            return response()->json([
                'message' => 'Mitra DU/DI berhasil ditambahkan.',
                'data' => $partner,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('IndustryPartner store failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal menambah mitra DU/DI.'], 500);
        }
    }

    public function show(Request $request, IndustryPartner $industry_partner): JsonResponse
    {
        if ($denied = $this->denyIfOutsideInstitution($request, $industry_partner->institution_id)) {
            return $denied;
        }

        return response()->json(['data' => $industry_partner]);
    }

    public function update(Request $request, IndustryPartner $industry_partner): JsonResponse
    {
        if ($denied = $this->denyIfOutsideInstitution($request, $industry_partner->institution_id)) {
            return $denied;
        }

        try {
            $data = $this->validatePartner($request, false);
            $industry_partner->update($data);

            return response()->json([
                'message' => 'Mitra DU/DI berhasil diperbarui.',
                'data' => $industry_partner->fresh(),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('IndustryPartner update failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal memperbarui mitra DU/DI.'], 500);
        }
    }

    public function destroy(Request $request, IndustryPartner $industry_partner): JsonResponse
    {
        if ($denied = $this->denyIfOutsideInstitution($request, $industry_partner->institution_id)) {
            return $denied;
        }

        if ($industry_partner->pklPlacements()->exists()) {
            return response()->json([
                'message' => 'Mitra masih dipakai di penempatan PKL. Nonaktifkan saja atau hapus penempatan terkait.',
            ], 422);
        }

        $industry_partner->delete();

        return response()->json(['message' => 'Mitra DU/DI berhasil dihapus.']);
    }

    private function validatePartner(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'name' => ($creating ? 'required' : 'sometimes') . '|string|max:255',
            'business_field' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:150',
            'pic_name' => 'nullable|string|max:150',
            'pic_phone' => 'nullable|string|max:50',
            'status' => ['nullable', Rule::in(['Aktif', 'Nonaktif'])],
            'notes' => 'nullable|string|max:5000',
        ]);
    }

    private function denyIfOutsideInstitution(Request $request, int $recordInstitutionId): ?JsonResponse
    {
        $user = $request->user();
        if ($user->isAdminOrSuperAdmin()) {
            return null;
        }
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId || (int) $recordInstitutionId !== (int) $institutionId) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return null;
    }
}
