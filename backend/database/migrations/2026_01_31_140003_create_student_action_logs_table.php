<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_action_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('student')->onDelete('cascade');
            $table->foreignId('point_threshold_id')->nullable()->constrained('point_thresholds')->onDelete('set null');
            $table->string('action_name', 255)->comment('Nama tindakan yang dilaksanakan');
            $table->date('action_date');
            $table->foreignId('recorded_by')->nullable()->constrained('user')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['institution_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_action_logs');
    }
};
