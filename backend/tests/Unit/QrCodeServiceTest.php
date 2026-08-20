<?php

namespace Tests\Unit;

use App\Services\QrCodeService;
use Tests\TestCase;

class QrCodeServiceTest extends TestCase
{
    public function test_signed_token_round_trips(): void
    {
        $service = app(QrCodeService::class);
        $token = $service->makeToken('student', 42, 7);
        $parsed = $service->parseQrData($token);

        $this->assertNotNull($parsed);
        $this->assertSame('student', $parsed['type']);
        $this->assertSame(42, $parsed['id']);
        $this->assertSame(7, $parsed['institution_id']);
        $this->assertStringStartsWith(QrCodeService::TOKEN_PREFIX.'.', $token);
    }

    public function test_tampered_token_is_rejected(): void
    {
        $service = app(QrCodeService::class);
        $token = $service->makeToken('student', 1, 1);
        $tampered = substr($token, 0, -2).'aa';

        $this->assertNull($service->parseQrData($tampered));
    }

    public function test_unsigned_json_is_rejected(): void
    {
        $service = app(QrCodeService::class);
        $legacy = json_encode([
            'type' => 'student',
            'id' => 1,
            'institution_id' => 1,
            'timestamp' => time(),
        ]);

        $this->assertNull($service->parseQrData($legacy));
    }

    public function test_generate_returns_svg_data_uri(): void
    {
        $service = app(QrCodeService::class);
        $image = $service->generateForStudent(1, 1);

        $this->assertStringStartsWith('data:image/svg+xml;base64,', $image);
    }
}
