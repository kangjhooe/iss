<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Visi, misi, dan gambar cover untuk halaman publik sekolah.
     */
    public function up(): void
    {
        Schema::table('institution', function (Blueprint $table) {
            $table->text('vision')->nullable()->after('description');
            $table->text('mission')->nullable()->after('vision');
            $table->string('cover_image')->nullable()->after('logo')->comment('Gambar cover/hero halaman publik');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('institution', function (Blueprint $table) {
            $table->dropColumn(['vision', 'mission', 'cover_image']);
        });
    }
};
