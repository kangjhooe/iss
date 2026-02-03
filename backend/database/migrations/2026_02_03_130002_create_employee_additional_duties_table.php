<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * One employee (guru) can have multiple additional duties.
     */
    public function up(): void
    {
        Schema::create('employee_additional_duties', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('additional_duty_id');
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->timestamps();

            $table->primary(['employee_id', 'additional_duty_id']);
            $table->foreign('employee_id')->references('id')->on('employee')->onDelete('cascade');
            $table->foreign('additional_duty_id')->references('id')->on('additional_duties')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_additional_duties');
    }
};
