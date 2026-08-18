<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pkl_journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('pkl_placement_id')->constrained('pkl_placements')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('student')->onDelete('cascade');
            $table->date('journal_date');
            $table->text('activities');
            $table->decimal('hours', 4, 1)->nullable();
            $table->string('status', 20)->default('submitted');
            $table->text('supervisor_notes')->nullable();
            $table->timestamps();

            $table->unique(['pkl_placement_id', 'journal_date']);
            $table->index(['institution_id', 'student_id']);
            $table->index(['pkl_placement_id', 'journal_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pkl_journals');
    }
};
