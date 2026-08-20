<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardChartsTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'SMP Chart',
            'npsn' => '60606060',
            'level' => 'SMP',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Chart',
            'email' => 'admin-chart@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
    }

    public function test_admin_can_load_dashboard_charts(): void
    {
        Student::create([
            'institution_id' => $this->institution->id,
            'nis' => '1001',
            'name' => 'Siswa Laki',
            'gender' => 'L',
            'status' => 'Aktif',
        ]);
        Student::create([
            'institution_id' => $this->institution->id,
            'nis' => '1002',
            'name' => 'Siswa Perempuan',
            'gender' => 'P',
            'status' => 'Aktif',
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/dashboard/charts')
            ->assertOk()
            ->assertJsonPath('data.students_gender.male', 1)
            ->assertJsonPath('data.students_gender.female', 1)
            ->assertJsonPath('data.students_gender.total', 2)
            ->assertJsonStructure([
                'data' => [
                    'students_gender' => ['male', 'female', 'other', 'total'],
                    'attendance_today' => ['hadir', 'izin', 'sakit', 'alpha', 'dinas_luar', 'students_recorded'],
                    'finance' => ['billed', 'collected', 'outstanding'],
                    'violations_by_month',
                ],
            ]);

        $this->assertCount(12, $response->json('data.violations_by_month'));
    }

    public function test_student_cannot_load_dashboard_charts(): void
    {
        $studentUser = User::create([
            'name' => 'Siswa Portal',
            'email' => 'siswa-chart@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'student',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        Sanctum::actingAs($studentUser);

        $this->getJson('/api/v1/dashboard/charts')->assertForbidden();
    }
}
