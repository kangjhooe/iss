<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('extracurriculars', 'is_pramuka')) {
            Schema::table('extracurriculars', function (Blueprint $table) {
                $table->boolean('is_pramuka')->default(false)->after('status');
            });
        }

        // Backfill: name/description containing "pramuka"
        DB::table('extracurriculars')
            ->where(function ($q) {
                $q->where('name', 'like', '%pramuka%')
                    ->orWhere('description', 'like', '%pramuka%');
            })
            ->update(['is_pramuka' => true]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('extracurriculars', 'is_pramuka')) {
            Schema::table('extracurriculars', function (Blueprint $table) {
                $table->dropColumn('is_pramuka');
            });
        }
    }
};
