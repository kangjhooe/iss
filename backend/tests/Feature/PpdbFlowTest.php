<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Institution;
use App\Models\PpdbApplicant;
use App\Models\PpdbChannel;
use App\Models\PpdbPeriod;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Notifications\PpdbApplicantMailNotification;
use App\Notifications\PpdbRegistrationNotification;
use App\Services\PpdbAcceptedElsewhereService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
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
            'birth_date' => '2011-03-15',
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
            'registration_number' => '70707070-'.$this->period->id.'-00001',
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

    public function test_set_result_succeeds_when_jobs_table_missing(): void
    {
        Schema::dropIfExists('jobs');
        Sanctum::actingAs($this->admin);

        $applicant = PpdbApplicant::create([
            'ppdb_period_id' => $this->period->id,
            'ppdb_channel_id' => $this->channel->id,
            'registration_number' => '70707070-'.$this->period->id.'-00006',
            'name' => 'Calon Tanpa Jobs',
            'gender' => 'L',
            'email' => 'tanpajobs@example.com',
            'status' => 'verified',
            'submitted_at' => now(),
        ]);

        $this->postJson("/api/v1/ppdb-applicants/{$applicant->id}/result", [
            'status' => 'passed',
        ])->assertOk()->assertJsonPath('data.status', 'passed');

        $this->assertSame('passed', $applicant->fresh()->status);
    }

    public function test_set_payment_and_export_includes_payment_columns(): void
    {
        Sanctum::actingAs($this->admin);

        $applicant = PpdbApplicant::create([
            'ppdb_period_id' => $this->period->id,
            'ppdb_channel_id' => $this->channel->id,
            'registration_number' => '70707070-'.$this->period->id.'-00002',
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

        $csv = $this->get('/api/v1/ppdb-applicants/export?format=csv&ppdb_period_id='.$this->period->id);
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
            'registration_number' => '70707070-'.$this->period->id.'-00003',
            'name' => 'Calon Daftar Ulang',
            'gender' => 'L',
            'birth_date' => '2011-04-01',
            'status' => 'passed',
            'submitted_at' => now(),
            'announcement_at' => now(),
        ]);

        $res = $this->postJson('/api/v1/public/ppdb/confirm-re-registration', [
            'registration_number' => $applicant->registration_number,
            'birth_date' => '2011-04-01',
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
            'registration_number' => '70707070-'.$this->period->id.'-00004',
            'name' => 'Calon Jadi Siswa',
            'gender' => 'P',
            'status' => 're_registration',
            'submitted_at' => now(),
            're_registration_confirmed_at' => now(),
            'payment_status' => 'paid',
            'address' => 'Jl. Melati No. 1',
            'village' => 'Way Halim',
            'sub_district' => 'Kedaton',
            'district' => 'Bandar Lampung',
            'province' => 'Lampung',
            'postal_code' => '35141',
            'wilayah_province_code' => '18',
        ]);

        $res = $this->postJson("/api/v1/ppdb-applicants/{$applicant->id}/convert-to-student", []);
        $res->assertOk();
        $studentId = $applicant->fresh()->student_id;
        $this->assertNotNull($studentId);
        $this->assertDatabaseHas('student', [
            'id' => $studentId,
            'address' => 'Jl. Melati No. 1',
            'village' => 'Way Halim',
            'sub_district' => 'Kedaton',
            'district' => 'Bandar Lampung',
            'province' => 'Lampung',
            'postal_code' => '35141',
            'wilayah_province_code' => '18',
        ]);
    }

    public function test_convert_to_student_succeeds_without_nis_sequence_table(): void
    {
        Schema::dropIfExists('institution_nis_sequences');
        Sanctum::actingAs($this->admin);

        $applicant = PpdbApplicant::create([
            'ppdb_period_id' => $this->period->id,
            'ppdb_channel_id' => $this->channel->id,
            'registration_number' => '70707070-'.$this->period->id.'-00005',
            'name' => 'Calon Tanpa Tabel NIS',
            'gender' => 'L',
            'status' => 're_registration',
            'submitted_at' => now(),
            're_registration_confirmed_at' => now(),
            'payment_status' => 'paid',
        ]);

        $res = $this->postJson("/api/v1/ppdb-applicants/{$applicant->id}/convert-to-student", []);
        $res->assertOk();
        $this->assertNotNull($applicant->fresh()->student_id);
        $this->assertStringContainsString('Akun login belum dibuat', $res->json('message'));
    }

    public function test_convert_rejects_duplicate_nisn_and_foreign_class(): void
    {
        Sanctum::actingAs($this->admin);

        Student::create([
            'institution_id' => $this->institution->id,
            'nisn' => '1234567890',
            'name' => 'Siswa Lama',
            'gender' => 'L',
            'status' => 'Aktif',
        ]);

        $duplicate = PpdbApplicant::create([
            'ppdb_period_id' => $this->period->id,
            'ppdb_channel_id' => $this->channel->id,
            'registration_number' => '70707070-'.$this->period->id.'-00007',
            'name' => 'Calon NISN Bentrok',
            'gender' => 'L',
            'nisn' => '1234567890',
            'status' => 're_registration',
            'submitted_at' => now(),
            're_registration_confirmed_at' => now(),
        ]);

        $this->postJson("/api/v1/ppdb-applicants/{$duplicate->id}/convert-to-student", [])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'NISN sudah terdaftar sebagai siswa aktif. Periksa data siswa atau kosongkan NISN calon.']);
        $this->assertNull($duplicate->fresh()->student_id);
        $this->assertSame('accepted_elsewhere', $duplicate->fresh()->status);

        $other = Institution::create([
            'name' => 'SMP Lain',
            'npsn' => '10101010',
            'level' => 'SMP',
            'is_active' => true,
        ]);
        $foreignClass = SchoolClass::create([
            'institution_id' => $other->id,
            'academic_year_id' => $this->year->id,
            'academic_year' => '2025/2026',
            'name' => 'VII-X',
            'grade' => 7,
            'status' => 'Aktif',
        ]);

        $applicant = PpdbApplicant::create([
            'ppdb_period_id' => $this->period->id,
            'ppdb_channel_id' => $this->channel->id,
            'registration_number' => '70707070-'.$this->period->id.'-00008',
            'name' => 'Calon Kelas Asing',
            'gender' => 'P',
            'status' => 're_registration',
            'submitted_at' => now(),
            're_registration_confirmed_at' => now(),
        ]);

        $this->postJson("/api/v1/ppdb-applicants/{$applicant->id}/convert-to-student", [
            'class_id' => $foreignClass->id,
        ])->assertStatus(422)
            ->assertJsonFragment(['message' => 'Kelas tidak valid untuk sekolah atau tahun ajaran periode PPDB ini.']);
        $this->assertNull($applicant->fresh()->student_id);
    }

    public function test_bulk_result_sends_applicant_emails(): void
    {
        Notification::fake();
        Sanctum::actingAs($this->admin);

        $a = PpdbApplicant::create([
            'ppdb_period_id' => $this->period->id,
            'ppdb_channel_id' => $this->channel->id,
            'registration_number' => '70707070-'.$this->period->id.'-00009',
            'name' => 'Bulk Satu',
            'gender' => 'L',
            'email' => 'bulk1@example.com',
            'status' => 'verified',
            'submitted_at' => now(),
        ]);
        $b = PpdbApplicant::create([
            'ppdb_period_id' => $this->period->id,
            'ppdb_channel_id' => $this->channel->id,
            'registration_number' => '70707070-'.$this->period->id.'-00010',
            'name' => 'Bulk Dua',
            'gender' => 'P',
            'email' => 'bulk2@example.com',
            'status' => 'verified',
            'submitted_at' => now(),
        ]);

        $this->postJson('/api/v1/ppdb-applicants/bulk-result', [
            'applicant_ids' => [$a->id, $b->id],
            'status' => 'passed',
        ])->assertOk()->assertJsonPath('updated_count', 2);

        $this->assertSame('passed', $a->fresh()->status);
        Notification::assertSentOnDemand(PpdbApplicantMailNotification::class, function (PpdbApplicantMailNotification $notification) {
            return $notification->action === 'result';
        });
    }

    public function test_bulk_result_can_update_already_passed_applicants(): void
    {
        Sanctum::actingAs($this->admin);

        $a = PpdbApplicant::create([
            'ppdb_period_id' => $this->period->id,
            'ppdb_channel_id' => $this->channel->id,
            'registration_number' => '70707070-'.$this->period->id.'-00011',
            'name' => 'Sudah Lulus',
            'gender' => 'L',
            'status' => 'passed',
            'submitted_at' => now(),
            'announcement_at' => now(),
        ]);

        $this->postJson('/api/v1/ppdb-applicants/bulk-result', [
            'applicant_ids' => [$a->id],
            'status' => 'reserve',
        ])->assertOk()->assertJsonPath('updated_count', 1);

        $this->assertSame('reserve', $a->fresh()->status);
    }

    public function test_bulk_verification_skips_passed_applicants(): void
    {
        Sanctum::actingAs($this->admin);

        $passed = PpdbApplicant::create([
            'ppdb_period_id' => $this->period->id,
            'ppdb_channel_id' => $this->channel->id,
            'registration_number' => '70707070-'.$this->period->id.'-00012',
            'name' => 'Jangan Diverifikasi Ulang',
            'gender' => 'P',
            'status' => 'passed',
            'submitted_at' => now(),
        ]);
        $pending = PpdbApplicant::create([
            'ppdb_period_id' => $this->period->id,
            'ppdb_channel_id' => $this->channel->id,
            'registration_number' => '70707070-'.$this->period->id.'-00013',
            'name' => 'Perlu Verifikasi',
            'gender' => 'L',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->postJson('/api/v1/ppdb-applicants/bulk-verification', [
            'applicant_ids' => [$passed->id, $pending->id],
            'documents_verified' => true,
        ])->assertOk()
            ->assertJsonPath('updated_count', 1);

        $this->assertSame('passed', $passed->fresh()->status);
        $this->assertSame('verified', $pending->fresh()->status);
    }

    public function test_channel_can_save_required_documents(): void
    {
        Sanctum::actingAs($this->admin);

        $this->putJson('/api/v1/ppdb-channels/'.$this->channel->id, [
            'required_documents' => [
                ['key' => 'kk', 'label' => 'Kartu Keluarga', 'required' => true],
                ['label' => 'Foto 3x4', 'required' => true],
            ],
        ])->assertOk()
            ->assertJsonPath('data.required_documents.0.key', 'kk')
            ->assertJsonPath('data.required_documents.1.key', 'foto_3x4');
    }

    public function test_applicant_index_includes_incomplete_document_summary(): void
    {
        Sanctum::actingAs($this->admin);

        $this->channel->update([
            'required_documents' => [
                ['key' => 'kk', 'label' => 'Kartu Keluarga', 'required' => true],
            ],
        ]);

        $applicant = PpdbApplicant::create([
            'ppdb_period_id' => $this->period->id,
            'ppdb_channel_id' => $this->channel->id,
            'registration_number' => '70707070-'.$this->period->id.'-00021',
            'name' => 'Calon Berkas',
            'gender' => 'L',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->getJson('/api/v1/ppdb-applicants?search='.$applicant->registration_number)
            ->assertOk()
            ->assertJsonPath('data.0.document_summary.complete', false)
            ->assertJsonPath('data.0.document_summary.required_total', 1)
            ->assertJsonPath('data.0.document_summary.required_uploaded', 0);
    }

    public function test_public_upload_with_document_key_completes_checklist_and_slip_pdf(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');

        $this->channel->update([
            'required_documents' => [
                ['key' => 'kk', 'label' => 'Kartu Keluarga', 'required' => true],
            ],
        ]);

        $applicant = PpdbApplicant::create([
            'ppdb_period_id' => $this->period->id,
            'ppdb_channel_id' => $this->channel->id,
            'registration_number' => '70707070-'.$this->period->id.'-00022',
            'name' => 'Calon PDF',
            'gender' => 'P',
            'birth_date' => '2011-06-12',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->getJson('/api/v1/public/ppdb/document-checklist?registration_number='.$applicant->registration_number.'&birth_date=2011-06-12&npsn=70707070')
            ->assertOk()
            ->assertJsonPath('data.document_summary.complete', false);

        $file = \Illuminate\Http\UploadedFile::fake()->create('kk.pdf', 80, 'application/pdf');
        $this->post('/api/v1/public/ppdb/documents', [
            'registration_number' => $applicant->registration_number,
            'birth_date' => '2011-06-12',
            'npsn' => '70707070',
            'document_key' => 'kk',
            'file' => $file,
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.document_key', 'kk')
            ->assertJsonPath('data.name', 'Kartu Keluarga')
            ->assertJsonPath('document_summary.complete', true);

        $this->assertDatabaseHas('ppdb_applicant_documents', [
            'ppdb_applicant_id' => $applicant->id,
            'document_key' => 'kk',
            'name' => 'Kartu Keluarga',
        ]);

        $publicPdf = $this->get('/api/v1/public/ppdb/registration-slip?registration_number='.$applicant->registration_number.'&birth_date=2011-06-12&npsn=70707070');
        $publicPdf->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $publicPdf->headers->get('content-type'));

        Sanctum::actingAs($this->admin);
        $adminPdf = $this->get('/api/v1/ppdb-applicants/'.$applicant->id.'/registration-slip');
        $adminPdf->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $adminPdf->headers->get('content-type'));
    }

    public function test_convert_marks_hanging_applicant_at_other_school_by_nisn(): void
    {
        $other = $this->makeOtherSchoolPpdb();
        $nisn = '0099887766';

        $hanging = PpdbApplicant::create([
            'ppdb_period_id' => $this->period->id,
            'ppdb_channel_id' => $this->channel->id,
            'registration_number' => '70707070-'.$this->period->id.'-00031',
            'name' => 'Calon Gantung A',
            'gender' => 'L',
            'nisn' => $nisn,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $rejected = PpdbApplicant::create([
            'ppdb_period_id' => $this->period->id,
            'ppdb_channel_id' => $this->channel->id,
            'registration_number' => '70707070-'.$this->period->id.'-00032',
            'name' => 'Calon Ditolak A',
            'gender' => 'L',
            'nisn' => $nisn,
            'status' => 'rejected',
            'submitted_at' => now(),
        ]);

        Sanctum::actingAs($other['admin']);
        $winner = PpdbApplicant::create([
            'ppdb_period_id' => $other['period']->id,
            'ppdb_channel_id' => $other['channel']->id,
            'registration_number' => '80808080-'.$other['period']->id.'-00001',
            'name' => 'Calon Diterima B',
            'gender' => 'L',
            'nisn' => $nisn,
            'status' => 're_registration',
            'submitted_at' => now(),
            're_registration_confirmed_at' => now(),
        ]);

        $this->postJson("/api/v1/ppdb-applicants/{$winner->id}/convert-to-student", [])
            ->assertOk();

        $this->assertSame('converted', $winner->fresh()->status);
        $this->assertNotNull($winner->fresh()->student_id);
        $this->assertSame('accepted_elsewhere', $hanging->fresh()->status);
        $this->assertSame(PpdbAcceptedElsewhereService::PUBLIC_NOTE, $hanging->fresh()->result_notes);
        $this->assertStringContainsString('SMP Tujuan PPDB', (string) $hanging->fresh()->notes);
        $this->assertSame('rejected', $rejected->fresh()->status);
    }

    public function test_convert_marks_hanging_applicant_at_other_school_by_nik(): void
    {
        $other = $this->makeOtherSchoolPpdb();
        $nik = '3271010101010001';

        $hanging = PpdbApplicant::create([
            'ppdb_period_id' => $this->period->id,
            'ppdb_channel_id' => $this->channel->id,
            'registration_number' => '70707070-'.$this->period->id.'-00033',
            'name' => 'Calon NIK A',
            'gender' => 'P',
            'nik' => $nik,
            'status' => 'verified',
            'submitted_at' => now(),
        ]);

        Sanctum::actingAs($other['admin']);
        $winner = PpdbApplicant::create([
            'ppdb_period_id' => $other['period']->id,
            'ppdb_channel_id' => $other['channel']->id,
            'registration_number' => '80808080-'.$other['period']->id.'-00002',
            'name' => 'Calon NIK B',
            'gender' => 'P',
            'nik' => $nik,
            'status' => 're_registration',
            'submitted_at' => now(),
            're_registration_confirmed_at' => now(),
        ]);

        $this->postJson("/api/v1/ppdb-applicants/{$winner->id}/convert-to-student", [])
            ->assertOk();

        $this->assertSame('accepted_elsewhere', $hanging->fresh()->status);
        $this->assertSame('converted', $winner->fresh()->status);
    }

    public function test_creating_student_marks_hanging_ppdb_elsewhere(): void
    {
        $hanging = PpdbApplicant::create([
            'ppdb_period_id' => $this->period->id,
            'ppdb_channel_id' => $this->channel->id,
            'registration_number' => '70707070-'.$this->period->id.'-00034',
            'name' => 'Calon Import',
            'gender' => 'L',
            'nisn' => '1122334455',
            'status' => 'passed',
            'submitted_at' => now(),
        ]);

        $other = Institution::create([
            'name' => 'SMP Input Manual',
            'npsn' => '90909090',
            'level' => 'SMP',
            'is_active' => true,
        ]);

        Student::create([
            'institution_id' => $other->id,
            'nisn' => '1122334455',
            'name' => 'Siswa Input',
            'gender' => 'L',
            'status' => 'Aktif',
        ]);

        $this->assertSame('accepted_elsewhere', $hanging->fresh()->status);
    }

    public function test_public_register_rejects_nisn_already_student(): void
    {
        Student::create([
            'institution_id' => $this->institution->id,
            'nisn' => '5566778899',
            'name' => 'Siswa Ada',
            'gender' => 'L',
            'status' => 'Aktif',
        ]);

        $this->postJson('/api/v1/public/ppdb/register', [
            'ppdb_period_id' => $this->period->id,
            'ppdb_channel_id' => $this->channel->id,
            'name' => 'Calon Duplikat',
            'gender' => 'L',
            'birth_date' => '2011-07-01',
            'nisn' => '5566778899',
        ])->assertStatus(422)
            ->assertJsonFragment(['NISN sudah terdaftar sebagai siswa aktif. Pendaftaran baru tidak dapat dilanjutkan.']);
    }

    public function test_committee_cannot_set_result_after_accepted_elsewhere(): void
    {
        Sanctum::actingAs($this->admin);

        $applicant = PpdbApplicant::create([
            'ppdb_period_id' => $this->period->id,
            'ppdb_channel_id' => $this->channel->id,
            'registration_number' => '70707070-'.$this->period->id.'-00035',
            'name' => 'Calon Terkunci',
            'gender' => 'L',
            'birth_date' => '2011-08-20',
            'status' => 'accepted_elsewhere',
            'submitted_at' => now(),
            'result_notes' => PpdbAcceptedElsewhereService::PUBLIC_NOTE,
        ]);

        $this->postJson("/api/v1/ppdb-applicants/{$applicant->id}/result", [
            'status' => 'passed',
        ])->assertStatus(422);

        $this->postJson("/api/v1/ppdb-applicants/{$applicant->id}/verification", [
            'documents_verified' => true,
        ])->assertStatus(422);

        $this->getJson('/api/v1/public/ppdb/check-result?registration_number='.$applicant->registration_number.'&birth_date=2011-08-20&npsn=70707070')
            ->assertOk()
            ->assertJsonPath('data.status', 'accepted_elsewhere')
            ->assertJsonPath('data.result_notes', PpdbAcceptedElsewhereService::PUBLIC_NOTE);
    }

    /**
     * @return array{institution: Institution, admin: User, period: PpdbPeriod, channel: PpdbChannel}
     */
    private function makeOtherSchoolPpdb(): array
    {
        $institution = Institution::create([
            'name' => 'SMP Tujuan PPDB',
            'npsn' => '80808080',
            'level' => 'SMP',
            'is_active' => true,
        ]);

        $admin = User::create([
            'name' => 'Admin Tujuan',
            'email' => 'admin-ppdb-b@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $period = PpdbPeriod::create([
            'institution_id' => $institution->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Gelombang B',
            'level' => 'SMP',
            'open_date' => Carbon::today()->subDays(1),
            'close_date' => Carbon::today()->addDays(14),
            'status' => 'open',
        ]);

        $channel = PpdbChannel::create([
            'institution_id' => $institution->id,
            'code' => 'UMUM-B',
            'name' => 'Jalur Umum B',
            'quota' => 40,
            'is_active' => true,
        ]);

        return compact('institution', 'admin', 'period', 'channel');
    }
}
