<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Katalog mapel global (super admin). Kode 4 digit:
     * 1xxx SD/MI, 2xxx SMP/MTs, 3xxx SMA/MA, 4xxx SMK/MAK, 5xxx PAUD/TK.
     */
    public function up(): void
    {
        Schema::create('subject_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name', 255);
            $table->enum('jenjang', ['SD', 'SMP', 'SMA', 'SMK', 'PAUD']);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['jenjang', 'is_active']);
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_catalog');
    }
};
