<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Employee;
use App\Models\Grade;
use App\Models\Institution;
use App\Models\LessonSchedule;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Subject;
use App\Models\TeachingJournal;
use App\Models\User;
use App\Services\TeacherTodaySessionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeacherTodaySessionPdfTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected User $teacherUser;

    protected Employee $teacher;

    protected AcademicYear $year;

    protected Semester $semester;

    protected SchoolClass $class;

    protected Subject $subject;

    protected Student $student;

    protected LessonSchedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        $today = Carbon::today();

        $this->institution = Institution::create([
            'name' => 'SMP Jurnal Cetak',
            'npsn' => '80808080',
            'level' => 'SMP',
            'is_active' => true,
        ]);

        $this->teacherUser = User::create([
            'name' => 'Guru Jurnal',
            'email' => 'guru-jurnal@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'teacher',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->teacher = Employee::create([
            'institution_id' => $this->institution->id,
            'type' => 'Guru',
            'nik' => '3175010101900088',
            'nip' => '198001012006041088',
            'name' => 'Guru Jurnal',
            'gender' => 'L',
            'email' => $this->teacherUser->email,
            'status' => 'Aktif',
        ]);

        $this->year = AcademicYear::create([
            'code' => '2026/2027',
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'status' => 'Aktif',
        ]);

        $this->semester = Semester::create([
            'academic_year_id' => $this->year->id,
            'name' => 'Ganjil',
            'order' => 1,
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'status' => 'Aktif',
        ]);

        $this->institution->update([
            'active_academic_year_id' => $this->year->id,
            'active_semester_id' => $this->semester->id,
        ]);

        $this->class = SchoolClass::create([
            'institution_id' => $this->institution->id,
            'academic_year_id' => $this->year->id,
            'academic_year' => '2026/2027',
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
            'academic_year' => '2026/2027',
            'semester_id' => $this->semester->id,
            'class_id' => $this->class->id,
            'nis' => '270001',
            'name' => 'Siswa Jurnal',
            'gender' => 'L',
            'status' => 'Aktif',
        ]);

        $this->schedule = LessonSchedule::create([
            'institution_id' => $this->institution->id,
            'semester_id' => $this->semester->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'employee_id' => $this->teacher->id,
            'day_of_week' => $today->dayOfWeekIso,
            'period' => 1,
            'start_time' => '07:00',
            'end_time' => '07:40',
        ]);

        $journal = TeachingJournal::create([
            'institution_id' => $this->institution->id,
            'semester_id' => $this->semester->id,
            'lesson_schedule_id' => $this->schedule->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'employee_id' => $this->teacher->id,
            'journal_date' => $today->toDateString(),
            'period' => 1,
            'penilaian_index' => 1,
            'material_taught' => 'Sistem gerak pada manusia',
            'attendance_notes' => 'Semua hadir',
        ]);

        StudentAttendance::create([
            'institution_id' => $this->institution->id,
            'teaching_journal_id' => $journal->id,
            'student_id' => $this->student->id,
            'status' => StudentAttendance::STATUS_HADIR,
        ]);

        Grade::create([
            'institution_id' => $this->institution->id,
            'academic_year_id' => $this->year->id,
            'semester_id' => $this->semester->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'student_id' => $this->student->id,
            'employee_id' => $this->teacher->id,
            'grade_type' => 'penilaian_1',
            'value' => 88,
        ]);
    }

    public function test_print_documents_includes_journal_attendance_and_daily_grade(): void
    {
        $data = app(TeacherTodaySessionService::class)->printDocuments(
            (int) $this->institution->id,
            (int) $this->teacher->id,
            (int) $this->semester->id,
            Carbon::today()->toDateString()
        );

        $this->assertCount(1, $data['sessions']);
        $session = $data['sessions'][0];
        $this->assertSame('Sistem gerak pada manusia', $session['journal']['material_taught']);
        $this->assertSame(1, $session['attendance']['counts']['hadir']);
        $this->assertSame([1], $session['grade_columns']);
        $this->assertSame('Siswa Jurnal', $session['students'][0]['name']);
        $this->assertEquals(88, $session['students'][0]['grades'][1]);
        $this->assertSame('Hadir', $session['students'][0]['status_label']);
    }

    public function test_teacher_can_export_session_pdf(): void
    {
        Sanctum::actingAs($this->teacherUser);

        $sessions = $this->getJson('/api/v1/teacher/today-sessions?date='.Carbon::today()->toDateString());
        $sessions->assertOk();
        $key = $sessions->json('data.sessions.0.key');
        $this->assertNotEmpty($key);

        $response = $this->get('/api/v1/teacher/today-sessions/export-pdf?date='.Carbon::today()->toDateString().'&session_key='.$key);

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_unknown_session_key_returns_not_found(): void
    {
        Sanctum::actingAs($this->teacherUser);

        $this->getJson('/api/v1/teacher/today-sessions/export-pdf?date='.Carbon::today()->toDateString().'&session_key=999-888')
            ->assertStatus(404);
    }

    public function test_empty_day_returns_unprocessable(): void
    {
        Sanctum::actingAs($this->teacherUser);

        $emptyDate = Carbon::today()->addDays(1)->toDateString();

        $this->getJson('/api/v1/teacher/today-sessions/export-pdf?date='.$emptyDate)
            ->assertStatus(422);
    }

    public function test_non_teacher_cannot_export_pdf(): void
    {
        $admin = User::create([
            'name' => 'Admin Institusi',
            'email' => 'admin-jurnal@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/teacher/today-sessions/export-pdf')
            ->assertStatus(403);
    }
}
