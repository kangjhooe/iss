<template>
  <div class="home-page">
    <!-- Navbar sticky -->
    <nav class="navbar">
      <div class="navbar-inner">
        <router-link to="/" class="navbar-brand">
          <div class="navbar-logo">
            <AppLogo :size="36" />
          </div>
          <span class="navbar-title">{{ appName }}</span>
        </router-link>

        <div class="navbar-links">
          <router-link to="/catatan-rilis" class="nav-link">Update</router-link>
          <router-link to="/login?demo=1" class="nav-link">Coba Demo</router-link>
        </div>

        <div class="navbar-auth">
          <router-link to="/login" class="navbar-login">Masuk</router-link>
          <router-link to="/register" class="btn btn-primary navbar-register">Daftar</router-link>
          <button
            type="button"
            class="navbar-toggle"
            :aria-expanded="menuOpen"
            aria-controls="home-mobile-menu"
            :aria-label="menuOpen ? 'Tutup menu' : 'Buka menu'"
            @click="menuOpen = !menuOpen"
          >
            <span class="navbar-toggle-bar" :class="{ open: menuOpen }"></span>
            <span class="navbar-toggle-bar" :class="{ open: menuOpen }"></span>
            <span class="navbar-toggle-bar" :class="{ open: menuOpen }"></span>
          </button>
        </div>
      </div>
    </nav>

    <Teleport to="body">
      <Transition name="menu">
        <div
          v-if="menuOpen"
          class="mobile-menu-backdrop"
          @click="closeMenu"
        ></div>
      </Transition>
      <Transition name="drawer">
        <div
          v-if="menuOpen"
          id="home-mobile-menu"
          class="mobile-menu-drawer"
          role="dialog"
          aria-label="Menu"
        >
          <button type="button" class="mobile-menu-close" aria-label="Tutup menu" @click="closeMenu">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
              <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
          </button>
          <router-link to="/catatan-rilis" class="mobile-menu-link" @click="closeMenu">Update</router-link>
          <router-link to="/login?demo=1" class="mobile-menu-link" @click="closeMenu">Coba Demo</router-link>
          <div class="mobile-menu-auth">
            <router-link to="/login" class="navbar-login" @click="closeMenu">Masuk</router-link>
            <router-link to="/register" class="btn btn-primary navbar-register" @click="closeMenu">Daftar</router-link>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Hero (teks & gambar dari Branding Aplikasi) -->
    <HeroSection
      :headline="appBranding.heroHeadline ?? undefined"
      :subheadline="appBranding.heroSubheadline ?? undefined"
      :hero-image-url="appBranding.heroImageUrl ?? undefined"
      :primary-cta-text="appBranding.heroPrimaryCtaText ?? undefined"
      :primary-cta-to="appBranding.heroPrimaryCtaTo ?? undefined"
      :secondary-cta-text="appBranding.heroSecondaryCtaText ?? undefined"
      :secondary-cta-to="appBranding.heroSecondaryCtaTo ?? undefined"
    />

    <!-- Cari sekolah (pintu masuk publik) -->
    <section id="cari-sekolah" class="section find-school-section" aria-labelledby="find-school-title">
      <div class="section-inner find-school-inner">
        <div class="find-school-card">
          <div class="find-school-copy">
            <h2 id="find-school-title" class="find-school-title">Cari Sekolah Anda</h2>
            <p class="find-school-subtitle">
              Masukkan NPSN untuk membuka profil, PPDB, perpustakaan digital, buku tamu, dan layanan publik sekolah.
            </p>
          </div>
          <form class="find-school-form" @submit.prevent="searchSchoolByNpsn">
            <div class="find-school-field">
              <label for="home-npsn" class="find-school-label">NPSN</label>
              <input
                id="home-npsn"
                :value="findNpsn"
                type="text"
                class="find-school-input"
                :class="{ 'find-school-input--error': findError }"
                inputmode="numeric"
                autocomplete="off"
                maxlength="8"
                placeholder="8 digit NPSN"
                aria-describedby="find-school-hint find-school-error"
                :disabled="findLoading"
                @input="onFindNpsnInput"
              />
            </div>
            <button type="submit" class="btn btn-primary find-school-btn" :disabled="findLoading || findNpsn.length !== 8">
              <span v-if="findLoading">Mencari...</span>
              <span v-else>Cari</span>
            </button>
          </form>
          <p id="find-school-hint" class="find-school-hint">NPSN terdiri dari 8 digit angka.</p>
          <p v-if="findError" id="find-school-error" class="find-school-error" role="alert">{{ findError }}</p>
        </div>
      </div>
    </section>

    <!-- Statistik: bukti sosial dekat hero -->
    <section class="section stats-section" :aria-busy="statsLoading" aria-labelledby="stats-heading">
      <div class="stats-bg" aria-hidden="true"></div>
      <div class="section-inner stats-inner">
        <h2 id="stats-heading" class="stats-heading">Dipercaya oleh institusi pendidikan</h2>
        <p class="stats-subheading">Bergabung bersama sekolah dan madrasah di seluruh Indonesia</p>
        <div class="stats-row">
          <div class="stats-total-block">
            <div class="stats-main">
              <div v-if="statsLoading" class="stats-number-wrap">
                <span class="stats-number stats-number--skeleton" aria-hidden="true">0</span>
              </div>
              <div v-else class="stats-number-wrap">
                <span class="stats-number" :aria-label="(institutionsCount ?? 0).toLocaleString('id-ID')">{{ displayTotal.toLocaleString('id-ID') }}</span>
              </div>
              <p class="stats-caption">Sekolah & madrasah sudah bergabung</p>
              <p v-if="statsLoadError" class="stats-error-msg">Statistik tidak dapat dimuat. Periksa koneksi atau coba lagi.</p>
              <p v-else-if="!statsLoading && (institutionsCount ?? 0) === 0" class="stats-zero-cta">Jadilah yang pertama bergabung.</p>
            </div>
            <div class="stats-decoration" aria-hidden="true">
              <svg class="stats-decoration-icon" width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 21H21V9L12 3L3 9V21Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M9 21V12H15V21" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
          </div>
          <div v-if="!statsLoading" class="stats-tiers" role="list">
            <div
              v-for="(tier, idx) in statsByTier"
              :key="tier.key"
              class="stats-tier-item"
              :class="{ 'stats-tier-item--active': (tier.count ?? 0) > 0, 'stats-tier-item--muted': (tier.count ?? 0) === 0 }"
              role="listitem"
              :title="tier.key === 'lainnya' ? 'Belum mengisi jenjang' : undefined"
            >
              <span class="stats-tier-label">{{ tier.label }}</span>
              <span class="stats-tier-value">{{ (animatedTierCounts[idx] ?? 0).toLocaleString('id-ID') }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Fitur -->
    <section id="fitur" ref="featuresRef" class="section features-section" :class="{ 'section--in-view': featuresVisible }">
      <div class="section-inner">
        <h2 class="section-title">Fitur unggulan</h2>
        <p class="section-subtitle">Modul yang paling sering dipakai sekolah dan madrasah, dalam satu platform</p>
        <div class="features-grid">
          <div class="feature-card">
            <div class="feature-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M16 4H18C18.5304 4 19.0391 4.21071 19.4142 4.58579C19.7893 4.96086 20 5.46957 20 6V20C20 20.5304 19.7893 21.0391 19.4142 21.4142C19.0391 21.7893 18.5304 22 18 22H6C5.46957 22 4.96086 21.7893 4.58579 21.4142C4.21071 21.0391 4 20.5304 4 20V6C4 5.46957 4.21071 4.96086 4.58579 4.58579C4.96086 4.21071 5.46957 4 6 4H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M15 2H9C8.46957 2 7.96086 2.21071 7.58579 2.58579C7.21071 2.96086 7 3.46957 7 4V16C7 16.5304 7.21071 17.0391 7.58579 17.4142C7.96086 17.7893 8.46957 18 9 18H15C15.5304 18 16.0391 17.7893 16.4142 17.4142C16.7893 17.0391 17 16.5304 17 16V4C17 3.46957 16.7893 2.96086 16.4142 2.58579C16.0391 2.21071 15.5304 2 15 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12 7V11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M10 9H14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h3>PPDB Online</h3>
            <p>Pendaftaran peserta didik baru, lengkapi berkas, dan cek hasil secara online</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 19.5C4 18.837 4.26339 18.2011 4.73223 17.7322C5.20107 17.2634 5.83696 17 6.5 17H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M6.5 2H20V22H6.5C5.83696 22 5.20107 21.7366 4.73223 21.2678C4.26339 20.7989 4 20.163 4 19.5V4.5C4 3.83696 4.26339 3.20107 4.73223 2.73223C5.20107 2.26339 5.83696 2 6.5 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M8 7H12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M8 11H16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M8 15H14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h3>Buku Nilai & Raport</h3>
            <p>KKM, bobot penilaian, remedial, buku nilai, dan cetak raport</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M16 2V6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M8 2V6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M3 10H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M8 14H8.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12 14H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M16 14H16.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M8 18H8.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12 18H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M16 18H16.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h3>Absensi & QR</h3>
            <p>Absensi siswa dan pegawai, termasuk presensi dengan scan QR code</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="5" y="2" width="14" height="20" rx="2" ry="2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <line x1="12" y1="18" x2="12.01" y2="18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h3>Portal & Ujian Online</h3>
            <p>Portal siswa/guru, ujian online, serta booking lab dan inventaris</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M16 13H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M16 17H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M10 9H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h3>Surat-menyurat</h3>
            <p>Buat surat dari template, lengkap dengan kop resmi dan tanda tangan</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h3>Data Siswa & Mutasi</h3>
            <p>Data siswa, mutasi, naik kelas, kelulusan, dan alumni dalam satu alur</p>
          </div>
        </div>

        <div v-if="showAllFeatures" class="features-grid features-grid--more">
          <div class="feature-card">
            <div class="feature-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 21H21V9L12 3L3 9V21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M9 21V12H15V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12 3V8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h3>Profil Sekolah/Madrasah</h3>
            <p>Kelola profil institusi, halaman publik by NPSN, dan identitas lembaga</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h3>Data Guru & Apresiasi</h3>
            <p>Data guru, mutasi, apresiasi, poin prestasi, dan ranking di satu tempat</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12 6V12L16 14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M3 12H4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12 3V4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M20 12H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12 20V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h3>Jadwal & Jurnal</h3>
            <p>Template jadwal per kelas, jadwal mengajar, dan jurnal harian guru</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12 8V12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12 16H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h3>BK, Pelanggaran & Piket</h3>
            <p>Konseling, poin pelanggaran/prestasi, laporan BK, dan jadwal guru piket</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h3>Ekstrakurikuler</h3>
            <p>Kelola ekskul, jadwal kegiatan, kehadiran, dan penilaian sesi</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 19.5C4 18.837 4.26339 18.2011 4.73223 17.7322C5.20107 17.2634 5.83696 17 6.5 17H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M6.5 2H20V22H6.5C5.83696 22 5.20107 21.7366 4.73223 21.2678C4.26339 20.7989 4 20.163 4 19.5V4.5C4 3.83696 4.26339 3.20107 4.73223 2.73223C5.20107 2.26339 5.83696 2 6.5 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M8 7H16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M8 11H16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M8 15H12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h3>Perpustakaan & E-book</h3>
            <p>Katalog buku, peminjaman, e-book publik, arsip digital, dan ijazah</p>
          </div>
        </div>

        <div class="features-more">
          <button type="button" class="features-more-btn" :aria-expanded="showAllFeatures" @click="showAllFeatures = !showAllFeatures">
            {{ showAllFeatures ? 'Tampilkan lebih sedikit' : 'Lihat semua fitur' }}
          </button>
        </div>
      </div>
    </section>

    <!-- Mengapa memilih kami -->
    <section id="mengapa" ref="whyRef" class="section why-section" :class="{ 'section--in-view': whyVisible }">
      <div class="section-inner">
        <h2 class="section-title">Mengapa Memilih Kami</h2>
        <p class="section-subtitle">Platform yang dirancang untuk kebutuhan sekolah dan madrasah di Indonesia</p>
        <div class="why-grid">
          <div class="why-card">
            <div class="why-icon">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h3>Data Aman</h3>
            <p>Data institusi dan siswa dikelola dengan keamanan yang terjamin</p>
          </div>
          <div class="why-card">
            <div class="why-icon">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h3>Terintegrasi</h3>
            <p>Semua modul terhubung dalam satu sistem, mengurangi duplikasi data</p>
          </div>
          <div class="why-card">
            <div class="why-icon">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="5" y="2" width="14" height="20" rx="2" ry="2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <line x1="12" y1="18" x2="12.01" y2="18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h3>Responsif</h3>
            <p>Akses dari desktop, tablet, atau ponsel kapan saja</p>
          </div>
          <div class="why-card">
            <div class="why-icon">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 9L12 2L21 9V20C21 20.5304 20.7893 21.0391 20.4142 21.4142C20.0391 21.7893 19.5304 22 19 22H5C4.46957 22 3.96086 21.7893 3.58579 21.4142C3.21071 21.0391 3 20.5304 3 20V9Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M9 22V12H15V22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h3>Multi-institusi</h3>
            <p>Mendukung berbagai jenjang dan jenis lembaga pendidikan</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Sekolah yang baru bergabung (slider) -->
    <section ref="recentRef" class="section recent-schools-section" :class="{ 'section--in-view': recentVisible }" aria-labelledby="recent-schools-title">
      <div class="section-inner">
        <h2 id="recent-schools-title" class="section-title">Sekolah yang Baru Bergabung</h2>
        <p class="section-subtitle">Selamat bergabung! Kunjungi profil sekolah berikut</p>

        <!-- Loading skeleton -->
        <div v-if="recentLoading" class="slider-wrap" aria-hidden="true">
          <div class="slider-track">
            <div v-for="i in 4" :key="i" class="recent-school-card recent-school-card--skeleton">
              <div class="recent-school-card-logo recent-school-card-logo--skeleton"></div>
              <div class="recent-school-card-name recent-school-card-name--skeleton"></div>
              <div class="recent-school-card-meta recent-school-card-meta--skeleton"></div>
            </div>
          </div>
        </div>

        <!-- Slider with data -->
        <div v-else-if="recentInstitutions.length > 0" class="slider-wrap">
          <button
            type="button"
            class="slider-btn slider-btn-prev"
            aria-label="Kartu sebelumnya"
            :disabled="!canScrollPrev"
            :aria-disabled="!canScrollPrev"
            @click="slidePrev"
          >
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
          </button>
          <div
            ref="sliderTrackRef"
            class="slider-track"
            role="list"
            tabindex="0"
            @scroll="updateSliderButtons"
          >
            <router-link
              v-for="inst in recentInstitutions"
              :key="inst.id"
              :to="`/${inst.npsn}`"
              class="recent-school-card"
              role="listitem"
            >
              <div class="recent-school-card-logo">
                <img
                  v-if="inst.logo_url && !logoFailedIds.has(inst.id)"
                  :src="inst.logo_url"
                  :alt="''"
                  @error="onLogoError(inst.id)"
                />
                <div v-else class="recent-school-card-placeholder">
                  <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 21H21V9L12 3L3 9V21Z"/><path d="M9 21V12H15V21"/></svg>
                </div>
              </div>
              <h3 class="recent-school-card-name">{{ inst.name }}</h3>
              <p class="recent-school-card-meta">{{ [inst.level, inst.type].filter(Boolean).join(' · ') || inst.npsn }}</p>
              <span class="recent-school-card-cta">Kunjungi</span>
            </router-link>
          </div>
          <button
            type="button"
            class="slider-btn slider-btn-next"
            aria-label="Kartu berikutnya"
            :disabled="!canScrollNext"
            :aria-disabled="!canScrollNext"
            @click="slideNext"
          >
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
          </button>
        </div>

        <p v-else class="recent-schools-empty">Belum ada data sekolah.</p>
      </div>
    </section>

    <!-- CTA akhir -->
    <section ref="ctaRef" class="section cta-section" :class="{ 'section--in-view': ctaVisible }">
      <div class="section-inner cta-inner">
        <h2 class="cta-title">Siap Memulai?</h2>
        <p class="cta-desc">Daftarkan sekolah atau madrasah Anda dan kelola segala kebutuhan administrasi dengan lebih mudah.</p>
        <div class="cta-actions">
          <router-link to="/register" class="btn btn-primary btn-lg">Daftar Sekarang</router-link>
          <router-link to="/login" class="btn btn-secondary btn-lg">Masuk</router-link>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
      <div class="footer-accent"></div>
      <div class="footer-inner">
        <div class="footer-brand">
          <AppLogo class="footer-logo" :size="28" />
          <div class="footer-text">
            <span class="footer-name">{{ appName }}</span>
            <p class="footer-tagline">{{ appTagline }}</p>
          </div>
        </div>
        <div class="footer-bottom">
          <span>&copy; {{ currentYear }} {{ appName }}</span>
          <span class="footer-sep">·</span>
          <router-link to="/catatan-rilis" class="footer-version">v{{ appVersion }}</router-link>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { useRouter } from 'vue-router'
import { appName, appTagline, appVersion } from '@/config/app'
import AppLogo from '@/components/AppLogo.vue'
import HeroSection from '@/components/HeroSection.vue'
import { useAppBrandingStore } from '@/stores/appBranding'
import { schoolPublicApi } from '@/api/schoolPublic'
import { validators } from '@/utils/validation'

const router = useRouter()
const appBranding = useAppBrandingStore()

const findNpsn = ref('')
const findError = ref('')
const findLoading = ref(false)
const menuOpen = ref(false)
const showAllFeatures = ref(false)

function closeMenu() {
  menuOpen.value = false
}

function onMenuKeydown(e) {
  if (e.key === 'Escape') closeMenu()
}

watch(menuOpen, (open) => {
  if (typeof document === 'undefined') return
  document.body.style.overflow = open ? 'hidden' : ''
})

function onFindNpsnInput(e) {
  findError.value = ''
  findNpsn.value = String(e.target.value || '').replace(/\D/g, '').slice(0, 8)
}

async function searchSchoolByNpsn() {
  findError.value = ''
  const npsn = findNpsn.value.trim()
  const formatError = validators.required(npsn, 'NPSN wajib diisi') || validators.npsn(npsn)
  if (formatError) {
    findError.value = formatError
    return
  }

  findLoading.value = true
  try {
    const res = await schoolPublicApi.getInstitution(npsn)
    const raw = res.data?.data ?? res.data
    if (!raw) {
      findError.value = 'Sekolah/madrasah tidak ditemukan atau tidak aktif.'
      return
    }
    await router.push(`/${npsn}`)
  } catch (e) {
    const status = e.response?.status
    if (status === 404) {
      findError.value = 'Sekolah/madrasah tidak ditemukan atau tidak aktif.'
    } else {
      findError.value = e.response?.data?.message || 'Gagal mencari sekolah. Coba lagi.'
    }
  } finally {
    findLoading.value = false
  }
}

const COUNT_UP_DURATION = 1200
const easeOutCubic = (t) => 1 - Math.pow(1 - t, 3)
const prefersReducedMotion = () => typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches

function useCountUp(targetRef, duration = COUNT_UP_DURATION) {
  const display = ref(0)
  watch(
    targetRef,
    (target) => {
      if (target == null) return
      const end = Number(target) || 0
      if (prefersReducedMotion()) {
        display.value = end
        return
      }
      const start = display.value
      if (end === start) return
      const startTime = performance.now()
      const tick = (now) => {
        const elapsed = now - startTime
        const progress = Math.min(elapsed / duration, 1)
        const eased = easeOutCubic(progress)
        display.value = Math.round(start + (end - start) * eased)
        if (progress < 1) requestAnimationFrame(tick)
      }
      requestAnimationFrame(tick)
    },
    { immediate: true }
  )
  return display
}

const currentYear = computed(() => new Date().getFullYear())

const institutionsCount = ref(null)
const statsByLevel = ref({})
const statsByType = ref({})
const recentInstitutions = ref([])
const statsLoading = ref(true)
const recentLoading = ref(true)
const statsLoadError = ref(false)
const sliderTrackRef = ref(null)
const canScrollPrev = ref(false)
const canScrollNext = ref(false)
const logoFailedIds = ref(new Set())

const featuresRef = ref(null)
const whyRef = ref(null)
const recentRef = ref(null)
const ctaRef = ref(null)
const featuresVisible = ref(false)
const whyVisible = ref(false)
const recentVisible = ref(false)
const ctaVisible = ref(false)

const SLIDER_CARD_WIDTH = 280
const SLIDER_GAP = 16

/** Kelompok jenjang: SD/MI, SMP/MTs, SMA/MA, SMK/MAK */
const STATS_TIERS = [
  { key: 'sd_mi', label: 'SD/MI', keys: ['SD', 'MI'] },
  { key: 'smp_mts', label: 'SMP/MTs', keys: ['SMP', 'MTs'] },
  { key: 'sma_ma', label: 'SMA/MA', keys: ['SMA', 'MA'] },
  { key: 'smk_mak', label: 'SMK/MAK', keys: ['SMK', 'MAK'] },
  { key: 'lainnya', label: 'Lainnya', keys: ['Lainnya'] },
]

const statsByTier = computed(() => {
  const byLevel = statsByLevel.value || {}
  return STATS_TIERS.map(({ key, label, keys }) => ({
    key,
    label,
    count: keys.reduce((sum, k) => sum + (byLevel[k] ?? 0), 0),
  }))
})

const displayTotal = useCountUp(institutionsCount)
const animatedTierCounts = ref([0, 0, 0, 0, 0])

function animateTierCounts() {
  const tiers = statsByTier.value
  if (!tiers.length) return
  const targets = tiers.map((t) => t.count ?? 0)
  if (prefersReducedMotion()) {
    animatedTierCounts.value = targets.slice()
    return
  }
  const start = animatedTierCounts.value.slice()
  const startTime = performance.now()
  const tick = (now) => {
    const elapsed = now - startTime
    const progress = Math.min(elapsed / COUNT_UP_DURATION, 1)
    const eased = easeOutCubic(progress)
    animatedTierCounts.value = start.map((s, i) =>
      Math.round(s + (targets[i] - s) * eased)
    )
    if (progress < 1) requestAnimationFrame(tick)
  }
  requestAnimationFrame(tick)
}

watch(
  () => statsByTier.value.map((t) => t.count),
  () => {
    if (statsLoading.value || !statsByTier.value.length) return
    animateTierCounts()
  },
  { immediate: true }
)

function updateSliderButtons() {
  const el = sliderTrackRef.value
  if (!el) return
  const { scrollLeft, scrollWidth, clientWidth } = el
  canScrollPrev.value = scrollLeft > 2
  canScrollNext.value = scrollLeft < scrollWidth - clientWidth - 2
}

function slidePrev() {
  const el = sliderTrackRef.value
  if (!el || !canScrollPrev.value) return
  el.scrollBy({ left: -(SLIDER_CARD_WIDTH + SLIDER_GAP), behavior: 'smooth' })
}

function slideNext() {
  const el = sliderTrackRef.value
  if (!el || !canScrollNext.value) return
  el.scrollBy({ left: SLIDER_CARD_WIDTH + SLIDER_GAP, behavior: 'smooth' })
}

function onLogoError(id) {
  logoFailedIds.value = new Set(logoFailedIds.value).add(id)
}

onMounted(async () => {
  window.addEventListener('keydown', onMenuKeydown)
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return
        const el = entry.target
        if (el === featuresRef.value) featuresVisible.value = true
        else if (el === whyRef.value) whyVisible.value = true
        else if (el === recentRef.value) recentVisible.value = true
        else if (el === ctaRef.value) ctaVisible.value = true
      })
    },
    { rootMargin: '0px 0px -8% 0px', threshold: 0 }
  )
  await nextTick()
  ;[featuresRef, whyRef, recentRef, ctaRef].forEach((ref) => {
    if (ref.value) observer.observe(ref.value)
  })

  try {
    const [statsRes, recentRes] = await Promise.all([
      schoolPublicApi.getStats(),
      schoolPublicApi.getRecentInstitutions(12),
    ])
    // Backend mengembalikan { data: { institutions_count, by_level, by_type } }; dukung juga bentuk langsung
    const data = statsRes.data?.data ?? statsRes.data ?? {}
    institutionsCount.value = data.institutions_count ?? 0
    statsByLevel.value = data.by_level ?? {}
    statsByType.value = data.by_type ?? {}
    recentInstitutions.value = Array.isArray(recentRes.data?.data) ? recentRes.data.data : (recentRes.data ?? [])
  } catch (_) {
    institutionsCount.value = 0
    statsByLevel.value = {}
    statsByType.value = {}
    recentInstitutions.value = []
    statsLoadError.value = true
  } finally {
    statsLoading.value = false
    recentLoading.value = false
  }
  await nextTick()
  updateSliderButtons()
})

