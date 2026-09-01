<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\GeocodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeocodeController extends Controller
{
    public function __construct(
        protected GeocodeService $geocodeService
    ) {}

    /**
     * Pencarian alamat → daftar koordinat (proxy Nominatim, hindari CORS browser).
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:200'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $results = $this->geocodeService->search(
            $validated['q'],
            (int) ($validated['limit'] ?? 5)
        );

        return response()->json([
            'message' => $results === []
                ? 'Lokasi tidak ditemukan. Coba kata kunci lain atau isi koordinat manual.'
                : 'Hasil pencarian lokasi.',
            'data' => $results,
        ]);
    }
}
