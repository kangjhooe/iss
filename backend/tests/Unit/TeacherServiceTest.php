<?php

namespace Tests\Unit;

use App\Models\Institution;
use App\Models\Teacher;
use App\Services\TeacherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TeacherService $teacherService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teacherService = new TeacherService();
    }

    /**
     * Helper to create a teacher (uses employee table).
     */
    protected function createTeacher(array $overrides = []): Teacher
    {
        $nik = str_pad((string) random_int(0, 9999999999999999), 16, '0', STR_PAD_LEFT);
        return Teacher::create(array_merge([
            'institution_id' => $overrides['institution_id'] ?? Institution::factory()->create()->id,
            'nik' => $nik,
            'type' => 'Guru',
            'name' => 'Test Guru',
            'gender' => 'L',
            'status' => 'Aktif',
        ], $overrides));
    }

    /**
     * Test list teachers with filters.
     */
    public function test_list_teachers_with_filters(): void
    {
        $institution = Institution::factory()->create();
        $this->createTeacher(['institution_id' => $institution->id, 'name' => 'Budi Guru']);
        $this->createTeacher(['institution_id' => $institution->id, 'name' => 'Budi Santoso']);
        $this->createTeacher(['institution_id' => $institution->id, 'name' => 'Ani Wijaya']);

        $result = $this->teacherService->list(
            ['search' => 'Budi'],
            $institution->id,
            15
        );

        $this->assertGreaterThanOrEqual(2, $result->total());
    }

    /**
     * Test list teachers with status filter.
     */
    public function test_list_teachers_with_status_filter(): void
    {
        $institution = Institution::factory()->create();
        $this->createTeacher(['institution_id' => $institution->id, 'status' => 'Aktif']);
        $this->createTeacher(['institution_id' => $institution->id, 'status' => 'Aktif']);
        $this->createTeacher(['institution_id' => $institution->id, 'status' => 'Pensiun']);

        $result = $this->teacherService->list(
            ['status' => 'Aktif'],
            $institution->id,
            15
        );

        $this->assertCount(2, $result->items());
    }

    /**
     * Test create teacher.
     */
    public function test_create_teacher(): void
    {
        $institution = Institution::factory()->create();
        $nik = str_pad((string) random_int(0, 9999999999999999), 16, '0', STR_PAD_LEFT);
        $data = [
            'institution_id' => $institution->id,
            'nik' => $nik,
            'type' => 'Guru',
            'name' => 'Guru Baru',
            'gender' => 'L',
            'status' => 'Aktif',
        ];

        $teacher = $this->teacherService->create($data);

        $this->assertDatabaseHas('employee', [
            'name' => 'Guru Baru',
            'nik' => $nik,
        ]);
        $this->assertEquals('Guru Baru', $teacher->name);
    }

    /**
     * Test find teacher by ID.
     */
    public function test_find_teacher_by_id(): void
    {
        $teacher = $this->createTeacher(['name' => 'Guru Find']);

        $found = $this->teacherService->find($teacher->id);

        $this->assertEquals($teacher->id, $found->id);
        $this->assertEquals('Guru Find', $found->name);
        $this->assertNotNull($found->institution);
    }

    /**
     * Test update teacher.
     */
    public function test_update_teacher(): void
    {
        $teacher = $this->createTeacher(['name' => 'Nama Lama']);

        $updated = $this->teacherService->update($teacher, [
            'name' => 'Nama Baru',
        ]);

        $this->assertEquals('Nama Baru', $updated->name);
        $this->assertDatabaseHas('employee', [
            'id' => $teacher->id,
            'name' => 'Nama Baru',
        ]);
    }

    /**
     * Test delete teacher (soft delete).
     */
    public function test_delete_teacher(): void
    {
        $teacher = $this->createTeacher();

        $result = $this->teacherService->delete($teacher);

        $this->assertTrue($result);
        $this->assertSoftDeleted('employee', [
            'id' => $teacher->id,
        ]);
    }

    /**
     * Test list respects per page limit.
     */
    public function test_list_respects_per_page_limit(): void
    {
        $institution = Institution::factory()->create();
        for ($i = 0; $i < 25; $i++) {
            $this->createTeacher(['institution_id' => $institution->id]);
        }

        $result = $this->teacherService->list([], $institution->id, 10);

        $this->assertCount(10, $result->items());
        $this->assertEquals(25, $result->total());
    }
}
