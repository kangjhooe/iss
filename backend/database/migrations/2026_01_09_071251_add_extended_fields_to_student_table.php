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
        Schema::table('student', function (Blueprint $table) {
            // Tab 1 - Identitas: NIK sebagai ID siswa (required, unique)
            $table->string('nik', 16)->unique()->nullable()->after('id');
            
            // Tab 2 - Tambahan
            $table->string('no_kk', 16)->nullable()->after('religion');
            $table->string('aspiration')->nullable()->after('no_kk'); // Cita-cita
            $table->string('hobby')->nullable()->after('aspiration'); // Hobi
            $table->string('disability')->nullable()->after('hobby'); // Disabilitas
            $table->integer('height')->nullable()->after('disability'); // Tinggi badan (cm)
            $table->integer('weight')->nullable()->after('height'); // Berat badan (kg)
            $table->string('previous_school')->nullable()->after('weight'); // Asal sekolah
            $table->enum('residence_type', ['asrama', 'kost_kontrak', 'tinggal_dengan_orang_tua', 'lainnya'])->nullable()->after('previous_school');
            
            // Tab 3 - Data Ayah Kandung
            $table->enum('father_status', ['masih_hidup', 'meninggal_dunia', 'tidak_diketahui'])->nullable()->after('father_name');
            $table->string('father_nik', 16)->nullable()->after('father_status');
            $table->string('father_birth_place')->nullable()->after('father_nik');
            $table->date('father_birth_date')->nullable()->after('father_birth_place');
            $table->string('father_education')->nullable()->after('father_birth_date');
            $table->string('father_occupation')->nullable()->after('father_education');
            $table->decimal('father_income', 15, 2)->nullable()->after('father_occupation');
            
            // Tab 4 - Data Ibu Kandung
            $table->enum('mother_status', ['masih_hidup', 'meninggal_dunia', 'tidak_diketahui'])->nullable()->after('mother_name');
            $table->string('mother_nik', 16)->nullable()->after('mother_status');
            $table->string('mother_birth_place')->nullable()->after('mother_nik');
            $table->date('mother_birth_date')->nullable()->after('mother_birth_place');
            $table->string('mother_education')->nullable()->after('mother_birth_date');
            $table->string('mother_occupation')->nullable()->after('mother_education');
            $table->decimal('mother_income', 15, 2)->nullable()->after('mother_occupation');
            
            // Tab 5 - Data Wali
            $table->enum('guardian_type', ['sama_dengan_ayah', 'sama_dengan_ibu', 'lainnya'])->nullable()->after('guardian_phone');
            $table->enum('guardian_status', ['masih_hidup', 'meninggal_dunia', 'tidak_diketahui'])->nullable()->after('guardian_type');
            $table->string('guardian_nik', 16)->nullable()->after('guardian_status');
            $table->string('guardian_birth_place')->nullable()->after('guardian_nik');
            $table->date('guardian_birth_date')->nullable()->after('guardian_birth_place');
            $table->string('guardian_education')->nullable()->after('guardian_birth_date');
            $table->string('guardian_occupation')->nullable()->after('guardian_education');
            $table->decimal('guardian_income', 15, 2)->nullable()->after('guardian_occupation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student', function (Blueprint $table) {
            $table->dropColumn([
                'nik',
                'no_kk',
                'aspiration',
                'hobby',
                'disability',
                'height',
                'weight',
                'previous_school',
                'residence_type',
                'father_status',
                'father_nik',
                'father_birth_place',
                'father_birth_date',
                'father_education',
                'father_occupation',
                'father_income',
                'mother_status',
                'mother_nik',
                'mother_birth_place',
                'mother_birth_date',
                'mother_education',
                'mother_occupation',
                'mother_income',
                'guardian_type',
                'guardian_status',
                'guardian_nik',
                'guardian_birth_place',
                'guardian_birth_date',
                'guardian_education',
                'guardian_occupation',
                'guardian_income',
            ]);
        });
    }
};
