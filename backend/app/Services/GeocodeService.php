<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodeService
{
    /**
     * Cari koordinat dari teks alamat via OpenStreetMap Nominatim.
     *
     * @return list<array{display_name: string, latitude: float, longitude: float}>
     */
    public function search(string $query, int $limit = 5): array
    {
        $query = trim($query);
        if ($query === '' || mb_strlen($query) < 3) {
            return [];
        }

        $limit = max(1, min($limit, 10));
        $cacheKey = 'geocode.search.'.md5(mb_strtolower($query)).'.'.$limit;

        return Cache::remember($cacheKey, 3600, function () use ($query, $limit) {
            try {
                $response = Http::timeout(10)
                    ->withHeaders([
                        'User-Agent' => config('app.name', 'ISS').' Geocode/1.0 (contact: '.config('mail.from.address', 'admin@localhost').')',
                        'Accept-Language' => 'id',
                    ])
                    ->get('https://nominatim.openstreetmap.org/search', [
                        'q' => $query,
                        'format' => 'json',
                        'limit' => $limit,
                        'countrycodes' => 'id',
                        'addressdetails' => 0,
                    ]);

                if (!$response->successful()) {
                    Log::warning('Geocode search failed', ['status' => $response->status(), 'query' => $query]);

                    return [];
                }

                $rows = $response->json();
                if (!is_array($rows)) {
                    return [];
                }

                $results = [];
                foreach ($rows as $row) {
                    if (!is_array($row) || !isset($row['lat'], $row['lon'], $row['display_name'])) {
                        continue;
                    }
                    $results[] = [
                        'display_name' => (string) $row['display_name'],
                        'latitude' => (float) $row['lat'],
                        'longitude' => (float) $row['lon'],
                    ];
                }

                return $results;
            } catch (\Throwable $e) {
                Log::warning('Geocode search exception', ['query' => $query, 'error' => $e->getMessage()]);

                return [];
            }
        });
    }
}
