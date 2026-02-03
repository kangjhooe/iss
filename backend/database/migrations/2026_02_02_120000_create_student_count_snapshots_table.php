<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Snapshot jumlah siswa per bulan per kelas (untuk laporan: Jumlah Awal = bulan sebelumnya).
     */
    public function up(): void
    {
        Schema::create('student_count_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->onDelete('set null');
            $table->unsignedSmallInteger('year')->comment('Tahun laporan (e.g. 2026)');
            $table->unsignedTinyInteger('month')->comment('Bulan 1-12');
            $table->unsignedTinyInteger('grade')->comment('Kelas/grade (e.g. 10, 11, 12)');
            $table->unsignedInteger('male')->default(0);
            $table->unsignedInteger('female')->default(0);
            $table->unsignedInteger('total')->default(0);
            $table->timestamps();

            $table->unique(['institution_id', 'year', 'month', 'grade'], 'student_count_snapshots_unique');
            $table->index(['institution_id', 'year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_count_snapshots');
    }
};
