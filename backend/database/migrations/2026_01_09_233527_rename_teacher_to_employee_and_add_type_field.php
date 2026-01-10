<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rename table teacher to employee
        Schema::rename('teacher', 'employee');
        
        // Update foreign key in class table
        Schema::table('class', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
        });
        
        Schema::table('class', function (Blueprint $table) {
            $table->foreign('teacher_id')->references('id')->on('employee')->onDelete('set null');
        });
        
        // Add type field to employee table
        Schema::table('employee', function (Blueprint $table) {
            $table->enum('type', ['Guru', 'Staff', 'Tenaga Administrasi', 'Tenaga Kebersihan', 'Tenaga Keamanan', 'Lainnya'])->default('Guru')->after('institution_id');
        });
        
        // Update employment_status enum to include more options for non-teachers
        Schema::table('employee', function (Blueprint $table) {
            $table->dropColumn('employment_status');
        });
        
        Schema::table('employee', function (Blueprint $table) {
            $table->enum('employment_status', [
                'PNS', 
                'CPNS', 
                'Guru Tetap Yayasan', 
                'Guru Honor Sekolah', 
                'Guru Kontrak',
                'Pegawai Tetap Yayasan',
                'Pegawai Honor',
                'Pegawai Kontrak'
            ])->nullable()->after('religion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove type field
        Schema::table('employee', function (Blueprint $table) {
            $table->dropColumn('type');
        });
        
        // Revert employment_status enum
        Schema::table('employee', function (Blueprint $table) {
            $table->dropColumn('employment_status');
        });
        
        Schema::table('employee', function (Blueprint $table) {
            $table->enum('employment_status', ['PNS', 'CPNS', 'Guru Tetap Yayasan', 'Guru Honor Sekolah', 'Guru Kontrak'])->nullable()->after('religion');
        });
        
        // Update foreign key in class table back to teacher
        Schema::table('class', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
        });
        
        Schema::table('class', function (Blueprint $table) {
            $table->foreign('teacher_id')->references('id')->on('teacher')->onDelete('set null');
        });
        
        // Rename table back to teacher
        Schema::rename('employee', 'teacher');
    }
};
