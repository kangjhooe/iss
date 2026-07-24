<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Achievement;
use App\Models\AchievementType;
use App\Models\Employee;
use App\Models\Institution;
use App\Models\Permission;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Models\Violation;
use App\Models\ViolationType;
use App\Models\WaliNote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WaliKelasHubTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $waliUser;
    protected Employee $waliEmployee;
    protected SchoolClass $class;
    protected Student $student;
    protected User $otherTeacher;
    protected SchoolClass $otherClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'SMP Uji Wali',
            'npsn' => '99887766',
            'is_active' => true,
        ]);

        $year = AcademicYear::create([
            'institution_id' => $this->institution->id,
            'name' => '2025/2026',
            'code' => '2526',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);
        $semester = Semester::create([
            'institution_id' => $this->institution->id,
            'academic_year_id' => $year->id,
            'name' => 'Ganjil',
            'code' => '1',
            'start_date' => '2025-07-01',
            'end_date' => '2025-12-31',
            'is_active' => true,
        ]);
        $this->institution->update([
            'active_academic_year_id' => $year->id,
            'active_semester_id' => $semester->id,
        ]);

        $this->waliUser = User::create([
            'name' => 'Guru Wali',
            'email' => 'wali@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'teacher',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
        ]);
        $this->waliEmployee = Employee::create([
            'institution_id' => $this->institution->id,
            'nik' => '1111222233334444',
            'type' => 'Guru',
            'name' => 'Guru Wali',
            'gender' => 'L',
            'email' => $this->waliUser->email,
            'status' => 'Aktif',
        ]);

        foreach (['bk_report', 'grade_book', 'teaching_journal', 'report'] as $key) {
            $perm = Permission::firstOrCreate(['key' => $key], ['label' => $key]);
            $this->waliUser->permissions()->attach($perm->id);
        }

        $this->class = SchoolClass::create([
            'institution_id' => $this->institution->id,
            'academic_year_id' => $year->id,
            'academic_year' => '2025/2026',
            'name' => 'VII-A',
            'grade' => 7,
            'teacher_id' => $this->waliEmployee->id,
            'status' => 'Aktif',
        ]);

        $this->student = Student::create([
            'institution_id' => $this->institution->id,
            'academic_year_id' => $year->id,
            'academic_year' => '2025/2026',
            'semester_id' => $semester->id,
            'class_id' => $this->class->id,
            'nis' => '1001',
            'nisn' => '0011223344',
            'name' => 'Siswa Wali',
            'gender' => 'L',
            'status' => 'Aktif',
            'phone' => '0811111111',
            'guardian_name' => 'Orang Tua',
            'guardian_phone' => '0822222222',
        ]);

        $this->otherTeacher = User::create([
            'name' => 'Guru Lain',
            'email' => 'guru-lain@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'teacher',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
        ]);
        $otherEmp = Employee::create([
            'institution_id' => $this->institution->id,
            'nik' => '5555666677778888',
            'type' => 'Guru',
            'name' => 'Guru Lain',
            'gender' => 'P',
            'email' => $this->otherTeacher->email,
            'status' => 'Aktif',
        ]);
        $this->otherClass = SchoolClass::create([
            'institution_id' => $this->institution->id,
            'academic_year_id' => $year->id,
            'academic_year' => '2025/2026',
            'name' => 'VII-B',
            'grade' => 7,
            'teacher_id' => $otherEmp->id,
            'status' => 'Aktif',
        ]);
    }

    public function test_wali_can_view_own_student_profile_but_not_other_class(): void
    {
        Sanctum::actingAs($this->waliUser);

        $ok = $this->getJson("/api/v1/teacher/wali/classes/{$this->class->id}/students/{$this->student->id}");
        $ok->assertOk();
        $ok->assertJsonPath('data.guardian_phone', '0822222222');

        $forbidden = $this->getJson("/api/v1/teacher/wali/classes/{$this->otherClass->id}/students/{$this->student->id}");
        $forbidden->assertForbidden();
    }

    public function test_wali_dashboard_and_notes_crud(): void
    {
        Sanctum::actingAs($this->waliUser);

        $dash = $this->getJson("/api/v1/teacher/wali/classes/{$this->class->id}/dashboard");
        $dash->assertOk();
        $dash->assertJsonPath('data.students.total', 1);
        $dash->assertJsonStructure([
            'data' => [
                'grades_incomplete',
                'grades_incomplete_students',
                'bk_high_scores' => ['count', 'threshold', 'top', 'students'],
            ],
        ]);

        $attendance = $this->getJson("/api/v1/teacher/wali/classes/{$this->class->id}/attendance-summary?period=week");
        $attendance->assertOk();
        $attendance->assertJsonPath('data.period', 'week');
        $attendance->assertJsonStructure([
            'data' => ['totals', 'repeat_alpha', 'rows', 'alpha_threshold'],
        ]);

        $grades = $this->getJson("/api/v1/teacher/wali/classes/{$this->class->id}/grades-overview");
        $grades->assertOk();
        $grades->assertJsonStructure([
            'data' => ['summary', 'rows', 'subjects'],
        ]);

        $profile = $this->getJson("/api/v1/teacher/wali/classes/{$this->class->id}/students/{$this->student->id}");
        $profile->assertOk();
        $profile->assertJsonStructure([
            'data' => [
                'snapshot' => ['attendance', 'bk', 'grades', 'recent_violations', 'recent_achievements', 'mutations'],
            ],
        ]);

        $create = $this->postJson(
            "/api/v1/teacher/wali/classes/{$this->class->id}/students/{$this->student->id}/notes",
            ['body' => 'Catatan awal wali']
        );
        $create->assertCreated();
        $noteId = $create->json('data.id');

        $list = $this->getJson("/api/v1/teacher/wali/classes/{$this->class->id}/students/{$this->student->id}/notes");
        $list->assertOk();
        $this->assertCount(1, $list->json('data'));

        $this->putJson(
            "/api/v1/teacher/wali/classes/{$this->class->id}/students/{$this->student->id}/notes/{$noteId}",
            ['body' => 'Catatan diubah']
        )->assertOk();

        $this->assertDatabaseHas('wali_notes', ['id' => $noteId, 'body' => 'Catatan diubah']);
    }

    public function test_wali_can_propose_violation_and_achievement_pending(): void
    {
        Sanctum::actingAs($this->waliUser);

        $vType = ViolationType::create([
            'institution_id' => $this->institution->id,
            'name' => 'Terlambat',
            'code' => 'TL',
            'category' => 'ringan',
            'point_weight' => 5,
            'is_active' => true,
        ]);
        $aType = AchievementType::create([
            'institution_id' => $this->institution->id,
            'name' => 'Juara Kelas',
            'point_value' => 10,
            'is_active' => true,
        ]);

        $vio = $this->postJson('/api/v1/teacher/wali/violations', [
            'class_id' => $this->class->id,
            'student_id' => $this->student->id,
            'violation_type_id' => $vType->id,
            'violation_date' => now()->toDateString(),
            'description' => 'Datang terlambat',
        ]);
        $vio->assertCreated();
        $vio->assertJsonPath('data.status', Violation::STATUS_PENDING);

        $ach = $this->postJson('/api/v1/teacher/wali/achievements', [
            'class_id' => $this->class->id,
            'student_id' => $this->student->id,
            'achievement_type_id' => $aType->id,
            'achievement_date' => now()->toDateString(),
            'notes' => 'Juara 1',
        ]);
        $ach->assertCreated();
        $ach->assertJsonPath('data.status', Achievement::STATUS_PENDING);
    }

    public function test_wali_can_propose_external_mutation_pending(): void
    {
        Sanctum::actingAs($this->waliUser);

        $res = $this->postJson('/api/v1/teacher/wali/mutations', [
            'class_id' => $this->class->id,
            'student_id' => $this->student->id,
            'target_npsn' => '12345678',
            'target_school_name' => 'SMP Tujuan Luar',
            'external' => true,
            'notes' => 'Pindah karena orang tua',
        ]);
        $res->assertCreated();
        $res->assertJsonPath('data.status', 'pending');
        $res->assertJsonPath('data.source', 'wali');
        $res->assertJsonPath('data.is_from_wali', true);
    }

    public function test_other_teacher_cannot_access_wali_export(): void
    {
        Sanctum::actingAs($this->otherTeacher);

        $this->get("/api/v1/teacher/wali/classes/{$this->class->id}/export/roster")
            ->assertForbidden();
    }
}
