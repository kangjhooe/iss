<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitutionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test admin can list all institutions.
     */
    public function test_admin_can_list_all_institutions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('auth_token')->plainTextToken;

        Institution::factory()->count(5)->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/institution');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'npsn',
                    ],
                ],
            ]);
    }

    /**
     * Test non-admin cannot list all institutions.
     */
    public function test_non_admin_cannot_list_all_institutions(): void
    {
        $user = User::factory()->create(['role' => 'institution_admin']);
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/institution');

        $response->assertStatus(403);
    }

    /**
     * Test user can get their own institution.
     */
    public function test_user_can_get_own_institution(): void
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->create([
            'institution_id' => $institution->id,
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/institution/my');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'npsn',
                ],
            ]);
    }

    /**
     * Test admin can create institution.
     */
    public function test_admin_can_create_institution(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/institution', [
                'npsn' => '87654321',
                'name' => 'Sekolah Baru',
                'level' => 'sd',
                'type' => 'negeri',
                'phone' => '081234567890',
                'email' => 'sekolah@test.com',
                'address' => 'Jl. Test No. 123',
                'is_active' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'name',
                    'npsn',
                ],
            ]);

        $this->assertDatabaseHas('institution', [
            'npsn' => '87654321',
            'name' => 'Sekolah Baru',
        ]);
    }

    /**
     * Test user can update their own institution.
     */
    public function test_user_can_update_own_institution(): void
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->create([
            'institution_id' => $institution->id,
            'role' => 'institution_admin',
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/v1/institution/' . $institution->id, [
                'name' => 'Sekolah Updated',
                'phone' => '081234567890',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'Sekolah Updated',
                ],
            ]);

        $this->assertDatabaseHas('institution', [
            'id' => $institution->id,
            'name' => 'Sekolah Updated',
        ]);
    }
}
