<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\PasswordResetRequest;
use App\Models\User;
use App\Notifications\PasswordResetRequestNotification;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PasswordResetRequestTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected User $schoolAdmin;

    protected User $superAdmin;

    protected User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'SMP Reset Sandi',
            'npsn' => '11223344',
            'level' => 'SMP',
            'is_active' => true,
        ]);

        $this->schoolAdmin = User::create([
            'name' => 'Admin Sekolah',
            'email' => 'admin-reset@example.com',
            'password' => Hash::make('OldPassword123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super-reset@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'super_admin',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->teacher = User::create([
            'name' => 'Guru Biasa',
            'email' => 'guru-reset@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'teacher',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
    }

    public function test_matching_school_admin_creates_pending_request_and_notifies_super_admin(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/password-reset-requests', [
            'email' => 'admin-reset@example.com',
            'npsn' => '11223344',
            'contact_phone' => '081234567890',
            'note' => 'Lupa sandi setelah ganti HP',
        ]);

        $response->assertOk()
            ->assertJsonPath(
                'message',
                'Jika data cocok dengan akun admin sekolah, permintaan telah dikirim. Admin akan menghubungi Anda melalui email.'
            );

        $this->assertDatabaseHas('password_reset_requests', [
            'user_id' => $this->schoolAdmin->id,
            'institution_id' => $this->institution->id,
            'email' => 'admin-reset@example.com',
            'npsn' => '11223344',
            'status' => PasswordResetRequest::STATUS_PENDING,
            'note' => 'Lupa sandi setelah ganti HP',
        ]);

        Notification::assertSentTo($this->superAdmin, PasswordResetRequestNotification::class);
    }

    public function test_unknown_or_mismatched_data_does_not_create_request(): void
    {
        $this->postJson('/api/v1/password-reset-requests', [
            'email' => 'tidak-ada@example.com',
            'npsn' => '11223344',
        ])->assertOk();

        $this->postJson('/api/v1/password-reset-requests', [
            'email' => 'admin-reset@example.com',
            'npsn' => '99999999',
        ])->assertOk();

        $this->postJson('/api/v1/password-reset-requests', [
            'email' => 'guru-reset@example.com',
            'npsn' => '11223344',
        ])->assertOk();

        $this->assertDatabaseCount('password_reset_requests', 0);
    }

    public function test_duplicate_pending_request_is_updated_not_duplicated(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/password-reset-requests', [
            'email' => 'admin-reset@example.com',
            'npsn' => '11223344',
            'note' => 'Pertama',
        ])->assertOk();

        $this->postJson('/api/v1/password-reset-requests', [
            'email' => 'admin-reset@example.com',
            'npsn' => '11223344',
            'note' => 'Kedua',
            'contact_phone' => '089999999999',
        ])->assertOk();

        $this->assertDatabaseCount('password_reset_requests', 1);
        $this->assertDatabaseHas('password_reset_requests', [
            'user_id' => $this->schoolAdmin->id,
            'note' => 'Kedua',
            'contact_phone' => '089999999999',
            'status' => PasswordResetRequest::STATUS_PENDING,
        ]);

        Notification::assertSentToTimes($this->superAdmin, PasswordResetRequestNotification::class, 1);
    }

    public function test_super_admin_can_list_and_process_request(): void
    {
        $resetRequest = PasswordResetRequest::create([
            'user_id' => $this->schoolAdmin->id,
            'institution_id' => $this->institution->id,
            'email' => $this->schoolAdmin->email,
            'npsn' => $this->institution->npsn,
            'status' => PasswordResetRequest::STATUS_PENDING,
        ]);

        Sanctum::actingAs($this->superAdmin);

        $this->getJson('/api/v1/password-reset-requests?status=pending')
            ->assertOk()
            ->assertJsonPath('data.0.id', $resetRequest->id);

        $this->getJson('/api/v1/password-reset-requests/pending-count')
            ->assertOk()
            ->assertJsonPath('count', 1);

        $response = $this->postJson("/api/v1/password-reset-requests/{$resetRequest->id}/process");

        $response->assertOk()
            ->assertJsonStructure(['temporary_password']);

        $this->assertNotEmpty($response->json('temporary_password'));
        $this->schoolAdmin->refresh();
        $this->assertTrue(Hash::check($response->json('temporary_password'), $this->schoolAdmin->password));
        $this->assertDatabaseHas('password_reset_requests', [
            'id' => $resetRequest->id,
            'status' => PasswordResetRequest::STATUS_PROCESSED,
            'processed_by' => $this->superAdmin->id,
        ]);
    }

    public function test_super_admin_can_reject_request(): void
    {
        $resetRequest = PasswordResetRequest::create([
            'user_id' => $this->schoolAdmin->id,
            'institution_id' => $this->institution->id,
            'email' => $this->schoolAdmin->email,
            'npsn' => $this->institution->npsn,
            'status' => PasswordResetRequest::STATUS_PENDING,
        ]);

        Sanctum::actingAs($this->superAdmin);

        $this->postJson("/api/v1/password-reset-requests/{$resetRequest->id}/reject", [
            'rejection_reason' => 'Data tidak cocok',
        ])->assertOk();

        $this->assertDatabaseHas('password_reset_requests', [
            'id' => $resetRequest->id,
            'status' => PasswordResetRequest::STATUS_REJECTED,
            'rejection_reason' => 'Data tidak cocok',
        ]);
    }

    public function test_school_admin_cannot_list_reset_requests(): void
    {
        Sanctum::actingAs($this->schoolAdmin);

        $this->getJson('/api/v1/password-reset-requests')->assertForbidden();
    }

    public function test_institution_admin_cannot_use_email_self_service_reset(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/forgot-password', [
            'email' => 'admin-reset@example.com',
        ])->assertOk();

        Notification::assertNotSentTo($this->schoolAdmin, ResetPasswordNotification::class);
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'admin-reset@example.com',
        ]);
    }

    public function test_resetting_from_admin_list_marks_pending_request_processed(): void
    {
        $resetRequest = PasswordResetRequest::create([
            'user_id' => $this->schoolAdmin->id,
            'institution_id' => $this->institution->id,
            'email' => $this->schoolAdmin->email,
            'npsn' => $this->institution->npsn,
            'status' => PasswordResetRequest::STATUS_PENDING,
        ]);

        Sanctum::actingAs($this->superAdmin);

        $this->postJson("/api/v1/super-admin/institution-admins/{$this->schoolAdmin->id}/reset-password")
            ->assertOk()
            ->assertJsonStructure(['temporary_password']);

        $this->assertDatabaseHas('password_reset_requests', [
            'id' => $resetRequest->id,
            'status' => PasswordResetRequest::STATUS_PROCESSED,
            'processed_by' => $this->superAdmin->id,
        ]);
    }
}
