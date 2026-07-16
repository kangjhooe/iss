<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('template_surat', function (Blueprint $table) {
            $table->unsignedBigInteger('source_template_id')->nullable()->after('institution_id');
            $table->foreign('source_template_id')
                ->references('id')
                ->on('template_surat')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('template_surat', function (Blueprint $table) {
            $table->dropForeign(['source_template_id']);
            $table->dropColumn('source_template_id');
        });
    }
};
