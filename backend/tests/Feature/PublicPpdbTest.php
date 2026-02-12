<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Institution;
use App\Models\PpdbApplicant;
use App\Models\PpdbChannel;
use App\Models\PpdbPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PublicPpdbTest extends TestCase
{
    use RefreshDatabase;

    /**
     * GET public/ppdb/periods tanpa institution_id dan npsn mengembalikan 422.
     */
    public function test_public_ppdb_open_periods_returns_422_when_missing_npsn_and_institution_id(): void
    {
        $response = $this->getJson('/api/v1/public/ppdb/periods');

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'institution_id atau npsn wajib diisi.',
            ]);
    }

    /**
     * GET public/ppdb/periods dengan npsn mengembalikan 200 dan struktur data saat ada periode buka.
     */
    public function test_public_ppdb_open_periods_returns_200_with_data_when_period_open(): void
    {
        $today = Carbon::today();
        $institution = Institution::create([
            'name' => 'Sekolah Test',
            'npsn' => '99988877',
            'is_active' => true,
        ]);
        $academicYear = AcademicYear::create([
            'code' => '2025/2026',
            'name' => 'Tahun 2025/2026',
            'start_date' => $today->copy()->subMonths(2),
            'end_date' => $today->copy()->addMonths(10),
            'status' => 'Aktif',
        ]);
        PpdbPeriod::create([
            'institution_id' => $institution->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'Gelombang 1',
            'level' => 'SMP',
            'open_date' => $today->copy()->subDays(1),
            'close_date' => $today->copy()->addDays(7),
            'status' => 'open',
        ]);

        $response = $this->getJson('/api/v1/public/ppdb/periods?npsn=99988877');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'level',
                        'open_date',
                        'close_date',
                        'academic_year',
                    ],
                ],
                'institution' => [
                    'id',
                    'name',
                    'npsn',
                ],
            ])
            ->assertJsonCount(1, 'data');
    }

    /**
     * GET public/ppdb/check-result tanpa registration_number dan nisn mengembalikan 422.
     */
    public function test_public_ppdb_check_result_returns_422_when_missing_registration_number_and_nisn(): void
    {
        $response = $this->getJson('/api/v1/public/ppdb/check-result');

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Isi nomor pendaftaran atau NISN.',
            ]);
    }

    /**
     * GET public/ppdb/check-result ketika data tidak ditemukan mengembalikan 404.
     */
    public function test_public_ppdb_check_result_returns_404_when_applicant_not_found(): void
    {
        $response = $this->getJson('/api/v1/public/ppdb/check-result?registration_number=PPDB-999-99999');

        $response->assertStatus(404)
            ->assertJsonFragment(['message' => 'Data tidak ditemukan. Periksa nomor pendaftaran dan pilihan sekolah.']);
    }
}
