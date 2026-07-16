<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surat', function (Blueprint $table) {
            // Enforce 1:1 surat ↔ correspondence (MySQL allows multiple NULLs)
            $table->unique('correspondence_id');
        });
    }

    public function down(): void
    {
        Schema::table('surat', function (Blueprint $table) {
            $table->dropUnique(['correspondence_id']);
        });
    }
};
