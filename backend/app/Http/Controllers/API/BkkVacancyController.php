<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Controllers\Controller;
use App\Models\BkkVacancy;
use App\Models\IndustryPartner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class BkkVacancyController extends Controller
{
    use ResolvesInstitution;

    public function index(Request $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $query = BkkVacancy::forInstitution($institutionId)
                ->with('industryPartner:id,name,city')
                ->withCount('applications')
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->get('status')))
                ->when($request->filled('search'), function ($q) use ($request) {
                    $s = '%' . $request->get('search') . '%';
                    $q->where(function ($inner) use ($s) {
                        $inner->where('title', 'like', $s)
                            ->orWhere('position', 'like', $s)
                            ->orWhere('company_name', 'like', $s)
                            ->orWhereHas('industryPartner', fn ($pq) => $pq->where('name', 'like', $s));
                    });
                })
                ->orderByDesc('id');

            $perPage = min((int) $request->get('per_page', 20), 100);

            return response()->json($query->paginate($perPage));
        } catch (\Exception $e) {
            Log::error('BkkVacancy index failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil lowongan BKK.'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'industry_partner_id' => 'nullable|integer|exists:industry_partners,id',
            'company_name' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'quota' => 'nullable|integer|min:1|max:9999',
            'deadline' => 'nullable|date',
            'status' => ['nullable', Rule::in(BkkVacancy::STATUSES)],
            'description' => 'nullable|string|max:10000',
            'requirements' => 'nullable|string|max:10000',
        ]);

        if (!empty($data['industry_partner_id'])) {
            $partner = IndustryPartner::findOrFail($data['industry_partner_id']);
            if ((int) $partner->institution_id !== (int) $institutionId) {
                return response()->json(['message' => 'Mitra DU/DI tidak valid.'], 422);
            }
            $data['company_name'] = !empty($data['company_name']) ? $data['company_name'] : $partner->name;
        }

        $data['institution_id'] = $institutionId;
        $data['status'] = $data['status'] ?? 'buka';

        $vacancy = BkkVacancy::create($data);
        $vacancy->load('industryPartner:id,name,city')->loadCount('applications');

        return response()->json([
            'message' => 'Lowongan BKK berhasil dibuat.',
            'data' => $vacancy,
        ], 201);
    }

    public function show(Request $request, BkkVacancy $bkk_vacancy): JsonResponse
    {
        if ($denied = $this->denyOutside($request, $bkk_vacancy->institution_id)) {
            return $denied;
        }

        $bkk_vacancy->load('industryPartner')->loadCount('applications');

        return response()->json(['data' => $bkk_vacancy]);
    }

    public function update(Request $request, BkkVacancy $bkk_vacancy): JsonResponse
    {
        if ($denied = $this->denyOutside($request, $bkk_vacancy->institution_id)) {
            return $denied;
        }

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'industry_partner_id' => 'nullable|integer|exists:industry_partners,id',
            'company_name' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'quota' => 'nullable|integer|min:1|max:9999',
            'deadline' => 'nullable|date',
            'status' => ['nullable', Rule::in(BkkVacancy::STATUSES)],
            'description' => 'nullable|string|max:10000',
            'requirements' => 'nullable|string|max:10000',
        ]);

        if (array_key_exists('industry_partner_id', $data) && $data['industry_partner_id']) {
            $partner = IndustryPartner::findOrFail($data['industry_partner_id']);
            if ((int) $partner->institution_id !== (int) $bkk_vacancy->institution_id) {
                return response()->json(['message' => 'Mitra DU/DI tidak valid.'], 422);
            }
            if (empty($data['company_name'])) {
                $data['company_name'] = $partner->name;
            }
        }

        $bkk_vacancy->update($data);
        $bkk_vacancy->load('industryPartner:id,name,city')->loadCount('applications');

        return response()->json([
            'message' => 'Lowongan BKK berhasil diperbarui.',
            'data' => $bkk_vacancy,
        ]);
    }

    public function destroy(Request $request, BkkVacancy $bkk_vacancy): JsonResponse
    {
        if ($denied = $this->denyOutside($request, $bkk_vacancy->institution_id)) {
            return $denied;
        }

        if ($bkk_vacancy->applications()->exists()) {
            return response()->json([
                'message' => 'Lowongan masih memiliki lamaran. Tutup lowongan atau hapus lamaran terkait.',
            ], 422);
        }

        $bkk_vacancy->delete();

        return response()->json(['message' => 'Lowongan BKK berhasil dihapus.']);
    }

    /**
     * Portal siswa/alumni: daftar lowongan terbuka di institusi sendiri.
     */
    public function myOpen(Request $request): JsonResponse
    {
        $ctx = $this->resolveStudentPortal($request);
        if ($ctx instanceof JsonResponse) {
            return $ctx;
        }
        [, , $institutionId] = $ctx;

        $today = now()->toDateString();
        $query = BkkVacancy::forInstitution($institutionId)
            ->where('status', 'buka')
            ->where(function ($q) use ($today) {
                $q->whereNull('deadline')->orWhere('deadline', '>=', $today);
            })
            ->with('industryPartner:id,name,city')
            ->withCount('applications')
            ->orderByDesc('id');

        $perPage = min((int) $request->get('per_page', 20), 100);

        return response()->json($query->paginate($perPage));
    }

    /**
     * @return array{0: \App\Models\User, 1: \App\Models\Student, 2: int}|\Illuminate\Http\JsonResponse
     */
    private function resolveStudentPortal(Request $request): array|JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isStudent()) {
            return response()->json(['message' => 'Hanya siswa/alumni yang dapat mengakses data ini.'], 403);
        }

        $student = $user->studentProfile;
        if (!$student) {
            return response()->json(['message' => 'Profil siswa tidak ditemukan untuk akun ini.'], 404);
        }

        $institutionId = (int) ($student->institution_id ?: $user->institution_id);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        return [$user, $student, $institutionId];
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
