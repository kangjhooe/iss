<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentImportTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'SMP Import Siswa',
            'npsn' => '81818181',
            'level' => 'SMP',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Import',
            'email' => 'admin-student-import@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
    }

    public function test_import_creates_new_students(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/student/import', [
            'students' => [
                $this->row([
                    'nik' => '3201010101010001',
                    'nisn' => '0096877778',
                    'name' => 'Rayhan Al Ayubi',
                ]),
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('success_count', 1)
            ->assertJsonPath('created_count', 1)
            ->assertJsonPath('updated_count', 0)
            ->assertJsonPath('error_count', 0);

        $this->assertDatabaseHas('student', [
            'institution_id' => $this->institution->id,
            'nik' => '3201010101010001',
            'nisn' => '0096877778',
            'name' => 'Rayhan Al Ayubi',
        ]);
    }

    public function test_import_updates_existing_student_when_nisn_matches_even_if_nik_differs(): void
    {
        Sanctum::actingAs($this->admin);

        $existing = Student::create([
            'institution_id' => $this->institution->id,
            'nik' => '3201010101010099',
            'nisn' => '0096877778',
            'name' => 'Rayhan Lama',
            'gender' => 'L',
            'birth_place' => 'Krui',
            'birth_date' => '2010-01-01',
            'tingkat' => 7,
            'status' => 'Aktif',
        ]);

        $response = $this->postJson('/api/v1/student/import', [
            'students' => [
                $this->row([
                    'nik' => '3201010101010001',
                    'nisn' => '0096877778',
                    'name' => "RAYHAN AL'AYUBI",
                    'tingkat' => 8,
                ]),
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('success_count', 1)
            ->assertJsonPath('created_count', 0)
            ->assertJsonPath('updated_count', 1)
            ->assertJsonPath('error_count', 0);

        $existing->refresh();
        $this->assertSame("RAYHAN AL'AYUBI", $existing->name);
        $this->assertSame('3201010101010001', $existing->nik);
        $this->assertSame(8, (int) $existing->tingkat);
        $this->assertSame(1, Student::query()->count());
    }

    public function test_import_matches_unpadded_nisn_from_excel_number(): void
    {
        Sanctum::actingAs($this->admin);

        Student::create([
            'institution_id' => $this->institution->id,
            'nik' => '3201010101010001',
            'nisn' => '0096877778',
            'name' => 'Rayhan Lama',
            'gender' => 'L',
            'birth_place' => 'Krui',
            'birth_date' => '2010-01-01',
            'tingkat' => 7,
            'status' => 'Aktif',
        ]);

        $response = $this->postJson('/api/v1/student/import', [
            'students' => [
                $this->row([
                    'nik' => '3201010101010001',
                    'nisn' => 96877778,
                    'name' => 'Rayhan Update',
                ]),
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('success_count', 1)
            ->assertJsonPath('updated_count', 1)
            ->assertJsonPath('error_count', 0);

        $this->assertSame('Rayhan Update', Student::query()->where('nisn', '0096877778')->value('name'));
        $this->assertSame(1, Student::query()->count());
    }

    public function test_import_rejects_soft_deleted_student_without_restoring(): void
    {
        Sanctum::actingAs($this->admin);

        $existing = Student::create([
            'institution_id' => $this->institution->id,
            'nik' => '3201010101010001',
            'nisn' => '0081007994',
            'name' => 'Jeni Lama',
            'gender' => 'P',
            'birth_place' => 'Krui',
            'birth_date' => '2010-02-02',
            'tingkat' => 7,
            'status' => 'Aktif',
        ]);
        $existing->delete();

        $response = $this->postJson('/api/v1/student/import', [
            'students' => [
                $this->row([
                    'nik' => '3201010101010001',
                    'nisn' => '0081007994',
                    'name' => 'Jeni Muamalia',
                    'gender' => 'P',
                    'excel_row' => 9,
                ]),
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('success_count', 0)
            ->assertJsonPath('error_count', 1);

        $error = $response->json('errors.0');
        $this->assertStringContainsString('Baris 9', $error);
        $this->assertStringContainsString('kotak sampah', $error);
        $this->assertTrue($existing->fresh()->trashed());
        $this->assertSame('Jeni Lama', $existing->fresh()->name);
    }

    /**
     * @dataProvider inactiveStatusProvider
     */
    public function test_import_rejects_inactive_or_mutated_students(string $status, string $expectedPhrase): void
    {
        Sanctum::actingAs($this->admin);

        $existing = Student::create([
            'institution_id' => $this->institution->id,
            'nik' => '3201010101010001',
            'nisn' => '0096877778',
            'name' => "RAYHAN AL'AYUBI",
            'gender' => 'L',
            'birth_place' => 'Krui',
            'birth_date' => '2010-01-01',
            'tingkat' => 7,
            'status' => $status,
        ]);

        $response = $this->postJson('/api/v1/student/import', [
            'students' => [
                $this->row([
                    'nik' => '3201010101010001',
                    'nisn' => '0096877778',
                    'name' => "RAYHAN AL'AYUBI",
                    'status' => 'Aktif',
                    'excel_row' => 4,
                ]),
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('success_count', 0)
            ->assertJsonPath('updated_count', 0)
            ->assertJsonPath('error_count', 1);

        $error = $response->json('errors.0');
        $this->assertStringContainsString('Baris 4', $error);
        $this->assertStringContainsString("RAYHAN AL'AYUBI", $error);
        $this->assertStringContainsString('0096877778', $error);
        $this->assertStringContainsString($expectedPhrase, $error);
        $this->assertStringNotContainsString('SQLSTATE', $error);

        $existing->refresh();
        $this->assertSame($status, $existing->status);
        $this->assertSame(7, (int) $existing->tingkat);
    }

    public static function inactiveStatusProvider(): array
    {
        return [
            'mutasi' => ['Pindah', 'Pindah (sudah dimutasi)'],
            'tidak aktif' => ['Tidak Aktif', 'Tidak Aktif'],
            'drop out' => ['Drop Out', 'Drop Out'],
            'alumni' => ['Lulus', 'Lulus (alumni)'],
        ];
    }

    public function test_import_rejects_inactive_but_still_imports_active_rows(): void
    {
        Sanctum::actingAs($this->admin);

        Student::create([
            'institution_id' => $this->institution->id,
            'nik' => '3201010101010001',
            'nisn' => '0096877778',
            'name' => "RAYHAN AL'AYUBI",
            'gender' => 'L',
            'birth_place' => 'Krui',
            'birth_date' => '2010-01-01',
            'tingkat' => 10,
            'status' => 'Pindah',
        ]);

        $response = $this->postJson('/api/v1/student/import', [
            'students' => [
                $this->row([
                    'nik' => '3201010101010003',
                    'nisn' => '0011111111',
                    'name' => 'Siswa Baru',
                ]),
                $this->row([
                    'nik' => '3201010101010001',
                    'nisn' => '0096877778',
                    'name' => "RAYHAN AL'AYUBI",
                    'excel_row' => 4,
                ]),
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('success_count', 1)
            ->assertJsonPath('created_count', 1)
            ->assertJsonPath('error_count', 1);

        $this->assertDatabaseHas('student', [
            'institution_id' => $this->institution->id,
            'name' => 'Siswa Baru',
            'nisn' => '0011111111',
        ]);
        $this->assertSame('Pindah', Student::query()->where('nisn', '0096877778')->value('status'));
        $this->assertStringContainsString('Baris 4', $response->json('errors.0'));
        $this->assertStringContainsString('Pindah', $response->json('errors.0'));
    }

    public function test_import_allows_alumni_from_another_institution(): void
    {
        Sanctum::actingAs($this->admin);

        $origin = Institution::create([
            'name' => 'MTs Asal',
            'npsn' => '84848484',
            'level' => 'MTs',
            'is_active' => true,
        ]);

        Student::create([
            'institution_id' => $origin->id,
            'nik' => '3201010101010999',
            'nisn' => '0088971999',
            'name' => 'Alumni MTs',
            'gender' => 'L',
            'status' => 'Lulus',
            'graduation_year' => 2026,
        ]);

        $response = $this->postJson('/api/v1/student/import', [
            'students' => [
                $this->row([
                    'nik' => '3201010101010999',
                    'nisn' => '0088971999',
                    'name' => 'Alumni MTs',
                    'excel_row' => 5,
                ]),
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('success_count', 1)
            ->assertJsonPath('created_count', 1)
            ->assertJsonPath('error_count', 0);

        $this->assertSame(2, Student::query()->where('nisn', '0088971999')->count());
        $this->assertSame('Lulus', Student::query()->where('institution_id', $origin->id)->where('nisn', '0088971999')->value('status'));
        $this->assertSame('Aktif', Student::query()->where('institution_id', $this->institution->id)->where('nisn', '0088971999')->value('status'));
    }

    public function test_import_rejects_nisn_owned_by_another_institution_without_sql_dump(): void
    {
        Sanctum::actingAs($this->admin);

        $other = Institution::create([
            'name' => 'MTs Lain',
            'npsn' => '82828282',
            'level' => 'MTs',
            'is_active' => true,
        ]);

        Student::create([
            'institution_id' => $other->id,
            'nik' => '3201010101010888',
            'nisn' => '0088971982',
            'name' => 'Rifal Asal',
            'gender' => 'L',
            'status' => 'Aktif',
        ]);

        $response = $this->postJson('/api/v1/student/import', [
            'students' => [
                $this->row([
                    'nik' => '3201010101010002',
                    'nisn' => '0088971982',
                    'name' => 'Rifal Rezka',
                    'excel_row' => 11,
                ]),
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('success_count', 0)
            ->assertJsonPath('error_count', 1);

        $error = $response->json('errors.0');
        $this->assertStringContainsString('Baris 11', $error);
        $this->assertStringContainsString('MTs Lain', $error);
        $this->assertStringNotContainsString('SQLSTATE', $error);
        $this->assertStringNotContainsString('INSERT INTO', $error);
        $this->assertSame(0, Student::query()->where('institution_id', $this->institution->id)->count());
    }

    public function test_import_can_succeed_for_some_rows_and_fail_for_others(): void
    {
        Sanctum::actingAs($this->admin);

        $other = Institution::create([
            'name' => 'MA Lain',
            'npsn' => '83838383',
            'level' => 'MA',
            'is_active' => true,
        ]);

        Student::create([
            'institution_id' => $other->id,
            'nik' => '3201010101010777',
            'nisn' => '0093037804',
            'name' => 'Siswa Sekolah Lain',
            'gender' => 'L',
            'status' => 'Aktif',
        ]);

        $response = $this->postJson('/api/v1/student/import', [
            'students' => [
                $this->row([
                    'nik' => '3201010101010003',
                    'nisn' => '0011111111',
                    'name' => 'Siswa Baru',
                ]),
                $this->row([
                    'nik' => '3201010101010004',
                    'nisn' => '0093037804',
                    'name' => 'Siswa Bentrok',
                    'excel_row' => 13,
                ]),
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('success_count', 1)
            ->assertJsonPath('created_count', 1)
            ->assertJsonPath('error_count', 1);

        $this->assertDatabaseHas('student', [
            'institution_id' => $this->institution->id,
            'name' => 'Siswa Baru',
        ]);
        $this->assertStringContainsString('Baris 13', $response->json('errors.0'));
        $this->assertStringNotContainsString('SQLSTATE', $response->json('errors.0'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function row(array $overrides): array
    {
        return array_merge([
            'nik' => '3201010101010001',
            'nisn' => '0012345678',
            'name' => 'Siswa Import',
            'gender' => 'L',
            'birth_place' => 'Krui',
            'birth_date' => '2010-01-01',
            'tingkat' => 7,
            'status' => 'Aktif',
        ], $overrides);
    }
}
