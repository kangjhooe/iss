<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Pengambilan Ijazah: catatan pengambilan dokumen (ijazah, raport, dll) oleh alumni.
     */
    public function up(): void
    {
        Schema::create('document_pickups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('student')->onDelete('cascade');
            $table->date('pickup_date');
            $table->boolean('taken_ijazah')->default(false);
            $table->boolean('taken_raport')->default(false);
            $table->boolean('taken_skhun')->default(false);
            $table->string('nomor_ijazah', 64)->nullable();
            $table->string('kode_blangko', 64)->nullable();
            $table->text('dokumen_lainnya')->nullable();
            $table->string('photo_path', 500)->nullable();
            $table->string('received_by', 255)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('user')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['institution_id', 'pickup_date']);
            $table->index('student_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_pickups');
    }
};
