<?php

namespace Tests\Feature;

use App\Models\BankSoal;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ExamSession;
use App\Models\Institution;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use App\Models\Subject;
use App\Models\User;
use App\Services\ExamQuestionSnapshot;
use App\Services\ExamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExamQuestionBankTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected User $admin;

    protected Subject $math;

    protected Subject $ipa;

    protected BankSoal $bankGrade7;

    protected BankSoal $bankGrade9;

    protected QuestionBank $qGrade7;

    protected QuestionBank $qGrade9;

    protected QuestionBank $qIpa;

    protected Exam $exam;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'SMP Bank Soal',
            'npsn' => '40404040',
            'level' => 'SMP',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Bank',
            'email' => 'admin-bank@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
        ]);

        $this->math = Subject::create([
            'institution_id' => $this->institution->id,
            'code' => 'MTK',
            'name' => 'Matematika',
            'is_active' => true,
        ]);
        $this->ipa = Subject::create([
            'institution_id' => $this->institution->id,
            'code' => 'IPA',
            'name' => 'IPA',
            'is_active' => true,
        ]);

        $this->bankGrade7 = BankSoal::create([
            'institution_id' => $this->institution->id,
            'created_by_user_id' => $this->admin->id,
            'code' => 'MTK-7',
            'name' => 'Matematika kelas 7',
            'subject_id' => $this->math->id,
            'grade' => 7,
        ]);
        $this->bankGrade9 = BankSoal::create([
            'institution_id' => $this->institution->id,
            'created_by_user_id' => $this->admin->id,
            'code' => 'MTK-9',
            'name' => 'Matematika kelas 9',
            'subject_id' => $this->math->id,
            'grade' => 9,
        ]);

        $this->qGrade7 = $this->makePg($this->bankGrade7, 'Hasil 3 + 4 adalah …', '7', ['6', '7', '8']);
        $this->qGrade9 = $this->makePg($this->bankGrade9, 'Akar 81 adalah …', '9', ['7', '8', '9']);
        $ipaBank = BankSoal::create([
            'institution_id' => $this->institution->id,
            'created_by_user_id' => $this->admin->id,
            'code' => 'IPA-7',
            'name' => 'IPA kelas 7',
            'subject_id' => $this->ipa->id,
            'grade' => 7,
        ]);
        $this->qIpa = $this->makeIsian($ipaBank, 'Lambang air', 'H2O');

        $this->exam = Exam::create([
            'institution_id' => $this->institution->id,
            'subject_id' => $this->math->id,
            'code' => 'PTS-9',
            'name' => 'PTS Matematika kelas 9',
            'duration_minutes' => 60,
            'created_by' => $this->admin->id,
        ]);
    }

    protected function makePg(BankSoal $bank, string $body, string $correct, array $options): QuestionBank
    {
        $q = QuestionBank::create([
            'institution_id' => $this->institution->id,
            'bank_soal_id' => $bank->id,
            'subject_id' => $bank->subject_id,
            'type' => QuestionBank::TYPE_PG,
            'body' => $body,
            'weight' => 1,
            'sort_order' => 1,
        ]);
        foreach ($options as $i => $text) {
            QuestionOption::create([
                'question_bank_id' => $q->id,
                'option_key' => chr(65 + $i),
                'body' => $text,
                'is_correct' => $text === $correct,
                'option_weight' => $text === $correct ? 1 : 0,
                'sort_order' => $i,
            ]);
        }

        return $q->fresh(['options']);
    }

    protected function makeIsian(BankSoal $bank, string $body, string $key): QuestionBank
    {
        return QuestionBank::create([
            'institution_id' => $this->institution->id,
            'bank_soal_id' => $bank->id,
            'subject_id' => $bank->subject_id,
            'type' => QuestionBank::TYPE_ISIAN,
            'body' => $body,
            'weight' => 2,
            'key_answer' => $key,
            'sort_order' => 1,
        ]);
    }

    public function test_exam_can_attach_grade_7_questions_to_grade_9_exam(): void
    {
        Sanctum::actingAs($this->admin);

        $res = $this->postJson('/api/v1/exam/exams/'.$this->exam->id.'/questions', [
            'question_bank_ids' => [$this->qGrade7->id, $this->qGrade9->id],
        ]);

        $res->assertOk()
            ->assertJsonPath('message', 'Paket soal ujian disimpan.');
        $this->assertDatabaseCount('exam_questions', 2);

        $row = ExamQuestion::where('question_bank_id', $this->qGrade7->id)->first();
        $this->assertNotNull($row->snapshot);
        $this->assertSame('Hasil 3 + 4 adalah …', $row->snapshot['body']);
        $this->assertSame(7, $row->snapshot['bank_grade']);
    }

    public function test_attach_rejects_other_subject(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/exam/exams/'.$this->exam->id.'/questions', [
            'question_bank_ids' => [$this->qGrade7->id, $this->qIpa->id],
        ])->assertStatus(422)
            ->assertJsonFragment(['message' => 'Soal harus dari mata pelajaran yang sama dengan ujian. Tingkat kelas tidak membatasi.']);
    }

    public function test_editing_bank_does_not_change_attached_exam_package(): void
    {
        Sanctum::actingAs($this->admin);
        $this->postJson('/api/v1/exam/exams/'.$this->exam->id.'/questions', [
            'question_bank_ids' => [$this->qGrade7->id],
        ])->assertOk();

        $this->qGrade7->update(['body' => 'Teks bank sudah diubah', 'key_answer' => 'xx']);

        $row = ExamQuestion::where('exam_id', $this->exam->id)->first();
        $this->assertSame('Hasil 3 + 4 adalah …', $row->snapshot['body']);

        $payload = $row->scoringPayload($this->qGrade7->fresh());
        $this->assertSame('Hasil 3 + 4 adalah …', $payload['body']);
    }

    public function test_attempt_serves_snapshot_not_live_bank_text(): void
    {
        Sanctum::actingAs($this->admin);
        $this->postJson('/api/v1/exam/exams/'.$this->exam->id.'/questions', [
            'question_bank_ids' => [$this->qGrade7->id],
        ])->assertOk();

        $this->qGrade7->update(['body' => 'Teks bank sudah diubah']);

        $session = ExamSession::create([
            'exam_id' => $this->exam->id,
            'name' => 'Sesi 1',
            'status' => ExamSession::STATUS_STARTED,
            'started_at' => now(),
            'entry_pin' => 'ABCDEF',
        ]);
        $participant = \App\Models\ExamParticipant::create([
            'exam_session_id' => $session->id,
            'student_id' => \App\Models\Student::create([
                'institution_id' => $this->institution->id,
                'name' => 'Siswa PTS',
                'nisn' => '0011223300',
                'gender' => 'L',
            ])->id,
            'participant_order' => 1,
            'login_token' => \App\Models\ExamParticipant::generateLoginToken(),
            'status' => \App\Models\ExamParticipant::STATUS_REGISTERED,
        ]);

        $service = app(ExamService::class);
        $participant = $service->startParticipant($participant);
        $q = $service->getQuestionAt($participant->fresh(), 0);

        $this->assertSame('Hasil 3 + 4 adalah …', $q['body']);
        $this->assertArrayNotHasKey('is_correct', $q['options'][0]);
    }

    public function test_cannot_delete_question_used_in_exam(): void
    {
        Sanctum::actingAs($this->admin);
        $this->postJson('/api/v1/exam/exams/'.$this->exam->id.'/questions', [
            'question_bank_ids' => [$this->qGrade7->id],
        ])->assertOk();

        $this->deleteJson('/api/v1/question-bank/'.$this->qGrade7->id)
            ->assertStatus(422);

        $this->deleteJson('/api/v1/banks/'.$this->bankGrade7->id)
            ->assertStatus(422);
    }

    public function test_cannot_replace_package_after_session_started(): void
    {
        Sanctum::actingAs($this->admin);
        $this->postJson('/api/v1/exam/exams/'.$this->exam->id.'/questions', [
            'question_bank_ids' => [$this->qGrade7->id],
        ])->assertOk();

        ExamSession::create([
            'exam_id' => $this->exam->id,
            'name' => 'Sesi jalan',
            'status' => ExamSession::STATUS_STARTED,
            'started_at' => now(),
        ]);

        $this->postJson('/api/v1/exam/exams/'.$this->exam->id.'/questions', [
            'question_bank_ids' => [$this->qGrade9->id],
        ])->assertStatus(422);
    }

    public function test_picker_lists_cross_grade_questions_and_can_filter_grade(): void
    {
        Sanctum::actingAs($this->admin);

        $all = $this->getJson('/api/v1/question-bank?subject_id='.$this->math->id.'&compact=1&per_page=50');
        $all->assertOk();
        $ids = collect($all->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($this->qGrade7->id));
        $this->assertTrue($ids->contains($this->qGrade9->id));

        $only7 = $this->getJson('/api/v1/question-bank?subject_id='.$this->math->id.'&grade=7&compact=1');
        $only7Ids = collect($only7->json('data'))->pluck('id');
        $this->assertTrue($only7Ids->contains($this->qGrade7->id));
        $this->assertFalse($only7Ids->contains($this->qGrade9->id));
    }

    public function test_snapshot_isian_score_uses_frozen_key(): void
    {
        $bank = $this->bankGrade7;
        $isian = $this->makeIsian($bank, 'Ibu kota', 'Jakarta');
        $exam = Exam::create([
            'institution_id' => $this->institution->id,
            'subject_id' => $this->math->id,
            'code' => 'LAT-1',
            'name' => 'Latihan',
            'duration_minutes' => 30,
            'created_by' => $this->admin->id,
        ]);
        $eq = ExamQuestion::create([
            'exam_id' => $exam->id,
            'question_bank_id' => $isian->id,
            'sort_order' => 0,
            'snapshot' => ExamQuestionSnapshot::capture($isian),
        ]);
        $isian->update(['key_answer' => 'Bandung']);

        $session = ExamSession::create([
            'exam_id' => $exam->id,
            'name' => 'Sesi',
            'status' => ExamSession::STATUS_STARTED,
            'started_at' => now(),
        ]);
        $participant = \App\Models\ExamParticipant::create([
            'exam_session_id' => $session->id,
            'student_id' => \App\Models\Student::create([
                'institution_id' => $this->institution->id,
                'name' => 'Siswa Isian',
                'nisn' => '0099887766',
                'gender' => 'P',
            ])->id,
            'login_token' => \App\Models\ExamParticipant::generateLoginToken(),
            'status' => \App\Models\ExamParticipant::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);
        $answer = \App\Models\ExamAnswer::create([
            'exam_participant_id' => $participant->id,
            'question_bank_id' => $isian->id,
            'answer_text' => 'Jakarta',
        ]);

        app(ExamService::class)->computeAutoScoreForParticipant($participant->fresh('answers'));
        $this->assertEquals(2, (float) $answer->fresh()->score);
        $this->assertSame('Jakarta', $eq->fresh()->snapshot['key_answer']);
    }
}
