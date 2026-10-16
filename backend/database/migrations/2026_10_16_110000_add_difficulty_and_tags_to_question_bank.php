<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_bank', function (Blueprint $table) {
            if (! Schema::hasColumn('question_bank', 'difficulty')) {
                $table->string('difficulty', 10)->nullable()->after('cognitive_level');
                $table->index('difficulty');
            }
            if (! Schema::hasColumn('question_bank', 'tags')) {
                $table->json('tags')->nullable()->after('difficulty');
            }
        });
    }

    public function down(): void
    {
        Schema::table('question_bank', function (Blueprint $table) {
            if (Schema::hasColumn('question_bank', 'tags')) {
                $table->dropColumn('tags');
            }
            if (Schema::hasColumn('question_bank', 'difficulty')) {
                $table->dropIndex(['difficulty']);
                $table->dropColumn('difficulty');
            }
        });
    }
};
