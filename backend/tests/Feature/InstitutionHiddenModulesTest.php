<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InstitutionHiddenModulesTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'SMP Sembunyi Modul',
            'npsn' => '81818181',
            'level' => 'SMP',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Sembunyi',
            'email' => 'admin-sembunyi@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
    }

    public function test_admin_can_hide_library_and_loses_api_access(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/library/books')->assertOk();

        $this->putJson('/api/v1/permissions/institution-visibility', [
            'hidden_keys' => ['library'],
        ])->assertOk()
            ->assertJsonPath('data.hidden_keys.0', 'library');

        $this->getJson('/api/v1/permissions/institution-visibility')
            ->assertOk()
            ->assertJsonPath('data.hidden_keys.0', 'library');

        $this->getJson('/api/v1/library/books')->assertForbidden();
        $this->getJson('/api/v1/institution/my')->assertOk();
    }

    public function test_hidden_module_is_included_in_auth_payload(): void
    {
        $this->institution->update(['hidden_module_keys' => ['uks', 'library']]);
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('user.hidden_module_keys', ['uks', 'library']);
    }

    public function test_teacher_with_permission_cannot_use_hidden_module(): void
    {
        $library = Permission::query()->where('key', 'library')->firstOrFail();

        $teacher = User::create([
            'name' => 'Guru Pustaka',
            'email' => 'guru-pustaka@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'teacher',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $teacher->permissions()->attach($library->id);

        $this->institution->update(['hidden_module_keys' => ['library']]);

        Sanctum::actingAs($teacher);
        $this->getJson('/api/v1/library/books')->assertForbidden();
    }

    public function test_teacher_cannot_update_institution_visibility(): void
    {
        $teacher = User::create([
            'name' => 'Guru Biasa',
            'email' => 'guru-biasa@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'teacher',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        Sanctum::actingAs($teacher);
        $this->putJson('/api/v1/permissions/institution-visibility', [
            'hidden_keys' => ['library'],
        ])->assertForbidden();
    }

    public function test_unhiding_restores_admin_access(): void
    {
        $this->institution->update(['hidden_module_keys' => ['library']]);
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/library/books')->assertForbidden();

        $this->putJson('/api/v1/permissions/institution-visibility', [
            'hidden_keys' => [],
        ])->assertOk();

        $this->getJson('/api/v1/library/books')->assertOk();
    }
}
