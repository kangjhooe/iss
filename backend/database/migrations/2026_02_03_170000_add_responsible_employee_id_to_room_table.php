<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Penanggung jawab ruangan (mis. Kepala Lab untuk ruang Laboratorium).
     */
    public function up(): void
    {
        Schema::table('room', function (Blueprint $table) {
            $table->unsignedBigInteger('responsible_employee_id')->nullable()->after('description');
            $table->foreign('responsible_employee_id')->references('id')->on('employee')->onDelete('set null');
            $table->index('responsible_employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('room', function (Blueprint $table) {
            $table->dropForeign(['responsible_employee_id']);
            $table->dropColumn('responsible_employee_id');
        });
    }
};
