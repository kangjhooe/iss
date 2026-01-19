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
        Schema::create('correspondence_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('correspondence_id')->constrained('correspondence')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('user')->onDelete('cascade');
            $table->enum('action', ['created', 'updated', 'approved', 'rejected', 'sent', 'disposed', 'archived', 'disposition_completed']);
            $table->text('notes')->nullable(); // Catatan perubahan
            $table->json('changes')->nullable(); // Perubahan data (untuk tracking)
            $table->timestamps();
            
            $table->index('correspondence_id');
            $table->index('user_id');
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('correspondence_histories');
    }
};
