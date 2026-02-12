<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Buku tamu publik boleh tanpa foto.
     */
    public function up(): void
    {
        Schema::table('guest_visits', function (Blueprint $table) {
            $table->string('foto_path', 500)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guest_visits', function (Blueprint $table) {
            $table->string('foto_path', 500)->nullable(false)->change();
        });
    }
};
