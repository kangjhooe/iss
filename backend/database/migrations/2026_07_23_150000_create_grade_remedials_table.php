<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remidi / pengayaan untuk siswa di bawah (atau di atas) KKM.
     */
    public function up(): void
    {
        Schema::create('grade_remedials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('semester_id')->constrained('semesters')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('class')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('student')->onDelete('cascade');
            $table->foreignId('employee_id')->nullable()->constrained('employee')->onDelete('set null');

            // remedial | pengayaan
            $table->string('type', 20)->default('remedial');
            // penilaian_N | uts | uas | nilai_akhir
            $table->string('source_grade_type', 40)->default('nilai_akhir');
            $table->decimal('original_value', 5, 2)->nullable();
            $table->decimal('kkm_snapshot', 5, 2)->nullable();

            $table->date('scheduled_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->decimal('remedial_value', 5, 2)->nullable();
            // planned | completed | cancelled
            $table->string('status', 20)->default('planned');
            $table->boolean('apply_to_grade')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(
                ['institution_id', 'class_id', 'subject_id', 'semester_id'],
                'grade_remedials_scope_idx'
            );
            $table->index(['student_id', 'status']);
            $table->index(['status', 'scheduled_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_remedials');
    }
};
