<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicGuestVisitRequest;
use App\Models\Institution;
use App\Services\GuestVisitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicSchoolController extends Controller
{
    public function __construct(
        protected GuestVisitService $guestVisitService
    ) {}

    /**
     * Data institusi publik by NPSN (untuk landing page sekolah).
     * Hanya field yang aman untuk public.
     */
    public function showInstitution(Request $request): JsonResponse
    {
        $npsn = $request->get('npsn');
        if (!$npsn) {
            return response()->json(['message' => 'npsn wajib diisi.'], 422);
        }

        $institution = Institution::where('npsn', $npsn)->where('is_active', true)->first();
        if (!$institution) {
            return response()->json(['message' => 'Sekolah tidak ditemukan.'], 404);
        }

        $fullAddress = trim(implode(', ', array_filter([
            $institution->address,
            $institution->village,
            $institution->sub_district,
            $institution->district,
            $institution->province,
            $institution->postal_code,
        ])));

        return response()->json([
            'data' => [
                'id' => $institution->id,
                'name' => $institution->name,
                'npsn' => $institution->npsn,
                'level' => $institution->level,
                'type' => $institution->type,
                'address' => $fullAddress ?: $institution->address,
                'phone' => $institution->phone,
                'email' => $institution->email,
                'website' => $institution->website,
                'description' => $institution->description,
                'latitude' => $institution->latitude,
                'longitude' => $institution->longitude,
                'logo_url' => $institution->logo ? asset('storage/' . $institution->logo) : null,
            ],
        ]);
    }

    /**
     * Submit buku tamu dari halaman publik (tanpa login).
     * Anti spam: throttle. Anti bot: honeypot di PublicGuestVisitRequest.
     */
    public function storeGuestVisit(PublicGuestVisitRequest $request): JsonResponse
    {
        $npsn = $request->input('npsn');
        $institution = Institution::where('npsn', $npsn)->where('is_active', true)->first();
        if (!$institution) {
            return response()->json(['message' => 'Sekolah tidak ditemukan.'], 404);
        }

        try {
            $visit = $this->guestVisitService->createPublic([
                'nama_tamu' => $request->input('nama_tamu'),
                'no_identitas' => $request->input('no_identitas'),
                'instansi_asal' => $request->input('instansi_asal'),
                'no_telepon' => $request->input('no_telepon'),
                'tujuan_kunjungan' => $request->input('tujuan_kunjungan'),
                'orang_ditemui' => $request->input('orang_ditemui'),
                'catatan' => $request->input('catatan'),
            ], $institution->id, $request->file('foto'));

            return response()->json([
                'message' => 'Buku tamu berhasil dicatat.',
                'data' => ['id' => $visit->id],
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Gagal mencatat buku tamu. Silakan coba lagi.',
            ], 500);
        }
    }

    /**
     * Statistik publik untuk halaman awal (tanpa auth).
     * Total + breakdown per jenjang (level) dan per type (Negeri/Swasta).
     */
    public function stats(): JsonResponse
    {
        $base = Institution::where('is_active', true);

        $institutionsCount = (clone $base)->count();

        $byLevel = (clone $base)
            ->selectRaw('level, count(*) as count')
            ->groupBy('level')
            ->pluck('count', 'level')
            ->mapWithKeys(fn ($count, $level) => [$level ?? 'Lainnya' => (int) $count])
            ->toArray();

        $byType = (clone $base)
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->mapWithKeys(fn ($count, $type) => [$type ?? 'Lainnya' => (int) $count])
            ->toArray();

        return response()->json([
            'data' => [
                'institutions_count' => $institutionsCount,
                'by_level' => $byLevel,
                'by_type' => $byType,
            ],
        ]);
    }

    /**
     * Daftar instansi yang baru bergabung (untuk slider di halaman awal).
     * Hanya field aman untuk public, urut created_at desc.
     */
    public function recentInstitutions(Request $request): JsonResponse
    {
        $limit = min((int) $request->get('limit', 10), 20);

        $institutions = Institution::where('is_active', true)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['id', 'name', 'npsn', 'level', 'type', 'logo', 'created_at']);

        $data = $institutions->map(function (Institution $institution) {
            return [
                'id' => $institution->id,
                'name' => $institution->name,
                'npsn' => $institution->npsn,
                'level' => $institution->level,
                'type' => $institution->type,
                'logo_url' => $institution->logo ? asset('storage/' . $institution->logo) : null,
                'created_at' => $institution->created_at?->toIso8601String(),
            ];
        });

        return response()->json(['data' => $data->values()->all()]);
    }
}
