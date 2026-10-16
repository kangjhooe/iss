<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_bank', function (Blueprint $table) {
            if (! Schema::hasColumn('question_bank', 'status')) {
                $table->string('status', 16)->default('ready')->after('type');
                $table->index('status');
            }
            if (! Schema::hasColumn('question_bank', 'topic')) {
                $table->string('topic', 120)->nullable()->after('status');
                $table->index('topic');
            }
        });
    }

    public function down(): void
    {
        Schema::table('question_bank', function (Blueprint $table) {
            if (Schema::hasColumn('question_bank', 'topic')) {
                $table->dropIndex(['topic']);
                $table->dropColumn('topic');
            }
            if (Schema::hasColumn('question_bank', 'status')) {
                $table->dropIndex(['status']);
                $table->dropColumn('status');
            }
        });
    }
};
