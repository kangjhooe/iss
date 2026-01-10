<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentTest extends TestCase
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
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Test admin can list students.
     */
    public function test_admin_can_list_students(): void
    {
        Student::factory()->count(5)->create([
            'institution_id' => $this->institution->id,
        ]);

        $token = $this->user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/student');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'nis',
                        'name',
                        'gender',
                        'class',
                        'status',
                    ],
                ],
            ]);
    }

    /**
     * Test user can create student.
     */
    public function test_user_can_create_student(): void
    {
        $token = $this->user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/student', [
                'institution_id' => $this->institution->id,
                'nis' => '12345',
                'nisn' => '1234567890',
                'name' => 'Test Student',
                'gender' => 'L',
                'class' => '7A',
                'status' => 'Aktif',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'nis',
                    'name',
                ],
            ]);

        $this->assertDatabaseHas('student', [
            'nis' => '12345',
            'name' => 'Test Student',
        ]);
    }

    /**
     * Test user can get student detail.
     */
    public function test_user_can_get_student_detail(): void
    {
        $student = Student::factory()->create([
            'institution_id' => $this->institution->id,
        ]);

        $token = $this->user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/v1/student/{$student->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'nis',
                    'name',
                ],
            ]);
    }

    /**
     * Test user can update student.
     */
    public function test_user_can_update_student(): void
    {
        $student = Student::factory()->create([
            'institution_id' => $this->institution->id,
            'name' => 'Old Name',
        ]);

        $token = $this->user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/v1/student/{$student->id}", [
                'name' => 'New Name',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('student', [
            'id' => $student->id,
            'name' => 'New Name',
        ]);
    }

    /**
     * Test user can delete student.
     */
    public function test_user_can_delete_student(): void
    {
        $student = Student::factory()->create([
            'institution_id' => $this->institution->id,
        ]);

        $token = $this->user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/v1/student/{$student->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('student', [
            'id' => $student->id,
        ]);
    }

    /**
     * Test user cannot access other institution's students.
     */
    public function test_user_cannot_access_other_institution_students(): void
    {
        $otherInstitution = Institution::factory()->create();
        $otherStudent = Student::factory()->create([
            'institution_id' => $otherInstitution->id,
        ]);

        $token = $this->user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/v1/student/{$otherStudent->id}");

        $response->assertStatus(403);
    }

    /**
     * Test student list with search filter.
     */
    public function test_student_list_with_search_filter(): void
    {
        Student::factory()->create([
            'institution_id' => $this->institution->id,
            'name' => 'John Doe',
            'nis' => '12345',
        ]);

        Student::factory()->create([
            'institution_id' => $this->institution->id,
            'name' => 'Jane Smith',
            'nis' => '67890',
        ]);

        $token = $this->user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/student?search=John');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }
}
