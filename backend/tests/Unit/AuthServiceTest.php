<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = new AuthService();
    }

    /**
     * Test successful login.
     */
    public function test_login_success(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
            'email_verified_at' => now(),
        ]);

        $result = $this->authService->login('test@example.com', 'Password123!');

        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('refresh_token', $result);
        $this->assertEquals($user->id, $result['user']->id);
    }

    /**
     * Test login with invalid credentials.
     */
    public function test_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
            'email_verified_at' => now(),
        ]);

        $this->expectException(ValidationException::class);

        $this->authService->login('test@example.com', 'wrongpassword');
    }

    /**
     * Test login with unverified email.
     */
    public function test_login_with_unverified_email(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
            'email_verified_at' => null,
        ]);

        $this->expectException(ValidationException::class);

        $this->authService->login('test@example.com', 'Password123!');
    }

    /**
     * Test account lockout after failed attempts.
     */
    public function test_account_lockout_after_failed_attempts(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
            'email_verified_at' => now(),
            'failed_login_attempts' => 0,
        ]);

        // Try to login with wrong password 5 times
        for ($i = 0; $i < 5; $i++) {
            try {
                $this->authService->login('test@example.com', 'wrongpassword');
            } catch (ValidationException $e) {
                // Expected
            }
        }

        $user->refresh();
        $this->assertTrue($user->isLocked());
    }
}
