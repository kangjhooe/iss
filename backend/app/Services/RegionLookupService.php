<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class RegionLookupService
{
    public const PROVINCE_PATTERN = '/^\d{2}$/';

    public const REGENCY_PATTERN = '/^\d{2}\.\d{2}$/';

    public const DISTRICT_PATTERN = '/^\d{2}\.\d{2}\.\d{2}$/';

    public function __construct(
        protected string $baseUrl,
        protected int $cacheTtl,
        protected int $timeout
    ) {
    }

    public static function fromConfig(): self
    {
        return new self(
            config('regions.base_url', 'https://wilayah.id/api'),
            (int) config('regions.cache_ttl', 2592000),
            (int) config('regions.timeout', 10),
        );
    }

    /**
     * @return list<array{code: string, name: string}>
     */
    public function provinces(): array
    {
        return $this->fetch('regions.provinces', $this->baseUrl.'/provinces.json');
    }

    /**
     * @return list<array{code: string, name: string}>
     */
    public function regencies(string $provinceCode): array
    {
        $this->assertCode($provinceCode, self::PROVINCE_PATTERN);

        return $this->fetch(
            'regions.regencies.'.$provinceCode,
            $this->baseUrl.'/regencies/'.$provinceCode.'.json'
        );
    }

    /**
     * @return list<array{code: string, name: string}>
     */
    public function districts(string $regencyCode): array
    {
        $this->assertCode($regencyCode, self::REGENCY_PATTERN);

        return $this->fetch(
            'regions.districts.'.$regencyCode,
            $this->baseUrl.'/districts/'.$regencyCode.'.json'
        );
    }

    /**
     * @return list<array{code: string, name: string}>
     */
    public function villages(string $districtCode): array
    {
        $this->assertCode($districtCode, self::DISTRICT_PATTERN);

        return $this->fetch(
            'regions.villages.'.$districtCode,
            $this->baseUrl.'/villages/'.$districtCode.'.json'
        );
    }

    /**
     * @return list<array{code: string, name: string}>
     */
    protected function fetch(string $cacheKey, string $url): array
    {
        if ($this->cacheTtl > 0) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->withHeaders([
                    'User-Agent' => config('app.name', 'servr.in') . '/1.0 (Region-Lookup)',
                ])
                ->get($url);
        } catch (ConnectionException $e) {
            Log::warning('Region lookup connection failed', ['url' => $url, 'error' => $e->getMessage()]);
            throw new RuntimeException('Data wilayah sedang tidak tersedia.', 0, $e);
        }

        if (! $response->successful()) {
            Log::warning('Region lookup HTTP error', ['url' => $url, 'status' => $response->status()]);
            throw new RuntimeException('Data wilayah sedang tidak tersedia.');
        }

        $data = $this->normalize($response->json());

        if ($this->cacheTtl > 0) {
            Cache::put($cacheKey, $data, $this->cacheTtl);
        }

        return $data;
    }

    /**
     * @return list<array{code: string, name: string}>
     */
    protected function normalize(mixed $payload): array
    {
        $rows = is_array($payload) && array_is_list($payload)
            ? $payload
            : (is_array($payload) ? ($payload['data'] ?? []) : []);

        if (! is_array($rows)) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $code = trim((string) ($row['code'] ?? $row['id'] ?? ''));
            $name = trim((string) ($row['name'] ?? $row['nama'] ?? ''));
            if ($code === '' || $name === '') {
                continue;
            }
            $out[] = ['code' => $code, 'name' => $name];
        }

        return $out;
    }

    protected function assertCode(string $code, string $pattern): void
    {
        if (! preg_match($pattern, $code)) {
            throw new InvalidArgumentException('Kode wilayah tidak valid.');
        }
    }
}
