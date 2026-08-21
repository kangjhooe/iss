<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AlumniDestination;
use App\Models\Institution;
use App\Models\PpdbChannel;
use App\Models\PpdbPeriod;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Notifications\AlumniDestinationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AlumniEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $mts;

    protected Institution $ma;

    protected User $mtsAdmin;

    protected User $maAdmin;

    protected Student $alumni;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mts = Institution::create([
            'name' => 'MTs A',
            'npsn' => '11112222',
            'level' => 'MTs',
            'is_active' => true,
        ]);
        $this->ma = Institution::create([
            'name' => 'MA B',
            'npsn' => '33334444',
            'level' => 'MA',
            'is_active' => true,
        ]);

        $this->mtsAdmin = User::create([
            'name' => 'Admin MTs A',
            'email' => 'admin-mts-a@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->mts->id,
            'email_verified_at' => now(),
        ]);
        $this->maAdmin = User::create([
            'name' => 'Admin MA B',
            'email' => 'admin-ma-b@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->ma->id,
            'email_verified_at' => now(),
        ]);

        $this->alumni = Student::create([
            'institution_id' => $this->mts->id,
            'nik' => '3271010101010008',
            'nisn' => '0011223344',
            'name' => 'Alumni MTs',
            'gender' => 'L',
            'status' => 'Lulus',
            'graduation_year' => 2026,
        ]);
    }

    public function test_alumni_can_be_enrolled_at_next_school_and_destination_waits_for_approval(): void
    {
        Notification::fake();

        $enrolled = Student::create([
            'institution_id' => $this->ma->id,
            'nik' => '3271010101010008',
            'nisn' => '0011223344',
            'name' => 'Alumni MTs',
            'gender' => 'L',
            'status' => 'Aktif',
            'tingkat' => 10,
        ]);

        $this->assertDatabaseHas('student', [
            'id' => $this->alumni->id,
            'institution_id' => $this->mts->id,
            'status' => 'Lulus',
        ]);
        $this->assertDatabaseHas('student', [
            'id' => $enrolled->id,
            'institution_id' => $this->ma->id,
            'status' => 'Aktif',
        ]);

        $destination = AlumniDestination::query()
            ->where('student_id', $this->alumni->id)
            ->where('related_student_id', $enrolled->id)
            ->first();

        $this->assertNotNull($destination);
        $this->assertSame(AlumniDestination::STATUS_PENDING, $destination->status);
        $this->assertSame(AlumniDestination::SOURCE_AUTO_ENROLLMENT, $destination->source);
        $this->assertSame('Sekolah', $destination->destination_type);
        $this->assertSame('MA B', $destination->destination_name);

        Notification::assertSentTo($this->mtsAdmin, AlumniDestinationNotification::class);
        Notification::assertNotSentTo($this->maAdmin, AlumniDestinationNotification::class);
    }

    public function test_active_student_identity_is_still_considered_taken(): void
    {
        $this->alumni->update(['status' => 'Aktif', 'graduation_year' => null]);

        $this->assertTrue(\App\Support\StudentIdentity::isTakenByActive('nisn', '0011223344'));
        $this->assertTrue(\App\Support\StudentIdentity::isTakenByActive('nik', '3271010101010008'));
    }

    public function test_origin_admin_can_approve_auto_destination(): void
    {
        Notification::fake();

        Student::create([
            'institution_id' => $this->ma->id,
            'nik' => $this->alumni->nik,
            'nisn' => $this->alumni->nisn,
            'name' => 'Alumni MTs',
            'gender' => 'L',
            'status' => 'Aktif',
        ]);

        $destination = AlumniDestination::query()->where('student_id', $this->alumni->id)->first();
        $this->assertNotNull($destination);

        Sanctum::actingAs($this->maAdmin);
        $this->postJson('/api/v1/alumni-destinations/'.$destination->id.'/approve')
            ->assertStatus(403);

        Sanctum::actingAs($this->mtsAdmin);
        $this->postJson('/api/v1/alumni-destinations/'.$destination->id.'/approve')
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertSame('approved', $destination->fresh()->status);
    }

    public function test_origin_admin_can_reject_auto_destination(): void
    {
        Student::create([
            'institution_id' => $this->ma->id,
            'nik' => $this->alumni->nik,
            'nisn' => $this->alumni->nisn,
            'name' => 'Alumni MTs',
            'gender' => 'L',
            'status' => 'Aktif',
        ]);

        $destination = AlumniDestination::query()->where('student_id', $this->alumni->id)->first();

        Sanctum::actingAs($this->mtsAdmin);
        $this->postJson('/api/v1/alumni-destinations/'.$destination->id.'/reject')
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected');
    }

    public function test_public_ppdb_allows_alumni_identity_but_rejects_active(): void
    {
        $year = AcademicYear::create([
            'code' => '2026/2027',
            'name' => 'Tahun 2026/2027',
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonths(10),
            'status' => 'Aktif',
        ]);
        $period = PpdbPeriod::create([
            'institution_id' => $this->ma->id,
            'academic_year_id' => $year->id,
            'name' => 'Gelombang 1',
            'level' => 'SMA',
            'open_date' => now()->subDay(),
            'close_date' => now()->addDays(10),
            'status' => 'open',
        ]);
        $channel = PpdbChannel::create([
            'institution_id' => $this->ma->id,
            'code' => 'REG',
            'name' => 'Reguler',
            'quota' => 50,
            'is_active' => true,
        ]);

        $this->postJson('/api/v1/public/ppdb/register', [
            'ppdb_period_id' => $period->id,
            'ppdb_channel_id' => $channel->id,
            'name' => 'Calon Alumni',
            'gender' => 'L',
            'birth_date' => '2011-07-01',
            'nisn' => $this->alumni->nisn,
            'nik' => $this->alumni->nik,
        ])->assertCreated();

        Student::create([
            'institution_id' => $this->ma->id,
            'nisn' => '9988776655',
            'name' => 'Siswa Aktif MA',
            'gender' => 'P',
            'status' => 'Aktif',
        ]);

        $this->postJson('/api/v1/public/ppdb/register', [
            'ppdb_period_id' => $period->id,
            'ppdb_channel_id' => $channel->id,
            'name' => 'Calon Bentrok',
            'gender' => 'P',
            'birth_date' => '2011-07-02',
            'nisn' => '9988776655',
        ])->assertStatus(422)
            ->assertJsonFragment(['NISN sudah terdaftar sebagai siswa aktif. Pendaftaran baru tidak dapat dilanjutkan.']);
    }

    public function test_ma_can_pull_alumni_from_mts_but_not_same_level(): void
    {
        $this->assertTrue($this->ma->canPullAlumniFrom($this->mts));
        $this->assertFalse($this->ma->canMutateWith($this->mts));
        $this->assertFalse($this->mts->canPullAlumniFrom($this->ma));

        $otherMa = Institution::create([
            'name' => 'MA C',
            'npsn' => '55556666',
            'level' => 'MA',
            'is_active' => true,
        ]);
        $this->assertFalse($this->ma->canPullAlumniFrom($otherMa));
        $this->assertTrue($this->ma->canMutateWith($otherMa));
    }

    public function test_feeder_alumni_list_shows_name_and_nisn_and_skips_active(): void
    {
        Student::create([
            'institution_id' => $this->mts->id,
            'nik' => '3271010101010099',
            'nisn' => '0099887766',
            'name' => 'Siswa Aktif MTs',
            'gender' => 'P',
            'status' => 'Aktif',
        ]);

        Student::create([
            'institution_id' => $this->ma->id,
            'nik' => $this->alumni->nik,
            'nisn' => $this->alumni->nisn,
            'name' => 'Sudah di MA',
            'gender' => 'L',
            'status' => 'Aktif',
        ]);

        Student::create([
            'institution_id' => $this->mts->id,
            'nik' => '3271010101010011',
            'nisn' => '1122334455',
            'name' => 'Alumni Lain',
            'gender' => 'P',
            'status' => 'Lulus',
            'graduation_year' => 2026,
        ]);

        Sanctum::actingAs($this->maAdmin);
        $this->getJson('/api/v1/student/feeder-alumni?origin_npsn='.$this->mts->npsn)
            ->assertOk()
            ->assertJsonPath('origin.name', 'MTs A')
            ->assertJsonPath('origin.npsn', '11112222')
            ->assertJsonFragment(['name' => 'Alumni MTs', 'nik' => '3271010101010008', 'nisn' => '0011223344', 'already_enrolled' => true])
            ->assertJsonFragment(['name' => 'Alumni Lain', 'nik' => '3271010101010011', 'nisn' => '1122334455', 'already_enrolled' => false])
            ->assertJsonMissing(['name' => 'Siswa Aktif MTs']);
    }

    public function test_pull_from_feeder_copies_alumni_and_keeps_origin_archive(): void
    {
        $this->alumni->update([
            'birth_date' => '2010-01-15',
            'father_name' => 'Ayah Alumni',
            'phone' => '081234567890',
        ]);

        $year = AcademicYear::create([
            'code' => '2026/2027',
            'name' => 'Tahun 2026/2027',
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonths(10),
            'status' => 'Aktif',
        ]);
        $semester = Semester::create([
            'academic_year_id' => $year->id,
            'name' => 'Ganjil',
            'order' => 1,
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'status' => 'Aktif',
        ]);
        $this->ma->update([
            'active_academic_year_id' => $year->id,
            'active_semester_id' => $semester->id,
        ]);

        Sanctum::actingAs($this->maAdmin);
        $this->postJson('/api/v1/student/pull-from-feeder', [
            'origin_npsn' => $this->mts->npsn,
            'student_ids' => [$this->alumni->id],
            'tingkat' => 10,
        ])->assertCreated()
            ->assertJsonPath('created_count', 1);

        $this->assertDatabaseHas('student', [
            'id' => $this->alumni->id,
            'institution_id' => $this->mts->id,
            'status' => 'Lulus',
        ]);

        $copied = Student::query()
            ->where('institution_id', $this->ma->id)
            ->where('nisn', $this->alumni->nisn)
            ->where('status', 'Aktif')
            ->first();

        $this->assertNotNull($copied);
        $this->assertNotSame($this->alumni->id, $copied->id);
        $this->assertSame('Alumni MTs', $copied->name);
        $this->assertSame('Ayah Alumni', $copied->father_name);
        $this->assertSame(10, (int) $copied->tingkat);
        $this->assertSame('MTs A', $copied->previous_school);
        $this->assertSame('11112222', $copied->previous_school_npsn);

        $this->assertDatabaseHas('alumni_destinations', [
            'student_id' => $this->alumni->id,
            'related_student_id' => $copied->id,
            'status' => AlumniDestination::STATUS_PENDING,
        ]);
    }

    public function test_cannot_list_feeder_alumni_from_same_jenjang(): void
    {
        $otherMa = Institution::create([
            'name' => 'MA C',
            'npsn' => '55556666',
            'level' => 'MA',
            'is_active' => true,
        ]);

        Sanctum::actingAs($this->maAdmin);
        $this->getJson('/api/v1/student/feeder-alumni?origin_npsn='.$otherMa->npsn)
            ->assertStatus(422)
            ->assertJsonFragment(['Tarik alumni hanya dari jenjang sebelumnya']);
    }
}
