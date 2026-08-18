<?php

namespace Tests\Feature;

use App\Models\AlumniDestination;
use App\Models\BkkApplication;
use App\Models\BkkVacancy;
use App\Models\IndustryPartner;
use App\Models\Institution;
use App\Models\PklPeriod;
use App\Models\PklPlacement;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PklBkkBetaFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected User $admin;

    protected Student $student;

    protected Student $alumni;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'SMK Beta PKL',
            'npsn' => '90909090',
            'level' => 'SMK',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin SMK',
            'email' => 'admin-pkl-bkk@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->student = Student::create([
            'institution_id' => $this->institution->id,
            'name' => 'Siswa PKL',
            'nis' => '1001',
            'nisn' => '0010010010',
            'gender' => 'L',
            'status' => 'Aktif',
        ]);

        $this->alumni = Student::create([
            'institution_id' => $this->institution->id,
            'name' => 'Alumni BKK',
            'nis' => '2001',
            'nisn' => '0020020020',
            'gender' => 'P',
            'status' => 'Lulus',
            'graduation_year' => 2025,
        ]);
    }

    public function test_pkl_flow_mitra_periode_penempatan_monitoring_score(): void
    {
        Sanctum::actingAs($this->admin);

        $partnerRes = $this->postJson('/api/v1/industry-partners', [
            'name' => 'PT Industri Maju',
            'city' => 'Bandung',
            'status' => 'Aktif',
        ]);
        $partnerRes->assertCreated();
        $partnerId = $partnerRes->json('data.id');

        $periodRes = $this->postJson('/api/v1/pkl/periods', [
            'name' => 'PKL Genap 2026',
            'status' => 'berlangsung',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
        ]);
        $periodRes->assertCreated();
        $periodId = $periodRes->json('data.id');

        $placementRes = $this->postJson('/api/v1/pkl/placements', [
            'pkl_period_id' => $periodId,
            'student_id' => $this->student->id,
            'industry_partner_id' => $partnerId,
            'status' => 'berlangsung',
        ]);
        $placementRes->assertCreated();
        $placementId = $placementRes->json('data.id');

        $this->postJson("/api/v1/pkl/placements/{$placementId}/monitoring-logs", [
            'visit_date' => '2026-02-01',
            'method' => 'kunjungan',
            'notes' => 'Siswa aktif',
        ])->assertCreated();

        $this->putJson("/api/v1/pkl/placements/{$placementId}", [
            'score' => 88.5,
            'assessment_notes' => 'Baik',
            'status' => 'selesai',
        ])->assertOk()
            ->assertJsonPath('data.score', '88.50');

        $this->assertDatabaseHas('pkl_placements', [
            'id' => $placementId,
            'status' => 'selesai',
        ]);
        $this->assertDatabaseHas('pkl_monitoring_logs', [
            'pkl_placement_id' => $placementId,
            'method' => 'kunjungan',
        ]);
    }

    public function test_pkl_bulk_assign_and_export(): void
    {
        Sanctum::actingAs($this->admin);

        $partner = IndustryPartner::create([
            'institution_id' => $this->institution->id,
            'name' => 'CV Mitra Bulk',
            'status' => 'Aktif',
        ]);
        $period = PklPeriod::create([
            'institution_id' => $this->institution->id,
            'name' => 'Periode Bulk',
            'status' => 'draft',
        ]);
        $student2 = Student::create([
            'institution_id' => $this->institution->id,
            'name' => 'Siswa Dua',
            'nis' => '1002',
            'gender' => 'P',
            'status' => 'Aktif',
        ]);

        $bulk = $this->postJson('/api/v1/pkl/placements/bulk', [
            'pkl_period_id' => $period->id,
            'industry_partner_id' => $partner->id,
            'student_ids' => [$this->student->id, $student2->id, $this->student->id],
            'status' => 'draft',
        ]);
        $bulk->assertCreated()
            ->assertJsonPath('data.created', 2);

        // Duplicate should skip
        $again = $this->postJson('/api/v1/pkl/placements/bulk', [
            'pkl_period_id' => $period->id,
            'industry_partner_id' => $partner->id,
            'student_ids' => [$this->student->id],
        ]);
        $again->assertCreated()
            ->assertJsonPath('data.created', 0)
            ->assertJsonPath('data.skipped', 1);

        $export = $this->get('/api/v1/pkl/placements/export?pkl_period_id=' . $period->id);
        $export->assertOk();
        $this->assertStringContainsString('text/csv', $export->headers->get('Content-Type'));
        $this->assertStringContainsString('Siswa PKL', $export->streamedContent());
    }

    public function test_bkk_flow_vacancy_application_accepted_writes_destination(): void
    {
        Sanctum::actingAs($this->admin);

        $partnerRes = $this->postJson('/api/v1/industry-partners', [
            'name' => 'PT Rekrutmen',
            'status' => 'Aktif',
        ])->assertCreated();

        $vacancyRes = $this->postJson('/api/v1/bkk/vacancies', [
            'title' => 'Operator Produksi',
            'industry_partner_id' => $partnerRes->json('data.id'),
            'position' => 'Operator',
            'status' => 'buka',
        ])->assertCreated();

        $appRes = $this->postJson('/api/v1/bkk/applications', [
            'bkk_vacancy_id' => $vacancyRes->json('data.id'),
            'student_id' => $this->alumni->id,
            'status' => 'diajukan',
        ])->assertCreated();

        $appId = $appRes->json('data.id');

        $this->putJson("/api/v1/bkk/applications/{$appId}", [
            'status' => 'diterima',
        ])->assertOk();

        $app = BkkApplication::find($appId);
        $this->assertNotNull($app->alumni_destination_id);

        $this->assertDatabaseHas('alumni_destinations', [
            'id' => $app->alumni_destination_id,
            'student_id' => $this->alumni->id,
            'destination_type' => 'Kerja',
            'destination_name' => 'PT Rekrutmen',
        ]);

        $export = $this->get('/api/v1/bkk/applications/export');
        $export->assertOk();
        $this->assertStringContainsString('Alumni BKK', $export->streamedContent());
    }

    public function test_active_student_cannot_apply_bkk(): void
    {
        Sanctum::actingAs($this->admin);

        $vacancy = BkkVacancy::create([
            'institution_id' => $this->institution->id,
            'title' => 'Staff Admin',
            'status' => 'buka',
        ]);

        $this->postJson('/api/v1/bkk/applications', [
            'bkk_vacancy_id' => $vacancy->id,
            'student_id' => $this->student->id,
        ])->assertStatus(422);
    }

    public function test_student_portal_can_manage_own_pkl_journal(): void
    {
        $this->student->update(['nik' => '3201010101010001']);

        $studentUser = User::create([
            'name' => 'Siswa Portal PKL',
            'email' => 'siswa-pkl@example.com',
            'login_nik' => '3201010101010001',
            'password' => Hash::make('Password123!'),
            'role' => 'student',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $partner = IndustryPartner::create([
            'institution_id' => $this->institution->id,
            'name' => 'PT Jurnal Mitra',
            'status' => 'Aktif',
        ]);
        $period = PklPeriod::create([
            'institution_id' => $this->institution->id,
            'name' => 'Periode Jurnal',
            'status' => 'berlangsung',
        ]);
        $placement = PklPlacement::create([
            'institution_id' => $this->institution->id,
            'pkl_period_id' => $period->id,
            'student_id' => $this->student->id,
            'industry_partner_id' => $partner->id,
            'status' => 'berlangsung',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
        ]);

        Sanctum::actingAs($studentUser);

        $this->getJson('/api/v1/pkl/my/placements')
            ->assertOk()
            ->assertJsonPath('data.0.id', $placement->id);

        $create = $this->postJson("/api/v1/pkl/my/placements/{$placement->id}/journals", [
            'journal_date' => '2026-02-10',
            'activities' => 'Membantu assembling produk',
            'hours' => 8,
            'status' => 'submitted',
        ]);
        $create->assertCreated();
        $journalId = $create->json('data.id');

        $this->assertDatabaseHas('pkl_journals', [
            'id' => $journalId,
            'student_id' => $this->student->id,
            'activities' => 'Membantu assembling produk',
        ]);

        $this->putJson("/api/v1/pkl/my/placements/{$placement->id}/journals/{$journalId}", [
            'activities' => 'Assembling + QC',
            'hours' => 7.5,
        ])->assertOk()
            ->assertJsonPath('data.activities', 'Assembling + QC');

        // Staff can list journals and add supervisor notes
        Sanctum::actingAs($this->admin);
        $this->getJson("/api/v1/pkl/placements/{$placement->id}/journals")
            ->assertOk()
            ->assertJsonPath('data.0.id', $journalId);

        $this->putJson("/api/v1/pkl/placements/{$placement->id}/journals/{$journalId}", [
            'supervisor_notes' => 'Bagus, lanjutkan',
        ])->assertOk()
            ->assertJsonPath('data.supervisor_notes', 'Bagus, lanjutkan');

        Sanctum::actingAs($studentUser);
        $this->deleteJson("/api/v1/pkl/my/placements/{$placement->id}/journals/{$journalId}")
            ->assertOk();
        $this->assertDatabaseMissing('pkl_journals', ['id' => $journalId]);
    }

    public function test_student_cannot_access_other_placement_journal(): void
    {
        $other = Student::create([
            'institution_id' => $this->institution->id,
            'name' => 'Siswa Lain',
            'nis' => '1999',
            'nik' => '3201010101010099',
            'gender' => 'L',
            'status' => 'Aktif',
        ]);
        $otherUser = User::create([
            'name' => 'Siswa Lain',
            'email' => 'siswa-lain-pkl@example.com',
            'login_nik' => '3201010101010099',
            'password' => Hash::make('Password123!'),
            'role' => 'student',
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);

        $partner = IndustryPartner::create([
            'institution_id' => $this->institution->id,
            'name' => 'PT Lain',
            'status' => 'Aktif',
        ]);
        $period = PklPeriod::create([
            'institution_id' => $this->institution->id,
            'name' => 'Periode Lain',
            'status' => 'berlangsung',
        ]);
        $placement = PklPlacement::create([
            'institution_id' => $this->institution->id,
            'pkl_period_id' => $period->id,
            'student_id' => $this->student->id,
            'industry_partner_id' => $partner->id,
            'status' => 'berlangsung',
        ]);

        Sanctum::actingAs($otherUser);
        $this->getJson("/api/v1/pkl/my/placements/{$placement->id}")
            ->assertForbidden();
        $this->postJson("/api/v1/pkl/my/placements/{$placement->id}/journals", [
            'journal_date' => '2026-02-11',
            'activities' => 'Tidak boleh',
        ])->assertForbidden();
    }
}
