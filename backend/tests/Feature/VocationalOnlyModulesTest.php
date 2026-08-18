<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\User;
use App\Support\VocationalAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VocationalOnlyModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAdmin(string $level, string $email): array
    {
        $institution = Institution::create([
            'name' => $level.' Tes PKL',
            'npsn' => (string) random_int(10000000, 99999999),
            'level' => $level,
            'is_active' => true,
        ]);

        $admin = User::create([
            'name' => 'Admin '.$level,
            'email' => $email,
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        return [$institution, $admin];
    }

    public function test_sma_admin_cannot_access_pkl_bkk_or_industry_partners(): void
    {
        [, $admin] = $this->makeAdmin('SMA', 'admin-sma-pkl@example.com');
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/industry-partners', [
            'name' => 'PT Tidak Berlaku',
            'status' => 'Aktif',
        ])->assertStatus(422)->assertJsonPath('code', 'vocational_only');

        $this->getJson('/api/v1/pkl/periods')
            ->assertStatus(422)
            ->assertJsonPath('code', 'vocational_only');

        $this->getJson('/api/v1/bkk/vacancies')
            ->assertStatus(422)
            ->assertJsonPath('code', 'vocational_only');
    }

    public function test_ma_admin_cannot_access_pkl(): void
    {
        [, $admin] = $this->makeAdmin('MA', 'admin-ma-pkl@example.com');
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/pkl/periods')
            ->assertStatus(422)
            ->assertJsonPath('code', 'vocational_only');
    }

    public function test_smk_admin_can_create_industry_partner(): void
    {
        [, $admin] = $this->makeAdmin('SMK', 'admin-smk-pkl@example.com');
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/industry-partners', [
            'name' => 'PT Industri Maju',
            'status' => 'Aktif',
        ])->assertCreated();
    }

    public function test_mak_admin_can_list_pkl_periods(): void
    {
        [, $admin] = $this->makeAdmin('MAK', 'admin-mak-pkl@example.com');
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/pkl/periods')->assertOk();
    }

    public function test_sma_hides_vocational_duties_and_permissions(): void
    {
        [, $admin] = $this->makeAdmin('SMA', 'admin-sma-duties@example.com');
        Sanctum::actingAs($admin);

        $duties = $this->getJson('/api/v1/additional-duties')->assertOk()->json('data');
        $keys = collect($duties)->pluck('key')->all();
        foreach (VocationalAccess::DUTY_KEYS as $key) {
            $this->assertNotContains($key, $keys);
        }

        $permissions = $this->getJson('/api/v1/permissions')->assertOk()->json('data');
        $permKeys = collect($permissions)->pluck('key')->all();
        $this->assertNotContains('pkl', $permKeys);
        $this->assertNotContains('bkk', $permKeys);
    }

    public function test_smk_lists_vocational_duties_and_permissions(): void
    {
        [, $admin] = $this->makeAdmin('SMK', 'admin-smk-duties@example.com');
        Sanctum::actingAs($admin);

        $duties = $this->getJson('/api/v1/additional-duties')->assertOk()->json('data');
        $keys = collect($duties)->pluck('key')->all();
        foreach (VocationalAccess::DUTY_KEYS as $key) {
            $this->assertContains($key, $keys);
        }

        $permissions = $this->getJson('/api/v1/permissions')->assertOk()->json('data');
        $permKeys = collect($permissions)->pluck('key')->all();
        $this->assertContains('pkl', $permKeys);
        $this->assertContains('bkk', $permKeys);
    }

    public function test_sma_student_cannot_access_pkl_portal(): void
    {
        [$institution] = $this->makeAdmin('SMA', 'admin-sma-student-pkl@example.com');

        $studentUser = User::create([
            'name' => 'Siswa SMA',
            'email' => 'siswa-sma-pkl@example.com',
            'login_nik' => '3201010101010099',
            'password' => Hash::make('Password123!'),
            'role' => 'student',
            'institution_id' => $institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        Sanctum::actingAs($studentUser);
        $this->getJson('/api/v1/pkl/my/placements')
            ->assertStatus(422)
            ->assertJsonPath('code', 'vocational_only');
        $this->getJson('/api/v1/bkk/my/vacancies')
            ->assertStatus(422)
            ->assertJsonPath('code', 'vocational_only');
    }
}
