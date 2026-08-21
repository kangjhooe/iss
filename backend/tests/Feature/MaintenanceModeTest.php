<?php

namespace Tests\Feature;

use App\Http\Middleware\AddTokenFromCookie;
use App\Models\AppBranding;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MaintenanceModeTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected User $admin;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        AppBranding::create([
            'maintenance_mode' => true,
            'maintenance_message' => 'Sedang pemeliharaan.',
        ]);

        $this->institution = Institution::create([
            'name' => 'SMP Maintenance',
            'npsn' => '12121212',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Sekolah',
            'email' => 'admin-maintenance@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super-maintenance@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'super_admin',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
    }

    public function test_institution_admin_is_blocked_during_maintenance(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/employee')
            ->assertStatus(503)
            ->assertJsonPath('maintenance', true);
    }

    public function test_arbitrary_impersonator_cookie_does_not_bypass_maintenance(): void
    {
        Sanctum::actingAs($this->admin);

        $this->withCookie(AddTokenFromCookie::COOKIE_IMPERSONATOR, 'not-a-real-token')
            ->getJson('/api/v1/employee')
            ->assertStatus(503)
            ->assertJsonPath('maintenance', true);
    }

    public function test_super_admin_can_access_during_maintenance(): void
    {
        Sanctum::actingAs($this->superAdmin);

        $this->getJson('/api/v1/employee')->assertOk();
    }

    public function test_valid_super_admin_impersonator_cookie_bypasses_maintenance(): void
    {
        Sanctum::actingAs($this->admin);

        $impersonatorToken = $this->superAdmin->createToken('auth_token')->plainTextToken;

        $this->withCookie(AddTokenFromCookie::COOKIE_IMPERSONATOR, $impersonatorToken)
            ->getJson('/api/v1/employee')
            ->assertOk();
    }
}
