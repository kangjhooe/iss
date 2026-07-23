<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('extracurriculars', function (Blueprint $table) {
            $table->decimal('kkm', 5, 2)->default(75)->after('capacity');
        });

        Schema::create('extracurricular_session_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('extracurricular_sessions')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('student')->onDelete('cascade');
            $table->decimal('score', 5, 2)->nullable();
            $table->string('predicate', 10)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('employee')->onDelete('set null');
            $table->timestamps();

            $table->unique(['session_id', 'student_id'], 'ekskul_session_grade_unique');
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extracurricular_session_grades');

        Schema::table('extracurriculars', function (Blueprint $table) {
            $table->dropColumn('kkm');
        });
    }
};
