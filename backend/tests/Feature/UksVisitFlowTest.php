<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Institution;
use App\Models\Permission;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Student;
use App\Models\UksVisit;
use App\Models\UksVisitType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UksVisitFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected User $admin;

    protected User $uksStaff;

    protected AcademicYear $year;

    protected Semester $semester;

    protected SchoolClass $class;

    protected Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'SMA Uji UKS',
            'npsn' => '88776655',
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
            'name' => 'Admin UKS',
            'email' => 'admin-uks@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->uksStaff = User::create([
            'name' => 'Petugas UKS',
            'email' => 'petugas-uks@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'teacher',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $uksPerm = Permission::firstOrCreate(['key' => 'uks'], ['label' => 'UKS']);
        $this->uksStaff->permissions()->attach($uksPerm->id);

        $this->class = SchoolClass::create([
            'institution_id' => $this->institution->id,
            'academic_year_id' => $this->year->id,
            'academic_year' => '2025/2026',
            'name' => 'X-A',
            'grade' => 10,
            'status' => 'Aktif',
        ]);

        $this->student = Student::create([
            'institution_id' => $this->institution->id,
            'name' => 'Siswa Sakit',
            'nis' => '3001',
            'nisn' => '0030010030',
            'nik' => '3201010101010008',
            'gender' => 'L',
            'status' => 'Aktif',
            'class_id' => $this->class->id,
        ]);
    }

    public function test_uks_staff_can_record_visit_without_student_module_and_see_it_in_list(): void
    {
        Sanctum::actingAs($this->uksStaff);

        $this->getJson('/api/v1/student')->assertForbidden();

        $classes = $this->getJson('/api/v1/uks/visits/classes-lite')->assertOk()->json('data');
        $this->assertCount(1, $classes);
        $this->assertSame('X-A', $classes[0]['name']);

        $students = $this->getJson('/api/v1/uks/visits/students-lite?class_id='.$this->class->id)
            ->assertOk()
            ->json('data');
        $this->assertCount(1, $students);
        $this->assertSame('Siswa Sakit', $students[0]['name']);

        $create = $this->postJson('/api/v1/uks/visits', [
            'student_id' => $this->student->id,
            'visit_date' => '2026-08-22',
            'status' => 'selesai',
            'complaint' => 'Pusing',
            'action_taken' => 'Istirahat di UKS',
            'temperature_c' => 37.2,
        ])->assertCreated();

        $visitId = $create->json('data.id');
        $this->assertNotNull($visitId);

        $visit = UksVisit::find($visitId);
        $this->assertSame($this->year->id, (int) $visit->academic_year_id);
        $this->assertSame($this->semester->id, (int) $visit->semester_id);
        $this->assertSame($this->class->id, (int) $visit->class_id);

        $list = $this->getJson('/api/v1/uks/visits')->assertOk()->json('data');
        $this->assertCount(1, $list);
        $this->assertSame($visitId, $list[0]['id']);
        $this->assertSame('Pusing', $list[0]['complaint']);
    }

    public function test_visit_is_listed_even_when_student_year_does_not_match_active_year(): void
    {
        $oldYear = AcademicYear::create([
            'institution_id' => $this->institution->id,
            'name' => '2024/2025',
            'code' => '2425',
            'start_date' => '2024-07-01',
            'end_date' => '2025-06-30',
            'is_active' => false,
        ]);
        $this->student->update([
            'academic_year_id' => $oldYear->id,
            'semester_id' => null,
        ]);

        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/uks/visits', [
            'student_id' => $this->student->id,
            'visit_date' => now()->toDateString(),
            'complaint' => 'Demam',
        ])->assertCreated();

        $list = $this->getJson('/api/v1/uks/visits')->assertOk()->json('data');
        $this->assertCount(1, $list);
        $this->assertSame($this->year->id, $list[0]['academic_year_id']);
    }

    public function test_empty_vital_fields_do_not_block_create(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/uks/visits', [
            'student_id' => $this->student->id,
            'visit_date' => now()->toDateString(),
            'uks_visit_type_id' => '',
            'height_cm' => '',
            'weight_kg' => '',
            'temperature_c' => '',
            'blood_pressure' => '',
            'complaint' => 'Luka ringan',
        ])->assertCreated();
    }

    public function test_staff_can_search_student_by_name_without_class(): void
    {
        Sanctum::actingAs($this->uksStaff);

        $this->getJson('/api/v1/uks/visits/students-lite')
            ->assertOk()
            ->assertJsonPath('data', []);

        $found = $this->getJson('/api/v1/uks/visits/students-lite?q=Sakit')
            ->assertOk()
            ->json('data');
        $this->assertCount(1, $found);
        $this->assertSame($this->student->id, $found[0]['id']);
    }

    public function test_admin_can_export_uks_report_pdf(): void
    {
        Sanctum::actingAs($this->admin);

        UksVisit::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'recorded_by' => $this->admin->id,
            'visit_date' => now()->toDateString(),
            'status' => 'selesai',
            'complaint' => 'Pusing',
            'academic_year_id' => $this->year->id,
            'semester_id' => $this->semester->id,
            'class_id' => $this->class->id,
        ]);

        $summary = $this->get('/api/v1/uks-reports/export-pdf?mode=summary')->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $summary->headers->get('content-type'));

        $detail = $this->get('/api/v1/uks-reports/export-pdf?mode=detail')->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $detail->headers->get('content-type'));
    }

    public function test_inactive_visit_type_is_rejected(): void
    {
        $type = UksVisitType::create([
            'institution_id' => $this->institution->id,
            'name' => 'Imunisasi',
            'code' => 'IMUN',
            'is_active' => false,
        ]);

        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/uks/visits', [
            'student_id' => $this->student->id,
            'visit_date' => now()->toDateString(),
            'uks_visit_type_id' => $type->id,
        ])->assertNotFound();
    }
}
