<?php

namespace Tests\Unit;

use App\Models\Institution;
use App\Models\Student;
use App\Services\StudentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StudentService $studentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->studentService = new StudentService();
    }

    /**
     * Test list students with filters.
     */
    public function test_list_students_with_filters(): void
    {
        $institution = Institution::factory()->create();
        
        Student::factory()->count(5)->create([
            'institution_id' => $institution->id,
            'name' => 'John Doe',
        ]);

        Student::factory()->count(3)->create([
            'institution_id' => $institution->id,
            'name' => 'Jane Smith',
        ]);

        $result = $this->studentService->list(
            ['search' => 'John'],
            $institution->id,
            15
        );

        $this->assertCount(5, $result->items());
        $this->assertEquals(5, $result->total());
    }

    /**
     * Test list students with class filter.
     */
    public function test_list_students_with_class_filter(): void
    {
        $institution = Institution::factory()->create();
        
        Student::factory()->count(3)->create([
            'institution_id' => $institution->id,
            'class' => '7A',
        ]);

        Student::factory()->count(2)->create([
            'institution_id' => $institution->id,
            'class' => '8A',
        ]);

        $result = $this->studentService->list(
            ['class' => '7A'],
            $institution->id,
            15
        );

        $this->assertCount(3, $result->items());
    }

    /**
     * Test create student.
     */
    public function test_create_student(): void
    {
        $institution = Institution::factory()->create();
        
        $data = [
            'institution_id' => $institution->id,
            'nis' => '12345',
            'nisn' => '1234567890',
            'name' => 'Test Student',
            'gender' => 'L',
            'class' => '7A',
            'status' => 'Aktif',
        ];

        $student = $this->studentService->create($data);

        $this->assertDatabaseHas('student', [
            'nis' => '12345',
            'name' => 'Test Student',
        ]);

        $this->assertEquals('Test Student', $student->name);
    }

    /**
     * Test find student by ID.
     */
    public function test_find_student_by_id(): void
    {
        $institution = Institution::factory()->create();
        $student = Student::factory()->create([
            'institution_id' => $institution->id,
        ]);

        $found = $this->studentService->find($student->id);

        $this->assertEquals($student->id, $found->id);
        $this->assertNotNull($found->institution);
    }

    /**
     * Test update student.
     */
    public function test_update_student(): void
    {
        $institution = Institution::factory()->create();
        $student = Student::factory()->create([
            'institution_id' => $institution->id,
            'name' => 'Old Name',
        ]);

        $updated = $this->studentService->update($student, [
            'name' => 'New Name',
        ]);

        $this->assertEquals('New Name', $updated->name);
        $this->assertDatabaseHas('student', [
            'id' => $student->id,
            'name' => 'New Name',
        ]);
    }

    /**
     * Test delete student (soft delete).
     */
    public function test_delete_student(): void
    {
        $institution = Institution::factory()->create();
        $student = Student::factory()->create([
            'institution_id' => $institution->id,
        ]);

        $this->studentService->delete($student);

        $this->assertSoftDeleted('student', [
            'id' => $student->id,
        ]);
    }

    /**
     * Test list respects per page limit.
     */
    public function test_list_respects_per_page_limit(): void
    {
        $institution = Institution::factory()->create();
        Student::factory()->count(25)->create([
            'institution_id' => $institution->id,
        ]);

        $result = $this->studentService->list([], $institution->id, 10);

        $this->assertCount(10, $result->items());
        $this->assertEquals(25, $result->total());
    }

    /**
     * Test list respects max per page limit (100).
     */
    public function test_list_respects_max_per_page_limit(): void
    {
        $institution = Institution::factory()->create();
        Student::factory()->count(150)->create([
            'institution_id' => $institution->id,
        ]);

        $result = $this->studentService->list([], $institution->id, 200);

        $this->assertCount(100, $result->items());
    }
}
