<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use App\Services\LocalNisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LocalNisNumberingTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected User $admin;

    protected AcademicYear $year;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'SMP NIS Lokal',
            'npsn' => '80808080',
            'level' => 'SMP',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin NIS',
            'email' => 'admin-nis@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->year = AcademicYear::create([
            'code' => '2026/2027',
            'name' => 'Tahun 2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'status' => 'Aktif',
        ]);

        $this->institution->update(['active_academic_year_id' => $this->year->id]);
        $this->institution->refresh();
    }

    public function test_default_format_matches_year_and_five_digit_sequence(): void
    {
        $service = app(LocalNisService::class);
        $nis = $service->next($this->institution->fresh('activeAcademicYear'));

        $this->assertSame('202600001', $nis);
        $this->assertSame('202600002', $service->preview($this->institution->fresh('activeAcademicYear')));
    }

    public function test_prefix_preset_and_custom_pattern(): void
    {
        $this->institution->update([
            'nis_numbering' => [
                'preset' => LocalNisService::PRESET_PREFIX_TAHUN2_URUT,
                'prefix' => 'S',
                'seq_digits' => 4,
                'reset' => LocalNisService::RESET_YEARLY,
                'pattern' => '',
            ],
        ]);

        $service = app(LocalNisService::class);
        $this->assertSame('S260001', $service->next($this->institution->fresh('activeAcademicYear')));

        $this->institution->update([
            'nis_numbering' => [
                'preset' => LocalNisService::PRESET_CUSTOM,
                'prefix' => 'MTs',
                'seq_digits' => 3,
                'reset' => LocalNisService::RESET_NEVER,
                'pattern' => '{PREFIX}/{YY}/{SEQ}',
            ],
        ]);

        $this->assertSame('MTs/26/001', $service->next($this->institution->fresh('activeAcademicYear')));
    }

    public function test_assign_if_empty_does_not_overwrite_existing_nis(): void
    {
        $student = Student::create([
            'institution_id' => $this->institution->id,
            'nis' => 'LAMA-01',
            'name' => 'Siswa Lama',
            'gender' => 'L',
            'status' => 'Aktif',
        ]);

        $result = app(LocalNisService::class)->assignIfEmpty($student);

        $this->assertNull($result);
        $this->assertSame('LAMA-01', $student->fresh()->nis);
    }

    public function test_admin_can_update_settings_and_bulk_generate(): void
    {
        Sanctum::actingAs($this->admin);

        $emptyA = Student::create([
            'institution_id' => $this->institution->id,
            'name' => 'Tanpa NIS A',
            'gender' => 'L',
            'status' => 'Aktif',
        ]);
        $emptyB = Student::create([
            'institution_id' => $this->institution->id,
            'name' => 'Tanpa NIS B',
            'gender' => 'P',
            'status' => 'Aktif',
        ]);
        Student::create([
            'institution_id' => $this->institution->id,
            'nis' => 'KEEP-1',
            'name' => 'Sudah NIS',
            'gender' => 'L',
            'status' => 'Aktif',
        ]);

        $this->putJson('/api/v1/student/nis-numbering', [
            'preset' => 'urut_saja',
            'prefix' => '',
            'seq_digits' => 3,
            'reset' => 'never',
            'pattern' => '',
        ])->assertOk()
            ->assertJsonPath('data.settings.preset', 'urut_saja')
            ->assertJsonPath('data.preview', '001');

        $preview = $this->postJson('/api/v1/student/generate-nis/preview', ['limit' => 50])
            ->assertOk()
            ->assertJsonPath('data.total_missing', 2);

        $this->assertSame('001', $preview->json('data.rows.0.proposed_nis'));
        $this->assertSame('002', $preview->json('data.rows.1.proposed_nis'));
        $this->assertNull($emptyA->fresh()->nis);
        $this->assertNull($emptyB->fresh()->nis);
        $this->assertDatabaseMissing('institution_nis_sequences', [
            'institution_id' => $this->institution->id,
        ]);

        $this->postJson('/api/v1/student/generate-nis', ['student_ids' => [$emptyA->id, $emptyB->id]])
            ->assertOk()
            ->assertJsonPath('data.assigned', 2)
            ->assertJsonPath('data.assigned_rows.0.nis', '001');

        $this->assertSame('001', $emptyA->fresh()->nis);
        $this->assertSame('002', $emptyB->fresh()->nis);
        $this->assertSame('KEEP-1', Student::where('name', 'Sudah NIS')->value('nis'));
    }

    public function test_generate_requires_student_ids_and_can_apply_subset(): void
    {
        Sanctum::actingAs($this->admin);

        $emptyA = Student::create([
            'institution_id' => $this->institution->id,
            'name' => 'Subset A',
            'gender' => 'L',
            'status' => 'Aktif',
        ]);
        $emptyB = Student::create([
            'institution_id' => $this->institution->id,
            'name' => 'Subset B',
            'gender' => 'P',
            'status' => 'Aktif',
        ]);

        $this->postJson('/api/v1/student/generate-nis', ['limit' => 50])
            ->assertStatus(422);

        $this->postJson('/api/v1/student/generate-nis', ['student_ids' => [$emptyB->id]])
            ->assertOk()
            ->assertJsonPath('data.assigned', 1);

        $this->assertNull($emptyA->fresh()->nis);
        $this->assertSame('202600001', $emptyB->fresh()->nis);
    }

    public function test_generate_one_and_reject_duplicate_nis_on_update(): void
    {
        Sanctum::actingAs($this->admin);

        $student = Student::create([
            'institution_id' => $this->institution->id,
            'nik' => '3201010101010099',
            'name' => 'Satu NIS',
            'gender' => 'P',
            'status' => 'Aktif',
            'birth_date' => '2010-01-01',
            'birth_place' => 'Jakarta',
            'tingkat' => 7,
        ]);

        $this->postJson('/api/v1/student/'.$student->id.'/generate-nis')
            ->assertOk()
            ->assertJsonPath('data.nis', '202600001');

        $other = Student::create([
            'institution_id' => $this->institution->id,
            'nik' => '3201010101010088',
            'nis' => '202600099',
            'name' => 'Lain',
            'gender' => 'L',
            'status' => 'Aktif',
            'birth_date' => '2010-02-02',
            'birth_place' => 'Bandung',
            'tingkat' => 7,
        ]);

        $this->putJson('/api/v1/student/'.$other->id, [
            'nis' => '202600001',
        ])->assertStatus(422);
    }

    public function test_next_explains_missing_sequence_table(): void
    {
        Schema::dropIfExists('institution_nis_sequences');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tabel penomoran NIS belum tersedia');

        app(LocalNisService::class)->next($this->institution->fresh('activeAcademicYear'));
    }

    public function test_prefix_preset_requires_prefix(): void
    {
        Sanctum::actingAs($this->admin);

        $this->putJson('/api/v1/student/nis-numbering', [
            'preset' => 'prefix_tahun_urut',
            'prefix' => '',
            'seq_digits' => 5,
            'reset' => 'yearly',
            'pattern' => '',
        ])->assertStatus(422);
    }
}
