<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Employee;
use App\Models\Institution;
use App\Models\LessonSchedule;
use App\Models\LessonScheduleTemplate;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LessonScheduleTemplateMultiTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected User $admin;

    protected Employee $teacher;

    protected AcademicYear $year;

    protected Semester $semester;

    protected SchoolClass $class;

    protected Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'SMP Template Multi',
            'npsn' => '60606060',
            'level' => 'SMP',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Jadwal',
            'email' => 'admin-jadwal@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->teacher = Employee::create([
            'institution_id' => $this->institution->id,
            'type' => 'Guru',
            'nik' => '3175010101910001',
            'name' => 'Guru Jadwal',
            'gender' => 'L',
            'email' => 'guru-jadwal@example.com',
            'status' => 'Aktif',
        ]);

        $this->year = AcademicYear::create([
            'code' => '2025/2026',
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'status' => 'Aktif',
        ]);

        $this->semester = Semester::create([
            'academic_year_id' => $this->year->id,
            'name' => 'Ganjil',
            'order' => 1,
            'start_date' => '2025-07-01',
            'end_date' => '2025-12-31',
            'status' => 'Aktif',
        ]);

        $this->institution->update([
            'active_academic_year_id' => $this->year->id,
            'active_semester_id' => $this->semester->id,
        ]);

        $this->class = SchoolClass::create([
            'institution_id' => $this->institution->id,
            'academic_year_id' => $this->year->id,
            'academic_year' => '2025/2026',
            'semester_id' => $this->semester->id,
            'name' => 'VII A',
            'grade' => 7,
            'status' => 'Aktif',
        ]);

        $this->subject = Subject::create([
            'institution_id' => $this->institution->id,
            'code' => 'MTK',
            'name' => 'Matematika',
            'is_active' => true,
        ]);
    }

    /** @return array<int, array{day_of_week:int,periods:int,is_holiday:bool}> */
    private function fiveDayTemplateDays(int $periods = 8): array
    {
        $days = [];
        for ($d = 1; $d <= 7; $d++) {
            $weekend = $d >= 6;
            $days[] = [
                'day_of_week' => $d,
                'periods' => $weekend ? 0 : $periods,
                'is_holiday' => $weekend,
            ];
        }

        return $days;
    }

    public function test_can_create_multiple_named_templates_per_semester(): void
    {
        Sanctum::actingAs($this->admin);

        $first = $this->postJson('/api/v1/lesson-schedule-templates', [
            'semester_id' => $this->semester->id,
            'name' => '5 Hari',
            'days' => $this->fiveDayTemplateDays(8),
        ]);
        $first->assertCreated()->assertJsonPath('data.name', '5 Hari');

        $second = $this->postJson('/api/v1/lesson-schedule-templates', [
            'semester_id' => $this->semester->id,
            'name' => '6 Hari',
            'days' => $this->fiveDayTemplateDays(6),
        ]);
        $second->assertCreated()->assertJsonPath('data.name', '6 Hari');

        $list = $this->getJson('/api/v1/lesson-schedule-templates?semester_id=' . $this->semester->id);
        $list->assertOk();
        $this->assertCount(2, $list->json('data'));
    }

    public function test_duplicate_template_name_in_same_semester_is_rejected(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/lesson-schedule-templates', [
            'semester_id' => $this->semester->id,
            'name' => 'Default',
            'days' => $this->fiveDayTemplateDays(),
        ])->assertCreated();

        $this->postJson('/api/v1/lesson-schedule-templates', [
            'semester_id' => $this->semester->id,
            'name' => 'Default',
            'days' => $this->fiveDayTemplateDays(6),
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Nama template sudah dipakai di semester ini.');
    }

    public function test_assign_requires_prune_when_slots_out_of_bounds(): void
    {
        Sanctum::actingAs($this->admin);

        $short = $this->postJson('/api/v1/lesson-schedule-templates', [
            'semester_id' => $this->semester->id,
            'name' => 'Pendek 4 JP',
            'days' => $this->fiveDayTemplateDays(4),
        ])->json('data');

        LessonSchedule::create([
            'institution_id' => $this->institution->id,
            'semester_id' => $this->semester->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'employee_id' => $this->teacher->id,
            'day_of_week' => 1,
            'period' => 6,
            'start_time' => '12:00',
            'end_time' => '12:40',
        ]);

        $blocked = $this->putJson("/api/v1/lesson-schedules/by-class/{$this->class->id}/template", [
            'lesson_schedule_template_id' => $short['id'],
            'prune_out_of_bounds' => false,
        ]);
        $blocked->assertStatus(409)
            ->assertJsonPath('requires_prune', true)
            ->assertJsonPath('data.affected_count', 1);

        $this->assertNull($this->class->fresh()->lesson_schedule_template_id);

        $pruned = $this->putJson("/api/v1/lesson-schedules/by-class/{$this->class->id}/template", [
            'lesson_schedule_template_id' => $short['id'],
            'prune_out_of_bounds' => true,
        ]);
        $pruned->assertOk()
            ->assertJsonPath('data.lesson_schedule_template_id', $short['id'])
            ->assertJsonPath('data.pruned_count', 1);

        $this->assertSame(0, LessonSchedule::where('class_id', $this->class->id)->count());
        $this->assertSame($short['id'], $this->class->fresh()->lesson_schedule_template_id);
    }

    public function test_delete_template_clears_class_assignment(): void
    {
        Sanctum::actingAs($this->admin);

        $templateId = $this->postJson('/api/v1/lesson-schedule-templates', [
            'semester_id' => $this->semester->id,
            'name' => 'Untuk Dihapus',
            'days' => $this->fiveDayTemplateDays(),
        ])->json('data.id');

        $this->putJson("/api/v1/lesson-schedules/by-class/{$this->class->id}/template", [
            'lesson_schedule_template_id' => $templateId,
        ])->assertOk();

        $this->deleteJson("/api/v1/lesson-schedule-templates/{$templateId}")
            ->assertOk();

        $this->assertDatabaseMissing('lesson_schedule_templates', ['id' => $templateId]);
        $this->assertNull($this->class->fresh()->lesson_schedule_template_id);
    }

    public function test_preview_reports_out_of_bound_slots(): void
    {
        Sanctum::actingAs($this->admin);

        $templateId = $this->postJson('/api/v1/lesson-schedule-templates', [
            'semester_id' => $this->semester->id,
            'name' => '3 JP',
            'days' => $this->fiveDayTemplateDays(3),
        ])->json('data.id');

        LessonSchedule::create([
            'institution_id' => $this->institution->id,
            'semester_id' => $this->semester->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'employee_id' => $this->teacher->id,
            'day_of_week' => 2,
            'period' => 5,
            'start_time' => '10:00',
            'end_time' => '10:40',
        ]);

        $preview = $this->getJson(
            "/api/v1/lesson-schedules/by-class/{$this->class->id}/template/preview?lesson_schedule_template_id={$templateId}"
        );

        $preview->assertOk()
            ->assertJsonPath('data.requires_prune', true)
            ->assertJsonPath('data.affected_count', 1)
            ->assertJsonPath('data.target_template_id', $templateId);
    }
}
