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
                'birth_place' => 'Jakarta',
                'birth_date' => '1988-03-12',
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
            'birth_place' => 'Jakarta',
            'birth_date' => '1988-03-12',
        ]);
    }

    public function test_create_teacher_requires_birth_place_and_date(): void
    {
        $token = $this->user->createToken('auth_token')->plainTextToken;
        $nik = str_pad((string) random_int(0, 9999999999999999), 16, '0', STR_PAD_LEFT);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/employee', [
                'institution_id' => $this->institution->id,
                'nik' => $nik,
                'type' => 'Guru',
                'name' => 'Guru Tanpa Lahir',
                'gender' => 'L',
                'status' => 'Aktif',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['birth_place', 'birth_date']);
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

    public function test_user_can_force_delete_trashed_teacher(): void
    {
        $employee = $this->createEmployee(['nik' => '3201999999999999']);
        $employee->delete();

        $token = $this->user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson("/api/v1/employee/{$employee->id}/force");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('employee', ['id' => $employee->id]);

        $replacement = $this->createEmployee(['nik' => '3201999999999999']);
        $this->assertDatabaseHas('employee', [
            'id' => $replacement->id,
            'nik' => '3201999999999999',
        ]);
    }

    public function test_force_delete_rejects_active_teacher(): void
    {
        $employee = $this->createEmployee();

        $token = $this->user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson("/api/v1/employee/{$employee->id}/force");

        $response->assertStatus(422);
        $this->assertDatabaseHas('employee', ['id' => $employee->id]);
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

    /**
     * Export returns full biodata fields (not the trimmed list select).
     */
    public function test_user_can_export_teachers_with_full_fields(): void
    {
        $this->createEmployee([
            'name' => 'Guru Export',
            'birth_place' => 'Bandung',
            'birth_date' => '1990-05-20',
            'address' => 'Jl. Merdeka 1',
            'phone' => '08123456789',
            'email' => 'guru.export@example.com',
            'religion' => 'Islam',
            'join_date' => '2015-07-01',
        ]);

        $token = $this->user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/employee/export');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Guru Export',
                'birth_place' => 'Bandung',
                'birth_date' => '1990-05-20',
                'address' => 'Jl. Merdeka 1',
                'phone' => '08123456789',
                'email' => 'guru.export@example.com',
                'religion' => 'Islam',
                'join_date' => '2015-07-01',
            ]);
    }

    public function test_admin_can_search_other_school_employee_by_nik_with_minimal_fields(): void
    {
        $otherSchool = Institution::factory()->create(['name' => 'SMP Asal']);
        $employee = $this->createEmployee([
            'institution_id' => $otherSchool->id,
            'nik' => '3201010101010099',
            'name' => 'Guru Tamu',
            'gender' => 'P',
            'type' => 'Guru',
        ]);

        $token = $this->user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/employee/search?nik=3201010101010099');

        $response->assertOk()
            ->assertJsonPath('data.id', $employee->id)
            ->assertJsonPath('data.name', 'Guru Tamu')
            ->assertJsonPath('data.nik', '3201010101010099')
            ->assertJsonPath('data.institution.id', $otherSchool->id)
            ->assertJsonPath('data.institution.name', 'SMP Asal')
            ->assertJsonMissingPath('data.gender')
            ->assertJsonMissingPath('data.type')
            ->assertJsonMissingPath('data.institution.npsn');
    }

    public function test_teacher_cannot_search_employee_by_nik(): void
    {
        $teacher = User::factory()->create([
            'institution_id' => $this->institution->id,
            'role' => 'teacher',
            'email_verified_at' => now(),
        ]);
        $this->createEmployee(['nik' => '3201010101010088']);

        $token = $teacher->createToken('auth_token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/employee/search?nik=3201010101010088')
            ->assertStatus(403);
    }
}
