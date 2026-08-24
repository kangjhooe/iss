<template>
  <div
    v-if="isOpen && isMobileOverlay"
    class="help-overlay"
    @click="toggleSidebar"
    aria-hidden="true"
  />

  <aside
    class="help-sidebar"
    :class="{
      'help-sidebar--open': isOpen,
      'help-sidebar--collapsed': !isOpen,
      'help-sidebar--overlay': isMobileOverlay && isOpen
    }"
    role="complementary"
    aria-label="Petunjuk penggunaan"
  >
    <button
      type="button"
      class="help-toggle"
      :aria-label="isOpen ? 'Sembunyikan petunjuk' : 'Tampilkan petunjuk'"
      :title="isOpen ? 'Sembunyikan petunjuk' : 'Tampilkan petunjuk'"
      @click="toggleSidebar"
    >
      <svg class="help-toggle-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path v-if="isOpen" d="M15 19l-7-7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <path v-else d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <span v-if="!isOpen" class="help-toggle-label">Bantuan</span>
    </button>

    <div v-show="isOpen" class="help-panel">
      <header class="help-header">
        <div class="help-header-glow" aria-hidden="true" />
        <div class="help-header-inner">
          <div class="help-header-badge">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M12 16v-4M12 8h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="help-header-text">
            <h2 class="help-header-title">Petunjuk Penggunaan</h2>
            <p class="help-header-subtitle">Navigasi sesuai menu sidebar kiri</p>
          </div>
        </div>
      </header>

      <div class="help-body">
        <!-- Memulai -->
        <section class="help-section help-section--start">
          <h3 class="help-section-head">
            <span class="help-section-icon help-section-icon--amber">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            Memulai
          </h3>
          <div class="help-steps">
            <div class="help-step">
              <span class="help-step-num">1</span>
              <span>Lengkapi <strong>Profil Instansi</strong> (nama, alamat, jenjang).</span>
            </div>
            <div class="help-step">
              <span class="help-step-num">2</span>
              <span>Atur <strong>Semester</strong> & tahun ajaran aktif (menu Semester / Tahun Ajaran).</span>
            </div>
            <div class="help-step">
              <span class="help-step-num">3</span>
              <span>Untuk admin: sembunyikan modul yang tidak dipakai di <strong>Akses Modul</strong>.</span>
            </div>
          </div>
        </section>

        <!-- Master Data -->
        <section class="help-section help-section--master">
          <h3 class="help-section-head">
            <span class="help-section-icon help-section-icon--blue">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v14a2 2 0 01-2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 7h6M9 12h6M9 17h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            Master Data
          </h3>
          <div class="help-cards">
            <div class="help-card">
              <span class="help-card-icon help-card-icon--blue">🏫</span>
              <div class="help-card-body">
                <div class="help-card-title">Profil Instansi</div>
                <p class="help-card-desc">Nama, alamat, jenjang. Dasar untuk modul lain. Super Admin mengelola di Kelola Institusi & Tahun Ajaran.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--teal">🏢</span>
              <div class="help-card-body">
                <div class="help-card-title">Sarana Prasarana & Lab</div>
                <p class="help-card-desc">Ruang, gedung, fasilitas, lab, dan booking lab oleh guru (approve oleh pengelola).</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--violet">📦</span>
              <div class="help-card-body">
                <div class="help-card-title">Inventaris</div>
                <p class="help-card-desc">Operasional (barang, transaksi, pemeliharaan, pinjam), laporan, dan pengaturan kategori.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--indigo">📚</span>
              <div class="help-card-body">
                <div class="help-card-title">Kelas</div>
                <p class="help-card-desc">Daftar kelas per tingkat/jurusan. Dipakai Data Siswa, Jadwal, dan Buku Nilai.</p>
              </div>
            </div>
          </div>
        </section>

        <!-- Keguruan -->
        <section class="help-section help-section--academic">
          <h3 class="help-section-head">
            <span class="help-section-icon help-section-icon--emerald">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 19.5A2.5 2.5 0 016.5 17H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M8 7h8M8 11h8M8 15h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            Keguruan
          </h3>
          <div class="help-cards">
            <div class="help-card">
              <span class="help-card-icon help-card-icon--lime">📅</span>
              <div class="help-card-body">
                <div class="help-card-title">Jadwal Pelajaran</div>
                <p class="help-card-desc">Hari, jam, mapel, guru per kelas. Tab Template: buat beberapa pola jam (mis. 8 JP / 9 JP), set default, lalu assign ke kelas.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--teal">⏰</span>
              <div class="help-card-body">
                <div class="help-card-title">Jam Mengajar Hari Ini</div>
                <p class="help-card-desc">Alur harian guru mapel: isi absensi siswa, jurnal, lalu nilai harian. Menu muncul jika ada jadwal mengajar.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--emerald">📖</span>
              <div class="help-card-body">
                <div class="help-card-title">Jurnal Mengajar</div>
                <p class="help-card-desc">Catatan mengajar per pertemuan, materi, kehadiran siswa.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--green">📋</span>
              <div class="help-card-body">
                <div class="help-card-title">Buku Nilai & Raport</div>
                <p class="help-card-desc">Input nilai per mapel, atur KKM & bobot (penilaian/UTS/UAS), remidi/pengayaan untuk di bawah KKM, cetak raport.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--teal">🏠</span>
              <div class="help-card-body">
                <div class="help-card-title">Wali Kelas (Hub)</div>
                <p class="help-card-desc">Dashboard terpadu untuk wali: roster siswa, rekap absensi 7/30 hari, monitoring nilai/KKM, skor BK, usulan pelanggaran/prestasi/mutasi, jadwal, dan profil siswa 360°. Menu muncul otomatis setelah ditunjuk sebagai wali kelas.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--sky">🛡️</span>
              <div class="help-card-body">
                <div class="help-card-title">Guru Piket</div>
                <p class="help-card-desc">Jadwal piket, log harian, catat insiden (bisa diusulkan jadi pelanggaran siswa/guru), scan kelas kosong/keterlambatan, laporan mingguan.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--violet">⭐</span>
              <div class="help-card-body">
                <div class="help-card-title">Apresiasi Guru</div>
                <p class="help-card-desc">Catatan prestasi/pelanggaran, usulan, poin, laporan, dan pengaturan jenis/reward.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--indigo">💻</span>
              <div class="help-card-body">
                <div class="help-card-title">Ujian Online</div>
                <p class="help-card-desc">4 menu: Daftar Ujian (pilih soal lintas rak/tingkat, paket disalin saat dipasang), Bank Soal (gudang jangka panjang; tingkat hanya label rak), Peserta Ujian, Kontrol Ujian (monitoring live, koreksi, rilis nilai, export). Status Beta.</p>
              </div>
            </div>
          </div>
        </section>

        <!-- Absensi -->
        <section class="help-section help-section--attendance">
          <h3 class="help-section-head">
            <span class="help-section-icon help-section-icon--sky">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 5C9 3.89543 9.89543 3 11 3H13C14.1046 3 15 3.89543 15 5C15 6.10457 14.1046 7 13 7H11C9.89543 7 9 6.10457 9 5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 12L11 14L15 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            Absensi
          </h3>
          <div class="help-cards">
            <div class="help-card">
              <span class="help-card-icon help-card-icon--sky">📋</span>
              <div class="help-card-body">
                <div class="help-card-title">Absensi Siswa</div>
                <p class="help-card-desc">Guru mapel mengisi absen lewat Jam Mengajar Hari Ini (atau Hub Mapel / Wali untuk rekap &amp; cetak). Menu sidebar Absensi Siswa hanya tampil jika modul Absensi pegawai juga aktif.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--cyan">👥</span>
              <div class="help-card-body">
                <div class="help-card-title">Absensi Guru & Staff</div>
                <p class="help-card-desc">Kehadiran pegawai: check-in/out, rekap per periode. Menu sidebar Absensi hanya untuk akun dengan modul Absensi (biasanya TU/admin).</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--indigo">📱</span>
              <div class="help-card-body">
                <div class="help-card-title">Absensi QR</div>
                <p class="help-card-desc">Generate kartu QR tetap per kelas (massal/cetak), lalu scan untuk mencatat kehadiran. Kartu tidak kadaluarsa; siswa tidak aktif tidak bisa di-scan.</p>
              </div>
            </div>
          </div>
        </section>

        <!-- Kesiswaan -->
        <section class="help-section help-section--student">
          <h3 class="help-section-head">
            <span class="help-section-icon help-section-icon--amber">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17 8l4 4-4 4M3 12h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            Kesiswaan
          </h3>
          <div class="help-cards">
            <div class="help-card">
              <span class="help-card-icon help-card-icon--emerald">👤</span>
              <div class="help-card-body">
                <div class="help-card-title">Data Siswa</div>
                <p class="help-card-desc">NIS, nama, kelas, filter, impor/ekspor. Tombol Tambah Siswa membuka pilihan: isi manual atau tarik alumni jenjang sebelumnya (NPSN sekolah asal, centang nama + NIK). Itu bukan mutasi: arsip di sekolah asal tetap ada. Generate NIS lokal lewat pratinjau. Buku Induk per siswa dari detail siswa.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--amber">↔️</span>
              <div class="help-card-body">
                <div class="help-card-title">Mutasi</div>
                <p class="help-card-desc">Pindah masuk/keluar dengan NIK siswa (16 digit). NISN tetap tercatat di data siswa. Notifikasi mutasi bisa dilihat di ikon lonceng (header).</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--lime">⬆️</span>
              <div class="help-card-body">
                <div class="help-card-title">Naik Kelas</div>
                <p class="help-card-desc">Proses kenaikan kelas siswa per tahun ajaran.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--blue">🎓</span>
              <div class="help-card-body">
                <div class="help-card-title">Luluskan</div>
                <p class="help-card-desc">Tetapkan siswa aktif sebagai lulusan. Pilih kelas, centang siswa, set tahun lulus.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--orange">🎓</span>
              <div class="help-card-body">
                <div class="help-card-title">Alumni</div>
                <p class="help-card-desc">Data lulusan per tahun. Filter, destinasi lanjut (termasuk usulan otomatis jika alumni terdaftar di jenjang berikutnya), & arsip.</p>
              </div>
            </div>
          </div>
        </section>

        <!-- Bimbingan Konseling -->
        <section class="help-section help-section--student">
          <h3 class="help-section-head">
            <span class="help-section-icon help-section-icon--cyan">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            Bimbingan Konseling
          </h3>
          <div class="help-cards">
            <div class="help-card">
              <span class="help-card-icon help-card-icon--red">⚠️</span>
              <div class="help-card-body">
                <div class="help-card-title">Pelanggaran</div>
                <p class="help-card-desc">Catatan (pelanggaran & prestasi), usulan, tindakan BK, dan pengaturan jenis/aturan skor.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--cyan">💬</span>
              <div class="help-card-body">
                <div class="help-card-title">Konseling</div>
                <p class="help-card-desc">Catat sesi konseling: cari siswa lewat kelas atau nama/NIS, pilih konselor dari daftar guru. Jenis konseling ada di Pengaturan.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--blue">📊</span>
              <div class="help-card-body">
                <div class="help-card-title">Laporan BK</div>
                <p class="help-card-desc">Tiga tampilan: ringkasan per kelas, skor siswa, dan catatan (pelanggaran/prestasi/konseling). Cetak PDF dengan TTD guru BK (kanan) dan kepala sekolah (mengetahui, kiri).</p>
              </div>
            </div>
          </div>
        </section>

        <!-- UKS -->
        <section class="help-section help-section--student">
          <h3 class="help-section-head">
            <span class="help-section-icon help-section-icon--cyan">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2v20M2 12h20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><rect x="4" y="4" width="16" height="16" rx="3" stroke="currentColor" stroke-width="2"/></svg>
            </span>
            UKS
          </h3>
          <div class="help-cards">
            <div class="help-card">
              <span class="help-card-icon help-card-icon--green">➕</span>
              <div class="help-card-body">
                <div class="help-card-title">Kunjungan UKS</div>
                <p class="help-card-desc">Pilih kelas atau cari nama/NIS siswa, lalu isi keluhan, tindakan, dan tanda vital. Kunjungan masuk ke tahun ajaran aktif.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--blue">📊</span>
              <div class="help-card-body">
                <div class="help-card-title">Laporan UKS</div>
                <p class="help-card-desc">Rekap per kelas/bulan/jenis/status + detail kunjungan. Cetak PDF resmi (kop sekolah + TTD Kepala Sekolah dan Koordinator UKS), atau export CSV.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--emerald">💊</span>
              <div class="help-card-body">
                <div class="help-card-title">Stok Obat</div>
                <p class="help-card-desc">Inventaris obat UKS: master obat, stok, kedaluwarsa, transaksi masuk/keluar/penyesuaian. Terpisah dari modul inventaris umum agar petugas UKS bisa kelola sendiri.</p>
              </div>
            </div>
          </div>
        </section>

        <!-- PKL / Prakerin -->
        <section v-if="isVocational" class="help-section help-section--admin">
          <h3 class="help-section-head">
            <span class="help-section-icon help-section-icon--sky">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 21h18M5 21V8l6-3v16M11 21V11h4l4 3v7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            PKL / Prakerin
          </h3>
          <div class="help-cards">
            <div class="help-card">
              <span class="help-card-icon help-card-icon--sky">🏭</span>
              <div class="help-card-body">
                <div class="help-card-title">Mitra DU/DI</div>
                <p class="help-card-desc">Master perusahaan mitra (shared dengan BKK). Isi nama, kota, PIC, dan status aktif.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--sky">📅</span>
              <div class="help-card-body">
                <div class="help-card-title">Periode &amp; Penempatan</div>
                <p class="help-card-desc">Buat periode PKL, tempatkan siswa ke mitra (satu per periode), bulk assign, nilai akhir, dan export CSV.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--sky">📍</span>
              <div class="help-card-body">
                <div class="help-card-title">Monitoring pembimbing</div>
                <p class="help-card-desc">Catat kunjungan/telepon/online dari pembimbing sekolah ke siswa di industri.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--sky">📝</span>
              <div class="help-card-body">
                <div class="help-card-title">Jurnal siswa</div>
                <p class="help-card-desc">Siswa mengisi jurnal harian di portal (/student/pkl). Staff bisa baca jurnal dan menulis catatan pembimbing dari tombol Jurnal di penempatan.</p>
              </div>
            </div>
          </div>
        </section>

        <!-- BKK / Bursa Kerja -->
        <section v-if="isVocational" class="help-section help-section--admin">
          <h3 class="help-section-head">
            <span class="help-section-icon help-section-icon--sky">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z" stroke="currentColor" stroke-width="2"/><path d="M12 12v5M9.5 14.5h5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </span>
            BKK / Bursa Kerja
          </h3>
          <div class="help-cards">
            <div class="help-card">
              <span class="help-card-icon help-card-icon--sky">💼</span>
              <div class="help-card-body">
                <div class="help-card-title">Lowongan</div>
                <p class="help-card-desc">Kelola lowongan kerja dari mitra DU/DI: posisi, kuota, deadline, status buka/tutup.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--sky">📝</span>
              <div class="help-card-body">
                <div class="help-card-title">Lamaran</div>
                <p class="help-card-desc">Staff dapat mendaftarkan alumni/siswa ke lowongan, ubah status lamaran, dan export CSV. Siswa/alumni dapat melamar mandiri dari portal.</p>
              </div>
            </div>
          </div>
        </section>

        <!-- Kepegawaian -->
        <section class="help-section help-section--admin">
          <h3 class="help-section-head">
            <span class="help-section-icon help-section-icon--indigo">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2" stroke="currentColor" stroke-width="2"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/><path d="M19 8v6M22 11h-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </span>
            Kepegawaian
          </h3>
          <div class="help-cards">
            <div class="help-card">
              <span class="help-card-icon help-card-icon--green">👨‍🏫</span>
              <div class="help-card-body">
                <div class="help-card-title">Data Pegawai</div>
                <p class="help-card-desc">NIP, nama, mapel, status, tugas tambahan operasional (operator, koordinator, pembina). Jabatan struktural diatur di Cuti, SK &amp; Jabatan.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--amber">↔️</span>
              <div class="help-card-body">
                <div class="help-card-title">Mutasi</div>
                <p class="help-card-desc">Push (sekolah asal) atau pull (sekolah tujuan) berdasarkan NIK. Setujui/tolak, batalkan, lihat riwayat &amp; laporan.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--indigo">🏖️</span>
              <div class="help-card-body">
                <div class="help-card-title">Cuti</div>
                <p class="help-card-desc">Admin kelola pengajuan cuti (setujui/tolak). Cuti yang disetujui otomatis mengisi absensi pegawai (hari kerja). Guru/staff ajukan sendiri lewat Cuti Saya.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--indigo">📄</span>
              <div class="help-card-body">
                <div class="help-card-title">SK &amp; Jabatan</div>
                <p class="help-card-desc">Register SK, tetapkan jabatan struktural (akses modul ikut), dan lihat riwayat karier pegawai.</p>
              </div>
            </div>
          </div>
        </section>

        <!-- Layanan -->
        <section class="help-section help-section--student">
          <h3 class="help-section-head">
            <span class="help-section-icon help-section-icon--cyan">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            Layanan
          </h3>
          <div class="help-cards">
            <div class="help-card">
              <span class="help-card-icon help-card-icon--orange">📄</span>
              <div class="help-card-body">
                <div class="help-card-title">Pengambilan Ijazah</div>
                <p class="help-card-desc">Catatan pengambilan dokumen (ijazah, raport, SKHUN) oleh alumni, tanggal, nomor ijazah/kode blangko.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--teal">⚽</span>
              <div class="help-card-body">
                <div class="help-card-title">Ekstrakurikuler</div>
                <p class="help-card-desc">Daftar ekskul, pembina, dan pendaftaran siswa.</p>
              </div>
            </div>
          </div>
        </section>

        <!-- Keuangan -->
        <section class="help-section help-section--admin">
          <h3 class="help-section-head">
            <span class="help-section-icon help-section-icon--sky">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="2" y="6" width="20" height="12" rx="2" stroke="currentColor" stroke-width="2"/><path d="M2 10h20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="14" r="1.5" fill="currentColor"/></svg>
            </span>
            Keuangan
          </h3>
          <div class="help-cards">
            <div class="help-card">
              <span class="help-card-icon help-card-icon--sky">📋</span>
              <div class="help-card-body">
                <div class="help-card-title">Jenis Biaya</div>
                <p class="help-card-desc">Katalog biaya: SPP bulanan, iuran sekali, kas kelas sesekali, dll.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--sky">💳</span>
              <div class="help-card-body">
                <div class="help-card-title">SPP</div>
                <p class="help-card-desc">Generate tagihan SPP bulanan untuk siswa aktif / per kelas.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--sky">🧾</span>
              <div class="help-card-body">
                <div class="help-card-title">Tagihan</div>
                <p class="help-card-desc">Buat tagihan non-rutin (iuran, kas kelas, seragam, kegiatan) ke kelas atau semua siswa.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--sky">💰</span>
              <div class="help-card-body">
                <div class="help-card-title">Pembayaran</div>
                <p class="help-card-desc">Catat pembayaran tunai/transfer atas tagihan.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--sky">⚠️</span>
              <div class="help-card-body">
                <div class="help-card-title">Tunggakan</div>
                <p class="help-card-desc">Pantau tagihan belum lunas dan bayar cepat.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--sky">📊</span>
              <div class="help-card-body">
                <div class="help-card-title">Laporan</div>
                <p class="help-card-desc">Rekap ditagih, terkumpul, tunggakan, per jenis biaya.</p>
              </div>
            </div>
          </div>
        </section>

        <!-- PPDB -->
        <section class="help-section help-section--student">
          <h3 class="help-section-head">
            <span class="help-section-icon help-section-icon--emerald">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/><path d="M19 8v6M22 11h-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            PPDB
          </h3>
          <div class="help-cards">
            <div class="help-card">
              <span class="help-card-icon help-card-icon--emerald">📊</span>
              <div class="help-card-body">
                <div class="help-card-title">Ringkasan</div>
                <p class="help-card-desc">Dashboard singkat: periode aktif, antrean verifikasi, salin link daftar publik.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--emerald">⚙️</span>
              <div class="help-card-body">
                <div class="help-card-title">Konfigurasi</div>
                <p class="help-card-desc">Atur label publik (PPDB/SPMB), periode (gelombang), dan jalur pendaftaran.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--emerald">👤</span>
              <div class="help-card-body">
                <div class="help-card-title">Data Pendaftar</div>
                <p class="help-card-desc">Daftar calon, verifikasi berkas, set hasil seleksi, export CSV/XLSX (termasuk status bayar), konversi ke siswa. Calon menerima email saat daftar sukses & hasil diumumkan (jika email diisi).</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--emerald">📈</span>
              <div class="help-card-body">
                <div class="help-card-title">Statistik</div>
                <p class="help-card-desc">Rekap per status dan jalur, termasuk isi kuota.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--emerald">💳</span>
              <div class="help-card-body">
                <div class="help-card-title">Pembayaran</div>
                <p class="help-card-desc">Atur biaya periode, tandai lunas/belum bayar per calon.</p>
              </div>
            </div>
          </div>
        </section>

        <!-- Administrasi -->
        <section class="help-section help-section--admin">
          <h3 class="help-section-head">
            <span class="help-section-icon help-section-icon--violet">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M22 6l-10 7L2 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            Administrasi
          </h3>
          <div class="help-cards">
            <div class="help-card">
              <span class="help-card-icon help-card-icon--violet">📝</span>
              <div class="help-card-body">
                <div class="help-card-title">Persuratan</div>
                <p class="help-card-desc">Editor surat dari template platform/sekolah, kop, TTD, terbitkan ke arsip, disposisi.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--amber">📅</span>
              <div class="help-card-body">
                <div class="help-card-title">Kalender Akademik</div>
                <p class="help-card-desc">Event, libur, ujian, dan kegiatan sekolah. Tampilan daftar atau bulan; siswa melihat event mendatang di dashboard.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--indigo">📁</span>
              <div class="help-card-body">
                <div class="help-card-title">Arsip Digital</div>
                <p class="help-card-desc">Dokumen & arsip digital per kategori, pencarian, unduh.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--blue">📚</span>
              <div class="help-card-body">
                <div class="help-card-title">Perpustakaan</div>
                <p class="help-card-desc">Katalog (buku & eksemplar), sirkulasi (pinjam & denda), laporan, pengaturan kategori. Ebook publik di /:npsn/ebooks.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--cyan">📖</span>
              <div class="help-card-body">
                <div class="help-card-title">Buku Tamu</div>
                <p class="help-card-desc">Daftar tamu yang berkunjung ke instansi.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--fuchsia">📊</span>
              <div class="help-card-body">
                <div class="help-card-title">Laporan</div>
                <p class="help-card-desc">Statistik siswa, guru, pelanggaran, dan lainnya. Hanya admin dan kepala sekolah. Ekspor untuk dinas.</p>
              </div>
            </div>
          </div>
        </section>

        <!-- Pengaturan -->
        <section class="help-section help-section--settings">
          <h3 class="help-section-head">
            <span class="help-section-icon help-section-icon--slate">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 15a3 3 0 100-6 3 3 0 000 6z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009.19 18a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            Pengaturan & Lainnya
          </h3>
          <div class="help-cards">
            <div class="help-card">
              <span class="help-card-icon help-card-icon--slate">🔐</span>
              <div class="help-card-body">
                <div class="help-card-title">Akses Modul</div>
                <p class="help-card-desc">Sembunyikan modul yang tidak dipakai sekolah, lalu atur akses per guru. Hanya admin instansi.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--slate">🔔</span>
              <div class="help-card-body">
                <div class="help-card-title">Notifikasi</div>
                <p class="help-card-desc">Notifikasi mutasi & info lain. Akses dari ikon lonceng di header.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--slate">📅</span>
              <div class="help-card-body">
                <div class="help-card-title">Semester</div>
                <p class="help-card-desc">Atur semester aktif. Super Admin: Tahun Ajaran & Request Perubahan Instansi di menu Sistem.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--slate">📜</span>
              <div class="help-card-body">
                <div class="help-card-title">Audit Log</div>
                <p class="help-card-desc">Riwayat aktivitas sistem. Untuk admin & super admin.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--slate">✏️</span>
              <div class="help-card-body">
                <div class="help-card-title">Permintaan Ubah Data</div>
                <p class="help-card-desc">Siswa/guru ajukan perubahan profil; admin setujui di menu permintaan terkait.</p>
              </div>
            </div>
            <div class="help-card">
              <span class="help-card-icon help-card-icon--slate">💬</span>
              <div class="help-card-body">
                <div class="help-card-title">Feedback (API)</div>
                <p class="help-card-desc">Admin, guru, dan staf sekolah bisa kirim tiket bug/request fitur lewat menu Lapor Bug / Fitur; Super Admin meninjau dan memperbarui status di Inbox Feedback (lonceng notifikasi).</p>
              </div>
            </div>
          </div>
        </section>

        <!-- Tips -->
        <section class="help-section help-section--tips">
          <h3 class="help-section-head">
            <span class="help-section-icon help-section-icon--sky">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 15a3 3 0 100-6 3 3 0 000 6z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            Tips
          </h3>
          <div class="help-tips">
            <div class="help-tip">
              <span class="help-tip-bulb">💡</span>
              <span>Gunakan <strong>filter</strong> dan <strong>pencarian</strong> di tiap halaman untuk data banyak.</span>
            </div>
            <div class="help-tip">
              <span class="help-tip-bulb">💾</span>
              <span>Backup rutin & perbarui <strong>Semester</strong> / <strong>Tahun Ajaran</strong> saat ganti periode.</span>
            </div>
            <div class="help-tip">
              <span class="help-tip-bulb">📤</span>
              <span>Banyak modul punya <strong>ekspor</strong> (Excel/PDF)—cek atas tabel atau menu aksi.</span>
            </div>
          </div>
        </section>

        <!-- FAQ & Kontak -->
        <section class="help-section help-section--faq">
          <h3 class="help-section-head">
            <span class="help-section-icon help-section-icon--rose">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            Pertanyaan Umum
          </h3>
          <div class="help-faq-list">
            <div class="help-faq-item">
              <p class="help-faq-q">Lupa password?</p>
              <p class="help-faq-a">Gunakan "Lupa Password" di halaman login atau minta reset ke administrator instansi.</p>
            </div>
            <div class="help-faq-item">
              <p class="help-faq-q">Di mana melihat notifikasi mutasi?</p>
              <p class="help-faq-a">Klik ikon lonceng di header, atau buka menu Notifikasi. Badge angka menandakan notifikasi belum dibaca.</p>
            </div>
            <div class="help-faq-item">
              <p class="help-faq-q">Ekspor data?</p>
              <p class="help-faq-a">Banyak modul punya tombol ekspor (Excel/PDF). Cek di atas tabel atau di menu aksi per baris.</p>
            </div>
          </div>
          <div class="help-cta">
            <span class="help-cta-icon">📞</span>
            <div class="help-cta-body">
              <p class="help-cta-text">Butuh bantuan lebih? Hubungi administrator atau tim support instansi Anda.</p>
              <router-link v-if="canSubmitFeedback" to="/feedback" class="help-cta-link">
                Lapor bug / request fitur ke platform →
              </router-link>
            </div>
          </div>
        </section>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { getActiveInstitutionLevel, isVocationalLevel } from '@/utils/institution'

