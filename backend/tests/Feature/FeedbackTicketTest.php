<?php

namespace Tests\Feature;

use App\Models\FeedbackTicket;
use App\Models\Institution;
use App\Models\User;
use App\Notifications\FeedbackTicketNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FeedbackTicketTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected Institution $otherInstitution;

    protected User $schoolAdmin;

    protected User $otherAdmin;

    protected User $superAdmin;

    protected User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'SMP Feedback',
            'npsn' => '30303030',
            'level' => 'SMP',
            'is_active' => true,
        ]);

        $this->otherInstitution = Institution::create([
            'name' => 'SMA Lain',
            'npsn' => '40404040',
            'level' => 'SMA',
            'is_active' => true,
        ]);

        $this->schoolAdmin = User::create([
            'name' => 'Admin Sekolah',
            'email' => 'admin-feedback@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->otherAdmin = User::create([
            'name' => 'Admin Lain',
            'email' => 'admin-lain@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->otherInstitution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super-feedback@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'super_admin',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->teacher = User::create([
            'name' => 'Guru Biasa',
            'email' => 'guru-feedback@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'teacher',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
    }

    public function test_school_admin_can_create_feedback_ticket(): void
    {
        Notification::fake();

        Sanctum::actingAs($this->schoolAdmin);

        $response = $this->postJson('/api/v1/feedback-tickets', [
            'type' => 'bug',
            'title' => 'Tombol simpan error',
            'description' => 'Saat klik simpan di modul siswa muncul error 500.',
            'module' => 'Siswa',
            'priority' => 'high',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'bug')
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.title', 'Tombol simpan error');

        $this->assertDatabaseHas('feedback_tickets', [
            'institution_id' => $this->institution->id,
            'submitted_by' => $this->schoolAdmin->id,
            'type' => 'bug',
            'title' => 'Tombol simpan error',
        ]);

        Notification::assertSentTo($this->superAdmin, FeedbackTicketNotification::class);
    }

    public function test_feedback_notification_includes_names_even_without_eager_load(): void
    {
        $ticket = FeedbackTicket::create([
            'institution_id' => $this->institution->id,
            'submitted_by' => $this->schoolAdmin->id,
            'type' => 'bug',
            'title' => 'Notif tanpa eager load',
            'description' => 'Pastikan nama tetap terisi saat queue',
            'priority' => 'medium',
            'status' => FeedbackTicket::STATUS_OPEN,
        ]);

        // Simulate queued payload: model without relations loaded.
        $fresh = FeedbackTicket::findOrFail($ticket->id);
        $this->assertFalse($fresh->relationLoaded('institution'));
        $this->assertFalse($fresh->relationLoaded('submitter'));

        $payload = (new FeedbackTicketNotification($fresh, 'submitted'))->toArray($this->superAdmin);

        $this->assertSame($this->institution->name, $payload['institution_name']);
        $this->assertSame($this->schoolAdmin->name, $payload['submitter_name']);
        $this->assertStringContainsString($this->institution->name, $payload['message']);
    }

    public function test_teacher_cannot_create_feedback_ticket(): void
    {
        Sanctum::actingAs($this->teacher);

        $response = $this->postJson('/api/v1/feedback-tickets', [
            'type' => 'feature',
            'title' => 'Minta fitur baru',
            'description' => 'Tolong tambahkan export PDF.',
        ]);

        $response->assertForbidden();
    }

    public function test_school_admin_only_sees_own_institution_tickets(): void
    {
        FeedbackTicket::create([
            'institution_id' => $this->institution->id,
            'submitted_by' => $this->schoolAdmin->id,
            'type' => 'bug',
            'title' => 'Ticket sekolah saya',
            'description' => 'Deskripsi A',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        FeedbackTicket::create([
            'institution_id' => $this->otherInstitution->id,
            'submitted_by' => $this->otherAdmin->id,
            'type' => 'feature',
            'title' => 'Ticket sekolah lain',
            'description' => 'Deskripsi B',
            'priority' => 'low',
            'status' => 'open',
        ]);

        Sanctum::actingAs($this->schoolAdmin);

        $response = $this->getJson('/api/v1/feedback-tickets');

        $response->assertOk();
        $titles = collect($response->json('data'))->pluck('title')->all();
        $this->assertContains('Ticket sekolah saya', $titles);
        $this->assertNotContains('Ticket sekolah lain', $titles);
    }

    public function test_super_admin_can_update_status_and_notifies_submitter(): void
    {
        Notification::fake();

        $ticket = FeedbackTicket::create([
            'institution_id' => $this->institution->id,
            'submitted_by' => $this->schoolAdmin->id,
            'type' => 'feature',
            'title' => 'Export Excel nilai',
            'description' => 'Mohon tambahkan export Excel di buku nilai.',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        Sanctum::actingAs($this->superAdmin);

        $response = $this->putJson("/api/v1/feedback-tickets/{$ticket->id}", [
            'status' => 'in_progress',
            'priority' => 'high',
            'admin_note' => 'Sedang kami tinjau untuk rilis berikutnya.',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonPath('data.priority', 'high')
            ->assertJsonPath('data.admin_note', 'Sedang kami tinjau untuk rilis berikutnya.');

        $this->assertDatabaseHas('feedback_tickets', [
            'id' => $ticket->id,
            'status' => 'in_progress',
            'handled_by' => $this->superAdmin->id,
        ]);

        Notification::assertSentTo($this->schoolAdmin, FeedbackTicketNotification::class);
    }

    public function test_school_admin_cannot_update_ticket(): void
    {
        $ticket = FeedbackTicket::create([
            'institution_id' => $this->institution->id,
            'submitted_by' => $this->schoolAdmin->id,
            'type' => 'bug',
            'title' => 'Bug UI',
            'description' => 'Layout rusak di mobile.',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        Sanctum::actingAs($this->schoolAdmin);

        $response = $this->putJson("/api/v1/feedback-tickets/{$ticket->id}", [
            'status' => 'resolved',
        ]);

        $response->assertForbidden();
    }

    public function test_open_count_only_for_super_admin(): void
    {
        FeedbackTicket::create([
            'institution_id' => $this->institution->id,
            'submitted_by' => $this->schoolAdmin->id,
            'type' => 'bug',
            'title' => 'Open 1',
            'description' => 'Desc',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        FeedbackTicket::create([
            'institution_id' => $this->institution->id,
            'submitted_by' => $this->schoolAdmin->id,
            'type' => 'feature',
            'title' => 'Resolved 1',
            'description' => 'Desc',
            'priority' => 'medium',
            'status' => 'resolved',
        ]);

        Sanctum::actingAs($this->superAdmin);
        $this->getJson('/api/v1/feedback-tickets/open-count')
            ->assertOk()
            ->assertJsonPath('count', 1);

        Sanctum::actingAs($this->schoolAdmin);
        $this->getJson('/api/v1/feedback-tickets/open-count')
            ->assertForbidden();
    }
}
