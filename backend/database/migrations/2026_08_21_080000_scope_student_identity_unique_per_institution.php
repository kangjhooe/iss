<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * NIK/NISN unik per sekolah, bukan global.
     * Alumni jenjang sebelumnya boleh memakai identitas yang sama di sekolah berikutnya.
     */
    public function up(): void
    {
        $indexes = $this->indexNames('student');

        Schema::table('student', function (Blueprint $table) use ($indexes) {
            if (in_array('student_nik_unique', $indexes, true)) {
                $table->dropUnique('student_nik_unique');
            } elseif (in_array('nik', $indexes, true)) {
                $table->dropUnique('nik');
            }

            if (in_array('student_nisn_unique', $indexes, true)) {
                $table->dropUnique('student_nisn_unique');
            } elseif (in_array('nisn', $indexes, true)) {
                $table->dropUnique('nisn');
            }
        });

        $indexes = $this->indexNames('student');

        Schema::table('student', function (Blueprint $table) use ($indexes) {
            if (! in_array('student_institution_nik_unique', $indexes, true)) {
                $table->unique(['institution_id', 'nik'], 'student_institution_nik_unique');
            }
            if (! in_array('student_institution_nisn_unique', $indexes, true)) {
                $table->unique(['institution_id', 'nisn'], 'student_institution_nisn_unique');
            }
        });
    }

    public function down(): void
    {
        $indexes = $this->indexNames('student');

        Schema::table('student', function (Blueprint $table) use ($indexes) {
            if (in_array('student_institution_nik_unique', $indexes, true)) {
                $table->dropUnique('student_institution_nik_unique');
            }
            if (in_array('student_institution_nisn_unique', $indexes, true)) {
                $table->dropUnique('student_institution_nisn_unique');
            }
        });

        $indexes = $this->indexNames('student');

        Schema::table('student', function (Blueprint $table) use ($indexes) {
            if (! in_array('student_nik_unique', $indexes, true)) {
                $table->unique('nik');
            }
            if (! in_array('student_nisn_unique', $indexes, true)) {
                $table->unique('nisn');
            }
        });
    }

    /**
     * @return array<int, string>
     */
    private function indexNames(string $table): array
    {
        return collect(Schema::getIndexes($table))->pluck('name')->filter()->values()->all();
    }
};
