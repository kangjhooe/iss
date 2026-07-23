<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Snapshot kelas/NIS sebelum mutasi agar bisa di-restore saat pembatalan.
     */
    public function up(): void
    {
        Schema::table('student_mutations', function (Blueprint $table) {
            $table->unsignedBigInteger('previous_class_id')->nullable()->after('student_gender');
            $table->string('previous_nis', 50)->nullable()->after('previous_class_id');
            $table->string('previous_class_name', 100)->nullable()->after('previous_nis');
        });
    }

    public function down(): void
    {
        Schema::table('student_mutations', function (Blueprint $table) {
            $table->dropColumn(['previous_class_id', 'previous_nis', 'previous_class_name']);
        });
    }
};
