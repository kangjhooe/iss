<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NpsnValidationService
{
    public function __construct(
        protected string $baseUrl,
        protected int $cacheTtl,
        protected int $timeout
    ) {
    }

    /**
     * Validasi NPSN ke referensi Kemendikbud.
     * Mengembalikan ['valid' => bool, 'name' => ?string, 'address' => ?string].
     */
    public function validate(string $npsn): array
    {
        $npsn = $this->normalizeNpsn($npsn);
        if ($npsn === null) {
            return ['valid' => false, 'name' => null, 'address' => null];
        }

        $cacheKey = 'npsn_validation_' . $npsn;
        if ($this->cacheTtl > 0) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $result = $this->fetchFromReferensi($npsn);

        if ($this->cacheTtl > 0) {
            Cache::put($cacheKey, $result, $this->cacheTtl);
        }

        return $result;
    }

    /**
     * Cek saja valid/tidak (tanpa parsing name/address). Berguna untuk validation rule.
     */
    public function isValid(string $npsn): bool
    {
        $r = $this->validate($npsn);
        return $r['valid'];
    }

    public function normalizeNpsn(string $npsn): ?string
    {
        $npsn = preg_replace('/\D/', '', $npsn);
        return strlen($npsn) === 8 ? $npsn : null;
    }

    protected function fetchFromReferensi(string $npsn): array
    {
        $url = rtrim($this->baseUrl, '/') . '/pendidikan/npsn/' . $npsn;

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; ISS-App/1.0; NPSN-Validation)',
                    'Accept' => 'text/html,application/xhtml+xml',
                ])
                ->get($url);

            $body = $response->body();

            if (! $response->successful()) {
                Log::warning('NPSN referensi HTTP error', ['npsn' => $npsn, 'status' => $response->status()]);
                return ['valid' => false, 'name' => null, 'address' => null];
            }

            // Utama: halaman "tidak ditemukan"
            if (stripos($body, 'NPSN TIDAK DITEMUKAN') !== false) {
                return ['valid' => false, 'name' => null, 'address' => null];
            }

            // Valid: ada NPSN di halaman (tabel identitas) dan bukan halaman error
            $hasNpsnLabel = preg_match('/NPSN\s*[:\|]/ui', $body) || stripos($body, 'Identitas Satuan') !== false;
            $hasNpsnNumber = strpos($body, $npsn) !== false;
            if (! $hasNpsnLabel || ! $hasNpsnNumber) {
                return ['valid' => false, 'name' => null, 'address' => null];
            }

            $name = $this->parseNameFromBody($body);
            $address = $this->parseAddressFromBody($body);

            return ['valid' => true, 'name' => $name, 'address' => $address];
        } catch (\Throwable $e) {
            Log::warning('NPSN referensi request failed', ['npsn' => $npsn, 'error' => $e->getMessage()]);
            return ['valid' => false, 'name' => null, 'address' => null];
        }
    }

    protected function parseNameFromBody(string $body): ?string
    {
        // Tabel markdown: | Nama | : | IAIN Langsa |
        if (preg_match('/\|\s*Nama\s*\|\s*:\s*\|\s*([^|\[]+)(?:\s*\||\s*\[)/u', $body, $m)) {
            $name = trim($m[1]);
            if ($name !== '' && $name !== '-') {
                return $name;
            }
        }
        // HTML: <td>Nama</td> ... <td>IAIN Langsa</td> (ambil teks setelah label Nama)
        if (preg_match('/Nama\s*<\/t[dh]>\s*[^<]*(?:<[^>]+>)*\s*[:\s]*\s*<\/t[dh]>\s*<t[dh][^>]*>([^<]+)<\/t[dh]>/u', $body, $m)) {
            $name = trim(strip_tags($m[1]));
            if ($name !== '' && $name !== '-') {
                return $name;
            }
        }
        // Heading (markdown): #### IAIN Langsa
        if (preg_match('/#+\s*([^\n<]+)/', $body, $m)) {
            $line = trim($m[1]);
            if (stripos($line, 'NPSN TIDAK') === false && stripos($line, 'Data Pendidikan') === false && strlen($line) > 2) {
                return $line;
            }
        }
        return null;
    }

    protected function parseAddressFromBody(string $body): ?string
    {
        // Alamat langsung
        if (preg_match('/\|\s*Alamat\s*\|\s*:\s*\|\s*([^|]*)\s*\|/u', $body, $m)) {
            $addr = trim(preg_replace('/\[[^\]]*\]\([^)]*\)/', '', $m[1]));
            $addr = trim($addr);
            if ($addr !== '' && $addr !== '-') {
                return $addr;
            }
        }
        // Gabung Desa, Kecamatan, Kab, Provinsi
        $parts = [];
        foreach (
            [
                'Desa\/Kelurahan' => 'Desa/Kelurahan',
                'Kecamatan\/Kota[^|]*' => 'Kecamatan',
                'Kab\.\-Kota[^|]*' => 'Kabupaten',
                'Propinsi[^|]*' => 'Provinsi',
            ] as $pattern => $_
        ) {
            if (preg_match('/\|\s*' . $pattern . '\s*\|\s*:\s*\|\s*([^|]*)\s*\|/u', $body, $m)) {
                $v = trim(preg_replace('/\[[^\]]*\]\([^)]*\)/', '', $m[1]));
                if ($v !== '' && $v !== '-') {
                    $parts[] = $v;
                }
            }
        }
        return $parts ? implode(', ', $parts) : null;
    }

    public static function fromConfig(): self
    {
        $baseUrl = config('npsn.referensi_base_url', 'https://referensi.data.kemendikdasmen.go.id');
        $cacheTtl = config('npsn.cache_ttl', 604800);
        $timeout = config('npsn.timeout', 10);
        return new self($baseUrl, $cacheTtl, $timeout);
    }
}