const authStore = useAuthStore()
const isVocational = computed(() => isVocationalLevel(getActiveInstitutionLevel(authStore)))
const canSubmitFeedback = computed(() => {
  const role = authStore.user?.role
  return ['institution_admin', 'admin', 'teacher', 'staff'].includes(role)
})

const STORAGE_KEY = 'helpSidebarOpen'
const isOpen = ref(false)
const isMobileOverlay = ref(false)

function checkMobile() {
  isMobileOverlay.value = window.innerWidth < 1024
}

const toggleSidebar = () => {
  isOpen.value = !isOpen.value
  try {
    localStorage.setItem(STORAGE_KEY, isOpen.value ? 'true' : 'false')
  } catch (_) {}
}

onMounted(() => {
  try {
    const saved = localStorage.getItem(STORAGE_KEY)
    if (saved !== null) {
      isOpen.value = saved === 'true'
    }
  } catch (_) {}
  checkMobile()
  window.addEventListener('resize', checkMobile)
})

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile)
})
</script>

<style scoped>
.help-overlay {
  display: none;
}

.help-sidebar {
  --help-width: 360px;
  --help-tab-width: 48px;
  flex-shrink: 0;
  width: var(--help-tab-width);
  height: 100vh;
  display: flex;
  align-items: stretch;
  background: #fff;
  border-left: 1px solid #e5e7eb;
  box-shadow: -2px 0 12px rgba(0, 0, 0, 0.04);
  transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  z-index: 100;
  overflow: hidden;
}

