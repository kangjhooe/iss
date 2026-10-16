<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('question_bank', 'difficulty')) {
            Schema::table('question_bank', function (Blueprint $table) {
                $column = $table->string('difficulty', 10)->nullable();
                if (Schema::hasColumn('question_bank', 'cognitive_level')) {
                    $column->after('cognitive_level');
                } elseif (Schema::hasColumn('question_bank', 'weight')) {
                    $column->after('weight');
                }
                $table->index('difficulty');
            });
        }

        if (! Schema::hasColumn('question_bank', 'tags')) {
            Schema::table('question_bank', function (Blueprint $table) {
                $column = $table->json('tags')->nullable();
                if (Schema::hasColumn('question_bank', 'difficulty')) {
                    $column->after('difficulty');
                }
            });
        }
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
