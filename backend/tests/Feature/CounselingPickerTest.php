<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\CounselingSession;
use App\Models\Institution;
use App\Models\Permission;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CounselingPickerTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected User $admin;

    protected User $teacher;

    protected User $studentUser;

    protected AcademicYear $year;

    protected Semester $semester;

    protected SchoolClass $class;

    protected Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'SMA Uji BK',
            'npsn' => '11223344',
            'level' => 'SMA',
            'is_active' => true,
        ]);

        $this->year = AcademicYear::create([
            'institution_id' => $this->institution->id,
            'name' => '2025/2026',
            'code' => '2526',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);
        $this->semester = Semester::create([
            'institution_id' => $this->institution->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Ganjil',
            'code' => '1',
            'start_date' => '2025-07-01',
            'end_date' => '2025-12-31',
            'is_active' => true,
        ]);
        $this->institution->update([
            'active_academic_year_id' => $this->year->id,
            'active_semester_id' => $this->semester->id,
        ]);

        $this->admin = User::create([
            'name' => 'Admin BK',
            'email' => 'admin-bk@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->teacher = User::create([
            'name' => 'Guru BK',
            'email' => 'guru-bk@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'teacher',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $counselingPerm = Permission::firstOrCreate(['key' => 'counseling'], ['label' => 'Konseling']);
        $this->teacher->permissions()->attach($counselingPerm->id);

        $this->studentUser = User::create([
            'name' => 'Akun Siswa',
            'email' => 'siswa-login@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'student',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->class = SchoolClass::create([
            'institution_id' => $this->institution->id,
            'academic_year_id' => $this->year->id,
            'academic_year' => '2025/2026',
            'name' => 'X-BK',
            'grade' => 10,
            'status' => 'Aktif',
        ]);

        $this->student = Student::create([
            'institution_id' => $this->institution->id,
            'name' => 'Siswa Konseling',
            'nis' => '4101',
            'nisn' => '0041010041',
            'nik' => '3201010101010099',
            'gender' => 'L',
            'status' => 'Aktif',
            'class_id' => $this->class->id,
        ]);
    }

    public function test_counselors_list_excludes_students(): void
    {
        Sanctum::actingAs($this->teacher);

        $list = $this->getJson('/api/v1/counseling/counselors')
            ->assertOk()
            ->json('data');

        $ids = collect($list)->pluck('id')->all();
        $this->assertContains($this->teacher->id, $ids);
        $this->assertNotContains($this->studentUser->id, $ids);
        $this->assertNotContains($this->admin->id, $ids);
        foreach ($list as $row) {
            $this->assertContains($row['role'], User::COUNSELOR_ROLES);
        }
    }

    public function test_bk_teacher_can_search_students_without_student_module(): void
    {
        Sanctum::actingAs($this->teacher);

        $this->getJson('/api/v1/student')->assertForbidden();

        $classes = $this->getJson('/api/v1/counseling/classes-lite')->assertOk()->json('data');
        $this->assertCount(1, $classes);
        $this->assertSame('X-BK', $classes[0]['name']);

        $this->getJson('/api/v1/counseling/students-lite')
            ->assertOk()
            ->assertJsonPath('data', []);

        $byClass = $this->getJson('/api/v1/counseling/students-lite?class_id='.$this->class->id)
            ->assertOk()
            ->json('data');
        $this->assertCount(1, $byClass);
        $this->assertSame('Siswa Konseling', $byClass[0]['name']);

        $byName = $this->getJson('/api/v1/counseling/students-lite?q=Konseling')
            ->assertOk()
            ->json('data');
        $this->assertCount(1, $byName);
        $this->assertSame($this->student->id, $byName[0]['id']);
    }

    public function test_cannot_create_session_with_student_as_counselor(): void
    {
        Sanctum::actingAs($this->teacher);

        $this->postJson('/api/v1/counseling', [
            'student_id' => $this->student->id,
            'counselor_id' => $this->studentUser->id,
            'session_date' => now()->toDateString(),
            'status' => 'jadwal',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('counselor_id');

        $this->postJson('/api/v1/counseling', [
            'student_id' => $this->student->id,
            'counselor_id' => $this->teacher->id,
            'session_date' => now()->toDateString(),
            'status' => 'jadwal',
            'summary' => 'Sesi uji',
        ])->assertCreated();

        $this->assertSame(1, CounselingSession::count());
        $this->assertSame($this->teacher->id, (int) CounselingSession::first()->counselor_id);
    }

    public function test_stats_and_upcoming_endpoints_return_counts(): void
    {
        Sanctum::actingAs($this->teacher);

        $type = \App\Models\CounselingType::create([
            'institution_id' => $this->institution->id,
            'name' => 'Pribadi',
            'code' => 'PR',
            'is_active' => true,
        ]);

        CounselingSession::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'counselor_id' => $this->teacher->id,
            'counseling_type_id' => $type->id,
            'session_date' => now()->toDateString(),
            'status' => 'selesai',
            'academic_year_id' => $this->year->id,
            'semester_id' => $this->semester->id,
            'class_id' => $this->class->id,
        ]);
        CounselingSession::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'counselor_id' => $this->teacher->id,
            'counseling_type_id' => $type->id,
            'session_date' => now()->addDay()->toDateString(),
            'status' => 'jadwal',
            'academic_year_id' => $this->year->id,
            'semester_id' => $this->semester->id,
            'class_id' => $this->class->id,
        ]);

        $stats = $this->getJson('/api/v1/counseling/stats')->assertOk()->json('data');
        $this->assertGreaterThanOrEqual(1, $stats['total_this_month']);
        $this->assertSame(1, $stats['completed_this_month']);
        $this->assertSame(1, $stats['students_this_month']);
        $this->assertSame(1, $stats['upcoming_count']);
        $this->assertSame(1, $stats['open_jadwal']);
        $this->assertSame(0, $stats['overdue_count']);
        $this->assertNotEmpty($stats['by_type']);
        $this->assertSame('Pribadi', $stats['by_type'][0]['name']);

        $upcoming = $this->getJson('/api/v1/counseling/upcoming')->assertOk()->json('data');
        $this->assertCount(1, $upcoming);
        $this->assertSame($this->student->name, $upcoming[0]['student']['name']);
    }
}
