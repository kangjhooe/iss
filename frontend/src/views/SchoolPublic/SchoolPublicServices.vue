<template>
  <section id="layanan" ref="sectionRef" class="section services-section reveal-section">
    <div class="section-inner">
      <header class="section-header">
        <p class="section-eyebrow">Layanan</p>
        <h2 class="section-title">Akses cepat</h2>
        <p class="section-lead">Layanan publik yang dapat Anda gunakan tanpa login.</p>
      </header>

      <div class="services-grid">
        <router-link
          v-if="admissionOpen"
          :to="`/${npsn}/daftar-ppdb`"
          class="service-card"
        >
          <span class="service-icon" aria-hidden="true">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
              <circle cx="9" cy="7" r="4"/>
              <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
          </span>
          <h3 class="service-title">Daftar {{ admissionLabel }}</h3>
          <p class="service-desc">Pendaftaran peserta didik baru secara online.</p>
          <span class="service-cta">Mulai daftar →</span>
        </router-link>

        <router-link :to="`/${npsn}/ebooks`" class="service-card">
          <span class="service-icon" aria-hidden="true">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
              <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
            </svg>
          </span>
          <h3 class="service-title">Perpustakaan Digital</h3>
          <p class="service-desc">Katalog ebook dan koleksi digital sekolah.</p>
          <span class="service-cta">Buka katalog →</span>
        </router-link>

        <router-link :to="`/${npsn}/buku-tamu`" class="service-card">
          <span class="service-icon" aria-hidden="true">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
              <path d="M14 2v6h6"/>
              <path d="M16 13H8"/>
              <path d="M16 17H8"/>
              <path d="M10 9H8"/>
            </svg>
          </span>
          <h3 class="service-title">Buku Tamu</h3>
          <p class="service-desc">Catat kunjungan Anda ke sekolah secara digital.</p>
          <span class="service-cta">Isi form →</span>
        </router-link>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref } from 'vue'

defineProps({
  npsn: { type: String, default: '' },
  admissionOpen: { type: Boolean, default: false },
  admissionLabel: { type: String, default: 'PPDB' },
})

const sectionRef = ref(null)
defineExpose({ sectionRef })
</script>

<style scoped>
.section {
  padding: 72px 24px;
  position: relative;
  z-index: 1;
}

.section-inner {
  max-width: 1000px;
  margin: 0 auto;
}

.section-header {
  margin-bottom: 2rem;
  text-align: center;
}

.section-eyebrow {
  margin: 0 0 0.35rem;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: #059669;
}

.section-title {
  margin: 0 0 0.5rem;
  font-size: clamp(1.5rem, 3vw, 1.875rem);
  font-weight: 700;
  color: #0f172a;
  letter-spacing: -0.02em;
}

.section-lead {
  margin: 0 auto;
  max-width: 28rem;
  font-size: 0.975rem;
  color: #64748b;
  line-height: 1.55;
}

.services-section {
  background: #f8fafc;
}

.services-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.25rem;
}

.service-card {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  padding: 1.5rem 1.4rem;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  text-decoration: none;
  color: inherit;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.service-card:hover {
  border-color: rgba(5, 150, 105, 0.45);
  box-shadow: 0 10px 28px -12px rgba(15, 23, 42, 0.12);
}

.service-card:hover .service-cta {
  color: #047857;
}

.service-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  margin-bottom: 1rem;
  border-radius: 10px;
  background: #ecfdf5;
  color: #059669;
}

.service-title {
  margin: 0 0 0.4rem;
  font-size: 1.0625rem;
  font-weight: 700;
  color: #0f172a;
}

.service-desc {
  margin: 0 0 1rem;
  flex: 1;
  font-size: 0.9rem;
  line-height: 1.5;
  color: #64748b;
}

.service-cta {
  font-size: 0.875rem;
  font-weight: 600;
  color: #059669;
  transition: color 0.2s;
}

.reveal-section {
  opacity: 0;
  transform: translateY(16px);
  transition: opacity 0.55s ease, transform 0.55s ease;
}

.reveal-section.is-visible {
  opacity: 1;
  transform: translateY(0);
}

@media (max-width: 800px) {
  .services-grid {
    grid-template-columns: 1fr;
  }
  .section {
    padding: 56px 20px;
  }
}
</style>
