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
        Schema::create('additional_duties', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        $duties = [
            ['key' => 'kepala_sekolah', 'label' => 'Kepala Sekolah', 'description' => 'Pimpinan sekolah: pengawasan, laporan, dan penandatanganan (bukan pengelolaan operasional harian)', 'sort_order' => 1],
            ['key' => 'waka_kurikulum', 'label' => 'Wakil Kepala Sekolah Kurikulum', 'description' => 'Urusan kurikulum, jadwal, pembelajaran', 'sort_order' => 2],
            ['key' => 'waka_kesiswaan', 'label' => 'Wakil Kepala Sekolah Kesiswaan', 'description' => 'Urusan siswa, OSIS, disiplin, ekstrakurikuler', 'sort_order' => 3],
            ['key' => 'waka_sarpras', 'label' => 'Wakil Kepala Sekolah Sarana Prasarana', 'description' => 'Sarana, prasarana, inventaris, pemeliharaan', 'sort_order' => 4],
            ['key' => 'waka_humas', 'label' => 'Wakil Kepala Sekolah Humas', 'description' => 'Hubungan masyarakat, publikasi', 'sort_order' => 5],
            ['key' => 'kepala_tata_usaha', 'label' => 'Kepala Tata Usaha', 'description' => 'Administrasi umum, kepegawaian, surat-menyurat', 'sort_order' => 6],
            ['key' => 'bendahara', 'label' => 'Bendahara', 'description' => 'Keuangan (BOS, SPP, dana lain)', 'sort_order' => 7],
            ['key' => 'ketua_perpus', 'label' => 'Ketua/Kepala Perpustakaan', 'description' => 'Perpustakaan, peminjaman, literasi', 'sort_order' => 8],
            ['key' => 'kepala_lab', 'label' => 'Kepala Lab', 'description' => 'Laboratorium (IPA, Komputer, Bahasa)', 'sort_order' => 9],
            ['key' => 'koordinator_bk', 'label' => 'Koordinator BK', 'description' => 'Bimbingan Konseling', 'sort_order' => 10],
            ['key' => 'koordinator_uks', 'label' => 'Koordinator UKS', 'description' => 'Usaha Kesehatan Sekolah', 'sort_order' => 11],
            ['key' => 'koordinator_osis', 'label' => 'Koordinator OSIS', 'description' => 'Pembina OSIS', 'sort_order' => 12],
            ['key' => 'koordinator_pramuka', 'label' => 'Koordinator Pramuka', 'description' => 'Pembina Pramuka', 'sort_order' => 13],
            ['key' => 'koordinator_literasi', 'label' => 'Koordinator Literasi', 'description' => 'Program literasi sekolah', 'sort_order' => 14],
            ['key' => 'operator_sekolah', 'label' => 'Operator Sekolah', 'description' => 'Dapodik/EMIS, data pokok pendidikan', 'sort_order' => 15],
            ['key' => 'koordinator_ekstrakurikuler', 'label' => 'Koordinator Ekstrakurikuler', 'description' => 'Koordinasi ekstrakurikuler', 'sort_order' => 16],
            ['key' => 'pembina_ekstrakurikuler', 'label' => 'Pembina Ekstrakurikuler', 'description' => 'Pembina satu atau lebih ekstrakurikuler', 'sort_order' => 17],
        ];

        $now = now();
        foreach ($duties as $duty) {
            $duty['created_at'] = $now;
            $duty['updated_at'] = $now;
            DB::table('additional_duties')->insert($duty);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('additional_duties');
    }
};
