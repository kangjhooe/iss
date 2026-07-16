<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('template_surat')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('student')->nullOnDelete();
            $table->string('nomor')->nullable();
            $table->string('judul');
            $table->longText('isi_html');
            $table->string('pdf')->nullable();
            $table->string('letter_type_code', 2)->default('16');
            $table->enum('status', ['draft', 'final'])->default('draft');
            $table->foreignId('created_by')->constrained('user')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['institution_id', 'created_at']);
            $table->index(['nomor']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat');
    }
};
