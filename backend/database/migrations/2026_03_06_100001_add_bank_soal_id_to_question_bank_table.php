<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_bank', function (Blueprint $table) {
            $table->foreignId('bank_soal_id')->nullable()->after('institution_id')->constrained('bank_soal')->onDelete('cascade');
        });
        Schema::table('question_bank', function (Blueprint $table) {
            $table->index('bank_soal_id');
        });
    }

    public function down(): void
    {
        Schema::table('question_bank', function (Blueprint $table) {
            $table->dropForeign(['bank_soal_id']);
            $table->dropIndex(['bank_soal_id']);
        });
    }
};
