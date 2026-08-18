<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Normalize student.academic_year to academic_years.code (e.g. 2026/2027)
     * instead of full names like "Tahun Ajaran 2026/2027" that break edit validation.
     */
    public function up(): void
    {
        DB::statement("
            UPDATE student s
            INNER JOIN academic_years ay ON ay.id = s.academic_year_id
            SET s.academic_year = ay.code
            WHERE s.academic_year_id IS NOT NULL
              AND ay.code IS NOT NULL
              AND (
                s.academic_year IS NULL
                OR s.academic_year = ''
                OR s.academic_year <> ay.code
              )
        ");
    }

    public function down(): void
    {
        // Irreversible data normalization.
    }
};
