<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->institution = Institution::factory()->create([
            'level' => 'SMP',
        ]);
        $this->user = User::factory()->create([
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Test user can list classes.
     */
    public function test_user_can_list_classes(): void
    {
        SchoolClass::factory()->count(3)->create([
            'institution_id' => $this->institution->id,
        ]);

        $token = $this->user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/class');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'grade',
                    ],
                ],
            ]);
    }

    /**
     * Test user can create class.
     */
    public function test_user_can_create_class(): void
    {
        $token = $this->user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/class', [
                'institution_id' => $this->institution->id,
                'name' => '7A',
                'grade' => 7,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('school_class', [
            'name' => '7A',
            'grade' => 7,
        ]);
    }

    /**
     * Test user can get class detail.
     */
    public function test_user_can_get_class_detail(): void
    {
        $class = SchoolClass::factory()->create([
            'institution_id' => $this->institution->id,
        ]);

        $token = $this->user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/v1/class/{$class->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                ],
            ]);
    }

    /**
     * Test user can update class.
     */
    public function test_user_can_update_class(): void
    {
        $class = SchoolClass::factory()->create([
            'institution_id' => $this->institution->id,
            'name' => '7A',
        ]);

        $token = $this->user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/v1/class/{$class->id}", [
                'name' => '7B',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('school_class', [
            'id' => $class->id,
            'name' => '7B',
        ]);
    }

    /**
     * Test user can delete class.
     */
    public function test_user_can_delete_class(): void
    {
        $class = SchoolClass::factory()->create([
            'institution_id' => $this->institution->id,
        ]);

        $token = $this->user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/v1/class/{$class->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('school_class', [
            'id' => $class->id,
        ]);
    }
}
