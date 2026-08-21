# Catatan Rilis

## [0.2.60821] — 2026-08-21

- Bank soal ujian online kini gudang jangka panjang: tingkat hanya label rak, ujian kelas 9 boleh memakai soal dari rak kelas 7
- Soal yang dipasang ke ujian disalin ke paket ujian, jadi edit di bank tidak mengubah ujian yang sudah dipasang
- Detail ujian punya pemilih soal (cari di semua rak mapel itu, filter tingkat opsional)

- Alumni (status Lulus) kini bisa didaftarkan sebagai siswa baru di sekolah jenjang berikutnya dengan NIK/NISN yang sama
- Pendaftaran itu otomatis mengajukan destinasi alumni "lanjut sekolah" di sekolah asal, dan admin sekolah asal yang menyetujui atau menolaknya
- Siswa yang masih aktif di sekolah lain tetap tidak bisa didaftarkan ulang
- Tambah siswa kini bisa tarik alumni dari jenjang sebelumnya (isi NPSN sekolah asal, pilih nama + NIK), tanpa menghapus arsip di sekolah asal
- Mutasi siswa (ajukan, tarik, riwayat) kini memakai NIK sebagai kunci pencarian, sama seperti mutasi guru; NISN tetap disimpan dan ditampilkan
- Kolom kelas di data siswa memakai nama kelas yang terhubung, bukan teks lama yang kosong
- Filter tanpa kelas hanya menampilkan siswa yang benar-benar belum terhubung ke kelas (atau kelasnya sudah dihapus)

## [0.2.60820] — 2026-08-20

- Alamat sekolah, siswa, guru, calon PPDB, onboarding institusi, dan mitra PKL kini dipilih berantai (provinsi → kabupaten/kota → kecamatan → desa/kelurahan/pekon)
- Impor/ekspor Excel siswa dan guru kini punya kolom Desa, Kecamatan, Kabupaten/Kota, Provinsi, dan Kode Pos (file lama yang hanya kolom Alamat tetap bisa diimpor)

- Laporan BK ditata ke tiga tab (ringkasan, skor siswa, catatan), dan cetak PDF kini TTD guru BK di kanan serta kepala di kiri (mengetahui)
- Guru kini bisa mencetak PDF lembar jurnal mengajar (absensi, materi, dan nilai harian) dari jam mengajar hari ini
- Laporan & statistik sekolah kini hanya bisa dibuka admin dan kepala sekolah
- NIS lokal kini bisa diatur formatnya per sekolah, dengan pratinjau sebelum diterapkan ke siswa yang belum punya nomor
- Dashboard sekolah kini menampilkan grafik komposisi siswa dan tren pelanggaran
- Grafik ringkasan kini juga tersedia di absensi, keuangan, PPDB, portal orang tua, dashboard siswa, dan wali kelas
- Absensi QR kini bisa digenerate massal per kelas atau pegawai, lalu dicetak sebagai kartu (termasuk PDF)
- Aplikasi kini bisa dipasang ke layar utama HP untuk akses lebih cepat (PWA)
- Jabatan struktural di kepegawaian otomatis mengisi tugas tambahan dan akses modul
- Akun login siswa yang baru dibuat kini menampilkan kredensial yang bisa disalin, termasuk pembuatan massal
- Sekolah yang lupa sandi kini mengajukan reset ke admin sistem, bukan lewat email sendiri
- Cek hasil, unggah berkas, dan daftar ulang PPDB publik kini wajib tanggal lahir di samping nomor pendaftaran/NISN
- Calon PPDB yang NISN/NIK-nya sudah jadi siswa di sekolah lain otomatis ditandai "sudah diterima di sekolah lain", dan formulir publik menolak daftar ulang identitas yang sama
- Data siswa kini punya filter NIS kosong dan kotak sampah
- Kotak sampah guru dan siswa kini bisa menghapus data secara permanen
- Sekolah tujuan kini bisa menarik siswa/guru yang masih di kotak sampah sekolah asal, lalu data dipulihkan di sekolah tujuan setelah disetujui
- Wali kelas kini bisa mencetak daftar siswa, kontak orang tua, rekap absen, dan jadwal kelas
- Detail kegiatan ekskul dan sesi ujian online kini lebih lengkap untuk pemantauan peserta dan penilaian
- Sekolah kini bisa mengatur berkas wajib per jalur PPDB, dan calon bisa unggah sesuai daftar itu
- Bukti pendaftaran PPDB kini bisa diunduh sebagai PDF (oleh calon dan admin)
- Template import pegawai kini menandai kolom wajib dengan *, plus lembar petunjuk, sama seperti import siswa

## [0.2.60723] — 2026-07-23

- Jadwal pelajaran kini mendukung banyak template per sekolah, dan tiap kelas bisa memakai template berbeda
- Penjadwalan guru tetap bisa dilanjutkan meski bentrok dengan jadwal di sekolah/kelas lain (opsional)
- Bobot nilai (penilaian, UTS, UAS) kini diatur per mapel/kelas, lengkap dengan tenggat
- KKM mata pelajaran kini bisa diatur, dan nilai di bawah KKM bisa ditindaklanjuti lewat remidi/pengayaan
- Buku nilai kini bisa dicetak per kelas/mapel, termasuk rekap rapor kelas
- Mutasi guru antar institusi kini tersedia (ajukan/tarik), lengkap dengan alur pembatalan
- Mutasi siswa kini punya alur pengajuan pembatalan yang perlu disetujui
- Wali kelas kini punya catatan per siswa dan dashboard kelas yang lebih lengkap
- Absensi siswa bisa disiapkan langsung dari jadwal pelajaran hari itu
- Ekskul kini punya KKM dan penilaian per sesi kegiatan
- Perpustakaan kini mendukung e-book (termasuk publik & hitungan baca), impor buku, dan laporan cetak tambahan
- Prestasi siswa bisa berstatus menunggu verifikasi sebelum disetujui
- Ranking apresiasi guru kini bisa dikonfigurasi mode leaderboard-nya per institusi

## [0.2.60718] — 2026-07-18

- Tabel data siswa kini mendukung pagination dan filter tingkat
- Tahun ajaran siswa otomatis ikut kelasnya
- Laporan cetak kini memakai kop resmi & tanda tangan yang seragam
- Laporan BK kini bisa difilter per periode/kelas dan diekspor CSV
- Guru piket kini punya jadwal, log harian, catat insiden, dan laporan mingguan PDF
- Apresiasi guru kini mengelola prestasi, pelanggaran, poin, dan ranking di satu tempat
- Lab kini bisa dibooking dan mencatat jurnal pemakaian
- Surat resmi kini bisa dibuat dari template, lengkap dengan kop & TTD
- Ekskul kini punya detail kegiatan, jadwal, dan lokasi
- Super Admin kini bisa onboard institusi, kirim broadcast, dan pantau adopsi
- Halaman catatan rilis kini tersedia untuk pengguna
- Institusi kini bisa mencantumkan nama yayasan
- Mode maintenance kini bisa diaktifkan dari pengaturan sistem
