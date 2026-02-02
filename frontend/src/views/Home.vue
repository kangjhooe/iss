<template>
  <div class="home-page">
    <!-- Navbar sticky -->
    <nav class="navbar">
      <div class="navbar-inner">
        <router-link to="/" class="navbar-brand">
          <div class="navbar-logo">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 2L2 7L12 12L22 7L12 2Z" fill="#667eea"/>
              <path d="M2 17L12 22L22 17" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M2 12L12 17L22 12" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <span class="navbar-title">Indonesia Smart School</span>
        </router-link>
        <div class="navbar-links">
          <a href="#fitur" class="nav-link">Fitur</a>
          <a href="#mengapa" class="nav-link">Mengapa Kami</a>
        </div>
        <div class="navbar-actions">
          <router-link to="/login" class="btn btn-ghost">Masuk</router-link>
          <router-link to="/register" class="btn btn-primary">Daftar</router-link>
        </div>
      </div>
    </nav>

    <!-- Hero -->
    <section class="hero">
      <div class="hero-bg"></div>
      <div class="hero-content">
        <h1>Indonesia Smart School</h1>
        <p class="hero-subtitle">Sistem Manajemen Sekolah Terintegrasi untuk seluruh sekolah dan madrasah di Indonesia</p>
        <p class="hero-desc">
          Kelola profil institusi, data siswa, guru, fasilitas, kelas, laporan, dan surat-menyurat dalam satu platform. Mulai digitalisasi sekolah Anda sekarang.
        </p>
        <div class="hero-actions">
          <router-link to="/register" class="btn btn-primary btn-lg">Daftar Sekolah Anda</router-link>
          <router-link to="/login" class="btn btn-secondary btn-lg">Masuk</router-link>
        </div>
      </div>
    </section>

    <!-- Fitur -->
    <section id="fitur" class="section features-section">
      <div class="section-inner">
        <h2 class="section-title">Fitur Lengkap</h2>
        <p class="section-subtitle">Semua yang Anda butuhkan untuk mengelola sekolah dan madrasah dalam satu tempat</p>
        <div class="features-grid">
          <div class="feature-card">
            <div class="feature-icon">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21Z" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h3>Profil Sekolah/Madrasah</h3>
            <p>Kelola informasi dan profil institusi pendidikan dengan mudah</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="12" cy="7" r="4" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h3>Data Siswa</h3>
            <p>Kelola data siswa secara terpusat dan terorganisir</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="9" cy="7" r="4" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h3>Data Guru</h3>
            <p>Kelola informasi dan data guru dengan sistem terintegrasi</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 9L12 2L21 9V20C21 20.5304 20.7893 21.0391 20.4142 21.4142C20.0391 21.7893 19.5304 22 19 22H5C4.46957 22 3.96086 21.7893 3.58579 21.4142C3.21071 21.0391 3 20.5304 3 20V9Z" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M9 22V12H15V22" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h3>Fasilitas & Inventori</h3>
            <p>Daftar fasilitas dan inventori sekolah dalam satu tempat</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 19.5C4 18.837 4.26339 18.2011 4.73223 17.7322C5.20107 17.2634 5.83696 17 6.5 17H20" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M6.5 2H20V22H6.5C5.83696 22 5.20107 21.7366 4.73223 21.2678C4.26339 20.7989 4 20.163 4 19.5V4.5C4 3.83696 4.26339 3.20107 4.73223 2.73223C5.20107 2.26339 5.83696 2 6.5 2Z" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M8 7H16" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M8 11H16" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h3>Kelas & Laporan</h3>
            <p>Manajemen kelas dan laporan akademik terintegrasi</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M14 2V8H20" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M16 13H8" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M16 17H8" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M10 9H8" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h3>Surat-menyurat</h3>
            <p>Kelola surat masuk dan keluar dengan rapi</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Mengapa memilih kami -->
    <section id="mengapa" class="section why-section">
      <div class="section-inner">
        <h2 class="section-title">Mengapa Memilih Kami</h2>
        <p class="section-subtitle">Platform yang dirancang untuk kebutuhan sekolah dan madrasah di Indonesia</p>
        <div class="why-grid">
          <div class="why-card">
            <div class="why-icon">🔒</div>
            <h3>Data Aman</h3>
            <p>Data institusi dan siswa dikelola dengan keamanan yang terjamin</p>
          </div>
          <div class="why-card">
            <div class="why-icon">⚡</div>
            <h3>Terintegrasi</h3>
            <p>Semua modul terhubung dalam satu sistem, mengurangi duplikasi data</p>
          </div>
          <div class="why-card">
            <div class="why-icon">📱</div>
            <h3>Responsif</h3>
            <p>Akses dari desktop, tablet, atau ponsel kapan saja</p>
          </div>
          <div class="why-card">
            <div class="why-icon">🏫</div>
            <h3>Multi-institusi</h3>
            <p>Mendukung berbagai jenjang dan jenis lembaga pendidikan</p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA akhir -->
    <section class="section cta-section">
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
      <div class="footer-inner">
        <div class="footer-brand">
          <div class="footer-logo">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 2L2 7L12 12L22 7L12 2Z" fill="#667eea"/>
              <path d="M2 17L12 22L22 17" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M2 12L12 17L22 12" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <span>Indonesia Smart School</span>
          <p class="footer-tagline">Sistem Manajemen Sekolah Terintegrasi</p>
        </div>
        <div class="footer-links">
          <router-link to="/login">Masuk</router-link>
          <router-link to="/register">Daftar</router-link>
        </div>
        <div class="footer-bottom">
          <p>&copy; {{ currentYear }} Indonesia Smart School. All rights reserved.</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const currentYear = computed(() => new Date().getFullYear())
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
  padding: 14px 24px;
  padding-left: max(24px, env(safe-area-inset-left));
  padding-right: max(24px, env(safe-area-inset-right));
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  flex-wrap: wrap;
}

