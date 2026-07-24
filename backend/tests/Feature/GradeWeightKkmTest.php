<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Employee;
use App\Models\Grade;
use App\Models\GradeWeight;
use App\Models\Institution;
use App\Models\LessonSchedule;
use App\Models\Permission;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectKkm;
use App\Models\User;
use App\Services\GradeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GradeWeightKkmTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected User $admin;

    protected User $teacherUser;

    protected Employee $teacher;

    protected AcademicYear $year;

    protected Semester $semester;

    protected SchoolClass $class;

    protected Subject $subject;

    protected Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'SMP Nilai KKM',
            'npsn' => '70707070',
            'level' => 'SMP',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Nilai',
            'email' => 'admin-nilai@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->teacherUser = User::create([
            'name' => 'Guru Mapel',
            'email' => 'guru-mapel@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'teacher',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->teacher = Employee::create([
            'institution_id' => $this->institution->id,
            'type' => 'Guru',
            'nik' => '3175010101900001',
            'name' => 'Guru Mapel',
            'gender' => 'L',
            'email' => $this->teacherUser->email,
            'status' => 'Aktif',
        ]);

        $gradePerm = Permission::firstOrCreate(['key' => 'grade_book'], ['label' => 'Buku Nilai']);
        $this->teacherUser->permissions()->attach($gradePerm->id);

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
            'code' => 'IPA',
            'name' => 'IPA',
            'is_active' => true,
        ]);

        $this->student = Student::create([
            'institution_id' => $this->institution->id,
            'academic_year_id' => $this->year->id,
            'academic_year' => '2025/2026',
            'semester_id' => $this->semester->id,
            'class_id' => $this->class->id,
            'nis' => '2001',
            'name' => 'Siswa Nilai',
            'gender' => 'L',
            'status' => 'Aktif',
        ]);

        LessonSchedule::create([
            'institution_id' => $this->institution->id,
            'semester_id' => $this->semester->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'employee_id' => $this->teacher->id,
            'day_of_week' => 1,
            'period' => 1,
            'start_time' => '07:00',
            'end_time' => '07:40',
        ]);
    }

    public function test_admin_can_upsert_kkm_and_predicates_work(): void
    {
        Sanctum::actingAs($this->admin);

        $res = $this->postJson('/api/v1/grades/kkm', [
            'semester_id' => $this->semester->id,
            'subject_id' => $this->subject->id,
            'grade' => 7,
            'kkm' => 75,
            'class_id' => $this->class->id,
        ]);

        $res->assertOk()
            ->assertJsonPath('data.kkm', 75);

        $this->assertDatabaseHas('subject_kkms', [
            'institution_id' => $this->institution->id,
            'subject_id' => $this->subject->id,
            'grade' => 7,
            'semester_id' => $this->semester->id,
        ]);

        $this->assertSame('D', SubjectKkm::predicateFromScore(74, 75));
        $this->assertSame('C', SubjectKkm::predicateFromScore(75, 75));
        $this->assertTrue(SubjectKkm::isTuntas(75, 75));
        $this->assertFalse(SubjectKkm::isTuntas(74, 75));
    }

    public function test_weight_sum_must_be_100(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/grades/weights', [
            'semester_id' => $this->semester->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'weight_penilaian' => 40,
            'weight_uts' => 30,
            'weight_uas' => 20,
        ])->assertStatus(422);
    }

    public function test_upsert_weights_recalculates_nilai_akhir(): void
    {
        Sanctum::actingAs($this->admin);

        Grade::create([
            'institution_id' => $this->institution->id,
            'academic_year_id' => $this->year->id,
            'semester_id' => $this->semester->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'student_id' => $this->student->id,
            'grade_type' => 'penilaian_1',
            'value' => 80,
        ]);
        Grade::create([
            'institution_id' => $this->institution->id,
            'academic_year_id' => $this->year->id,
            'semester_id' => $this->semester->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'student_id' => $this->student->id,
            'grade_type' => Grade::TYPE_UTS,
            'value' => 70,
        ]);
        Grade::create([
            'institution_id' => $this->institution->id,
            'academic_year_id' => $this->year->id,
            'semester_id' => $this->semester->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'student_id' => $this->student->id,
            'grade_type' => Grade::TYPE_UAS,
            'value' => 90,
        ]);

        // Default 40/30/30 → 80*0.4 + 70*0.3 + 90*0.3 = 80
        $expectedDefault = GradeService::computeNilaiAkhir(80, 70, 90);
        $this->assertSame(80.0, $expectedDefault);

        $res = $this->postJson('/api/v1/grades/weights', [
            'semester_id' => $this->semester->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'weight_penilaian' => 50,
            'weight_uts' => 20,
            'weight_uas' => 30,
        ]);

        $res->assertOk()
            ->assertJsonPath('data.recalculated_students', 1);

        // 80*0.5 + 70*0.2 + 90*0.3 = 40 + 14 + 27 = 81
        $nilaiAkhir = Grade::query()
            ->where('student_id', $this->student->id)
            ->where('grade_type', Grade::TYPE_NILAI_AKHIR)
            ->value('value');

        $this->assertEquals(81.0, (float) $nilaiAkhir);
        $this->assertDatabaseHas('grade_weights', [
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'semester_id' => $this->semester->id,
            'weight_penilaian' => 50,
            'weight_uts' => 20,
            'weight_uas' => 30,
        ]);
    }

    public function test_teacher_without_schedule_cannot_set_weights(): void
    {
        $outsider = User::create([
            'name' => 'Guru Lain',
            'email' => 'guru-lain-nilai@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'teacher',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        Employee::create([
            'institution_id' => $this->institution->id,
            'type' => 'Guru',
            'nik' => '3175010101900099',
            'name' => 'Guru Lain',
            'gender' => 'P',
            'email' => $outsider->email,
            'status' => 'Aktif',
        ]);
        $outsider->permissions()->attach(
            Permission::firstOrCreate(['key' => 'grade_book'], ['label' => 'Buku Nilai'])->id
        );

        Sanctum::actingAs($outsider);

        $this->postJson('/api/v1/grades/weights', [
            'semester_id' => $this->semester->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'weight_penilaian' => 40,
            'weight_uts' => 30,
            'weight_uas' => 30,
        ])->assertForbidden();
    }

    public function test_teacher_who_teaches_pair_can_set_weights_and_kkm(): void
    {
        Sanctum::actingAs($this->teacherUser);

        $this->postJson('/api/v1/grades/weights', [
            'semester_id' => $this->semester->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'weight_penilaian' => 40,
            'weight_uts' => 30,
            'weight_uas' => 30,
        ])->assertOk();

        $this->postJson('/api/v1/grades/kkm', [
            'semester_id' => $this->semester->id,
            'subject_id' => $this->subject->id,
            'grade' => 7,
            'kkm' => 70,
        ])->assertOk()
            ->assertJsonPath('data.kkm', 70);

        $this->assertInstanceOf(GradeWeight::class, GradeWeight::first());
    }
}
