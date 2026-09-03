<?php

namespace App\Support\Guides;

/**
 * Konten panduan Guru & Staf (mirror frontend/src/content/guides/guru.js).
 */
class GuruGuide
{
    public static function definition(): array
    {
        return [
            'slug' => 'guru',
            'title' => 'Panduan Guru & Staf',
            'subtitle' => 'Ikuti langkah berurutan: masuk, kenali dashboard, isi absensi & jurnal, nilai, lalu peran tambahan seperti wali kelas.',
            'audience' => 'Guru & Staf',
            'roadmap' => 'Akun → Login → Dashboard → Profil → Jadwal → Absen & Jurnal → Nilai → Wali Kelas → Tambahan → Tips',
            'closing_title' => 'Siap mencoba?',
            'closing_desc' => 'Masuk dengan akun dari admin sekolah, lalu ikuti langkah di panduan ini secara berurutan.',
            'footer_label' => 'Panduan Guru & Staf',
            'steps' => [
                [
                    'id' => 'akun',
                    'short_title' => 'Akun',
                    'title' => 'Dapatkan akun dari admin sekolah',
                    'summary' => 'Akun guru/staf dibuat oleh Admin Institusi, bukan lewat halaman Daftar publik. Siapkan email aktif yang diberikan ke admin.',
                    'actions' => [
                        'Minta Admin membuat akun di Data Pegawai dan mengaktifkan login',
                        'Pastikan Anda mendapat email (atau username) serta password awal',
                        'Jika diminta ganti password saat pertama masuk, selesaikan dulu',
                        'Hubungi admin jika belum muncul menu yang sesuai tugas Anda',
                    ],
                    'tip' => 'Menu sidebar mengikuti akses modul yang admin centang untuk akun Anda.',
                ],
                [
                    'id' => 'login',
                    'short_title' => 'Login',
                    'title' => 'Masuk ke aplikasi',
                    'summary' => 'Login dengan kredensial yang diberikan admin. Setelah berhasil, Anda diarahkan ke Dashboard Guru.',
                    'actions' => [
                        'Buka halaman Masuk',
                        'Masukkan email/username dan password',
                        'Ganti password jika sistem meminta',
                        'Anda akan masuk ke Dashboard Guru',
                    ],
                    'tip' => 'Lupa password? Gunakan Lupa Password di halaman login, atau minta reset ke Admin sekolah.',
                ],
                [
                    'id' => 'dashboard',
                    'short_title' => 'Dashboard',
                    'title' => 'Kenali Dashboard Guru',
                    'summary' => 'Dashboard merangkum tugas harian: jadwal, piket, disposisi surat, dan pintasan ke menu yang relevan dengan peran Anda.',
                    'actions' => [
                        'Buka Dashboard setelah login',
                        'Periksa tahun ajaran / semester yang tampil',
                        'Lihat alert piket, disposisi, atau pengumuman jika ada',
                        'Gunakan sidebar: Jam Mengajar, Jadwal, Wali Kelas, Mapel, dan modul lain sesuai akses',
                    ],
                    'tip' => 'Jika menu kosong atau sedikit, kemungkinan belum ada penugasan mengajar / wali kelas, atau akses modul belum dibuka admin.',
                ],
                [
                    'id' => 'profil',
                    'short_title' => 'Profil',
                    'title' => 'Lengkapi profil Anda',
                    'summary' => 'Profil dipakai untuk identitas di dokumen, absensi, dan data kepegawaian. Perbarui data yang belum lengkap.',
                    'actions' => [
                        'Buka Profil dari Dashboard atau menu akun',
                        'Periksa nama, NIP/NUPTK, kontak, dan foto jika tersedia',
                        'Ajukan perubahan data lewat alur yang disediakan jika ada field terkunci',
                        'Simpan perubahan yang diizinkan',
                    ],
                    'tip' => 'Beberapa data induk hanya bisa diubah admin; guru dapat mengajukan perubahan jika fitur usulan aktif.',
                ],
                [
                    'id' => 'jadwal',
                    'short_title' => 'Jadwal',
                    'title' => 'Cek jadwal & jam mengajar hari ini',
                    'summary' => 'Setelah admin menyusun jadwal pelajaran, Anda melihat sesi mengajar di Jadwal Mengajar dan Jam Mengajar Hari Ini.',
                    'actions' => [
                        'Buka Jadwal Mengajar untuk melihat minggu mengajar Anda',
                        'Buka Jam Mengajar Hari Ini untuk daftar sesi pada tanggal dipilih',
                        'Pilih sesi (kelas + mapel + jam) yang akan diisi',
                        'Pantau ringkasan: absen selesai, jurnal terisi, sesi lengkap',
                    ],
                    'tip' => 'Tidak ada sesi? Pastikan semester aktif benar dan admin sudah assign Anda di jadwal pelajaran.',
                ],
                [
                    'id' => 'absen-jurnal',
                    'short_title' => 'Absen & Jurnal',
                    'title' => 'Isi absensi siswa & jurnal mengajar',
                    'summary' => 'Alur harian utama: dari sesi mengajar, absenkan siswa lalu isi jurnal. Ini fondasi rekap kehadiran dan dokumentasi mengajar.',
                    'actions' => [
                        'Dari Jam Mengajar Hari Ini, pilih sesi yang sedang berlangsung',
                        'Isi kehadiran siswa (hadir, izin, sakit, alfa, dsb.) lalu simpan',
                        'Isi jurnal: materi, kegiatan, catatan bila perlu',
                        'Ulangi untuk sesi berikutnya; cetak rekap bila diperlukan',
                    ],
                    'tip' => 'Absensi pegawai (finger/QR masuk sekolah) biasanya terpisah dari absensi siswa di jam mengajar.',
                ],
                [
                    'id' => 'nilai',
                    'short_title' => 'Nilai',
                    'title' => 'Kelola nilai mata pelajaran',
                    'summary' => 'Isi nilai lewat Hub Mata Pelajaran (per kelas–mapel) atau Buku Nilai jika menu generik tersedia. Ikuti KKM/bobot yang sudah disetel.',
                    'actions' => [
                        'Buka grup Mata Pelajaran di sidebar, pilih kelas–mapel Anda',
                        'Atau buka Buku Nilai jika muncul di menu Keguruan',
                        'Isi nilai sesuai jenis penilaian (harian, PTS, PAS, dsb.)',
                        'Simpan berkala; pantau siswa di bawah KKM untuk remedial bila dipakai',
                    ],
                    'tip' => 'KKM dan bobot biasanya diatur admin/kurikulum. Jika kolom nilai belum muncul, hubungi admin.',
                ],
                [
                    'id' => 'wali',
                    'short_title' => 'Wali Kelas',
                    'title' => 'Peran wali kelas (jika ditunjuk)',
                    'summary' => 'Guru yang ditunjuk wali kelas mendapat menu Wali Kelas: data siswa, absensi, nilai, usulan, jadwal, dan ringkasan keuangan siswa.',
                    'actions' => [
                        'Buka grup Wali Kelas di sidebar',
                        'Pantau Data Siswa dan Absensi kelas Anda',
                        'Cek Nilai dan Usulan (naik kelas / catatan) sesuai kebijakan sekolah',
                        'Gunakan panel Jadwal dan Keuangan untuk pantauan ringkas',
                    ],
                    'tip' => 'Menu Wali Kelas hanya muncul jika admin menunjuk Anda sebagai wali di Data Kelas.',
                ],
                [
                    'id' => 'tambahan',
                    'short_title' => 'Tambahan',
                    'title' => 'Peran & modul tambahan',
                    'summary' => 'Bergantung penugasan dan akses modul: piket, BK, ekskul, lab, ujian online, atau tugas operasional lain.',
                    'actions' => [
                        'Guru Piket — isi log kegiatan / lapor kejadian saat bertugas',
                        'BK — catat pelanggaran, prestasi, atau konseling jika diberi akses',
                        'Ekskul / Lab Saya — kelola kegiatan yang Anda bina atau ruang yang Anda jaga',
                        'Ujian Online / Bank Soal — jika modul ujian diaktifkan untuk akun Anda',
                    ],
                    'tip' => 'Tidak semua guru memakai semua modul. Fokus pada yang muncul di sidebar Anda.',
                ],
                [
                    'id' => 'tips',
                    'short_title' => 'Tips',
                    'title' => 'Tips harian & FAQ',
                    'summary' => 'Setelah terbiasa, jaga konsistensi pengisian dan komunikasi dengan admin bila ada kendala akses.',
                    'actions' => [
                        'Isi absensi & jurnal di hari yang sama agar rekap akurat',
                        'Periksa filter semester/tanggal sebelum input nilai',
                        'Pantau notifikasi (lonceng) untuk disposisi atau pengumuman',
                        'Laporkan bug/kendala lewat Feedback jika tersedia',
                    ],
                    'faqs' => [
                        [
                            'q' => 'Menu Jam Mengajar tidak muncul?',
                            'a' => 'Biasanya karena belum ada penugasan di jadwal pelajaran, atau akses modul jurnal/nilai belum dibuka. Minta admin memeriksa.',
                        ],
                        [
                            'q' => 'Lupa password?',
                            'a' => 'Gunakan Lupa Password di halaman login, atau minta Admin sekolah mereset akun Anda.',
                        ],
                        [
                            'q' => 'Saya wali kelas tapi menu tidak ada?',
                            'a' => 'Pastikan admin sudah menetapkan Anda sebagai wali di Data Kelas untuk semester/tahun ajaran aktif.',
                        ],
                    ],
                ],
            ],
        ];
    }
}
