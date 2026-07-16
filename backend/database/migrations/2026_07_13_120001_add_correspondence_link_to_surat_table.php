<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surat', function (Blueprint $table) {
            $table->foreignId('correspondence_id')
                ->nullable()
                ->after('institution_id')
                ->constrained('correspondence')
                ->nullOnDelete();
            $table->date('tanggal')->nullable()->after('judul');
            $table->timestamp('published_at')->nullable()->after('status');
        });

        // Normalisasi status: final → terbit, lalu set enum draft|terbit
        DB::table('surat')->where('status', 'final')->update(['status' => 'terbit']);

        DB::statement("ALTER TABLE surat MODIFY COLUMN status ENUM('draft', 'terbit') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE surat MODIFY COLUMN status ENUM('draft', 'final') NOT NULL DEFAULT 'draft'");
        DB::table('surat')->where('status', 'terbit')->update(['status' => 'final']);

        Schema::table('surat', function (Blueprint $table) {
            $table->dropConstrainedForeignId('correspondence_id');
            $table->dropColumn(['tanggal', 'published_at']);
        });
    }
};
