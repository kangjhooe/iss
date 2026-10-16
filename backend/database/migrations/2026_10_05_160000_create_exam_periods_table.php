<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->string('code', 64);
            $table->string('name');
            $table->text('description')->nullable();
            $table->dateTime('window_start_at')->nullable();
            $table->dateTime('window_end_at')->nullable();
            $table->enum('status', ['draft', 'open', 'closed'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('user')->onDelete('set null');
            $table->timestamps();

            $table->unique(['institution_id', 'code']);
            $table->index(['institution_id', 'status']);
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->foreignId('exam_period_id')
                ->nullable()
                ->after('institution_id')
                ->constrained('exam_periods')
                ->nullOnDelete();
            $table->index(['exam_period_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('exam_period_id');
        });
        Schema::dropIfExists('exam_periods');
    }
};
