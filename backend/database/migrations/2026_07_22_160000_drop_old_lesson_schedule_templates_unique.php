<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Unique lama (institution_id, semester_id) masih bisa tersisa jika
        // migration expand sebelumnya gagal drop — menghalangi multi-template.
        $exists = collect(DB::select('SHOW INDEX FROM lesson_schedule_templates'))
            ->contains(fn ($idx) => $idx->Key_name === 'lesson_schedule_templates_unique');

        if ($exists) {
            $fkNames = collect(DB::select("
                SELECT DISTINCT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'lesson_schedule_templates'
                  AND REFERENCED_TABLE_NAME IS NOT NULL
                  AND COLUMN_NAME IN ('institution_id', 'semester_id')
            "))->pluck('CONSTRAINT_NAME')->filter()->values();

            foreach ($fkNames as $fkName) {
                Schema::table('lesson_schedule_templates', function (Blueprint $table) use ($fkName) {
                    $table->dropForeign($fkName);
                });
            }

            Schema::table('lesson_schedule_templates', function (Blueprint $table) {
                $table->dropUnique('lesson_schedule_templates_unique');
            });

            Schema::table('lesson_schedule_templates', function (Blueprint $table) {
                $table->foreign('institution_id')
                    ->references('id')
                    ->on('institution')
                    ->cascadeOnDelete();
                $table->foreign('semester_id')
                    ->references('id')
                    ->on('semesters')
                    ->cascadeOnDelete();
            });
        }

        $hasNameUnique = collect(DB::select('SHOW INDEX FROM lesson_schedule_templates'))
            ->contains(fn ($idx) => $idx->Key_name === 'lesson_schedule_templates_inst_sem_name_unique');

        if (! $hasNameUnique) {
            Schema::table('lesson_schedule_templates', function (Blueprint $table) {
                $table->unique(
                    ['institution_id', 'semester_id', 'name'],
                    'lesson_schedule_templates_inst_sem_name_unique'
                );
            });
        }
    }

    public function down(): void
    {
        // Tidak mengembalikan unique lama — akan memblokir multi-template.
    }
};