onUnmounted(() => {
  window.removeEventListener('keydown', onMenuKeydown)
  document.body.style.overflow = ''
})
</script>

<style scoped>
.home-page {
  min-height: 100vh;
  background: #ffffff;
  overflow-x: hidden;
  -webkit-overflow-scrolling: touch;
}

/* Navbar */
.navbar {
  position: sticky;
  top: 0;
  z-index: 100;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(8px);
  border-bottom: 1px solid #e2e8f0;
  padding-top: env(safe-area-inset-top, 0);
}

.navbar-inner {
  max-width: 1100px;
  margin: 0 auto;
  padding: 12px 24px;
  padding-left: max(24px, env(safe-area-inset-left));
  padding-right: max(24px, env(safe-area-inset-right));
  display: flex;
  align-items: center;
  gap: 28px;
  flex-wrap: nowrap;
}

.navbar-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  text-decoration: none;
  color: #1e293b;
  font-weight: 600;
  font-size: 18px;
  flex-shrink: 0;
}

.navbar-brand:hover {
  color: #059669;
}

.navbar-logo svg {
  flex-shrink: 0;
}

.navbar-title {
  white-space: nowrap;
}

.navbar-links {
  display: flex;
  align-items: center;
  gap: 4px 20px;
  flex: 1;
  min-width: 0;
}

