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
        Schema::create('feedback_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('submitted_by')->constrained('user')->onDelete('cascade');
            $table->foreignId('handled_by')->nullable()->constrained('user')->onDelete('set null');
            $table->enum('type', ['bug', 'feature']);
            $table->string('title');
            $table->text('description');
            $table->string('module')->nullable();
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed', 'rejected'])->default('open');
            $table->text('admin_note')->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();

            $table->index('institution_id');
            $table->index('submitted_by');
            $table->index('status');
            $table->index('type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback_tickets');
    }
};
