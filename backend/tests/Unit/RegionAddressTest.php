<?php

namespace Tests\Unit;

use App\Support\RegionAddress;
use PHPUnit\Framework\TestCase;

class RegionAddressTest extends TestCase
{
    public function test_format_joins_structured_parts(): void
    {
        $model = (object) [
            'address' => 'Jl. Melati No. 1',
            'village' => 'Way Halim',
            'sub_district' => 'Kedaton',
            'district' => 'Kota Bandar Lampung',
            'province' => 'Lampung',
            'postal_code' => '35141',
        ];

        $this->assertSame(
            'Jl. Melati No. 1, Way Halim, Kec. Kedaton, Kota Bandar Lampung, Lampung, 35141',
            RegionAddress::format($model)
        );
    }

    public function test_format_returns_null_when_empty(): void
    {
        $this->assertNull(RegionAddress::format((object) [
            'address' => '  ',
            'village' => null,
        ]));
    }

    public function test_only_keeps_known_address_keys(): void
    {
        $this->assertSame(
            ['address' => 'Jl. A', 'village' => 'Sukajaya'],
            RegionAddress::only([
                'address' => 'Jl. A',
                'village' => 'Sukajaya',
                'name' => 'Ignore',
            ])
        );
    }

    public function test_values_reads_model_fields(): void
    {
        $values = RegionAddress::values((object) [
            'address' => 'Jl. A',
            'village' => 'Sukajaya',
        ]);

        $this->assertSame('Jl. A', $values['address']);
        $this->assertSame('Sukajaya', $values['village']);
        $this->assertArrayHasKey('wilayah_village_code', $values);
        $this->assertNull($values['province']);
    }
}
