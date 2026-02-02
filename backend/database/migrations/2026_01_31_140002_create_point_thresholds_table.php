<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_thresholds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->integer('point_min')->comment('Batas bawah skor pelanggaran (positif = buruk, mis. 40)');
            $table->integer('point_max')->comment('Batas atas skor pelanggaran (mis. 999)');
            $table->string('action_name', 255)->comment('Nama tindakan: Peringatan, Panggilan orang tua, Skorsing, dll');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0)->comment('Urutan pengecekan (threshold terberat dulu)');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['institution_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_thresholds');
    }
};
