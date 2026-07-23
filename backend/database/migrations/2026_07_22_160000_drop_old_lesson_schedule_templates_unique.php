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
            Schema::table('lesson_schedule_templates', function (Blueprint $table) {
                $table->dropUnique('lesson_schedule_templates_unique');
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
