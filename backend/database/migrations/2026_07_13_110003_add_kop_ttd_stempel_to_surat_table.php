<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surat', function (Blueprint $table) {
            $table->foreignId('kop_id')->nullable()->after('template_id')->constrained('kop_surat')->nullOnDelete();
            $table->boolean('tampilkan_kop')->default(true)->after('kop_id');
            $table->foreignId('tanda_tangan_id')->nullable()->after('tampilkan_kop')->constrained('aset_tanda_tangan')->nullOnDelete();
            $table->boolean('tampilkan_tanda_tangan')->default(false)->after('tanda_tangan_id');
            $table->foreignId('stempel_id')->nullable()->after('tampilkan_tanda_tangan')->constrained('aset_tanda_tangan')->nullOnDelete();
            $table->boolean('tampilkan_stempel')->default(false)->after('stempel_id');
            $table->enum('posisi_ttd', ['kanan', 'kiri', 'ganda'])->default('kanan')->after('tampilkan_stempel');
        });
    }

    public function down(): void
    {
        Schema::table('surat', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kop_id');
            $table->dropColumn('tampilkan_kop');
            $table->dropConstrainedForeignId('tanda_tangan_id');
            $table->dropColumn('tampilkan_tanda_tangan');
            $table->dropConstrainedForeignId('stempel_id');
            $table->dropColumn(['tampilkan_stempel', 'posisi_ttd']);
        });
    }
};
