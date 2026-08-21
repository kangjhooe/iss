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
        $this->studentService = new StudentService(new \App\Services\StudentAccountService());
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

    public function test_force_delete_student_from_trash(): void
    {
        $institution = Institution::factory()->create();
        $student = Student::factory()->create([
            'institution_id' => $institution->id,
            'nisn' => '9988776655',
        ]);

        $this->studentService->delete($student);
        $trashed = Student::withTrashed()->find($student->id);

        $this->studentService->forceDelete($trashed);

        $this->assertDatabaseMissing('student', [
            'id' => $student->id,
        ]);
    }

    public function test_force_delete_rejects_student_not_in_trash(): void
    {
        $institution = Institution::factory()->create();
        $student = Student::factory()->create([
            'institution_id' => $institution->id,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->studentService->forceDelete($student);
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

    public function test_list_filters_students_without_class(): void
    {
        $institution = Institution::factory()->create();

        Student::factory()->count(2)->create([
            'institution_id' => $institution->id,
            'class_id' => null,
            'class' => null,
        ]);
        Student::factory()->count(3)->create([
            'institution_id' => $institution->id,
            'class' => '7A',
        ]);

        $result = $this->studentService->list(
            ['class_id' => '__none__'],
            $institution->id,
            15
        );

        $this->assertEquals(2, $result->total());
    }

    public function test_list_without_class_filter_ignores_empty_class_string_if_class_id_is_set(): void
    {
        $institution = Institution::factory()->create();
        $class = \App\Models\SchoolClass::create([
            'institution_id' => $institution->id,
            'academic_year' => '2025/2026',
            'name' => '11 IPA 1',
            'grade' => 11,
            'status' => 'Aktif',
        ]);

        Student::factory()->create([
            'institution_id' => $institution->id,
            'class_id' => $class->id,
            'class' => null,
            'status' => 'Aktif',
        ]);
        $unassigned = Student::factory()->create([
            'institution_id' => $institution->id,
            'class_id' => null,
            'class' => null,
            'status' => 'Aktif',
        ]);

        $result = $this->studentService->list(
            ['class_id' => '__none__'],
            $institution->id,
            15
        );

        $this->assertEquals(1, $result->total());
        $this->assertEquals($unassigned->id, $result->items()[0]->id);
    }

    public function test_list_without_class_filter_includes_students_whose_class_was_deleted(): void
    {
        $institution = Institution::factory()->create();
        $class = \App\Models\SchoolClass::create([
            'institution_id' => $institution->id,
            'academic_year' => '2025/2026',
            'name' => 'Kelas Lama',
            'grade' => 11,
            'status' => 'Aktif',
        ]);
        $student = Student::factory()->create([
            'institution_id' => $institution->id,
            'class_id' => $class->id,
            'class' => null,
            'status' => 'Aktif',
        ]);
        $class->delete();

        $result = $this->studentService->list(
            ['class_id' => '__none__'],
            $institution->id,
            15
        );

        $this->assertEquals(1, $result->total());
        $this->assertEquals($student->id, $result->items()[0]->id);
    }

    public function test_list_resolves_class_name_from_linked_class(): void
    {
        $institution = Institution::factory()->create();
        $class = \App\Models\SchoolClass::create([
            'institution_id' => $institution->id,
            'academic_year' => '2025/2026',
            'name' => '11 IPA 1',
            'grade' => 11,
            'status' => 'Aktif',
        ]);
        Student::factory()->create([
            'institution_id' => $institution->id,
            'class_id' => $class->id,
            'class' => null,
        ]);

        $result = $this->studentService->list(
            ['class_id' => $class->id],
            $institution->id,
            15
        );

        $this->assertEquals(1, $result->total());
        $this->assertSame('11 IPA 1', $result->items()[0]->resolvedClassName());
    }

    public function test_list_filters_students_without_tingkat(): void
    {
        $institution = Institution::factory()->create();

        Student::factory()->count(2)->create([
            'institution_id' => $institution->id,
            'tingkat' => null,
        ]);
        Student::factory()->count(3)->create([
            'institution_id' => $institution->id,
            'tingkat' => 7,
        ]);

        $result = $this->studentService->list(
            ['tingkat' => '__none__'],
            $institution->id,
            15
        );

        $this->assertEquals(2, $result->total());
    }

    public function test_list_sorts_by_name_asc(): void
    {
        $institution = Institution::factory()->create();

        Student::factory()->create([
            'institution_id' => $institution->id,
            'name' => 'Zaid',
        ]);
        Student::factory()->create([
            'institution_id' => $institution->id,
            'name' => 'Ahmad',
        ]);

        $result = $this->studentService->list(
            ['sort_by' => 'name', 'sort_dir' => 'asc'],
            $institution->id,
            15
        );

        $names = collect($result->items())->pluck('name')->all();
        $this->assertSame(['Ahmad', 'Zaid'], $names);
    }

    public function test_list_for_export_returns_all_rows(): void
    {
        $institution = Institution::factory()->create();
        Student::factory()->count(12)->create([
            'institution_id' => $institution->id,
        ]);

        $result = $this->studentService->listForExport([], $institution->id);

        $this->assertCount(12, $result);
    }
}
