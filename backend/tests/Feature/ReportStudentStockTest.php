<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Institution;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentMutation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportStudentStockTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected User $admin;

    protected AcademicYear $year;

    protected SchoolClass $class;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 8, 19, 10, 0, 0, 'Asia/Jakarta'));

        $this->institution = Institution::create([
            'name' => 'SMP Laporan Stok',
            'npsn' => '19191919',
            'level' => 'SMP',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Laporan',
            'email' => 'admin-laporan@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->year = AcademicYear::create([
            'code' => '2026/2027',
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'status' => 'Aktif',
        ]);

        $this->institution->update([
            'active_academic_year_id' => $this->year->id,
        ]);

        $this->class = SchoolClass::create([
            'institution_id' => $this->institution->id,
            'academic_year_id' => $this->year->id,
            'academic_year' => '2026/2027',
            'name' => 'VII A',
            'grade' => 7,
            'status' => 'Aktif',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_august_report_counts_students_already_enrolled_plus_new_ones(): void
    {
        $this->seedStudents(30, Carbon::create(2026, 7, 10));
        $this->seedStudents(3, Carbon::create(2026, 8, 5), 30);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/report/institution?month=8&year=2026');

        $response->assertOk();
        $this->assertSame(33, $response->json('data.students.grade_7.total'));
        $this->assertSame(33, $response->json('data.summary.total_students'));
        $this->assertSame(33, $response->json('data.students_table_detail.totals.jumlah_awal.total'));
        $this->assertSame(0, $response->json('data.students_table_detail.totals.siswa_masuk.total'));
        $this->assertSame(0, $response->json('data.students_table_detail.totals.siswa_keluar.total'));
        $this->assertSame(33, $response->json('data.students_table_detail.totals.jumlah_akhir.total'));
    }

    public function test_july_report_does_not_include_students_added_in_august(): void
    {
        $this->seedStudents(30, Carbon::create(2026, 7, 10));
        $this->seedStudents(3, Carbon::create(2026, 8, 5), 30);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/report/institution?month=7&year=2026');

        $response->assertOk();
        $this->assertSame(30, $response->json('data.students.grade_7.total'));
        $this->assertSame(30, $response->json('data.students_table_detail.totals.jumlah_awal.total'));
        $this->assertSame(30, $response->json('data.students_table_detail.totals.jumlah_akhir.total'));
    }

    public function test_excel_or_manual_students_stay_in_jumlah_awal_mutations_use_masuk(): void
    {
        $this->seedStudents(30, Carbon::create(2026, 7, 10));
        $incoming = $this->seedStudents(2, Carbon::create(2026, 8, 5), 30);

        foreach ($incoming as $student) {
            StudentMutation::create([
                'origin_school_name' => 'SMP Luar',
                'target_institution_id' => $this->institution->id,
                'student_id' => $student->id,
                'student_grade' => 7,
                'student_gender' => $student->gender,
                'initiated_by' => 'target',
                'requested_by' => $this->admin->id,
                'approved_by' => $this->admin->id,
                'status' => 'approved',
                'approved_at' => Carbon::create(2026, 8, 8),
            ]);
        }

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/report/institution?month=8&year=2026');

        $response->assertOk();
        $detail = $response->json('data.students_table_detail.totals');
        $this->assertSame(30, $detail['jumlah_awal']['total']);
        $this->assertSame(2, $detail['siswa_masuk']['total']);
        $this->assertSame(0, $detail['siswa_keluar']['total']);
        $this->assertSame(32, $detail['jumlah_akhir']['total']);
        $this->assertSame(
            $detail['jumlah_awal']['total'] + $detail['siswa_masuk']['total'] - $detail['siswa_keluar']['total'],
            $detail['jumlah_akhir']['total']
        );
    }

    /**
     * @return list<Student>
     */
    private function seedStudents(int $count, Carbon $createdAt, int $nisOffset = 0): array
    {
        $students = [];
        for ($i = 1; $i <= $count; $i++) {
            $student = Student::create([
                'institution_id' => $this->institution->id,
                'academic_year_id' => $this->year->id,
                'academic_year' => '2026/2027',
                'class_id' => $this->class->id,
                'class' => '7A',
                'nis' => (string) (1000 + $nisOffset + $i),
                'name' => 'Siswa '.($nisOffset + $i),
                'gender' => $i % 2 === 0 ? 'P' : 'L',
                'status' => 'Aktif',
            ]);
            $student->timestamps = false;
            $student->created_at = $createdAt;
            $student->updated_at = $createdAt;
            $student->save();
            $students[] = $student;
        }

        return $students;
    }
}
