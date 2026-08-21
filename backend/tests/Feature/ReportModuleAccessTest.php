<?php

namespace Tests\Feature;

use App\Models\AdditionalDuty;
use App\Models\Employee;
use App\Models\Institution;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportModuleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'SMP Laporan Akses',
            'npsn' => '20202020',
            'level' => 'SMP',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Laporan',
            'email' => 'admin-report-access@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
    }

    public function test_teacher_with_legacy_report_permission_cannot_open_report(): void
    {
        $teacher = $this->makeTeacher('guru-laporan@example.com', 'Guru Laporan');
        $report = Permission::query()->where('key', 'report')->firstOrFail();
        $teacher->permissions()->attach($report->id);

        Sanctum::actingAs($teacher);

        $this->getJson('/api/v1/report/institution')->assertForbidden();
        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('user.is_kepala_sekolah', false);
    }

    public function test_kepala_sekolah_can_open_report(): void
    {
        $principal = $this->makeTeacher('ks-laporan@example.com', 'Kepala Sekolah');
        $employee = Employee::query()->where('email', $principal->email)->firstOrFail();
        $duty = AdditionalDuty::query()->where('key', 'kepala_sekolah')->firstOrFail();
        $employee->additionalDuties()->attach($duty->id, [
            'started_at' => now()->toDateString(),
        ]);

        Sanctum::actingAs($principal);

        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('user.is_kepala_sekolah', true);

        $this->getJson('/api/v1/report/institution')->assertOk();
    }

    public function test_admin_can_open_report(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/report/institution')->assertOk();
    }

    public function test_module_access_cannot_grant_report_to_regular_teacher(): void
    {
        $teacher = $this->makeTeacher('guru-modul@example.com', 'Guru Modul');

        Sanctum::actingAs($this->admin);

        $this->putJson('/api/v1/permissions/users/'.$teacher->id, [
            'permission_keys' => ['report'],
        ])->assertOk();

        $keys = $teacher->fresh()->permissions()->pluck('key')->all();
        $this->assertNotContains('report', $keys);
    }

    private function makeTeacher(string $email, string $name): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('Password123!'),
            'role' => 'teacher',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        Employee::create([
            'institution_id' => $this->institution->id,
            'nik' => str_pad((string) crc32($email), 16, '0', STR_PAD_LEFT),
            'type' => 'Guru',
            'name' => $name,
            'gender' => 'L',
            'email' => $email,
            'status' => 'Aktif',
        ]);

        return $user;
    }
}
