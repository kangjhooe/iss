<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('class', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('room_id')->nullable()->constrained('room')->onDelete('set null');
            $table->foreignId('teacher_id')->nullable()->constrained('teacher')->onDelete('set null');
            $table->string('code')->nullable(); // Kode kelas (contoh: VII-A, 10-IPA-1)
            $table->string('name'); // Nama kelas (contoh: VII-A, 10 IPA 1, Kelas A)
            $table->integer('grade')->nullable(); // Tingkat (1-6 untuk SD/MI, 7-9 untuk SMP/MTs, 10-12 untuk SMA/MA/MAK/SMK, null untuk PAUD)
            $table->string('academic_year'); // Tahun ajaran (contoh: 2024/2025)
            $table->integer('capacity')->nullable(); // Kapasitas maksimal siswa
            $table->enum('status', ['Aktif', 'Nonaktif'])->default('Aktif');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('institution_id');
            $table->index('room_id');
            $table->index('teacher_id');
            $table->index(['institution_id', 'academic_year']);
            $table->index(['institution_id', 'grade', 'academic_year']);
            $table->index('status');
            
            // Note: Validasi untuk memastikan satu ruangan hanya digunakan oleh satu kelas aktif 
            // di tahun ajaran yang sama dilakukan di aplikasi (ClassService)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class');
    }
};
