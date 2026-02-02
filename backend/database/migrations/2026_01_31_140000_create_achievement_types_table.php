<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achievement_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->string('name', 255);
            $table->string('code', 50)->nullable();
            $table->integer('point_value')->default(0)->comment('Poin plus (pengurangan dari total pelanggaran)');
            $table->string('category', 50)->nullable()->comment('akademik, non_akademik, sikap');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['institution_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achievement_types');
    }
};
