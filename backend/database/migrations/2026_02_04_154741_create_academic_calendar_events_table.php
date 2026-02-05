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
        Schema::create('academic_calendar_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('event_type', ['Ujian', 'Libur', 'Kegiatan', 'Other'])->default('Kegiatan');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_all_day')->default(true);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->json('reminder_days_before')->nullable()->comment('Array of days before event to send reminder, e.g. [7, 3, 1]');
            $table->string('color', 7)->nullable()->comment('Hex color code for calendar display');
            $table->enum('status', ['Aktif', 'Dibatalkan', 'Draft'])->default('Aktif');
            $table->timestamp('reminder_sent_at')->nullable();
            $table->foreignId('created_by')->constrained('user')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('institution_id');
            $table->index('academic_year_id');
            $table->index('semester_id');
            $table->index('event_type');
            $table->index('status');
            $table->index(['start_date', 'end_date']);
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_calendar_events');
    }
};
