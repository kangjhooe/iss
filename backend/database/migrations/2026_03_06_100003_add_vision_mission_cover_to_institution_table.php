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
            if (!Schema::hasColumn('institution', 'vision')) {
                $table->text('vision')->nullable()->after('description');
            }
            if (!Schema::hasColumn('institution', 'mission')) {
                $table->text('mission')->nullable()->after('vision');
            }
            if (!Schema::hasColumn('institution', 'cover_image')) {
                $table->string('cover_image')->nullable()->after('logo')->comment('Gambar cover/hero halaman publik');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('institution', function (Blueprint $table) {
            $columnsToDrop = array_filter(
                ['vision', 'mission', 'cover_image'],
                fn ($col) => Schema::hasColumn('institution', $col)
            );
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