.navbar-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  text-decoration: none;
  color: #1e293b;
  font-weight: 600;
  font-size: 18px;
}

.navbar-brand:hover {
  color: #667eea;
}

.navbar-logo svg {
  flex-shrink: 0;
}

.navbar-title {
  white-space: nowrap;
}

.navbar-links {
  display: flex;
  gap: 24px;
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
  color: #667eea;
}

.navbar-actions {
  display: flex;
  gap: 12px;
  align-items: center;
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
  color: #667eea;
}

.btn-primary {
  background: #667eea;
  color: white;
}

.btn-primary:hover {
  background: #5568d3;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.25);
  transform: translateY(-1px);
}

.btn-secondary {
  background: white;
  color: #667eea;
  border: 1.5px solid #667eea;
}

.btn-secondary:hover {
  background: #f8fafc;
  border-color: #5568d3;
  color: #5568d3;
  transform: translateY(-1px);
}

.btn-lg {
  padding: 14px 28px;
  font-size: 16px;
}

/* Hero */
.hero {
  position: relative;
  padding: 72px 24px 80px;
  padding-left: max(24px, env(safe-area-inset-left));
  padding-right: max(24px, env(safe-area-inset-right));
  text-align: center;
  overflow: hidden;
}

.hero-bg {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 50%, #f8fafc 100%);
  z-index: 0;
}

.hero-content {
  position: relative;
  z-index: 1;
  max-width: 720px;
  margin: 0 auto;
}

.hero h1 {
  font-size: 42px;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 16px;
  letter-spacing: -0.02em;
  line-height: 1.2;
}

.hero-subtitle {
  font-size: 20px;
  color: #64748b;
  margin-bottom: 16px;
  font-weight: 500;
}

.hero-desc {
  font-size: 17px;
  color: #475569;
  line-height: 1.7;
  margin-bottom: 32px;
}

