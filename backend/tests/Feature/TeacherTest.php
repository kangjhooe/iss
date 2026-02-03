<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::factory()->create();
        $this->user = User::factory()->create([
            'institution_id' => $this->institution->id,
            'role' => 'institution_admin',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Helper to create a valid employee (teacher) for the institution.
     */
    protected function createEmployee(array $overrides = []): Employee
    {
        $nik = str_pad((string) random_int(0, 9999999999999999), 16, '0', STR_PAD_LEFT);
        return Employee::create(array_merge([
            'institution_id' => $this->institution->id,
            'nik' => $nik,
            'type' => 'Guru',
            'name' => 'Test Guru',
            'gender' => 'L',
            'status' => 'Aktif',
        ], $overrides));
    }

    /**
     * Test user can list employees (teachers).
     */
    public function test_user_can_list_teachers(): void
    {
        $this->createEmployee(['name' => 'Guru Satu']);
        $this->createEmployee(['name' => 'Guru Dua']);

        $token = $this->user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/employee');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'gender',
                        'type',
                        'status',
                    ],
                ],
            ]);
    }

    /**
     * Test user can create employee (teacher).
     */
    public function test_user_can_create_teacher(): void
    {
        $token = $this->user->createToken('auth_token')->plainTextToken;
        $nik = str_pad((string) random_int(0, 9999999999999999), 16, '0', STR_PAD_LEFT);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/employee', [
                'institution_id' => $this->institution->id,
                'nik' => $nik,
                'type' => 'Guru',
                'name' => 'Guru Baru',
                'gender' => 'L',
                'status' => 'Aktif',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'nik',
                    'type',
                ],
            ]);

        $this->assertDatabaseHas('employee', [
            'name' => 'Guru Baru',
            'nik' => $nik,
        ]);
    }

    /**
     * Test user can get employee (teacher) detail.
     */
    public function test_user_can_get_teacher_detail(): void
    {
        $employee = $this->createEmployee(['name' => 'Guru Detail']);

        $token = $this->user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/v1/employee/{$employee->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'nik',
                    'type',
                ],
            ])
            ->assertJsonPath('data.name', 'Guru Detail');
    }

    /**
     * Test user can update employee (teacher).
     */
    public function test_user_can_update_teacher(): void
    {
        $employee = $this->createEmployee(['name' => 'Nama Lama']);

        $token = $this->user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/v1/employee/{$employee->id}", [
                'name' => 'Nama Baru',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('employee', [
            'id' => $employee->id,
            'name' => 'Nama Baru',
        ]);
    }

    /**
     * Test user can delete employee (teacher).
     */
    public function test_user_can_delete_teacher(): void
    {
        $employee = $this->createEmployee();

        $token = $this->user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/v1/employee/{$employee->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('employee', [
            'id' => $employee->id,
        ]);
    }

    /**
     * Test employee list with search filter.
     */
    public function test_teacher_list_with_search_filter(): void
    {
        $this->createEmployee(['name' => 'Budi Santoso', 'nik' => '1111111111111111']);
        $this->createEmployee(['name' => 'Ani Wijaya', 'nik' => '2222222222222222']);

        $token = $this->user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/employee?search=Budi');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(1, count($data));
    }
}
