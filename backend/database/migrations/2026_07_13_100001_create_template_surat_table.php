<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_surat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->nullable()->constrained('institution')->nullOnDelete();
            $table->string('nama');
            $table->string('kode', 50);
            $table->longText('isi_html');
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->string('letter_type_code', 2)->default('16');
            $table->timestamps();

            $table->unique(['institution_id', 'kode']);
            $table->index(['status', 'institution_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_surat');
    }
};
