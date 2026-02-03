<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Jenis lab (hanya untuk ruang tipe Laboratorium): IPA, Komputer, Bahasa, Lainnya.
     */
    public function up(): void
    {
        Schema::table('room', function (Blueprint $table) {
            $table->string('lab_type', 50)->nullable()->after('type')->comment('IPA, Komputer, Bahasa, Lainnya');
            $table->index('lab_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('room', function (Blueprint $table) {
            $table->dropIndex(['lab_type']);
            $table->dropColumn('lab_type');
        });
    }
};
