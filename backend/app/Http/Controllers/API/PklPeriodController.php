<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Controllers\Controller;
use App\Models\PklPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class PklPeriodController extends Controller
{
    use ResolvesInstitution;

    public function index(Request $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $query = PklPeriod::forInstitution($institutionId)
                ->with('academicYear:id,name')
                ->withCount('placements')
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->get('status')))
                ->when($request->filled('search'), function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->get('search') . '%');
                })
                ->orderByDesc('start_date')
                ->orderByDesc('id');

            $perPage = min((int) $request->get('per_page', 20), 100);

            return response()->json($query->paginate($perPage));
        } catch (\Exception $e) {
            Log::error('PklPeriod index failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil periode PKL.'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'academic_year_id' => 'nullable|integer|exists:academic_years,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => ['nullable', Rule::in(['draft', 'berlangsung', 'selesai'])],
            'notes' => 'nullable|string|max:5000',
        ]);
        $data['institution_id'] = $institutionId;
        $data['status'] = $data['status'] ?? 'draft';

        $period = PklPeriod::create($data);
        $period->load('academicYear:id,name');

        return response()->json([
            'message' => 'Periode PKL berhasil dibuat.',
            'data' => $period,
        ], 201);
    }

    public function show(Request $request, PklPeriod $pkl_period): JsonResponse
    {
        if ($denied = $this->denyOutside($request, $pkl_period->institution_id)) {
            return $denied;
        }

        $pkl_period->load(['academicYear:id,name'])->loadCount('placements');

        return response()->json(['data' => $pkl_period]);
    }

    public function update(Request $request, PklPeriod $pkl_period): JsonResponse
    {
        if ($denied = $this->denyOutside($request, $pkl_period->institution_id)) {
            return $denied;
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'academic_year_id' => 'nullable|integer|exists:academic_years,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => ['nullable', Rule::in(['draft', 'berlangsung', 'selesai'])],
            'notes' => 'nullable|string|max:5000',
        ]);

        $pkl_period->update($data);
        $pkl_period->load('academicYear:id,name')->loadCount('placements');

        return response()->json([
            'message' => 'Periode PKL berhasil diperbarui.',
            'data' => $pkl_period,
        ]);
    }

    public function destroy(Request $request, PklPeriod $pkl_period): JsonResponse
    {
        if ($denied = $this->denyOutside($request, $pkl_period->institution_id)) {
            return $denied;
        }

        if ($pkl_period->placements()->exists()) {
            return response()->json([
                'message' => 'Periode masih memiliki penempatan. Hapus penempatan terlebih dahulu.',
            ], 422);
        }

        $pkl_period->delete();

        return response()->json(['message' => 'Periode PKL berhasil dihapus.']);
    }

    private function denyOutside(Request $request, int $recordInstitutionId): ?JsonResponse
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
