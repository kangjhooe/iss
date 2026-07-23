<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Migrasi nilai lama: uh → penilaian_1, tugas → penilaian_2.
     * Unique key (student, subject, semester, grade_type) tetap dipakai.
     */
    public function up(): void
    {
        // Jika sudah ada penilaian_1 dari data baru, jangan timpa uh.
        DB::table('grades')
            ->where('grade_type', 'uh')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('grades as g2')
                    ->whereColumn('g2.student_id', 'grades.student_id')
                    ->whereColumn('g2.subject_id', 'grades.subject_id')
                    ->whereColumn('g2.semester_id', 'grades.semester_id')
                    ->where('g2.grade_type', 'penilaian_1');
            })
            ->update(['grade_type' => 'penilaian_1']);

        DB::table('grades')
            ->where('grade_type', 'tugas')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('grades as g2')
                    ->whereColumn('g2.student_id', 'grades.student_id')
                    ->whereColumn('g2.subject_id', 'grades.subject_id')
                    ->whereColumn('g2.semester_id', 'grades.semester_id')
                    ->where('g2.grade_type', 'penilaian_2');
            })
            ->update(['grade_type' => 'penilaian_2']);

        // Sisa uh/tugas yang bentrok: hapus (sudah ada penilaian_*).
        DB::table('grades')->whereIn('grade_type', ['uh', 'tugas'])->delete();
    }

    public function down(): void
    {
        DB::table('grades')
            ->where('grade_type', 'penilaian_1')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('grades as g2')
                    ->whereColumn('g2.student_id', 'grades.student_id')
                    ->whereColumn('g2.subject_id', 'grades.subject_id')
                    ->whereColumn('g2.semester_id', 'grades.semester_id')
                    ->where('g2.grade_type', 'uh');
            })
            ->update(['grade_type' => 'uh']);

        DB::table('grades')
            ->where('grade_type', 'penilaian_2')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('grades as g2')
                    ->whereColumn('g2.student_id', 'grades.student_id')
                    ->whereColumn('g2.subject_id', 'grades.subject_id')
                    ->whereColumn('g2.semester_id', 'grades.semester_id')
                    ->where('g2.grade_type', 'tugas');
            })
            ->update(['grade_type' => 'tugas']);
    }
};
