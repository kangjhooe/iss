<?php

namespace Tests\Unit;

use App\Models\Institution;
use Tests\TestCase;

class InstitutionMutasiLevelGroupTest extends TestCase
{
    public function test_mts_is_previous_jenjang_for_ma(): void
    {
        $ma = new Institution(['level' => 'MA']);
        $mts = new Institution(['level' => 'MTs']);

        $this->assertSame('atas', Institution::getMutasiLevelGroup('MA'));
        $this->assertSame('menengah', Institution::getMutasiLevelGroup('MTs'));
        $this->assertSame('menengah', Institution::getMutasiLevelGroup('mts'));
        $this->assertTrue($ma->canPullAlumniFrom($mts));
        $this->assertFalse($mts->canPullAlumniFrom($ma));
        $this->assertFalse($ma->canMutateWith($mts));
        $this->assertSame('2', $mts->jenjang_code);
        $this->assertSame('3', $ma->jenjang_code);
    }
}
