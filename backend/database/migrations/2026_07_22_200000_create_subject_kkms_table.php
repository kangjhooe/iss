<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_kkms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->unsignedTinyInteger('grade'); // tingkat: 7, 8, 9, ...
            $table->foreignId('semester_id')->constrained('semesters')->onDelete('cascade');
            $table->decimal('kkm', 5, 2);
            $table->foreignId('set_by_employee_id')->nullable()->constrained('employee')->onDelete('set null');
            $table->timestamps();

            $table->unique(
                ['institution_id', 'subject_id', 'grade', 'semester_id'],
                'subject_kkms_unique'
            );
            $table->index(['institution_id', 'semester_id']);
            $table->index(['subject_id', 'grade']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_kkms');
    }
};
