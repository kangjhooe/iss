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
}
