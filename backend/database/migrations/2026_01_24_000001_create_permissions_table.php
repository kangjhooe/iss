<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->timestamps();
        });

        DB::table('permissions')->insert([
            ['key' => 'institution', 'label' => 'Profil Instansi', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'student', 'label' => 'Data Siswa', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'teacher', 'label' => 'Data Guru', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'facility', 'label' => 'Sarana Prasarana', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'inventory', 'label' => 'Inventaris', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'class', 'label' => 'Kelas', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'correspondence', 'label' => 'Persuratan', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'report', 'label' => 'Laporan', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
