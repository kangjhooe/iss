<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\ProgramKeahlian;
use App\Support\VocationalAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ProgramKeahlianController extends Controller
{
    use ResolvesInstitution;

    private function assertSmkInstitution(int $institutionId): ?JsonResponse
    {
        $level = Institution::query()->where('id', $institutionId)->value('level');
        if (! VocationalAccess::isVocationalLevel($level)) {
            return response()->json([
                'message' => 'Program keahlian hanya berlaku untuk institusi SMK/MAK.',
            ], 422);
        }

        return null;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (! $institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $query = ProgramKeahlian::forInstitution($institutionId)
                ->withCount('classes')
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->get('status')))
                ->when($request->filled('search'), function ($q) use ($request) {
                    $s = '%' . $request->get('search') . '%';
                    $q->where(function ($inner) use ($s) {
                        $inner->where('name', 'like', $s)->orWhere('code', 'like', $s);
                    });
                })
                ->orderBy('sort_order')
                ->orderBy('name');

            if ($request->boolean('all')) {
                return response()->json(['data' => $query->get()]);
            }

            $perPage = min((int) $request->get('per_page', 50), 100);

            return response()->json($query->paginate($perPage));
        } catch (\Exception $e) {
            Log::error('ProgramKeahlian index failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal mengambil program keahlian.'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (! $institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }
        if ($denied = $this->assertSmkInstitution($institutionId)) {
            return $denied;
        }

        $data = $request->validate([
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('program_keahlian', 'code')->where(fn ($q) => $q->where('institution_id', $institutionId)->whereNull('deleted_at')),
            ],
            'name' => 'required|string|max:255',
            'status' => 'nullable|in:Aktif,Nonaktif',
            'description' => 'nullable|string|max:5000',
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ]);

        $data['institution_id'] = $institutionId;
        $data['status'] = $data['status'] ?? 'Aktif';
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $row = ProgramKeahlian::create($data);
        $row->loadCount('classes');

        return response()->json([
            'message' => 'Program keahlian berhasil dibuat.',
            'data' => $row,
        ], 201);
    }

    public function show(Request $request, ProgramKeahlian $program_keahlian): JsonResponse
    {
        if ($denied = $this->denyOutside($request, (int) $program_keahlian->institution_id)) {
            return $denied;
        }

        $program_keahlian->loadCount('classes');

        return response()->json(['data' => $program_keahlian]);
    }

    public function update(Request $request, ProgramKeahlian $program_keahlian): JsonResponse
    {
        if ($denied = $this->denyOutside($request, (int) $program_keahlian->institution_id)) {
            return $denied;
        }

        $institutionId = (int) $program_keahlian->institution_id;
        $data = $request->validate([
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('program_keahlian', 'code')
                    ->where(fn ($q) => $q->where('institution_id', $institutionId)->whereNull('deleted_at'))
                    ->ignore($program_keahlian->id),
            ],
            'name' => 'sometimes|required|string|max:255',
            'status' => 'nullable|in:Aktif,Nonaktif',
            'description' => 'nullable|string|max:5000',
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ]);

        $program_keahlian->update($data);
        $program_keahlian->loadCount('classes');

        return response()->json([
            'message' => 'Program keahlian berhasil diperbarui.',
            'data' => $program_keahlian,
        ]);
    }

    public function destroy(Request $request, ProgramKeahlian $program_keahlian): JsonResponse
    {
        if ($denied = $this->denyOutside($request, (int) $program_keahlian->institution_id)) {
            return $denied;
        }

        if ($program_keahlian->classes()->exists()) {
            return response()->json([
                'message' => 'Masih ada kelas yang memakai program ini. Pindahkan atau kosongkan jurusan kelas terlebih dahulu.',
            ], 422);
        }

        $program_keahlian->delete();

        return response()->json(['message' => 'Program keahlian dihapus.']);
    }

    private function denyOutside(Request $request, int $recordInstitutionId): ?JsonResponse
    {
        $user = $request->user();
        if ($user->isAdminOrSuperAdmin()) {
            return null;
        }
        $institutionId = $this->resolveInstitutionId($request);
        if (! $institutionId || (int) $recordInstitutionId !== (int) $institutionId) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return null;
    }
}
