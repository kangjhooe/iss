<?php

namespace App\Support;

final class RegionAddress
{
    public const NAME_FIELDS = [
        'address',
        'village',
        'sub_district',
        'district',
        'province',
        'postal_code',
    ];

    public const CODE_FIELDS = [
        'wilayah_province_code',
        'wilayah_regency_code',
        'wilayah_district_code',
        'wilayah_village_code',
    ];

    private const REGION_PREFIX_PATTERN = '(?:desa|kelurahan|pekon|kel\.?|ds\.?|kecamatan|kec\.?|kabupaten|kab\.?|kota|provinsi|prov\.?)';

    /**
     * @return list<string>
     */
    public static function fields(): array
    {
        return array_merge(self::NAME_FIELDS, self::CODE_FIELDS);
    }

    /**
     * @return array<string, string>
     */
    public static function rules(): array
    {
        return [
            'address' => 'nullable|string',
            'village' => 'nullable|string|max:255',
            'sub_district' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'wilayah_province_code' => 'nullable|string|max:8',
            'wilayah_regency_code' => 'nullable|string|max:16',
            'wilayah_district_code' => 'nullable|string|max:16',
            'wilayah_village_code' => 'nullable|string|max:20',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function only(array $data): array
    {
        $out = [];
        foreach (self::fields() as $field) {
            if (array_key_exists($field, $data)) {
                $out[$field] = $data[$field];
            }
        }

        return self::sanitize($out);
    }

    /**
     * Remove wilayah names from a legacy full-text street so they are not stored twice.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function sanitize(array $data): array
    {
        if (! array_key_exists('address', $data) || ! is_string($data['address'])) {
            return $data;
        }

        $data['address'] = self::stripTrailingRegions($data['address'], [
            $data['village'] ?? null,
            $data['sub_district'] ?? null,
            $data['district'] ?? null,
            $data['province'] ?? null,
            $data['postal_code'] ?? null,
        ]);

        if ($data['address'] === '') {
            $data['address'] = null;
        }

        return $data;
    }

    public static function format(?object $model): ?string
    {
        if ($model === null) {
            return null;
        }

        $village = self::trimString($model->village ?? null);
        $sub = self::trimString($model->sub_district ?? null);
        $district = self::trimString($model->district ?? null);
        $province = self::trimString($model->province ?? null);
        $postal = self::trimString($model->postal_code ?? null);
        $street = self::stripTrailingRegions(self::trimString($model->address ?? null) ?? '', [
            $village,
            $sub,
            $district,
            $province,
            $postal,
        ]);

        $parts = array_filter([
            $street !== '' ? $street : null,
            $village,
            $sub ? 'Kec. '.$sub : null,
            $district,
            $province,
            $postal,
        ]);

        $joined = implode(', ', $parts);

        return $joined !== '' ? $joined : null;
    }

    /**
     * @return array<string, mixed>
     */
    public static function values(?object $model): array
    {
        $out = [];
        foreach (self::fields() as $field) {
            $out[$field] = $model?->{$field} ?? null;
        }

        return $out;
    }

    /**
     * @param  list<mixed>  $regionNames
     */
    public static function stripTrailingRegions(?string $street, array $regionNames): string
    {
        $result = trim((string) $street);
        $wanted = [];
        foreach ($regionNames as $name) {
            $normalized = self::normalizeRegionSegment($name);
            if ($normalized !== '') {
                $wanted[] = $normalized;
            }
        }

        if ($result === '' || $wanted === []) {
            return $result;
        }

        $parts = preg_split('/\s*,\s*/', $result) ?: [];
        $parts = array_values(array_filter($parts, fn ($part) => trim((string) $part) !== ''));

        while ($parts !== []) {
            $last = self::normalizeRegionSegment($parts[array_key_last($parts)]);
            if ($last === '' || ! self::segmentMatchesRegion($last, $wanted)) {
                break;
            }
            array_pop($parts);
        }

        $result = implode(', ', $parts);

        $changed = true;
        while ($changed) {
            $changed = false;
            $next = self::stripOnePrefixedTrailingName($result, $wanted);
            if ($next !== $result) {
                $result = $next;
                $changed = true;
            }
        }

        return trim($result, " \t\n\r\0\x0B,");
    }

    /**
     * @param  list<string>  $wanted
     */
    private static function stripOnePrefixedTrailingName(string $street, array $wanted): string
    {
        foreach ($wanted as $name) {
            if ($name === '' || preg_match('/^\d+$/', $name) === 1) {
                continue;
            }

            $quoted = preg_quote($name, '/');
            $pattern = '/\s+'.self::REGION_PREFIX_PATTERN.'\s+'.$quoted.'\s*$/iu';
            $next = preg_replace($pattern, '', $street);
            if (is_string($next) && $next !== $street) {
                return rtrim($next);
            }
        }

        return $street;
    }

    /**
     * @param  list<string>  $wanted
     */
    private static function segmentMatchesRegion(string $segment, array $wanted): bool
    {
        foreach ($wanted as $name) {
            if ($segment === $name) {
                return true;
            }
            $segmentCore = self::stripExtraQualifier($segment);
            $nameCore = self::stripExtraQualifier($name);
            if ($segmentCore === $name || $nameCore === $segment || $segmentCore === $nameCore) {
                return true;
            }
        }

        return false;
    }

    private static function stripExtraQualifier(string $text): string
    {
        return trim((string) (preg_replace('/^(kota|kabupaten|kab|adm)\s+/u', '', $text) ?? $text));
    }

    private static function normalizeRegionSegment(mixed $value): string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return '';
        }

        $text = strtolower(trim((string) $value));
        if ($text === '') {
            return '';
        }

        $text = preg_replace('/[.,]/', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        $text = preg_replace('/^'.self::REGION_PREFIX_PATTERN.'\s+/iu', '', $text) ?? $text;

        return trim($text);
    }

    private static function trimString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
