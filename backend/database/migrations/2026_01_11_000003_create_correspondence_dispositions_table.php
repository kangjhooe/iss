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
        Schema::create('correspondence_dispositions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('correspondence_id')->constrained('correspondence')->onDelete('cascade');
            $table->foreignId('from_user_id')->constrained('user')->onDelete('cascade'); // Yang memberikan disposisi
            $table->foreignId('to_user_id')->constrained('user')->onDelete('cascade'); // Yang menerima disposisi
            $table->text('instruction'); // Instruksi/isi disposisi
            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index('correspondence_id');
            $table->index('from_user_id');
            $table->index('to_user_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('correspondence_dispositions');
    }
};
