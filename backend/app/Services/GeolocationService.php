<?php

namespace App\Services;

class GeolocationService
{
    /**
     * Hitung jarak antara dua koordinat menggunakan Haversine formula.
     * Return jarak dalam meter.
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // Radius bumi dalam meter

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Validasi apakah lokasi user berada dalam radius yang diizinkan.
     */
    public function isWithinRadius(
        float $userLat,
        float $userLon,
        float $institutionLat,
        float $institutionLon,
        int $radiusMeters
    ): bool {
        $distance = $this->calculateDistance(
            $userLat,
            $userLon,
            $institutionLat,
            $institutionLon
        );

        return $distance <= $radiusMeters;
    }
}
