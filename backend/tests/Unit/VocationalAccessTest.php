<?php

namespace Tests\Unit;

use App\Support\VocationalAccess;
use Tests\TestCase;

class VocationalAccessTest extends TestCase
{
    public function test_vocational_levels(): void
    {
        $this->assertTrue(VocationalAccess::isVocationalLevel('SMK'));
        $this->assertTrue(VocationalAccess::isVocationalLevel('MAK'));
        $this->assertFalse(VocationalAccess::isVocationalLevel('SMA'));
        $this->assertFalse(VocationalAccess::isVocationalLevel('MA'));
        $this->assertFalse(VocationalAccess::isVocationalLevel('SMP'));
        $this->assertFalse(VocationalAccess::isVocationalLevel(null));
        $this->assertFalse(VocationalAccess::isVocationalLevel(''));
    }
}
