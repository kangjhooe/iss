<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Institution;
use App\Models\PpdbApplicant;
use App\Models\PpdbChannel;
use App\Models\PpdbPeriod;
use App\Models\User;
use App\Notifications\PpdbApplicantMailNotification;
use App\Notifications\PpdbRegistrationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PpdbFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected User $admin;

    protected AcademicYear $year;

    protected PpdbPeriod $period;

    protected PpdbChannel $channel;

    protected function setUp(): void
    {
        parent::setUp();

        $today = Carbon::today();
        $this->institution = Institution::create([
            'name' => 'SMP PPDB Flow',
            'npsn' => '70707070',
            'level' => 'SMP',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin PPDB',
            'email' => 'admin-ppdb@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->year = AcademicYear::create([
            'code' => '2025/2026',
            'name' => 'Tahun 2025/2026',
            'start_date' => $today->copy()->subMonths(2),
            'end_date' => $today->copy()->addMonths(10),
            'status' => 'Aktif',
        ]);

        $this->period = PpdbPeriod::create([
            'institution_id' => $this->institution->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Gelombang 1',
            'level' => 'SMP',
            'open_date' => $today->copy()->subDays(1),
            'close_date' => $today->copy()->addDays(14),
            'status' => 'open',
            'registration_fee' => 150000,
        ]);

        $this->channel = PpdbChannel::create([
            'institution_id' => $this->institution->id,
            'code' => 'UMUM',
            'name' => 'Jalur Umum',
            'quota' => 50,
            'is_active' => true,
        ]);
    }

    public function test_public_register_notifies_admin_and_emails_applicant(): void
    {
        Notification::fake();

        $res = $this->postJson('/api/v1/public/ppdb/register', [
            'ppdb_period_id' => $this->period->id,
            'ppdb_channel_id' => $this->channel->id,
            'name' => 'Calon Satu',
            'gender' => 'L',
            'email' => 'calon@example.com',
        ]);

        $res->assertCreated()
            ->assertJsonPath('data.name', 'Calon Satu');

        Notification::assertSentTo($this->admin, PpdbRegistrationNotification::class);
        Notification::assertSentOnDemand(
            PpdbApplicantMailNotification::class,
            function (PpdbApplicantMailNotification $notification) {
                return $notification->action === 'registered';
            }
        );
    }

    public function test_set_result_sends_applicant_email(): void
    {
        Notification::fake();
        Sanctum::actingAs($this->admin);

        $applicant = PpdbApplicant::create([
            'ppdb_period_id' => $this->period->id,
            'ppdb_channel_id' => $this->channel->id,
            'registration_number' => '70707070-' . $this->period->id . '-00001',
            'name' => 'Calon Hasil',
            'gender' => 'P',
            'email' => 'hasil@example.com',
            'status' => 'verified',
            'submitted_at' => now(),
        ]);

        $res = $this->postJson("/api/v1/ppdb-applicants/{$applicant->id}/result", [
            'status' => 'passed',
        ]);
        $res->assertOk()->assertJsonPath('data.status', 'passed');

        Notification::assertSentOnDemand(
            PpdbApplicantMailNotification::class,
            function (PpdbApplicantMailNotification $notification) {
                return $notification->action === 'result'
                    && $notification->applicant->status === 'passed';
            }
        );
    }

    public function test_set_payment_and_export_includes_payment_columns(): void
    {
        Sanctum::actingAs($this->admin);

        $applicant = PpdbApplicant::create([
            'ppdb_period_id' => $this->period->id,
            'ppdb_channel_id' => $this->channel->id,
            'registration_number' => '70707070-' . $this->period->id . '-00002',
            'name' => 'Calon Bayar',
            'gender' => 'L',
            'status' => 'passed',
            'submitted_at' => now(),
            'payment_status' => 'unpaid',
        ]);

        $pay = $this->postJson("/api/v1/ppdb-applicants/{$applicant->id}/payment", [
            'payment_status' => 'paid',
            'payment_amount' => 150000,
        ]);
        $pay->assertOk();
        $this->assertDatabaseHas('ppdb_applicants', [
            'id' => $applicant->id,
            'payment_status' => 'paid',
        ]);

        $waive = $this->postJson("/api/v1/ppdb-applicants/{$applicant->id}/payment", [
            'payment_status' => 'waived',
        ]);
        $waive->assertOk();
        $this->assertDatabaseHas('ppdb_applicants', [
            'id' => $applicant->id,
            'payment_status' => 'waived',
            'paid_at' => null,
        ]);

        $csv = $this->get('/api/v1/ppdb-applicants/export?format=csv&ppdb_period_id=' . $this->period->id);
        $csv->assertOk();
        $body = $csv->streamedContent();
        $this->assertStringContainsString('Status Bayar', $body);
        $this->assertStringContainsString('Nominal Bayar', $body);
        $this->assertStringContainsString('Calon Bayar', $body);
    }

    public function test_public_re_registration_notifies_admin(): void
    {
        Notification::fake();

        $applicant = PpdbApplicant::create([
            'ppdb_period_id' => $this->period->id,
            'ppdb_channel_id' => $this->channel->id,
            'registration_number' => '70707070-' . $this->period->id . '-00003',
            'name' => 'Calon Daftar Ulang',
            'gender' => 'L',
            'status' => 'passed',
            'submitted_at' => now(),
            'announcement_at' => now(),
        ]);

        $res = $this->postJson('/api/v1/public/ppdb/confirm-re-registration', [
            'registration_number' => $applicant->registration_number,
            'npsn' => $this->institution->npsn,
        ]);
        $res->assertOk();

        Notification::assertSentTo(
            $this->admin,
            PpdbRegistrationNotification::class,
            function (PpdbRegistrationNotification $notification) {
                return $notification->action === 're_registration';
            }
        );
    }

    public function test_convert_to_student_after_re_registration(): void
    {
        Sanctum::actingAs($this->admin);

        $applicant = PpdbApplicant::create([
            'ppdb_period_id' => $this->period->id,
            'ppdb_channel_id' => $this->channel->id,
            'registration_number' => '70707070-' . $this->period->id . '-00004',
            'name' => 'Calon Jadi Siswa',
            'gender' => 'P',
            'status' => 're_registration',
            'submitted_at' => now(),
            're_registration_confirmed_at' => now(),
            'payment_status' => 'paid',
        ]);

        $res = $this->postJson("/api/v1/ppdb-applicants/{$applicant->id}/convert-to-student", []);
        $res->assertOk();
        $this->assertNotNull($res->json('data.student_id') ?? $res->json('student_id') ?? $applicant->fresh()->student_id);
        $this->assertNotNull($applicant->fresh()->student_id);
    }
}
