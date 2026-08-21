<?php

namespace Tests\Unit;

use App\Support\InstitutionModuleVisibility;
use Tests\TestCase;

class InstitutionModuleVisibilityTest extends TestCase
{
    public function test_normalize_keys(): void
    {
        $this->assertSame(
            ['library', 'uks'],
            InstitutionModuleVisibility::normalize([' library ', 'uks', 'library', '', 12])
        );
        $this->assertSame([], InstitutionModuleVisibility::normalize(null));
        $this->assertSame(['finance'], InstitutionModuleVisibility::normalize('["finance"]'));
    }
}
