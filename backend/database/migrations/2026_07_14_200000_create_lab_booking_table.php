<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_booking', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('room_id');
            $table->unsignedBigInteger('requester_employee_id')->nullable();
            $table->string('requester_name')->nullable();
            $table->string('purpose');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('institution_id')->references('id')->on('institution')->onDelete('cascade');
            $table->foreign('room_id')->references('id')->on('room')->onDelete('cascade');
            $table->foreign('requester_employee_id')->references('id')->on('employee')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('user')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('user')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('user')->onDelete('set null');

            $table->index(['room_id', 'date', 'status']);
            $table->index(['institution_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_booking');
    }
};
