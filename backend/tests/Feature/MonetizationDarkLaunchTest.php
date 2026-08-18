<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\SubscriptionAddon;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\MonetizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MonetizationDarkLaunchTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $schoolAdmin;

    protected Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'SMA Monetisasi Test',
            'npsn' => '80808080',
            'level' => 'SMA',
            'type' => 'Swasta',
            'is_active' => true,
        ]);

        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super-monetization@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'super_admin',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->schoolAdmin = User::create([
            'name' => 'Admin Sekolah',
            'email' => 'admin-monetization@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
    }

    public function test_migration_seeds_default_plans_and_addons(): void
    {
        $this->assertTrue(SubscriptionPlan::query()->where('key', 'starter')->exists());
        $this->assertTrue(SubscriptionAddon::query()->where('key', 'storage_upgrade')->exists());
        $this->assertTrue(SubscriptionAddon::query()->where('key', 'online_exam')->exists());
    }

    public function test_school_billing_is_hidden_while_not_launched(): void
    {
        Sanctum::actingAs($this->schoolAdmin);

        $this->getJson('/api/v1/billing/overview')
            ->assertStatus(404)
            ->assertJsonFragment(['message' => 'Modul monetisasi belum diluncurkan.']);

        $features = app(MonetizationService::class)->featuresForInstitution($this->institution);
        $this->assertFalse($features['launched']);
        $this->assertFalse($features['store_visible']);
        $this->assertTrue($features['online_exam']['entitled']);
        $this->assertFalse($features['online_exam']['enforced']);
    }

    public function test_non_super_admin_cannot_access_monetization_console(): void
    {
        Sanctum::actingAs($this->schoolAdmin);

        $this->getJson('/api/v1/super-admin/monetization/summary')
            ->assertStatus(403);
    }

    public function test_super_admin_can_manage_launch_and_grants(): void
    {
        Sanctum::actingAs($this->superAdmin);

        $this->getJson('/api/v1/super-admin/monetization/summary')
            ->assertOk()
            ->assertJsonPath('data.launched', false);

        $this->putJson('/api/v1/super-admin/monetization/launch', [
            'launched' => true,
            'default_storage_quota_mb' => 8192,
        ])
            ->assertOk()
            ->assertJsonPath('data.launched', true)
            ->assertJsonPath('data.default_storage_quota_mb', 8192);

        $planId = SubscriptionPlan::query()->where('key', 'standard')->value('id');

        $this->putJson('/api/v1/super-admin/monetization/institutions/' . $this->institution->id . '/subscription', [
            'subscription_plan_id' => $planId,
            'status' => 'manual',
        ])->assertOk();

        $this->putJson('/api/v1/super-admin/monetization/institutions/' . $this->institution->id . '/addons', [
            'addon_key' => 'storage_upgrade',
            'is_active' => true,
            'storage_mb' => 10240,
            'source' => 'manual',
        ])->assertOk();

        Sanctum::actingAs($this->schoolAdmin);

        $this->getJson('/api/v1/billing/overview')
            ->assertOk()
            ->assertJsonPath('data.features.launched', true)
            ->assertJsonPath('data.features.store_visible', true)
            ->assertJsonPath('data.features.online_exam.entitled', true);

        $this->institution->refresh();
        $this->assertSame(10240, (int) $this->institution->storage_addon_mb);
    }

    public function test_online_exam_denied_without_entitlement_when_launched(): void
    {
        Sanctum::actingAs($this->superAdmin);
        $this->putJson('/api/v1/super-admin/monetization/launch', ['launched' => true])->assertOk();

        $starterId = SubscriptionPlan::query()->where('key', 'starter')->value('id');
        $this->putJson('/api/v1/super-admin/monetization/institutions/' . $this->institution->id . '/subscription', [
            'subscription_plan_id' => $starterId,
            'status' => 'manual',
        ])->assertOk();

        $this->institution->refresh();
        $this->assertFalse(app(MonetizationService::class)->isOnlineExamEntitled($this->institution));

        Sanctum::actingAs($this->schoolAdmin);
        // Tanpa permission module juga 403; assign permission dulu bila perlu.
        // Pastikan service assert storage menolak over-quota.
        $this->institution->update(['storage_quota_mb' => 1, 'storage_addon_mb' => 0]);
        $denied = app(MonetizationService::class)->assertCanStoreBytes($this->institution->fresh(), 5 * 1024 * 1024);
        $this->assertIsArray($denied);
        $this->assertSame('storage_quota_exceeded', $denied['code']);
    }
}
