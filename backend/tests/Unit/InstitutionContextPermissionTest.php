<?php

namespace Tests\Unit;

use App\Models\Permission;
use App\Models\User;
use App\Support\InstitutionContext;
use Illuminate\Http\Request;
use Tests\TestCase;

class InstitutionContextPermissionTest extends TestCase
{
    public function test_home_institution_keeps_elevated_permissions(): void
    {
        $user = $this->makeTeacherWithPermissions(1, [
            'institution',
            'correspondence',
            'report',
            'schedule',
        ]);

        $keys = InstitutionContext::effectivePermissionKeys($user, 1, Request::create('/'));

        $this->assertContains('institution', $keys);
        $this->assertContains('report', $keys);
        $this->assertContains('schedule', $keys);
    }

    public function test_non_induk_strips_kepala_sekolah_modules_and_keeps_teaching_defaults(): void
    {
        $user = $this->makeTeacherWithPermissions(1, [
            'institution',
            'correspondence',
            'report',
            'schedule',
        ]);

        $keys = InstitutionContext::effectivePermissionKeys($user, 99, Request::create('/'));

        $this->assertNotContains('institution', $keys);
        $this->assertNotContains('report', $keys);
        $this->assertContains('correspondence', $keys);
        $this->assertContains('schedule', $keys);
        $this->assertContains('teaching_journal', $keys);
        $this->assertContains('grade_book', $keys);
    }

    public function test_has_module_access_respects_active_institution(): void
    {
        $user = $this->makeTeacherWithPermissions(1, [
            'institution',
            'correspondence',
            'report',
        ]);

        $request = Request::create('/');

        $this->assertTrue(
            InstitutionContext::hasEffectivePermission($user, 'institution', 1, $request)
        );
        $this->assertFalse(
            InstitutionContext::hasEffectivePermission($user, 'institution', 99, $request)
        );
        $this->assertTrue(
            InstitutionContext::hasEffectivePermission($user, 'teaching_journal', 99, $request)
        );
    }

    /**
     * @param  array<int, string>  $keys
     */
    private function makeTeacherWithPermissions(int $homeInstitutionId, array $keys): User
    {
        $user = new User([
            'role' => 'teacher',
            'institution_id' => $homeInstitutionId,
            'name' => 'Guru Uji',
            'email' => 'guru-uji@example.com',
        ]);

        $permissions = collect($keys)->map(function (string $key) {
            $permission = new Permission(['key' => $key, 'name' => $key]);
            $permission->id = crc32($key);

            return $permission;
        });

        $user->setRelation('permissions', $permissions);

        return $user;
    }
}
