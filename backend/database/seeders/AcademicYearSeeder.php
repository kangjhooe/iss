<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Semester;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AcademicYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create academic year 2025/2026
        $academicYear2025 = AcademicYear::create([
            'code' => '2025/2026',
            'name' => 'Tahun Ajaran 2025/2026',
            'start_date' => Carbon::parse('2025-07-01'),
            'end_date' => Carbon::parse('2026-06-30'),
            'status' => 'Aktif',
            'description' => 'Tahun ajaran aktif',
        ]);

        // Create semesters for 2025/2026
        Semester::create([
            'academic_year_id' => $academicYear2025->id,
            'name' => 'Ganjil',
            'order' => 1,
            'start_date' => Carbon::parse('2025-07-01'),
            'end_date' => Carbon::parse('2025-12-31'),
            'status' => 'Aktif',
            'description' => 'Semester Ganjil 2025/2026',
        ]);

        Semester::create([
            'academic_year_id' => $academicYear2025->id,
            'name' => 'Genap',
            'order' => 2,
            'start_date' => Carbon::parse('2026-01-01'),
            'end_date' => Carbon::parse('2026-06-30'),
            'status' => 'Draft',
            'description' => 'Semester Genap 2025/2026',
        ]);

        // Create academic year 2026/2027 (for future)
        $academicYear2026 = AcademicYear::create([
            'code' => '2026/2027',
            'name' => 'Tahun Ajaran 2026/2027',
            'start_date' => Carbon::parse('2026-07-01'),
            'end_date' => Carbon::parse('2027-06-30'),
            'status' => 'Draft',
            'description' => 'Tahun ajaran berikutnya',
        ]);

        // Create semesters for 2026/2027
        Semester::create([
            'academic_year_id' => $academicYear2026->id,
            'name' => 'Ganjil',
            'order' => 1,
            'start_date' => Carbon::parse('2026-07-01'),
            'end_date' => Carbon::parse('2026-12-31'),
            'status' => 'Draft',
            'description' => 'Semester Ganjil 2026/2027',
        ]);

        Semester::create([
            'academic_year_id' => $academicYear2026->id,
            'name' => 'Genap',
            'order' => 2,
            'start_date' => Carbon::parse('2027-01-01'),
            'end_date' => Carbon::parse('2027-06-30'),
            'status' => 'Draft',
            'description' => 'Semester Genap 2026/2027',
        ]);
    }
}