.nav-link {
  color: #64748b;
  text-decoration: none;
  font-size: 15px;
  font-weight: 500;
  transition: color 0.2s;
  padding: 8px 4px;
  min-height: 44px;
  display: inline-flex;
  align-items: center;
  -webkit-tap-highlight-color: transparent;
  touch-action: manipulation;
}

.nav-link:hover {
  color: #059669;
}

.nav-link:focus-visible {
  outline: 2px solid #059669;
  outline-offset: 2px;
  border-radius: 6px;
}

.navbar-auth {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-left: auto;
  padding-left: 20px;
  border-left: 1px solid #e2e8f0;
  flex-shrink: 0;
}

.navbar-login {
  color: #475569;
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
  padding: 8px 12px;
  min-height: 44px;
  display: inline-flex;
  align-items: center;
  border-radius: 8px;
  -webkit-tap-highlight-color: transparent;
}

.navbar-login:hover {
  color: #059669;
  background: #f8fafc;
}

.navbar-login:focus-visible {
  outline: 2px solid #059669;
  outline-offset: 2px;
}

.navbar-register {
  font-weight: 600;
  padding: 10px 18px;
}

/* Hamburger: only on mobile */
.navbar-toggle {
  display: none;
  flex-direction: column;
  justify-content: center;
  gap: 5px;
  width: 44px;
  min-width: 44px;
  height: 44px;
  padding: 10px;
  background: transparent;
  border: none;
  cursor: pointer;
  border-radius: 8px;
  -webkit-tap-highlight-color: transparent;
  touch-action: manipulation;
  color: #1e293b;
}
.navbar-toggle:focus-visible {
  outline: 2px solid #059669;
  outline-offset: 2px;
}
.navbar-toggle-bar {
  display: block;
  width: 22px;
  height: 2px;
  background: currentColor;
  border-radius: 1px;
  transition: transform 0.2s ease, opacity 0.2s ease;
}
.navbar-toggle-bar.open:nth-child(1) {
  transform: translateY(7px) rotate(45deg);
}
.navbar-toggle-bar.open:nth-child(2) {
  opacity: 0;
}
.navbar-toggle-bar.open:nth-child(3) {
  transform: translateY(-7px) rotate(-45deg);
}

