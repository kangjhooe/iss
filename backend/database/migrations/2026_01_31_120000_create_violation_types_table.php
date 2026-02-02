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
        Schema::create('violation_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->string('name', 255);
            $table->string('code', 50)->nullable();
            $table->enum('category', ['ringan', 'sedang', 'berat'])->default('ringan');
            $table->integer('point_weight')->default(0)->comment('Bobot point minus untuk sistem point');
            $table->string('default_sanction', 255)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['institution_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('violation_types');
    }
};
