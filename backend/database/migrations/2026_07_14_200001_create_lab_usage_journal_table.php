<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_usage_journal', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('room_id');
            $table->date('date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->unsignedBigInteger('class_id')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('lab_booking_id')->nullable();
            $table->unsignedBigInteger('lesson_schedule_id')->nullable();
            $table->string('activity');
            $table->unsignedInteger('participants_count')->nullable();
            $table->text('notes')->nullable();
            $table->text('incident_notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('institution_id')->references('id')->on('institution')->onDelete('cascade');
            $table->foreign('room_id')->references('id')->on('room')->onDelete('cascade');
            $table->foreign('recorded_by')->references('id')->on('user')->onDelete('set null');
            $table->foreign('class_id')->references('id')->on('class')->onDelete('set null');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('set null');
            $table->foreign('lab_booking_id')->references('id')->on('lab_booking')->onDelete('set null');
            $table->foreign('lesson_schedule_id')->references('id')->on('lesson_schedules')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('user')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('user')->onDelete('set null');

            $table->index(['room_id', 'date']);
            $table->index(['institution_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_usage_journal');
    }
};
