<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Employee;
use App\Models\Institution;
use App\Models\Permission;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Subject;
use App\Models\TeachingJournal;
use App\Models\User;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QrAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected Institution $otherInstitution;

    protected User $admin;

    protected User $otherAdmin;

    protected AcademicYear $year;

    protected Semester $semester;

    protected SchoolClass $class;

    protected Subject $subject;

    protected Employee $teacher;

    protected Student $student;

    protected Student $inactiveStudent;

    protected TeachingJournal $journal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'SMP QR Absensi',
            'npsn' => '90909090',
            'level' => 'SMP',
            'is_active' => true,
        ]);

        $this->otherInstitution = Institution::create([
            'name' => 'SMP Lain',
            'npsn' => '90909091',
            'level' => 'SMP',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin QR',
            'email' => 'admin-qr@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->otherAdmin = User::create([
            'name' => 'Admin Lain',
            'email' => 'admin-lain@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->otherInstitution->id,
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
            'code' => 'MTK',
            'name' => 'Matematika',
            'is_active' => true,
        ]);

        $this->teacher = Employee::create([
            'institution_id' => $this->institution->id,
            'type' => 'Guru',
            'nik' => '3175010101900099',
            'nip' => '198001012006041001',
            'name' => 'Guru QR',
            'gender' => 'L',
            'status' => 'Aktif',
        ]);

        $this->student = Student::create([
            'institution_id' => $this->institution->id,
            'academic_year_id' => $this->year->id,
            'class_id' => $this->class->id,
            'nis' => '260001',
            'name' => 'Siswa Aktif',
            'gender' => 'L',
            'status' => 'Aktif',
        ]);

        $this->inactiveStudent = Student::create([
            'institution_id' => $this->institution->id,
            'academic_year_id' => $this->year->id,
            'class_id' => $this->class->id,
            'nis' => '260002',
            'name' => 'Siswa Nonaktif',
            'gender' => 'P',
            'status' => 'Tidak Aktif',
        ]);

        Student::create([
            'institution_id' => $this->institution->id,
            'academic_year_id' => $this->year->id,
            'class_id' => $this->class->id,
            'nis' => '260003',
            'name' => 'Siswa Kedua',
            'gender' => 'P',
            'status' => 'Aktif',
        ]);

        $this->journal = TeachingJournal::create([
            'institution_id' => $this->institution->id,
            'semester_id' => $this->semester->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'employee_id' => $this->teacher->id,
            'journal_date' => now()->toDateString(),
            'period' => 1,
            'material_taught' => 'Pecahan',
        ]);
    }

    public function test_admin_can_generate_single_student_qr(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson("/api/v1/qr-attendance/student/{$this->student->id}/generate");

        $response->assertOk()
            ->assertJsonPath('data.student_name', 'Siswa Aktif')
            ->assertJsonPath('data.nis', '260001');

        $this->assertStringStartsWith('data:image/svg+xml;base64,', $response->json('data.qr_code'));
    }

    public function test_inactive_student_qr_is_rejected(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson("/api/v1/qr-attendance/student/{$this->inactiveStudent->id}/generate")
            ->assertStatus(422);
    }

    public function test_cannot_generate_qr_for_other_institution_student(): void
    {
        Sanctum::actingAs($this->otherAdmin);

        $this->getJson("/api/v1/qr-attendance/student/{$this->student->id}/generate")
            ->assertStatus(403);
    }

    public function test_bulk_generate_class_skips_inactive_students(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/qr-attendance/students/generate-bulk', [
            'class_id' => $this->class->id,
        ]);

        $response->assertOk()->assertJsonPath('data.count', 2);
        $names = collect($response->json('data.cards'))->pluck('name')->all();
        $this->assertContains('Siswa Aktif', $names);
        $this->assertContains('Siswa Kedua', $names);
        $this->assertNotContains('Siswa Nonaktif', $names);
    }

    public function test_bulk_generate_requires_class_or_ids(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/qr-attendance/students/generate-bulk', [])
            ->assertStatus(422);
    }

    public function test_bulk_generate_rejects_foreign_class(): void
    {
        Sanctum::actingAs($this->otherAdmin);

        $this->postJson('/api/v1/qr-attendance/students/generate-bulk', [
            'class_id' => $this->class->id,
        ])->assertStatus(404);
    }

    public function test_print_pdf_returns_pdf(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->get('/api/v1/qr-attendance/students/print-pdf?class_id='.$this->class->id);

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_scan_records_student_attendance(): void
    {
        Sanctum::actingAs($this->admin);
        $token = app(QrCodeService::class)->makeToken('student', $this->student->id, $this->institution->id);

        $response = $this->postJson('/api/v1/qr-attendance/scan', [
            'qr_data' => $token,
            'attendance_type' => 'student',
            'teaching_journal_id' => $this->journal->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('already_recorded', false)
            ->assertJsonPath('data.student_name', 'Siswa Aktif');

        $this->assertDatabaseHas('student_attendances', [
            'student_id' => $this->student->id,
            'teaching_journal_id' => $this->journal->id,
            'status' => 'hadir',
        ]);
    }

    public function test_scan_duplicate_returns_already_recorded(): void
    {
        Sanctum::actingAs($this->admin);
        StudentAttendance::create([
            'institution_id' => $this->institution->id,
            'teaching_journal_id' => $this->journal->id,
            'student_id' => $this->student->id,
            'status' => 'hadir',
            'notes' => 'manual',
        ]);

        $token = app(QrCodeService::class)->makeToken('student', $this->student->id, $this->institution->id);

        $this->postJson('/api/v1/qr-attendance/scan', [
            'qr_data' => $token,
            'attendance_type' => 'student',
            'teaching_journal_id' => $this->journal->id,
        ])->assertOk()->assertJsonPath('already_recorded', true);
    }

    public function test_scan_rejects_unsigned_json_and_inactive_student(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/qr-attendance/scan', [
            'qr_data' => json_encode([
                'type' => 'student',
                'id' => $this->student->id,
                'institution_id' => $this->institution->id,
                'timestamp' => time(),
            ]),
            'attendance_type' => 'student',
            'teaching_journal_id' => $this->journal->id,
        ])->assertStatus(400);

        $token = app(QrCodeService::class)->makeToken('student', $this->inactiveStudent->id, $this->institution->id);
        $this->postJson('/api/v1/qr-attendance/scan', [
            'qr_data' => $token,
            'attendance_type' => 'student',
            'teaching_journal_id' => $this->journal->id,
        ])->assertStatus(400);
    }

    public function test_scan_rejects_foreign_institution_token(): void
    {
        Sanctum::actingAs($this->admin);
        $token = app(QrCodeService::class)->makeToken('student', $this->student->id, $this->otherInstitution->id);

        $this->postJson('/api/v1/qr-attendance/scan', [
            'qr_data' => $token,
            'attendance_type' => 'student',
            'teaching_journal_id' => $this->journal->id,
        ])->assertStatus(400);
    }

    public function test_teacher_with_journal_module_can_scan(): void
    {
        $teacherUser = User::create([
            'name' => 'Guru Scan',
            'email' => 'guru-scan@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'teacher',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $perm = Permission::firstOrCreate(['key' => 'teaching_journal'], ['label' => 'Jurnal Mengajar']);
        $teacherUser->permissions()->attach($perm->id);

        Sanctum::actingAs($teacherUser);
        $token = app(QrCodeService::class)->makeToken('student', $this->student->id, $this->institution->id);

        $this->postJson('/api/v1/qr-attendance/scan', [
            'qr_data' => $token,
            'attendance_type' => 'student',
            'teaching_journal_id' => $this->journal->id,
        ])->assertCreated();
    }

    public function test_employee_scan_records_daily_attendance(): void
    {
        Sanctum::actingAs($this->admin);
        $token = app(QrCodeService::class)->makeToken('employee', $this->teacher->id, $this->institution->id);

        $this->postJson('/api/v1/qr-attendance/scan', [
            'qr_data' => $token,
            'attendance_type' => 'employee',
            'date' => now()->toDateString(),
        ])->assertCreated()->assertJsonPath('data.employee_name', 'Guru QR');

        $this->assertDatabaseHas('employee_attendances', [
            'employee_id' => $this->teacher->id,
            'institution_id' => $this->institution->id,
            'status' => 'hadir',
        ]);
    }
}
