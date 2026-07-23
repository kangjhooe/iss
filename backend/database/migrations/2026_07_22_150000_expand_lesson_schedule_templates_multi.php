<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_schedule_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('lesson_schedule_templates', 'name')) {
                $table->string('name', 100)->default('Default')->after('semester_id');
            }
            if (! Schema::hasColumn('lesson_schedule_templates', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('name');
            }
        });

        // Satu template per semester yang sudah ada → jadi default.
        DB::table('lesson_schedule_templates')
            ->where(function ($q) {
                $q->whereNull('name')->orWhere('name', '');
            })
            ->update(['name' => 'Default']);
        DB::table('lesson_schedule_templates')->update(['is_default' => true]);

        $hasOldUnique = collect(DB::select('SHOW INDEX FROM lesson_schedule_templates'))
            ->contains(fn ($idx) => $idx->Key_name === 'lesson_schedule_templates_unique');

        if ($hasOldUnique) {
            Schema::table('lesson_schedule_templates', function (Blueprint $table) {
                $table->dropUnique('lesson_schedule_templates_unique');
            });
        }

        try {
            Schema::table('lesson_schedule_templates', function (Blueprint $table) {
                $table->unique(
                    ['institution_id', 'semester_id', 'name'],
                    'lesson_schedule_templates_inst_sem_name_unique'
                );
            });
        } catch (\Throwable) {
            // Already exists.
        }

        if (! Schema::hasColumn('class', 'lesson_schedule_template_id')) {
            Schema::table('class', function (Blueprint $table) {
                $table->foreignId('lesson_schedule_template_id')
                    ->nullable()
                    ->after('semester_id')
                    ->constrained('lesson_schedule_templates')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('class', 'lesson_schedule_template_id')) {
            Schema::table('class', function (Blueprint $table) {
                $table->dropConstrainedForeignId('lesson_schedule_template_id');
            });
        }

        try {
            Schema::table('lesson_schedule_templates', function (Blueprint $table) {
                $table->dropUnique('lesson_schedule_templates_inst_sem_name_unique');
            });
        } catch (\Throwable) {
            //
        }

        $keepIds = DB::table('lesson_schedule_templates')
            ->select(DB::raw('MIN(id) as id'))
            ->groupBy('institution_id', 'semester_id')
            ->pluck('id');

        DB::table('lesson_schedule_templates')
            ->whereNotIn('id', $keepIds)
            ->delete();

        try {
            Schema::table('lesson_schedule_templates', function (Blueprint $table) {
                $table->unique(['institution_id', 'semester_id'], 'lesson_schedule_templates_unique');
            });
        } catch (\Throwable) {
            //
        }

        Schema::table('lesson_schedule_templates', function (Blueprint $table) {
            if (Schema::hasColumn('lesson_schedule_templates', 'is_default')) {
                $table->dropColumn('is_default');
            }
            if (Schema::hasColumn('lesson_schedule_templates', 'name')) {
                $table->dropColumn('name');
            }
        });
    }
};
