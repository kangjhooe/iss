<?php

namespace App\Support\Guides;

/**
 * Konten panduan Admin Institusi (mirror frontend/src/content/guides/admin.js).
 */
class AdminGuide
{
    public static function definition(): array
    {
        return [
            'slug' => 'admin',
            'title' => 'Panduan Admin',
            'subtitle' => 'Ikuti langkah berurutan: dari mendaftar sekolah, masuk, menyiapkan data, hingga operasional harian.',
            'audience' => 'Admin Institusi',
            'roadmap' => 'Daftar → Login → Profil → Semester → Modul → Master data → Siswa → Operasional → Layanan → Rutin',
            'closing_title' => 'Siap mencoba?',
            'closing_desc' => 'Daftarkan sekolah Anda di aplikasi, lalu ikuti langkah di panduan ini secara berurutan.',
            'footer_label' => 'Panduan Admin Institusi',
            'steps' => [
                [
                    'id' => 'daftar',
                    'short_title' => 'Daftar',
                    'title' => 'Daftarkan sekolah Anda',
                    'summary' => 'Buat akun administrator dan data institusi dari halaman Daftar. Siapkan NPSN (8 digit) serta email yang masih aktif.',
                    'actions' => [
                        'Buka Daftar Sekolah Baru',
                        'Isi NPSN, nama sekolah/madrasah, nama admin, email, dan nomor HP/WA',
                        'Buat password (minimal 8 karakter) lalu konfirmasi',
                        'Kirim formulir dan tunggu akun siap dipakai',
                    ],
                    'tip' => 'Pastikan NPSN belum terdaftar di sistem. Satu NPSN = satu sekolah.',
                ],
                [
                    'id' => 'login',
                    'short_title' => 'Login',
                    'title' => 'Masuk ke aplikasi',
                    'summary' => 'Login dengan email dan password yang didaftarkan. Jika sistem meminta ganti password, selesaikan dulu sebelum memakai menu lain.',
                    'actions' => [
                        'Buka halaman Masuk',
                        'Masukkan email dan password admin',
                        'Jika diminta ganti password, buat password baru yang kuat',
                        'Anda akan diarahkan ke Dashboard admin',
                    ],
                    'tip' => 'Lupa password? Gunakan tautan Lupa Password di halaman login, atau minta reset ke Super Admin platform.',
                ],
                [
                    'id' => 'profil',
                    'short_title' => 'Profil',
                    'title' => 'Lengkapi profil instansi',
                    'summary' => 'Profil sekolah menjadi dasar kop surat, laporan PDF, dan identitas di portal publik. Lengkapi sedini mungkin.',
                    'actions' => [
                        'Buka Profil Instansi dari sidebar',
                        'Periksa nama, alamat, jenjang, dan kontak',
                        'Unggah logo sekolah jika ada',
                        'Simpan perubahan',
                    ],
                    'tip' => 'Logo yang jelas akan muncul di dokumen cetak (raport, surat, kwitansi).',
                ],
                [
                    'id' => 'periode',
                    'short_title' => 'Semester',
                    'title' => 'Atur tahun ajaran & semester',
                    'summary' => 'Banyak modul (nilai, absensi, keuangan, jurnal) mengikuti semester/tahun ajaran aktif. Setel sebelum mengisi data operasional.',
                    'actions' => [
                        'Buka menu Semester (dan Tahun Ajaran jika tersedia)',
                        'Pastikan tahun ajaran berjalan sudah ada',
                        'Aktifkan semester yang sedang dipakai sekolah',
                        'Ganti semester lagi saat pergantian periode',
                    ],
                    'tip' => 'Salah semester aktif bisa membuat data absensi/nilai masuk ke periode yang keliru.',
                ],
                [
                    'id' => 'modul',
                    'short_title' => 'Modul',
                    'title' => 'Pilih modul yang dipakai',
                    'summary' => 'Sembunyikan modul yang tidak relevan agar sidebar lebih ringkas, lalu atur akses per guru/staf sesuai tugas.',
                    'actions' => [
                        'Buka Akses Modul',
                        'Nonaktifkan modul yang sekolah belum pakai',
                        'Untuk tiap guru/staf, centang modul yang boleh diakses',
                        'Simpan — menu sidebar akan menyesuaikan',
                    ],
                    'tip' => 'Anda bisa mengaktifkan modul lagi kapan saja saat sekolah siap memakai fitur baru.',
                ],
                [
                    'id' => 'master',
                    'short_title' => 'Master data',
                    'title' => 'Siapkan master data',
                    'summary' => 'Urutan yang disarankan: kelas → pegawai/guru → (opsional) mata pelajaran. Ini fondasi sebelum siswa dan jadwal.',
                    'actions' => [
                        'Buat daftar Kelas sesuai tingkat/jurusan',
                        'Tambah Data Pegawai (guru & staf), lengkapi NIP/mapel bila perlu',
                        'Buat akun login guru/staf dan tetapkan akses modul',
                        'Tambah mata pelajaran jika akan memakai jadwal & nilai',
                    ],
                    'tip' => 'Untuk SMK, siapkan juga Program Keahlian sebelum membuat kelas jurusan.',
                ],
                [
                    'id' => 'siswa',
                    'short_title' => 'Siswa',
                    'title' => 'Isi data siswa',
                    'summary' => 'Masukkan siswa aktif ke kelas. Bisa input manual, impor massal, atau tarik alumni jenjang sebelumnya (jika tersedia).',
                    'actions' => [
                        'Buka Data Siswa → Tambah atau Impor',
                        'Pastikan siswa terhubung ke kelas yang benar',
                        'Buat akun login siswa bila portal siswa dipakai',
                        'Kelola pindah/DO di Siswa Keluar; lulusan di Luluskan & Alumni',
                    ],
                    'tip' => 'Mutasi antar sekolah memakai NIK (16 digit). Pantau notifikasi lewat ikon lonceng di header.',
                ],
                [
                    'id' => 'operasional',
                    'short_title' => 'Operasional',
                    'title' => 'Jalankan operasional akademik',
                    'summary' => 'Setelah master data siap, aktifkan alur harian: jadwal, absensi, jurnal, nilai, serta peran wali kelas.',
                    'actions' => [
                        'Susun Jadwal Pelajaran (template jam → assign ke kelas)',
                        'Guru mengisi absensi & jurnal lewat Jam Mengajar Hari Ini',
                        'Atur KKM/bobot lalu isi Buku Nilai; cetak Raport saat akhir periode',
                        'Tunjuk wali kelas agar menu Wali Kelas muncul di akun guru',
                    ],
                    'tip' => 'Absensi pegawai (guru/staf) dan kartu QR absensi dikelola terpisah di grup Absensi.',
                ],
                [
                    'id' => 'opsional',
                    'short_title' => 'Layanan',
                    'title' => 'Aktifkan layanan sesuai kebutuhan',
                    'summary' => 'Modul berikut boleh dinyalakan bertahap. Tidak wajib di minggu pertama.',
                    'actions' => [
                        'PPDB — periode, jalur, verifikasi calon, konversi ke siswa',
                        'Keuangan — jenis biaya, SPP, tagihan, pembayaran, tunggakan',
                        'BK & UKS — pelanggaran/prestasi, konseling, kunjungan UKS',
                        'Inventaris, Lab, Perpustakaan, Persuratan, Kalender, Buku Tamu',
                        'SMK: Mitra DU/DI, PKL/Prakerin, dan BKK',
                    ],
                    'tip' => 'Aktifkan dulu di Akses Modul, lalu ikuti menu sidebar yang muncul.',
                ],
                [
                    'id' => 'rutin',
                    'short_title' => 'Rutin',
                    'title' => 'Rutinitas & tips',
                    'summary' => 'Setelah sistem jalan, jaga kelancaran dengan kebiasaan sederhana setiap periode.',
                    'actions' => [
                        'Ganti semester/tahun ajaran tepat waktu',
                        'Pakai filter & pencarian di halaman data besar',
                        'Ekspor Excel/PDF bila butuh arsip atau laporan dinas',
                        'Pantau Audit Log untuk jejak perubahan penting',
                        'Kirim masukan bug/fitur lewat menu Feedback jika perlu',
                    ],
                    'faqs' => [
                        [
                            'q' => 'Lupa password admin?',
                            'a' => 'Gunakan Lupa Password di halaman login, atau hubungi Super Admin platform.',
                        ],
                        [
                            'q' => 'Menu tidak muncul di sidebar?',
                            'a' => 'Cek Akses Modul: modul mungkin dimatikan untuk sekolah, atau akun Anda belum diberi akses.',
                        ],
                        [
                            'q' => 'Di mana notifikasi mutasi?',
                            'a' => 'Ikon lonceng di header. Badge angka menandakan yang belum dibaca.',
                        ],
                    ],
                ],
            ],
        ];
    }
}
