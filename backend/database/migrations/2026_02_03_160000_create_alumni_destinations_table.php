<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tracking destinasi alumni setelah lulus: lanjut sekolah, kuliah, kerja, wirausaha, dll.
     */
    public function up(): void
    {
        Schema::create('alumni_destinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('student')->onDelete('cascade');
            $table->string('destination_type', 32); // Sekolah, Perguruan_Tinggi, Kerja, Wirausaha, Lainnya
            $table->string('destination_name', 255);
            $table->string('program_or_position', 255)->nullable();
            $table->year('year_entered')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['institution_id', 'destination_type']);
            $table->index('student_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alumni_destinations');
    }
};
