<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\Institution;
use App\Models\Permission;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\WaliKelasPermissionService;
use App\Support\TeacherAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WaliKelasPermissionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_revoke_wali_keeps_teaching_permissions(): void
    {
        $institution = Institution::create([
            'name' => 'SMP Uji Mapel',
            'npsn' => '11223344',
            'is_active' => true,
        ]);

        foreach (array_merge(TeacherAccess::defaultPermissionKeys(), ['bk_report', 'report']) as $key) {
            Permission::firstOrCreate(['key' => $key], ['label' => $key]);
        }

        $user = User::create([
            'name' => 'Guru Mapel',
            'email' => 'mapel@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'teacher',
            'institution_id' => $institution->id,
            'email_verified_at' => now(),
        ]);

        $employee = Employee::create([
            'institution_id' => $institution->id,
            'nik' => '1234567890123456',
            'type' => 'Guru',
            'name' => 'Guru Mapel',
            'gender' => 'L',
            'email' => $user->email,
            'status' => 'Aktif',
        ]);

        $class = SchoolClass::create([
            'institution_id' => $institution->id,
            'academic_year' => '2025/2026',
            'name' => 'VII-A',
            'grade' => 7,
            'teacher_id' => $employee->id,
            'status' => 'Aktif',
        ]);

        $service = app(WaliKelasPermissionService::class);
        $service->grantWaliKelasPermissionsToEmployee($employee->id);

        $keysAsWali = $user->fresh()->permissions()->pluck('key')->all();
        foreach (TeacherAccess::defaultPermissionKeys() as $key) {
            $this->assertContains($key, $keysAsWali);
        }
        $this->assertContains('bk_report', $keysAsWali);
        $this->assertNotContains('report', $keysAsWali);

        $class->update(['teacher_id' => null]);
        $service->syncWaliKelasPermissionsForEmployee($employee->id);

        $keysAfter = $user->fresh()->permissions()->pluck('key')->all();
        foreach (TeacherAccess::defaultPermissionKeys() as $key) {
            $this->assertContains($key, $keysAfter, "Teaching key {$key} must remain after leaving wali");
        }
        $this->assertNotContains('bk_report', $keysAfter);
        $this->assertNotContains('report', $keysAfter);
    }

    public function test_teacher_access_defaults_include_grade_and_journal(): void
    {
        $keys = TeacherAccess::defaultPermissionKeys();
        $this->assertContains('grade_book', $keys);
        $this->assertContains('teaching_journal', $keys);
        $this->assertContains('schedule', $keys);
        $this->assertNotContains('correspondence', $keys);
    }
}
