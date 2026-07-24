<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamParticipant;
use App\Models\ExamSession;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExamOnlineControlTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected User $admin;

    protected Exam $exam;

    protected ExamSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'SMA Ujian',
            'npsn' => '30303030',
            'level' => 'SMA',
            'is_active' => true,
            'province_code' => '32',
            'district_code' => '01',
        ]);

        $this->admin = User::create([
            'name' => 'Admin Ujian',
            'email' => 'admin-ujian@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
        ]);

        $this->exam = Exam::create([
            'institution_id' => $this->institution->id,
            'code' => 'UJ-01',
            'name' => 'Ujian Matematika',
            'duration_minutes' => 60,
            'created_by' => $this->admin->id,
        ]);

        $this->session = ExamSession::create([
            'exam_id' => $this->exam->id,
            'name' => 'Sesi Pagi',
            'status' => ExamSession::STATUS_STARTED,
            'started_at' => now(),
            'entry_pin' => 'ABCDEF',
            'entry_pin_updated_at' => now(),
        ]);
    }

    protected function makeParticipant(array $overrides = []): ExamParticipant
    {
        $student = Student::create([
            'institution_id' => $this->institution->id,
            'name' => $overrides['student_name'] ?? 'Siswa Uji',
            'nisn' => $overrides['nisn'] ?? ('00'.random_int(10000000, 99999999)),
            'gender' => 'L',
        ]);

        return ExamParticipant::create(array_merge([
            'exam_session_id' => $this->session->id,
            'student_id' => $student->id,
            'participant_order' => 1,
            'login_token' => ExamParticipant::generateLoginToken(),
            'status' => ExamParticipant::STATUS_REGISTERED,
        ], collect($overrides)->except(['student_name', 'nisn'])->all()));
    }

    public function test_monitor_returns_summary_and_participants(): void
    {
        $this->makeParticipant([
            'status' => ExamParticipant::STATUS_STARTED,
            'started_at' => now(),
            'student_name' => 'Sedang Mengerjakan',
        ]);
        $this->makeParticipant([
            'participant_order' => 2,
            'status' => ExamParticipant::STATUS_SUBMITTED,
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
            'score' => 80,
            'score_max' => 100,
            'student_name' => 'Sudah Selesai',
            'nisn' => '0011223344',
        ]);

        Sanctum::actingAs($this->admin);

        $res = $this->getJson('/api/v1/exam/sessions/'.$this->session->id.'/monitor');

        $res->assertOk()
            ->assertJsonPath('data.summary.total', 2)
            ->assertJsonPath('data.summary.started', 1)
            ->assertJsonPath('data.summary.submitted', 1)
            ->assertJsonPath('data.summary.avg_score', 80);
    }

    public function test_release_all_scores_and_reset_participant(): void
    {
        $ready = $this->makeParticipant([
            'status' => ExamParticipant::STATUS_SUBMITTED,
            'score' => 90,
            'score_max' => 100,
            'score_released' => false,
            'student_name' => 'Siap Rilis',
        ]);
        $busy = $this->makeParticipant([
            'participant_order' => 2,
            'status' => ExamParticipant::STATUS_STARTED,
            'started_at' => now(),
            'student_name' => 'Masih Kerja',
            'nisn' => '0099887766',
        ]);

        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/exam/sessions/'.$this->session->id.'/release-scores')
            ->assertOk()
            ->assertJsonPath('released_count', 1);

        $this->assertTrue($ready->fresh()->score_released);

        $this->postJson('/api/v1/exam/participants/'.$busy->id.'/reset')
            ->assertOk();

        $busy->refresh();
        $this->assertSame(ExamParticipant::STATUS_REGISTERED, $busy->status);
        $this->assertNull($busy->started_at);
    }

    public function test_export_results_returns_xlsx(): void
    {
        $this->makeParticipant([
            'status' => ExamParticipant::STATUS_SUBMITTED,
            'score' => 75,
            'score_max' => 100,
            'score_released' => true,
        ]);

        Sanctum::actingAs($this->admin);

        $res = $this->get('/api/v1/exam/sessions/'.$this->session->id.'/export-results');

        $res->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml',
            (string) $res->headers->get('content-type')
        );
    }
}