.help-sidebar--open {
  width: calc(var(--help-tab-width) + var(--help-width));
  pointer-events: auto;
}

.help-sidebar--collapsed {
  width: var(--help-tab-width);
  pointer-events: none;
}

.help-sidebar--collapsed .help-toggle {
  pointer-events: auto;
}

/* Toggle tab */
.help-toggle {
  width: var(--help-tab-width);
  min-width: var(--help-tab-width);
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
  padding-top: 88px;
  gap: 8px;
  background: #f8fafc;
  border: none;
  border-right: 1px solid #e5e7eb;
  color: #64748b;
  cursor: pointer;
  transition: color 0.2s, background 0.2s;
}

.help-toggle:hover {
  background: #f1f5f9;
  color: #475569;
}

.help-toggle:focus-visible {
  outline: 2px solid #059669;
  outline-offset: -2px;
}

.help-toggle-icon {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
}

.help-toggle-label {
  font-size: 10px;
  font-weight: 600;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  writing-mode: vertical-rl;
  text-orientation: mixed;
  transform: rotate(180deg);
  line-height: 1.3;
}

/* Panel */
.help-panel {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  background: #fff;
  overflow: hidden;
}

/* Header */
.help-header {
  flex-shrink: 0;
  position: relative;
  padding: 20px 20px 18px;
  border-bottom: 1px solid #e5e7eb;
  overflow: hidden;
}

