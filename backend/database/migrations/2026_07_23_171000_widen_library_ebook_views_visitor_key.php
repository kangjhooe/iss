<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('library_ebook_views', function (Blueprint $table) {
            $table->string('visitor_key', 80)->change();
        });
    }

    public function down(): void
    {
        Schema::table('library_ebook_views', function (Blueprint $table) {
            $table->string('visitor_key', 64)->change();
        });
    }
};
