<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmployeeImportTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'SMP Import Pegawai',
            'npsn' => '82828282',
            'level' => 'SMP',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Import Pegawai',
            'email' => 'admin-employee-import@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
    }

    protected function row(array $overrides = []): array
    {
        return array_merge([
            'type' => 'Guru',
            'nik' => '3201010101010001',
            'name' => 'Ahmad Fauzi',
            'gender' => 'L',
            'birth_place' => 'Jakarta',
            'birth_date' => '1980-01-15',
            'email' => 'ahmad.fauzi@example.com',
            'status' => 'Aktif',
        ], $overrides);
    }

    public function test_import_creates_employee_with_required_fields(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/employee/import', [
            'employees' => [$this->row()],
        ]);

        $response->assertOk()
            ->assertJsonPath('success_count', 1)
            ->assertJsonPath('error_count', 0);

        $this->assertDatabaseHas('employee', [
            'institution_id' => $this->institution->id,
            'nik' => '3201010101010001',
            'name' => 'Ahmad Fauzi',
            'type' => 'Guru',
            'gender' => 'L',
            'birth_place' => 'Jakarta',
            'birth_date' => '1980-01-15',
        ]);
    }

    public function test_import_allows_staff_without_email(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/employee/import', [
            'employees' => [$this->row([
                'type' => 'Staff',
                'nik' => '3201010101010002',
                'name' => 'Siti Staff',
                'email' => null,
            ])],
        ]);

        $response->assertOk()
            ->assertJsonPath('success_count', 1)
            ->assertJsonPath('error_count', 0);

        $this->assertDatabaseHas('employee', [
            'nik' => '3201010101010002',
            'type' => 'Staff',
            'email' => null,
        ]);
    }

    public function test_import_rejects_missing_required_columns(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/employee/import', [
            'employees' => [
                $this->row(['type' => '', 'nik' => '3201010101010011']),
                $this->row(['gender' => '', 'nik' => '3201010101010012', 'email' => 'a12@example.com']),
                $this->row(['birth_place' => '', 'nik' => '3201010101010013', 'email' => 'a13@example.com']),
                $this->row(['birth_date' => '', 'nik' => '3201010101010014', 'email' => 'a14@example.com']),
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('success_count', 0)
            ->assertJsonPath('error_count', 4);

        $errors = $response->json('errors');
        $this->assertStringContainsString('Tipe Pegawai', $errors[0]);
        $this->assertStringContainsString('Jenis kelamin', $errors[1]);
        $this->assertStringContainsString('Tempat Lahir', $errors[2]);
        $this->assertStringContainsString('Tanggal Lahir', $errors[3]);
    }

    public function test_import_rejects_guru_without_email(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/employee/import', [
            'employees' => [$this->row(['email' => ''])],
        ]);

        $response->assertOk()
            ->assertJsonPath('success_count', 0)
            ->assertJsonPath('error_count', 1);

        $this->assertStringContainsString('Email wajib diisi untuk guru', $response->json('errors.0'));
    }

    public function test_import_updates_existing_employee_in_same_institution(): void
    {
        Sanctum::actingAs($this->admin);

        Employee::create([
            'institution_id' => $this->institution->id,
            'type' => 'Guru',
            'nik' => '3201010101010001',
            'name' => 'Nama Lama',
            'gender' => 'L',
            'birth_place' => 'Bandung',
            'birth_date' => '1979-02-02',
            'status' => 'Aktif',
        ]);

        $response = $this->postJson('/api/v1/employee/import', [
            'employees' => [$this->row(['name' => 'Nama Baru'])],
        ]);

        $response->assertOk()
            ->assertJsonPath('success_count', 1)
            ->assertJsonPath('error_count', 0);

        $this->assertDatabaseHas('employee', [
            'nik' => '3201010101010001',
            'name' => 'Nama Baru',
            'birth_place' => 'Jakarta',
        ]);
        $this->assertSame(1, Employee::where('nik', '3201010101010001')->count());
    }

    public function test_import_rejects_nik_registered_in_another_institution(): void
    {
        Sanctum::actingAs($this->admin);

        $other = Institution::create([
            'name' => 'SMP Lain',
            'npsn' => '83838383',
            'level' => 'SMP',
            'is_active' => true,
        ]);

        Employee::create([
            'institution_id' => $other->id,
            'type' => 'Guru',
            'nik' => '3201010101010001',
            'name' => 'Guru Institusi Lain',
            'gender' => 'L',
            'status' => 'Aktif',
        ]);

        $response = $this->postJson('/api/v1/employee/import', [
            'employees' => [$this->row()],
        ]);

        $response->assertOk()
            ->assertJsonPath('success_count', 0)
            ->assertJsonPath('error_count', 1);

        $this->assertStringContainsString('institusi lain', $response->json('errors.0'));
    }
}
