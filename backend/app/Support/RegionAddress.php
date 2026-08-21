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

        return $out;
    }

    public static function format(?object $model): ?string
    {
        if ($model === null) {
            return null;
        }

        $parts = array_filter([
            self::trimString($model->address ?? null),
            self::trimString($model->village ?? null),
            ($sub = self::trimString($model->sub_district ?? null)) ? 'Kec. '.$sub : null,
            self::trimString($model->district ?? null),
            self::trimString($model->province ?? null),
            self::trimString($model->postal_code ?? null),
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

    private static function trimString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
