<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('library_books', function (Blueprint $table) {
            $table->string('ebook_path')->nullable()->after('cover_path');
        });
    }

    public function down(): void
    {
        Schema::table('library_books', function (Blueprint $table) {
            $table->dropColumn('ebook_path');
        });
    }
};
