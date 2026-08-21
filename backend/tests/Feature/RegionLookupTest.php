<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RegionLookupTest extends TestCase
{
    public function test_provinces_are_proxied_and_cached(): void
    {
        Http::fake([
            'https://wilayah.id/api/provinces.json' => Http::response([
                'data' => [
                    ['code' => '32', 'name' => 'Jawa Barat'],
                    ['code' => '31', 'name' => 'DKI Jakarta'],
                ],
            ], 200),
        ]);

        $first = $this->getJson('/api/v1/public/regions/provinces');
        $first->assertOk()
            ->assertJsonPath('data.0.code', '32')
            ->assertJsonPath('data.0.name', 'Jawa Barat');

        Http::fake([
            'https://wilayah.id/api/provinces.json' => Http::response(['data' => []], 500),
        ]);

        $this->getJson('/api/v1/public/regions/provinces')
            ->assertOk()
            ->assertJsonPath('data.0.code', '32');
    }

    public function test_regencies_require_valid_province_code(): void
    {
        $this->getJson('/api/v1/public/regions/regencies?province_code=abc')
            ->assertStatus(422)
            ->assertJsonPath('data', []);
    }

    public function test_regencies_are_filtered_by_province(): void
    {
        Http::fake([
            'https://wilayah.id/api/regencies/32.json' => Http::response([
                'data' => [
                    ['code' => '32.73', 'name' => 'Kota Bandung'],
                ],
            ], 200),
        ]);

        $this->getJson('/api/v1/public/regions/regencies?province_code=32')
            ->assertOk()
            ->assertJsonPath('data.0.code', '32.73')
            ->assertJsonPath('data.0.name', 'Kota Bandung');
    }

    public function test_upstream_failure_returns_service_unavailable(): void
    {
        Cache::flush();
        Http::fake([
            'https://wilayah.id/api/provinces.json' => Http::response('error', 502),
        ]);

        $this->getJson('/api/v1/public/regions/provinces')
            ->assertStatus(503)
            ->assertJsonPath('data', []);
    }
}