/* Mobile menu backdrop & drawer */
.mobile-menu-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  z-index: 199;
  -webkit-tap-highlight-color: transparent;
}
.mobile-menu-drawer {
  position: fixed;
  top: 0;
  right: 0;
  bottom: 0;
  width: min(280px, 85vw);
  background: #ffffff;
  z-index: 200;
  padding: 72px 24px 24px;
  padding-top: max(72px, calc(env(safe-area-inset-top) + 56px));
  padding-right: max(24px, env(safe-area-inset-right));
  padding-bottom: max(24px, env(safe-area-inset-bottom));
  display: flex;
  flex-direction: column;
  gap: 8px;
  box-shadow: -4px 0 24px rgba(0, 0, 0, 0.12);
}
.mobile-menu-close {
  position: absolute;
  top: max(16px, env(safe-area-inset-top));
  right: max(16px, env(safe-area-inset-right));
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: #f8fafc;
  border-radius: 10px;
  color: #334155;
  cursor: pointer;
}
.mobile-menu-close:hover {
  background: #ecfdf5;
  color: #059669;
}
.mobile-menu-link {
  padding: 14px 16px;
  border-radius: 8px;
  color: #1e293b;
  text-decoration: none;
  font-size: 16px;
  font-weight: 500;
  min-height: 48px;
  display: flex;
  align-items: center;
  -webkit-tap-highlight-color: transparent;
  transition: background 0.15s, color 0.15s;
}
.mobile-menu-link:hover {
  background: #f1f5f9;
  color: #059669;
}
.mobile-menu-link:focus-visible {
  outline: 2px solid #059669;
  outline-offset: 2px;
}
.mobile-menu-auth {
  margin-top: auto;
  padding-top: 16px;
  border-top: 1px solid #e2e8f0;
  display: flex;
  gap: 8px;
  align-items: center;
}
.mobile-menu-auth .navbar-login,
.mobile-menu-auth .navbar-register {
  flex: 1;
  justify-content: center;
}
.menu-enter-active,
.menu-leave-active {
  transition: opacity 0.2s ease;
}
.menu-enter-from,
.menu-leave-to {
  opacity: 0;
}
.drawer-enter-active,
.drawer-leave-active {
  transition: transform 0.25s ease;
}
.drawer-enter-from,
.drawer-leave-to {
  transform: translateX(100%);
}

