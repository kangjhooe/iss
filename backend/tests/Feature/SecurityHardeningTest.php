<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\FinanceFeeType;
use App\Models\FinanceInvoice;
use App\Models\Institution;
use App\Models\PpdbApplicant;
use App\Models\PpdbChannel;
use App\Models\PpdbPeriod;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_institution_admin_cannot_scope_finance_to_foreign_institution(): void
    {
        $schoolA = Institution::create([
            'name' => 'Sekolah A',
            'npsn' => '11111111',
            'level' => 'SMP',
            'is_active' => true,
        ]);
        $schoolB = Institution::create([
            'name' => 'Sekolah B',
            'npsn' => '22222222',
            'level' => 'SMP',
            'is_active' => true,
        ]);

        $adminA = User::create([
            'name' => 'Admin A',
            'email' => 'admin-a@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $schoolA->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $studentB = Student::create([
            'institution_id' => $schoolB->id,
            'nis' => 'B001',
            'name' => 'Siswa B',
            'gender' => 'L',
            'status' => 'Aktif',
        ]);

        $feeB = FinanceFeeType::create([
            'institution_id' => $schoolB->id,
            'name' => 'SPP B',
            'frequency' => 'monthly',
            'scope' => 'school',
            'default_amount' => 50000,
            'is_active' => true,
        ]);

        FinanceInvoice::create([
            'institution_id' => $schoolB->id,
            'student_id' => $studentB->id,
            'fee_type_id' => $feeB->id,
            'title' => 'Tagihan B',
            'amount' => 50000,
            'amount_paid' => 0,
            'status' => 'unpaid',
            'due_date' => now()->addDays(7),
        ]);

        Sanctum::actingAs($adminA);

        $res = $this->getJson('/api/v1/finance/invoices?institution_id=' . $schoolB->id);
        $res->assertOk();
        $this->assertSame(0, count($res->json('data') ?? []));
    }

    public function test_public_ppdb_prefill_omits_sensitive_pii(): void
    {
        $target = Institution::create([
            'name' => 'Sekolah Tujuan',
            'npsn' => '33333333',
            'is_active' => true,
        ]);
        $origin = Institution::create([
            'name' => 'Sekolah Asal',
            'npsn' => '44444444',
            'address' => 'Jl. Asal 1',
            'is_active' => true,
        ]);

        Student::create([
            'institution_id' => $origin->id,
            'nis' => '100',
            'nisn' => '1234567890',
            'nik' => '3201010101010001',
            'name' => 'Budi Prefill',
            'gender' => 'L',
            'birth_date' => '2012-01-15',
            'address' => 'Rumah Rahasia',
            'phone' => '08123456789',
            'email' => 'budi@example.com',
            'father_name' => 'Ayah',
            'father_nik' => '3201010101010002',
            'mother_name' => 'Ibu',
            'mother_nik' => '3201010101010003',
            'guardian_phone' => '08111111111',
            'status' => 'Aktif',
        ]);

        $res = $this->getJson('/api/v1/public/ppdb/prefill?' . http_build_query([
            'institution_id' => $target->id,
            'previous_school_npsn' => $origin->npsn,
            'nisn' => '1234567890',
        ]));

        $res->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('data.name', 'Budi Prefill')
            ->assertJsonMissingPath('data.nik')
            ->assertJsonMissingPath('data.phone')
            ->assertJsonMissingPath('data.email')
            ->assertJsonMissingPath('data.address')
            ->assertJsonMissingPath('data.father_nik')
            ->assertJsonMissingPath('data.mother_nik')
            ->assertJsonMissingPath('data.guardian_phone');
    }

    public function test_public_ppdb_check_result_requires_birth_date_for_nisn(): void
    {
        $institution = Institution::create([
            'name' => 'Sekolah Cek',
            'npsn' => '55555555',
            'is_active' => true,
        ]);
        $year = AcademicYear::create([
            'code' => '2025/2026',
            'name' => 'Tahun 2025/2026',
            'start_date' => Carbon::today()->subMonths(2),
            'end_date' => Carbon::today()->addMonths(10),
            'status' => 'Aktif',
        ]);
        $period = PpdbPeriod::create([
            'institution_id' => $institution->id,
            'academic_year_id' => $year->id,
            'name' => 'Gelombang 1',
            'level' => 'SMP',
            'open_date' => Carbon::today()->subDays(1),
            'close_date' => Carbon::today()->addDays(7),
            'status' => 'open',
        ]);
        $channel = PpdbChannel::create([
            'institution_id' => $institution->id,
            'code' => 'REG',
            'name' => 'Reguler',
            'is_active' => true,
        ]);

        PpdbApplicant::create([
            'ppdb_period_id' => $period->id,
            'ppdb_channel_id' => $channel->id,
            'registration_number' => '55555555-1-00001',
            'name' => 'Calon Cek',
            'nisn' => '9876543210',
            'nik' => '3201010101010099',
            'gender' => 'P',
            'birth_date' => '2011-05-20',
            'status' => 'passed',
            'submitted_at' => now(),
        ]);

        $this->getJson('/api/v1/public/ppdb/check-result?nisn=9876543210')
            ->assertStatus(422);

        $this->getJson('/api/v1/public/ppdb/check-result?nisn=9876543210&birth_date=2011-05-20')
            ->assertOk()
            ->assertJsonPath('data.name', 'Calon Cek')
            ->assertJsonPath('data.nisn', '9876543210');

        $this->getJson('/api/v1/public/ppdb/check-result?registration_number=55555555-1-00001')
            ->assertOk()
            ->assertJsonPath('data.name', 'Calon Cek');
    }

    public function test_teacher_cannot_access_audit_logs(): void
    {
        $institution = Institution::create([
            'name' => 'Sekolah Audit',
            'npsn' => '66666666',
            'is_active' => true,
        ]);
        $teacher = User::create([
            'name' => 'Guru Biasa',
            'email' => 'guru-audit@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'teacher',
            'institution_id' => $institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        Sanctum::actingAs($teacher);

        $this->getJson('/api/v1/audit-logs')->assertForbidden();
        $this->getJson('/api/v1/audit-logs/filter-options')->assertForbidden();
        $this->getJson('/api/v1/audit-logs/export')->assertForbidden();
    }
}
