<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('library_books', function (Blueprint $table) {
            $table->string('grade', 20)->nullable()->after('shelf_code');
            $table->date('acquired_at')->nullable()->after('grade');
        });
    }

    public function down(): void
    {
        Schema::table('library_books', function (Blueprint $table) {
            $table->dropColumn(['grade', 'acquired_at']);
        });
    }
};