/* Why section SVG icons */
.why-icon {
  width: 48px;
  height: 48px;
  margin: 0 auto 16px;
  display: flex;
  justify-content: center;
  align-items: center;
  color: #059669;
  background: #ecfdf5;
  border-radius: 12px;
}
.why-icon svg {
  flex-shrink: 0;
  width: 24px;
  height: 24px;
}

/* Buttons - min touch target 44px (accessibility) */
.btn {
  padding: 10px 20px;
  min-height: 44px;
  min-width: 44px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  text-decoration: none;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  border: none;
  -webkit-tap-highlight-color: transparent;
  touch-action: manipulation;
}

.btn-ghost {
  background: transparent;
  color: #64748b;
}

.btn-ghost:hover {
  color: #059669;
}

.btn-primary {
  background: #059669;
  color: white;
}

.btn-primary:hover {
  background: #047857;
  box-shadow: 0 4px 14px rgba(5, 150, 105, 0.4);
  transform: translateY(-1px);
}

.btn-secondary {
  background: white;
  color: #059669;
  border: 1.5px solid #059669;
}

.btn-secondary:hover {
  background: #f8fafc;
  border-color: #047857;
  color: #047857;
  transform: translateY(-1px);
}

.btn-lg {
  padding: 14px 28px;
  font-size: 16px;
}

.btn:focus-visible,
a.btn:focus-visible {
  outline: 2px solid #059669;
  outline-offset: 2px;
}

@keyframes fade-slide-up {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Sections */
.section {
  padding: 64px 24px;
}

.section-inner {
  max-width: 1100px;
  margin: 0 auto;
  padding-left: env(safe-area-inset-left, 0);
  padding-right: env(safe-area-inset-right, 0);
}

/* Find school (public entry) */
.section.find-school-section {
  padding: 0 24px 8px;
  margin-top: -28px;
  background: transparent;
  border-bottom: none;
  scroll-margin-top: 72px;
  position: relative;
  z-index: 2;
}

.find-school-inner {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 0;
  max-width: 720px;
}

.find-school-card {
  width: 100%;
  padding: 28px 28px 22px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08), 0 0 0 1px rgba(5, 150, 105, 0.06);
}

.find-school-title {
  margin: 0 0 6px;
  font-size: 22px;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: -0.02em;
}

.find-school-subtitle {
  margin: 0 0 18px;
  font-size: 15px;
  line-height: 1.5;
  color: #64748b;
}

.find-school-form {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  justify-content: center;
  gap: 12px;
  width: 100%;
  margin-top: 4px;
}

.find-school-field {
  flex: 1 1 240px;
  max-width: 360px;
  text-align: left;
}

.find-school-label {
  display: block;
  margin-bottom: 6px;
  font-size: 13px;
  font-weight: 600;
  color: #475569;
}

.find-school-input {
  width: 100%;
  min-height: 52px;
  padding: 12px 16px;
  border: 1.5px solid #cbd5e1;
  border-radius: 12px;
  font-size: 18px;
  letter-spacing: 0.08em;
  color: #0f172a;
  background: #ffffff;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.find-school-input:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.15);
}

.find-school-input--error {
  border-color: #dc2626;
}

.find-school-input--error:focus {
  box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12);
}

.find-school-input:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.find-school-btn {
  flex: 0 0 auto;
  min-width: 120px;
  min-height: 52px;
  border-radius: 12px;
  font-size: 16px;
}

.find-school-btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.find-school-hint {
  margin: 12px 0 0;
  font-size: 13px;
  color: #94a3b8;
}

.find-school-error {
  margin: 8px 0 0;
  font-size: 14px;
  font-weight: 500;
  color: #dc2626;
}

.section-title {
  font-size: 28px;
  font-weight: 700;
  color: #1e293b;
  text-align: center;
  margin-bottom: 12px;
}

.section-subtitle {
  font-size: 17px;
  color: #64748b;
  text-align: center;
  margin-bottom: 40px;
  max-width: 560px;
  margin-left: auto;
  margin-right: auto;
}

/* Stats section – padat, hirarki jelas, angka utama menonjol */
.stats-section {
  position: relative;
  padding: 40px 24px 48px;
  overflow: hidden;
}

.stats-bg {
  position: absolute;
  inset: 0;
  background: linear-gradient(160deg, #ecfdf5 0%, #f0fdf4 40%, #f8fafc 75%);
  z-index: 0;
}

.stats-section::before {
  content: '';
  position: absolute;
  top: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 120%;
  max-width: 800px;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(5, 150, 105, 0.2), transparent);
  z-index: 1;
}

.stats-inner {
  position: relative;
  z-index: 1;
  text-align: center;
}

.stats-heading {
  font-size: 20px;
  font-weight: 600;
  color: #475569;
  margin: 0 0 4px;
  letter-spacing: 0.02em;
}

.stats-subheading {
  font-size: 14px;
  color: #64748b;
  margin: 0 0 24px;
  max-width: 420px;
  margin-left: auto;
  margin-right: auto;
}

.stats-row {
  display: flex;
  flex-wrap: wrap;
  align-items: stretch;
  justify-content: center;
  gap: 20px;
  max-width: 920px;
  margin: 0 auto;
}

.stats-total-block {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 24px 32px;
  background: #ffffff;
  border-radius: 20px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 4px 24px rgba(30, 41, 59, 0.06), 0 0 0 1px rgba(5, 150, 105, 0.08);
  flex-shrink: 0;
}

.stats-main {
  flex: 1;
  min-width: 0;
  text-align: left;
}

.stats-number-wrap {
  margin-bottom: 4px;
}