.help-header-glow {
  position: absolute;
  top: -40%;
  right: -20%;
  width: 70%;
  height: 140%;
  background: radial-gradient(ellipse at center, rgba(59, 130, 246, 0.12) 0%, transparent 70%);
  pointer-events: none;
}

.help-header-inner {
  position: relative;
  display: flex;
  align-items: flex-start;
  gap: 14px;
}

.help-header-badge {
  width: 46px;
  height: 46px;
  border-radius: 14px;
  background: linear-gradient(145deg, #059669 0%, #047857 100%);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35);
}

.help-header-badge svg {
  width: 24px;
  height: 24px;
}

.help-header-text {
  flex: 1;
  min-width: 0;
}

.help-header-title {
  margin: 0 0 2px 0;
  font-size: 17px;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.03em;
  line-height: 1.25;
}

.help-header-subtitle {
  margin: 0;
  font-size: 12px;
  color: #64748b;
  line-height: 1.4;
}

/* Body */
.help-body {
  flex: 1;
  overflow-y: auto;
  padding: 18px 18px 24px;
  background: linear-gradient(180deg, #fafbfc 0%, #f8fafc 100%);
}

.help-body::-webkit-scrollbar {
  width: 5px;
}

.help-body::-webkit-scrollbar-track {
  background: transparent;
}

.help-body::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 10px;
}

.help-body::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Section */
.help-section {
  margin-bottom: 22px;
}

.help-section:last-child {
  margin-bottom: 0;
}

.help-section-head {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 0 0 12px 0;
  font-size: 12px;
  font-weight: 800;
  color: #334155;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  padding-left: 2px;
}

.help-beta-badge {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  padding: 2px 7px;
  border-radius: 999px;
  background: #ecfdf5;
  color: #047857;
}

.help-section-icon {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.help-section-icon svg {
  width: 16px;
  height: 16px;
  color: inherit;
}

.help-section-icon--amber { background: #fef3c7; color: #d97706; }
.help-section-icon--blue { background: #ecfdf5; color: #059669; }
.help-section-icon--emerald { background: #d1fae5; color: #059669; }
.help-section-icon--violet { background: #ecfdf5; color: #047857; }
.help-section-icon--slate { background: #f1f5f9; color: #475569; }
.help-section-icon--sky { background: #ecfdf5; color: #047857; }
.help-section-icon--rose { background: #ffe4e6; color: #e11d48; }

/* Steps (Memulai) */
.help-steps {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.help-step {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px 14px;
  background: #fff;
  border-radius: 10px;
  border: 1px solid #e5e7eb;
  font-size: 13px;
  color: #475569;
  line-height: 1.45;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.help-step:hover {
  border-color: #fcd34d;
  box-shadow: 0 2px 8px rgba(251, 191, 36, 0.15);
}

.help-step-num {
  flex-shrink: 0;
  width: 24px;
  height: 24px;
  border-radius: 8px;
  background: linear-gradient(145deg, #fbbf24 0%, #f59e0b 100%);
  color: #fff;
  font-size: 12px;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
}

.help-step strong {
  color: #1e293b;
  font-weight: 600;
}

/* Cards */
.help-cards {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.help-card {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px 14px;
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  transition: border-color 0.2s, box-shadow 0.2s, transform 0.15s ease;
}

.help-card:hover {
  border-color: #c7d2fe;
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.08);
  transform: translateX(2px);
}

.help-card--single {
  margin-bottom: 0;
}

.help-card-icon {
  flex-shrink: 0;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  line-height: 1;
}

.help-card-icon--blue { background: #dbeafe; }
.help-card-icon--teal { background: #ccfbf1; }
.help-card-icon--violet { background: #ecfdf5; }
.help-card-icon--indigo { background: #ecfdf5; }
.help-card-icon--emerald { background: #d1fae5; }
.help-card-icon--green { background: #dcfce7; }
.help-card-icon--lime { background: #ecfccb; }
.help-card-icon--amber { background: #fef3c7; }
.help-card-icon--orange { background: #ffedd5; }
.help-card-icon--red { background: #fee2e2; }
.help-card-icon--cyan { background: #cffafe; }
.help-card-icon--fuchsia { background: #fae8ff; }
.help-card-icon--slate { background: #f1f5f9; }

.help-card-body {
  flex: 1;
  min-width: 0;
}

.help-card-title {
  font-size: 13px;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 4px;
  line-height: 1.3;
}

.help-card-desc {
  margin: 0;
  font-size: 12px;
  color: #64748b;
  line-height: 1.48;
}

/* Tips */
.help-tips {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.help-tip {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 10px 12px;
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
  border-radius: 10px;
  border: 1px solid #bae6fd;
  font-size: 12px;
  color: #475569;
  line-height: 1.5;
}

.help-tip-bulb {
  flex-shrink: 0;
  font-size: 16px;
  line-height: 1;
}

.help-tip strong {
  color: #0c4a6e;
  font-weight: 600;
}

/* FAQ */
.help-faq-list {
  margin-bottom: 14px;
}

.help-faq-item {
  padding: 12px 14px;
  background: #fff;
  border-radius: 10px;
  border: 1px solid #e5e7eb;
  margin-bottom: 8px;
  transition: border-color 0.2s;
}

.help-faq-item:last-child {
  margin-bottom: 0;
}

.help-faq-item:hover {
  border-color: #fecdd3;
}

.help-faq-q {
  margin: 0 0 4px 0;
  font-size: 12px;
  font-weight: 700;
  color: #334155;
}

.help-faq-a {
  margin: 0;
  font-size: 12px;
  color: #64748b;
  line-height: 1.5;
}

/* CTA */
.help-cta {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px 16px;
  background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
  border-radius: 12px;
  border: 1px solid #fecaca;
}

.help-cta-icon {
  flex-shrink: 0;
  font-size: 22px;
  line-height: 1;
}

.help-cta-text {
  margin: 0;
  font-size: 12px;
  color: #991b1b;
  line-height: 1.5;
  font-weight: 500;
}

.help-cta-body {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 0;
}

.help-cta-link {
  font-size: 12px;
  font-weight: 600;
  color: #b91c1c;
  text-decoration: underline;
}

.help-cta-link:hover {
  color: #7f1d1d;
}

/* Mobile overlay */
@media (max-width: 1023px) {
  .help-overlay {
    display: block;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.35);
    z-index: 998;
    animation: helpOverlayIn 0.2s ease;
  }

  .help-sidebar {
    position: fixed;
    top: 0;
    right: 0;
    width: var(--help-tab-width);
    height: 100vh;
    z-index: 999;
  }

  .help-sidebar--open.help-sidebar--overlay {
    top: 0;
    height: 100vh;
    width: min(calc(var(--help-tab-width) + var(--help-width)), 100vw);
    box-shadow: -8px 0 24px rgba(0, 0, 0, 0.12);
  }

  .help-sidebar--collapsed {
    top: 112px;
    height: auto;
    width: var(--help-tab-width);
    border-radius: 10px 0 0 10px;
    box-shadow: -2px 4px 14px rgba(0, 0, 0, 0.1);
  }

  .help-sidebar--collapsed .help-toggle {
    height: auto;
    padding: 14px 0 16px;
    min-height: 92px;
  }

  .help-panel {
    max-width: var(--help-width);
  }
}

@keyframes helpOverlayIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@media (max-width: 480px) {
  .help-sidebar {
    --help-width: 300px;
    --help-tab-width: 44px;
  }

  .help-sidebar--open .help-toggle {
    padding-top: 72px;
  }

  .help-sidebar--collapsed .help-toggle {
    padding: 12px 0 14px;
    min-height: 84px;
  }

  .help-header {
    padding: 16px 16px 14px;
  }

  .help-header-badge {
    width: 40px;
    height: 40px;
  }

  .help-header-title {
    font-size: 15px;
  }

  .help-body {
    padding: 14px 14px 20px;
  }

  .help-section-head {
    margin-bottom: 10px;
  }

  .help-section-icon {
    width: 26px;
    height: 26px;
  }

  .help-card,
  .help-step {
    padding: 10px 12px;
  }

  .help-card-icon {
    width: 32px;
    height: 32px;
    font-size: 16px;
  }
}
</style>
