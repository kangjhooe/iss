<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_soal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->string('code', 50)->comment('Kode bank soal, unik per institusi');
            $table->string('name', 200)->nullable()->comment('Nama/deskripsi bank');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->unsignedTinyInteger('grade')->nullable()->comment('Kelas/tingkat (7-9 SMP, 10-12 SMA, dll)');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['institution_id', 'code']);
            $table->index(['institution_id', 'subject_id', 'grade']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_soal');
    }
};
