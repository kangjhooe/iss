<template>
  <footer ref="sectionRef" class="footer reveal-section">
    <div class="footer-inner">
      <div class="footer-top">
        <div class="footer-brand">
          <img
            v-if="institution?.logo_url"
            :src="institution.logo_url"
            alt=""
            class="footer-logo"
          />
          <div>
            <span class="footer-name">{{ institution?.name }}</span>
            <p v-if="institution?.npsn" class="footer-npsn">NPSN {{ institution.npsn }}</p>
          </div>
        </div>

        <nav class="footer-links" aria-label="Tautan footer">
          <a href="#tentang">Tentang</a>
          <a href="#layanan">Layanan</a>
          <a href="#kontak">Lokasi</a>
          <router-link :to="`/${npsn}/buku-tamu`">Buku Tamu</router-link>
          <router-link v-if="admissionOpen" :to="`/${npsn}/daftar-ppdb`">{{ admissionLabel }}</router-link>
          <router-link to="/login">Masuk</router-link>
        </nav>
      </div>

      <div class="footer-bottom">
        <p>&copy; {{ currentYear }} {{ institution?.name }}</p>
        <router-link to="/" class="footer-home">Beranda {{ appName }}</router-link>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { computed, ref } from 'vue'
import { appName } from '@/config/app'

defineProps({
  institution: { type: Object, default: null },
  npsn: { type: String, default: '' },
  admissionOpen: { type: Boolean, default: false },
  admissionLabel: { type: String, default: 'PPDB' },
})

const currentYear = computed(() => new Date().getFullYear())
const sectionRef = ref(null)
defineExpose({ sectionRef })
</script>

<style scoped>
.footer {
  background: #0f172a;
  color: #94a3b8;
  padding: 48px 24px 28px;
  margin-top: 0;
  position: relative;
  z-index: 1;
}

.footer-inner {
  max-width: 1000px;
  margin: 0 auto;
}

.footer-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 2rem;
  flex-wrap: wrap;
  padding-bottom: 1.75rem;
  border-bottom: 1px solid rgba(148, 163, 184, 0.15);
}

.footer-brand {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.footer-logo {
  width: 44px;
  height: 44px;
  object-fit: contain;
  border-radius: 10px;
  background: #fff;
}

.footer-name {
  font-weight: 700;
  font-size: 1.05rem;
  color: #f1f5f9;
  display: block;
  letter-spacing: -0.02em;
  line-height: 1.3;
}

.footer-npsn {
  font-size: 0.8125rem;
  margin: 0.25rem 0 0;
  color: #94a3b8;
}

.footer-links {
  display: flex;
  gap: 1.25rem 1.5rem;
  flex-wrap: wrap;
  align-items: center;
}

.footer-links a {
  color: #94a3b8;
  text-decoration: none;
  font-size: 0.9rem;
  font-weight: 500;
  transition: color 0.2s;
}

.footer-links a:hover {
  color: #e2e8f0;
}

.footer-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
  padding-top: 1.25rem;
}

.footer-bottom p {
  margin: 0;
  font-size: 0.8125rem;
  color: #64748b;
}

.footer-home {
  font-size: 0.8125rem;
  color: #64748b;
  text-decoration: none;
  font-weight: 500;
}

.footer-home:hover {
  color: #94a3b8;
}

.reveal-section {
  opacity: 0;
  transform: translateY(12px);
  transition: opacity 0.5s ease, transform 0.5s ease;
}

.reveal-section.is-visible {
  opacity: 1;
  transform: translateY(0);
}

@media (max-width: 640px) {
  .footer-top {
    flex-direction: column;
  }
}
</style>
