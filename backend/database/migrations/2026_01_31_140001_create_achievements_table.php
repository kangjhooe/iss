<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('student')->onDelete('cascade');
            $table->foreignId('achievement_type_id')->constrained('achievement_types')->onDelete('restrict');
            $table->foreignId('given_by')->constrained('user')->onDelete('cascade');
            $table->date('achievement_date');
            $table->integer('point_value')->comment('Poin plus yang diberikan');
            $table->text('notes')->nullable();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->onDelete('set null');
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->onDelete('set null');
            $table->timestamps();
            $table->index(['institution_id', 'achievement_date']);
            $table->index(['student_id', 'achievement_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achievements');
    }
};