.stats-number {
  display: inline-block;
  font-size: 72px;
  font-weight: 800;
  line-height: 1;
  letter-spacing: -0.04em;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.stats-number--skeleton {
  -webkit-text-fill-color: transparent;
  background: linear-gradient(90deg, #e2e8f0 30%, #f1f5f9 50%, #e2e8f0 70%);
  background-size: 200% 100%;
  background-clip: border-box;
  -webkit-background-clip: border-box;
  animation: stats-shine 1.5s ease-in-out infinite;
  border-radius: 8px;
  min-width: 100px;
  display: inline-block;
}

.stats-caption {
  font-size: 15px;
  font-weight: 500;
  color: #475569;
  margin: 0;
  line-height: 1.35;
}

.stats-zero-cta {
  font-size: 13px;
  color: #059669;
  margin: 8px 0 0;
  font-weight: 500;
}

.stats-error-msg {
  font-size: 13px;
  color: #b91c1c;
  margin: 8px 0 0;
  font-weight: 500;
}

.stats-decoration {
  flex-shrink: 0;
  width: 72px;
  height: 72px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 18px;
  background: linear-gradient(135deg, rgba(5, 150, 105, 0.14) 0%, rgba(4, 120, 87, 0.14) 100%);
  color: #059669;
}

.stats-decoration-icon {
  opacity: 0.9;
}

/* Tiers: kartu aktif (warna) vs redup (0) */
.stats-tiers {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
  list-style: none;
  padding: 0;
  margin: 0;
}

.stats-tier-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-width: 84px;
  padding: 14px 18px;
  background: #ffffff;
  border-radius: 14px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 2px 10px rgba(30, 41, 59, 0.04);
  transition: border-color 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease, background 0.2s ease;
}

.stats-tier-item--active {
  border-color: rgba(5, 150, 105, 0.35);
  background: linear-gradient(180deg, #ffffff 0%, rgba(5, 150, 105, 0.04) 100%);
  box-shadow: 0 2px 12px rgba(5, 150, 105, 0.1);
}

.stats-tier-item--active:hover {
  border-color: #059669;
  box-shadow: 0 4px 16px rgba(5, 150, 105, 0.15);
}

.stats-tier-item--muted {
  opacity: 0.6;
  border-color: #e2e8f0;
}

.stats-tier-item--muted .stats-tier-value {
  color: #94a3b8;
}

.stats-tier-label {
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  margin-bottom: 2px;
  text-align: center;
}

.stats-tier-item--active .stats-tier-label {
  color: #475569;
}

.stats-tier-value {
  font-size: 24px;
  font-weight: 700;
  color: #059669;
}

@keyframes stats-shine {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

@media (prefers-reduced-motion: reduce) {
  .stats-number--skeleton {
    animation: none;
    background: #e2e8f0;
    -webkit-text-fill-color: transparent;
  }
}

/* Recent schools slider */
.recent-schools-section {
  background: #f8fafc;
}

.recent-schools-section.section--in-view .section-title {
  opacity: 0;
  animation: fade-slide-up 0.45s ease-out forwards;
}

.recent-schools-section.section--in-view .section-subtitle {
  opacity: 0;
  animation: fade-slide-up 0.45s ease-out 0.08s forwards;
}

.recent-schools-section .section-subtitle {
  margin-bottom: 28px;
}

.slider-wrap {
  position: relative;
  display: flex;
  align-items: center;
  gap: 12px;
}

.slider-btn {
  flex-shrink: 0;
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #e2e8f0;
  background: #fff;
  border-radius: 12px;
  color: #64748b;
  cursor: pointer;
  transition: border-color 0.2s, color 0.2s, background 0.2s;
}

.slider-btn:hover {
  border-color: #059669;
  color: #059669;
  background: #f8fafc;
}

.slider-btn:active:not(:disabled) {
  transform: scale(0.96);
}

.slider-btn:focus-visible {
  outline: 2px solid #059669;
  outline-offset: 2px;
}

.slider-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
  border-color: #e2e8f0;
  color: #94a3b8;
}

.slider-btn:disabled:hover {
  background: #fff;
  border-color: #e2e8f0;
  color: #94a3b8;
}

.slider-track {
  flex: 1;
  display: flex;
  gap: 16px;
  overflow-x: auto;
  scroll-snap-type: x mandatory;
  scroll-behavior: smooth;
  padding: 8px 0 16px;
  -webkit-overflow-scrolling: touch;
}

.slider-track::-webkit-scrollbar {
  height: 6px;
}

.slider-track::-webkit-scrollbar-track {
  background: #e2e8f0;
  border-radius: 3px;
}

.slider-track::-webkit-scrollbar-thumb {
  background: #94a3b8;
  border-radius: 3px;
}

.recent-school-card {
  flex: 0 0 280px;
  scroll-snap-align: start;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 24px 20px;
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  text-decoration: none;
  color: inherit;
  transition: border-color 0.2s, box-shadow 0.2s, transform 0.2s;
}

.recent-school-card:hover {
  border-color: #059669;
  box-shadow: 0 4px 16px rgba(5, 150, 105, 0.12);
  transform: translateY(-2px);
}

.recent-school-card-logo {
  width: 64px;
  height: 64px;
  border-radius: 12px;
  overflow: hidden;
  margin-bottom: 12px;
  background: #f1f5f9;
}

.recent-school-card-logo img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.recent-school-card-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
}

.recent-school-card-name {
  font-size: 16px;
  font-weight: 600;
  color: #1e293b;
  margin: 0 0 4px;
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.recent-school-card-meta {
  font-size: 13px;
  color: #64748b;
  margin: 0 0 12px;
  line-height: 1.4;
}

.recent-school-card-cta {
  font-size: 14px;
  font-weight: 600;
  color: #059669;
  margin-top: auto;
}

.recent-schools-empty {
  text-align: center;
  color: #64748b;
  margin: 0;
}

/* Slider loading skeleton */
.recent-school-card--skeleton {
  pointer-events: none;
  cursor: default;
}

.recent-school-card-logo--skeleton {
  background: linear-gradient(90deg, #e2e8f0 25%, #f1f5f9 50%, #e2e8f0 75%);
  background-size: 200% 100%;
  animation: skeleton-shine 1.2s ease-in-out infinite;
}

.recent-school-card-name--skeleton,
.recent-school-card-meta--skeleton {
  width: 80%;
  height: 14px;
  margin-left: auto;
  margin-right: auto;
  border-radius: 6px;
  background: linear-gradient(90deg, #e2e8f0 25%, #f1f5f9 50%, #e2e8f0 75%);
  background-size: 200% 100%;
  animation: skeleton-shine 1.2s ease-in-out infinite;
}

.recent-school-card-name--skeleton {
  height: 18px;
  margin-bottom: 8px;
}

.recent-school-card-meta--skeleton {
  width: 60%;
}

@keyframes skeleton-shine {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

@media (prefers-reduced-motion: reduce) {
  .recent-school-card-logo--skeleton,
  .recent-school-card-name--skeleton,
  .recent-school-card-meta--skeleton {
    animation: none;
  }
}

/* Features */
.features-section {
  background: #f8fafc;
  scroll-margin-top: 72px;
}

.features-section.section--in-view .feature-card {
  opacity: 0;
  animation: fade-slide-up 0.5s ease-out forwards;
}

.features-section.section--in-view .feature-card:nth-child(1) { animation-delay: 0ms; }
.features-section.section--in-view .feature-card:nth-child(2) { animation-delay: 50ms; }
.features-section.section--in-view .feature-card:nth-child(3) { animation-delay: 100ms; }
.features-section.section--in-view .feature-card:nth-child(4) { animation-delay: 150ms; }
.features-section.section--in-view .feature-card:nth-child(5) { animation-delay: 200ms; }
.features-section.section--in-view .feature-card:nth-child(6) { animation-delay: 250ms; }
.features-section.section--in-view .feature-card:nth-child(7) { animation-delay: 300ms; }
.features-section.section--in-view .feature-card:nth-child(8) { animation-delay: 350ms; }
.features-section.section--in-view .feature-card:nth-child(9) { animation-delay: 400ms; }
.features-section.section--in-view .feature-card:nth-child(10) { animation-delay: 450ms; }
.features-section.section--in-view .feature-card:nth-child(11) { animation-delay: 500ms; }
.features-section.section--in-view .feature-card:nth-child(12) { animation-delay: 550ms; }

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 24px;
}

.features-grid--more {
  margin-top: 24px;
}

.features-more {
  display: flex;
  justify-content: center;
  margin-top: 28px;
}

.features-more-btn {
  min-height: 44px;
  padding: 10px 20px;
  border: 1.5px solid #059669;
  border-radius: 999px;
  background: #fff;
  color: #047857;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
}

.features-more-btn:hover {
  background: #ecfdf5;
  box-shadow: 0 4px 14px rgba(5, 150, 105, 0.12);
  transform: translateY(-1px);
}

.feature-card {
  padding: 28px 24px;
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  text-align: left;
  transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
}

.feature-card:hover {
  border-color: #059669;
  box-shadow: 0 8px 30px rgba(5, 150, 105, 0.1);
  transform: translateY(-2px);
}

.feature-card:hover .feature-icon {
  transform: none;
  background: #059669;
  color: #fff;
}

.feature-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: #ecfdf5;
  color: #059669;
  transition: transform 0.25s ease, background 0.2s ease, color 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
}

.feature-icon svg {
  color: inherit;
  flex-shrink: 0;
}

.feature-card h3 {
  font-size: 18px;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 8px;
}

.feature-card p {
  font-size: 14px;
  color: #64748b;
  line-height: 1.6;
  margin: 0;
}

/* Why section */
.why-section {
  background: #ffffff;
}

.why-section.section--in-view .why-card {
  opacity: 0;
  animation: fade-slide-up 0.5s ease-out forwards;
}

.why-section.section--in-view .why-card:nth-child(1) { animation-delay: 0ms; }
.why-section.section--in-view .why-card:nth-child(2) { animation-delay: 80ms; }
.why-section.section--in-view .why-card:nth-child(3) { animation-delay: 160ms; }
.why-section.section--in-view .why-card:nth-child(4) { animation-delay: 240ms; }

.why-card:hover .why-icon {
  transform: none;
  background: #059669;
  color: #fff;
}

.why-icon {
  transition: transform 0.25s ease, background 0.2s ease, color 0.2s ease;
}

.why-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 24px;
}

.why-card {
  padding: 28px 24px;
  background: #f8fafc;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  text-align: center;
  transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
}

.why-card:hover {
  border-color: #059669;
  box-shadow: 0 8px 24px rgba(5, 150, 105, 0.08);
  transform: translateY(-2px);
}


.why-card h3 {
  font-size: 17px;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 8px;
}

.why-card p {
  font-size: 14px;
  color: #64748b;
  line-height: 1.6;
  margin: 0;
}

/* CTA section */
.cta-section {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  padding: 64px 24px;
}

.cta-section.section--in-view .cta-inner {
  opacity: 0;
  animation: fade-slide-up 0.55s ease-out forwards;
}

.cta-inner {
  text-align: center;
}

.cta-title {
  font-size: 28px;
  font-weight: 700;
  color: #ffffff;
  margin-bottom: 12px;
}

.cta-desc {
  font-size: 17px;
  color: rgba(255, 255, 255, 0.9);
  margin-bottom: 28px;
  max-width: 480px;
  margin-left: auto;
  margin-right: auto;
}

.cta-actions {
  display: flex;
  gap: 16px;
  justify-content: center;
  flex-wrap: wrap;
}

.cta-section .btn-primary {
  background: #ffffff;
  color: #059669;
}

.cta-section .btn-primary:hover {
  background: #f1f5f9;
  color: #047857;
}

.cta-section .btn-secondary {
  background: transparent;
  color: #ffffff;
  border-color: rgba(255, 255, 255, 0.8);
}

.cta-section .btn-secondary:hover {
  background: rgba(255, 255, 255, 0.15);
  border-color: #ffffff;
  color: #ffffff;
}

/* Reduced motion: tampilkan konten tanpa animasi */
@media (prefers-reduced-motion: reduce) {
  .features-section.section--in-view .feature-card,
  .why-section.section--in-view .why-card {
    opacity: 1;
    transform: translateY(0);
    animation: none;
  }
  .recent-schools-section.section--in-view .section-title,
  .recent-schools-section.section--in-view .section-subtitle {
    opacity: 1;
    animation: none;
  }
  .cta-section.section--in-view .cta-inner {
    opacity: 1;
    animation: none;
  }
}

/* Footer */
.footer {
  position: relative;
  background: linear-gradient(180deg, #0f172a 0%, #1e293b 50%, #172033 100%);
  color: #94a3b8;
  padding: 12px 24px 16px;
  padding-bottom: max(16px, env(safe-area-inset-bottom));
  padding-left: max(24px, env(safe-area-inset-left));
  padding-right: max(24px, env(safe-area-inset-right));
}

.footer-accent {
  height: 2px;
  background: linear-gradient(90deg, #059669 0%, #047857 50%, #059669 100%);
  background-size: 200% 100%;
  margin-bottom: 12px;
}

.footer-inner {
  max-width: 1100px;
  margin: 0 auto;
}

.footer-brand {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  margin-bottom: 10px;
}

.footer-logo {
  flex-shrink: 0;
  filter: drop-shadow(0 1px 4px rgba(5, 150, 105, 0.2));
}

.footer-text {
  min-width: 0;
  text-align: center;
}

.footer-name {
  font-size: 15px;
  font-weight: 600;
  color: #f1f5f9;
  letter-spacing: -0.02em;
  display: block;
  line-height: 1.3;
}

.footer-tagline {
  font-size: 12px;
  color: #94a3b8;
  margin: 2px 0 0;
  line-height: 1.4;
}

.footer-bottom {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-wrap: wrap;
  gap: 6px 8px;
  padding-top: 10px;
  border-top: 1px solid rgba(51, 65, 85, 0.8);
  font-size: 12px;
  color: #64748b;
}

.footer-sep {
  color: #475569;
  user-select: none;
}

.footer-version {
  opacity: 0.9;
  color: inherit;
  text-decoration: none;
}

.footer-version:hover {
  color: #a7f3d0;
  text-decoration: underline;
}

/* ========== Responsive: Tablet (768px - 1024px) ========== */
@media (max-width: 1024px) {
  .section {
    padding: 56px 24px;
  }

  .section.find-school-section {
    padding: 0 24px 8px;
    margin-top: -24px;
  }

  .section-inner {
    padding-left: max(24px, env(safe-area-inset-left));
    padding-right: max(24px, env(safe-area-inset-right));
  }

  .features-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
  }

  .why-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
  }

  .feature-card,
  .why-card {
    padding: 24px 20px;
  }
}

/* ========== Responsive: Tablet portrait / large phone (≤768px) ========== */
@media (max-width: 768px) {
  .navbar-links {
    display: none;
  }

  .navbar-toggle {
    display: flex;
  }

  .navbar-auth {
    border-left: none;
    padding-left: 0;
    gap: 6px;
  }

  .navbar-inner {
    padding: 12px 20px;
    padding-left: max(20px, env(safe-area-inset-left));
    padding-right: max(20px, env(safe-area-inset-right));
    gap: 12px;
  }

  .find-school-section {
    padding: 0 20px 8px;
    margin-top: -16px;
  }

  .find-school-card {
    padding: 22px 18px 18px;
  }

  .find-school-title {
    font-size: 20px;
  }

  .find-school-form {
    flex-direction: column;
    align-items: stretch;
  }

  .find-school-field {
    max-width: none;
  }

  .find-school-btn {
    width: 100%;
  }

  .hero {
    padding: 56px 20px 64px;
    padding-left: max(20px, env(safe-area-inset-left));
    padding-right: max(20px, env(safe-area-inset-right));
  }

  .hero h1 {
    font-size: 32px;
  }

  .hero-subtitle {
    font-size: 18px;
  }

  .hero-desc {
    font-size: 16px;
  }

  .hero-actions {
    flex-direction: column;
    gap: 12px;
  }

  .hero-actions .btn {
    width: 100%;
    max-width: 320px;
    min-height: 48px;
    font-size: 16px;
  }

  .section {
    padding: 48px 20px;
    padding-left: max(20px, env(safe-area-inset-left));
    padding-right: max(20px, env(safe-area-inset-right));
  }

  .section.find-school-section {
    padding: 0 20px 8px;
    padding-left: max(20px, env(safe-area-inset-left));
    padding-right: max(20px, env(safe-area-inset-right));
    margin-top: -16px;
  }

  .section-title {
    font-size: 24px;
  }

  .section-subtitle {
    font-size: 16px;
    margin-bottom: 32px;
  }

  .features-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
  }

  .why-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
  }

  .feature-card,
  .why-card {
    padding: 24px 20px;
  }

  .cta-section {
    padding: 48px 20px;
    padding-left: max(20px, env(safe-area-inset-left));
    padding-right: max(20px, env(safe-area-inset-right));
  }

  .cta-title {
    font-size: 24px;
  }

  .cta-desc {
    font-size: 16px;
  }

  .cta-actions {
    flex-direction: column;
    gap: 12px;
  }

  .cta-actions .btn {
    width: 100%;
    max-width: 320px;
    min-height: 48px;
    font-size: 16px;
  }

  .footer {
    padding: 0 20px 14px;
  }

  .footer-accent {
    margin-bottom: 10px;
  }

  .recent-school-card {
    flex: 0 0 260px;
  }
}

