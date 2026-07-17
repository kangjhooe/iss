<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Perbaiki siswa aktif yang academic_year_id/semester_id tidak sama dengan kelasnya.
     * Kondisi ini membuat halaman Luluskan/Naik Kelas terlihat kosong.
     */
    public function up(): void
    {
        if (!Schema::hasTable('student') || !Schema::hasTable('class')) {
            return;
        }

        DB::statement("
            UPDATE student s
            INNER JOIN class c ON c.id = s.class_id
            SET
                s.academic_year_id = c.academic_year_id,
                s.academic_year = COALESCE(c.academic_year, s.academic_year),
                s.semester_id = COALESCE(c.semester_id, s.semester_id)
            WHERE s.status = 'Aktif'
              AND s.deleted_at IS NULL
              AND s.class_id IS NOT NULL
              AND c.academic_year_id IS NOT NULL
              AND (
                  s.academic_year_id IS NULL
                  OR s.academic_year_id <> c.academic_year_id
                  OR (c.semester_id IS NOT NULL AND (s.semester_id IS NULL OR s.semester_id <> c.semester_id))
              )
        ");
    }

    public function down(): void
    {
        // Data repair — tidak di-rollback.
    }
};
