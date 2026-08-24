<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Institution;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentBiodataPersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected User $admin;

    protected AcademicYear $year;

    protected Semester $semester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'SMP Biodata Siswa',
            'npsn' => '82828282',
            'level' => 'SMP',
            'is_active' => true,
        ]);

        $this->year = AcademicYear::create([
            'code' => '2526-bio',
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

        $this->admin = User::create([
            'name' => 'Admin Biodata',
            'email' => 'admin-student-biodata@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
    }

    public function test_store_and_show_keep_extended_biodata_fields(): void
    {
        Sanctum::actingAs($this->admin);

        $payload = $this->biodataPayload();

        $this->postJson('/api/v1/student', $payload)
            ->assertCreated()
            ->assertJsonPath('data.birth_place', 'Krui')
            ->assertJsonPath('data.father_birth_place', 'Liwa')
            ->assertJsonPath('data.previous_school_npsn', '12345678');

        $student = Student::query()->where('nik', $payload['nik'])->firstOrFail();

        $this->getJson('/api/v1/student/'.$student->id)
            ->assertOk()
            ->assertJsonPath('data.birth_place', 'Krui')
            ->assertJsonPath('data.address', 'Jl. Melati No. 12')
            ->assertJsonPath('data.village', 'Pasar Krui')
            ->assertJsonPath('data.phone', '081234567890')
            ->assertJsonPath('data.email', 'siswa@example.com')
            ->assertJsonPath('data.religion', 'Islam')
            ->assertJsonPath('data.no_kk', '3201010101010002')
            ->assertJsonPath('data.father_name', 'Budi Santoso')
            ->assertJsonPath('data.father_birth_place', 'Liwa')
            ->assertJsonPath('data.mother_name', 'Siti Aminah')
            ->assertJsonPath('data.mother_birth_place', 'Krui')
            ->assertJsonPath('data.previous_school', 'SD Negeri 1 Krui')
            ->assertJsonPath('data.previous_school_npsn', '12345678')
            ->assertJsonPath('data.previous_school_address', 'Jl. Pendidikan No. 1')
            ->assertJsonPath('data.notes', 'Alergi debu');

        $this->assertDatabaseHas('student', [
            'id' => $student->id,
            'birth_place' => 'Krui',
            'father_birth_place' => 'Liwa',
            'phone' => '081234567890',
            'previous_school_npsn' => '12345678',
        ]);
    }

    public function test_update_keeps_biodata_when_full_payload_is_resent(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/student', $this->biodataPayload())->assertCreated();
        $id = $create->json('data.id');

        $this->putJson('/api/v1/student/'.$id, array_merge($this->biodataPayload(), [
            'name' => 'Ahmad Fauzi Updated',
            'class' => '8A',
        ]))->assertOk();

        $this->assertDatabaseHas('student', [
            'id' => $id,
            'name' => 'Ahmad Fauzi Updated',
            'birth_place' => 'Krui',
            'father_name' => 'Budi Santoso',
            'mother_birth_place' => 'Krui',
            'phone' => '081234567890',
            'previous_school_npsn' => '12345678',
            'notes' => 'Alergi debu',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function biodataPayload(): array
    {
        return [
            'nik' => '3201010101010091',
            'nisn' => '0091000001',
            'name' => 'Ahmad Fauzi',
            'gender' => 'L',
            'birth_place' => 'Krui',
            'birth_date' => '2011-03-15',
            'tingkat' => 7,
            'status' => 'Aktif',
            'address' => 'Jl. Melati No. 12',
            'village' => 'Pasar Krui',
            'sub_district' => 'Pesisir Tengah',
            'district' => 'Pesisir Barat',
            'province' => 'Lampung',
            'postal_code' => '34874',
            'phone' => '081234567890',
            'email' => 'siswa@example.com',
            'religion' => 'Islam',
            'no_kk' => '3201010101010002',
            'aspiration' => 'Dokter',
            'hobby' => 'Membaca',
            'previous_school' => 'SD Negeri 1 Krui',
            'previous_school_npsn' => '12345678',
            'previous_school_address' => 'Jl. Pendidikan No. 1',
            'residence_type' => 'tinggal_dengan_orang_tua',
            'father_name' => 'Budi Santoso',
            'father_status' => 'masih_hidup',
            'father_nik' => '3201010101010092',
            'father_birth_place' => 'Liwa',
            'father_birth_date' => '1980-05-20',
            'mother_name' => 'Siti Aminah',
            'mother_status' => 'masih_hidup',
            'mother_nik' => '3201010101010093',
            'mother_birth_place' => 'Krui',
            'mother_birth_date' => '1982-08-10',
            'notes' => 'Alergi debu',
        ];
    }
}