/* ========== Responsive: Smartphone (≤480px) ========== */
@media (max-width: 480px) {
  .navbar-inner {
    padding: 12px 16px;
    padding-left: max(16px, env(safe-area-inset-left));
    padding-right: max(16px, env(safe-area-inset-right));
  }

  .navbar-brand {
    font-size: 16px;
  }

  .navbar-title {
    font-size: 15px;
    white-space: nowrap;
    line-height: 1.3;
  }

  .navbar-login {
    padding: 8px 8px;
    font-size: 13px;
  }

  .navbar-register {
    padding: 8px 14px;
    min-height: 40px;
    font-size: 13px;
  }

  .hero {
    padding: 40px 16px 56px;
    padding-left: max(16px, env(safe-area-inset-left));
    padding-right: max(16px, env(safe-area-inset-right));
  }

  .hero h1 {
    font-size: 26px;
    line-height: 1.25;
  }

  .hero-subtitle {
    font-size: 15px;
  }

  .hero-desc {
    font-size: 15px;
  }

  .hero-actions .btn {
    min-height: 48px;
    padding: 14px 20px;
  }

  .section {
    padding: 40px 16px;
    padding-left: max(16px, env(safe-area-inset-left));
    padding-right: max(16px, env(safe-area-inset-right));
  }

  .section.find-school-section {
    padding: 0 16px 8px;
    padding-left: max(16px, env(safe-area-inset-left));
    padding-right: max(16px, env(safe-area-inset-right));
    margin-top: -12px;
  }

  .section-title {
    font-size: 22px;
  }

  .section-subtitle {
    font-size: 15px;
    margin-bottom: 28px;
  }

  .stats-section {
    padding: 48px 16px 56px;
  }

  .stats-heading {
    font-size: 18px;
  }

  .stats-subheading {
    font-size: 14px;
    margin-bottom: 28px;
  }

  .stats-block {
    flex-direction: column;
    padding: 32px 24px;
    gap: 24px;
  }

  .stats-number {
    font-size: 44px;
  }

  .stats-caption {
    font-size: 15px;
  }

  .stats-decoration {
    width: 64px;
    height: 64px;
  }

  .stats-decoration-icon {
    width: 36px;
    height: 36px;
  }

  .stats-section {
    padding: 32px 16px 40px;
  }

  .stats-heading {
    font-size: 18px;
  }

  .stats-subheading {
    font-size: 13px;
    margin-bottom: 20px;
  }

  .stats-row {
    gap: 16px;
    flex-direction: column;
    align-items: stretch;
  }

  .stats-total-block {
    flex-direction: column;
    padding: 20px 20px;
    text-align: center;
  }

  .stats-main {
    text-align: center;
  }

  .stats-number {
    font-size: 56px;
  }

  .stats-caption {
    font-size: 14px;
  }

  .stats-decoration {
    width: 56px;
    height: 56px;
  }

  .stats-tiers {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    width: 100%;
    max-width: 320px;
    margin: 0 auto;
  }

  .stats-tier-item {
    min-width: 0;
    padding: 12px 14px;
  }

  .stats-tier-label {
    font-size: 11px;
  }

  .stats-tier-value {
    font-size: 20px;
  }

  .stats-zero-cta {
    font-size: 13px;
    margin-top: 8px;
  }

  .slider-wrap {
    gap: 8px;
  }

  .slider-btn {
    width: 40px;
    height: 40px;
  }

  .recent-school-card {
    flex: 0 0 240px;
    padding: 20px 16px;
  }

  .recent-school-card-name {
    font-size: 15px;
  }

  .feature-card,
  .why-card {
    padding: 20px 16px;
  }

  .feature-card {
    padding: 16px 12px;
  }

  .feature-icon {
    margin-bottom: 12px;
  }

  .why-icon {
    margin-bottom: 12px;
  }

  .feature-card h3,
  .why-card h3 {
    font-size: 16px;
  }

  .feature-card h3 {
    font-size: 14px;
  }

  .why-card h3 {
    font-size: 14px;
  }

  .feature-card p,
  .why-card p {
    font-size: 14px;
  }

  .feature-card p {
    font-size: 12px;
    line-height: 1.5;
  }

  .why-card {
    padding: 16px 12px;
  }

  .why-card p {
    font-size: 12px;
    line-height: 1.5;
  }

  .cta-section {
    padding: 40px 16px;
    padding-left: max(16px, env(safe-area-inset-left));
    padding-right: max(16px, env(safe-area-inset-right));
  }

  .cta-title {
    font-size: 22px;
  }

  .cta-desc {
    font-size: 15px;
  }

  .footer {
    padding: 10px 16px 14px;
    padding-left: max(16px, env(safe-area-inset-left));
    padding-right: max(16px, env(safe-area-inset-right));
    padding-bottom: max(14px, env(safe-area-inset-bottom));
  }

  .footer-brand {
    flex-wrap: wrap;
    justify-content: center;
    text-align: center;
  }

  .footer-text {
    text-align: center;
  }

  .footer-tagline {
    text-align: center;
    font-size: 11px;
  }

  .footer-name {
    font-size: 14px;
  }

  .footer-bottom {
    font-size: 11px;
    padding-top: 8px;
  }
}

/* ========== Touch devices: avoid hover-only states ========== */
@media (hover: none) and (pointer: coarse) {
  .feature-card:hover,
  .why-card:hover {
    box-shadow: none;
  }

  .btn:active,
  .nav-link:active {
    opacity: 0.9;
  }
}
</style>
