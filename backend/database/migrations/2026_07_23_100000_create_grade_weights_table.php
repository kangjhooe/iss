<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bobot nilai per kelas + mapel + semester (diatur guru pengajar).
     * weight_* dalam persen (0–100), jumlah harus 100.
     */
    public function up(): void
    {
        Schema::create('grade_weights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('class')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('semester_id')->constrained('semesters')->onDelete('cascade');
            $table->decimal('weight_penilaian', 5, 2)->default(40);
            $table->decimal('weight_uts', 5, 2)->default(30);
            $table->decimal('weight_uas', 5, 2)->default(30);
            $table->unsignedSmallInteger('assessment_count')->default(1);
            $table->foreignId('set_by_employee_id')->nullable()->constrained('employee')->onDelete('set null');
            $table->timestamps();

            $table->unique(
                ['institution_id', 'class_id', 'subject_id', 'semester_id'],
                'grade_weights_unique'
            );
            $table->index(['institution_id', 'semester_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_weights');
    }
};
