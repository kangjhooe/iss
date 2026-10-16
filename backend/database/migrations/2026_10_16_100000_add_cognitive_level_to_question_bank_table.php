<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_bank', function (Blueprint $table) {
            if (! Schema::hasColumn('question_bank', 'cognitive_level')) {
                $table->string('cognitive_level', 5)->nullable()->after('weight')
                    ->comment('Taksonomi Bloom: c1–c6');
                $table->index('cognitive_level');
            }
        });
    }

    public function down(): void
    {
        Schema::table('question_bank', function (Blueprint $table) {
            if (Schema::hasColumn('question_bank', 'cognitive_level')) {
                $table->dropIndex(['cognitive_level']);
                $table->dropColumn('cognitive_level');
            }
        });
    }
};