.hero-actions {
  display: flex;
  gap: 16px;
  justify-content: center;
  flex-wrap: wrap;
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

/* Features */
.features-section {
  background: #f8fafc;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 24px;
}

.feature-card {
  padding: 32px 24px;
  background: #ffffff;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  transition: all 0.2s ease;
}

.feature-card:hover {
  border-color: #cbd5e1;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
}

.feature-icon {
  display: flex;
  justify-content: center;
  margin-bottom: 20px;
}

.feature-icon svg {
  color: #667eea;
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
  transition: all 0.2s ease;
}

.why-card:hover {
  border-color: #cbd5e1;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
}

.why-icon {
  font-size: 32px;
  margin-bottom: 16px;
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
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 64px 24px;
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
  color: #667eea;
}

.cta-section .btn-primary:hover {
  background: #f1f5f9;
  color: #5568d3;
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

/* Footer */
.footer {
  background: #1e293b;
  color: #94a3b8;
  padding: 48px 24px 24px;
  padding-bottom: max(24px, env(safe-area-inset-bottom));
  padding-left: max(24px, env(safe-area-inset-left));
  padding-right: max(24px, env(safe-area-inset-right));
}

.footer-inner {
  max-width: 1100px;
  margin: 0 auto;
}

.footer-brand {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  margin-bottom: 32px;
}

.footer-logo {
  margin-bottom: 12px;
}

.footer-brand span {
  font-size: 18px;
  font-weight: 600;
  color: #f1f5f9;
}

.footer-tagline {
  font-size: 14px;
  color: #94a3b8;
  margin: 4px 0 0;
}

.footer-links {
  display: flex;
  justify-content: center;
  gap: 24px;
  margin-bottom: 32px;
}

.footer-links a {
  color: #94a3b8;
  text-decoration: none;
  font-size: 14px;
}

.footer-links a:hover {
  color: #ffffff;
}

.footer-bottom {
  text-align: center;
  padding-top: 24px;
  border-top: 1px solid #334155;
}

.footer-bottom p {
  font-size: 13px;
  color: #64748b;
  margin: 0;
}

/* ========== Responsive: Tablet (768px - 1024px) ========== */
@media (max-width: 1024px) {
  .section {
    padding: 56px 24px;
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

  .navbar-inner {
    padding: 12px 20px;
    padding-left: max(20px, env(safe-area-inset-left));
    padding-right: max(20px, env(safe-area-inset-right));
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

  .section-title {
    font-size: 24px;
  }

  .section-subtitle {
    font-size: 16px;
    margin-bottom: 32px;
  }

  .features-grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }

  .why-grid {
    grid-template-columns: 1fr;
    gap: 16px;
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
    padding: 40px 20px 24px;
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
    white-space: normal;
    line-height: 1.3;
  }

  .navbar-actions {
    gap: 8px;
  }

  .navbar-actions .btn {
    padding: 10px 16px;
    min-height: 44px;
    font-size: 14px;
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

  .section-title {
    font-size: 22px;
  }

  .section-subtitle {
    font-size: 15px;
    margin-bottom: 28px;
  }

  .feature-card,
  .why-card {
    padding: 20px 16px;
  }

  .feature-card h3,
  .why-card h3 {
    font-size: 16px;
  }

  .feature-card p,
  .why-card p {
    font-size: 14px;
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
    padding: 32px 16px 24px;
    padding-left: max(16px, env(safe-area-inset-left));
    padding-right: max(16px, env(safe-area-inset-right));
    padding-bottom: max(24px, env(safe-area-inset-bottom));
  }

  .footer-brand span {
    font-size: 16px;
  }

  .footer-tagline {
    font-size: 13px;
  }

  .footer-links a {
    padding: 8px 0;
    min-height: 44px;
    display: inline-flex;
    align-items: center;
    -webkit-tap-highlight-color: transparent;
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
