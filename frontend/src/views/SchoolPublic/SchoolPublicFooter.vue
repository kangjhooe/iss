<template>
  <footer ref="sectionRef" class="footer reveal-section">
    <div class="footer-inner">
      <div class="footer-brand">
        <span class="footer-name">{{ institution?.name }}</span>
        <p v-if="institution?.npsn" class="footer-npsn">NPSN {{ institution.npsn }}</p>
      </div>
      <div class="footer-links">
        <router-link to="/">Beranda</router-link>
        <router-link :to="`/${npsn}/daftar-ppdb`">Daftar PPDB</router-link>
        <router-link :to="{ path: '/cek-hasil-ppdb', query: { npsn } }">Cek Hasil PPDB</router-link>
        <a href="#buku-tamu">Buku Tamu</a>
        <a href="#kontak">Lokasi</a>
        <router-link to="/login">Masuk</router-link>
      </div>
      <div class="footer-bottom">
        <p>&copy; {{ currentYear }} {{ institution?.name }}</p>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { computed, ref } from 'vue'

defineProps({
  institution: { type: Object, default: null },
  npsn: { type: String, default: '' }
})

const currentYear = computed(() => new Date().getFullYear())
const sectionRef = ref(null)
defineExpose({ sectionRef })
</script>

<style scoped>
.footer { background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%); color: #94a3b8; padding: 48px 24px 28px; margin-top: 56px; position: relative; z-index: 1; }
.footer-inner { max-width: 1000px; margin: 0 auto; text-align: center; }
.footer-brand { margin-bottom: 1.25rem; }
.footer-name { font-weight: 700; font-size: 1.125rem; color: #f1f5f9; display: block; letter-spacing: -0.02em; }
.footer-npsn { font-size: 0.875rem; margin: 0.35rem 0 0; color: #94a3b8; }
.footer-links { display: flex; gap: 28px; justify-content: center; margin-bottom: 1.75rem; flex-wrap: wrap; }
.footer-links a { color: #94a3b8; text-decoration: none; transition: color 0.2s, transform 0.15s; font-weight: 500; }
.footer-links a:hover { color: #e2e8f0; transform: translateY(-1px); }
.footer-bottom p { margin: 0; font-size: 0.8125rem; color: #64748b; }
.reveal-section {
  opacity: 1;
  transform: translateY(0);
  transition: opacity 0.5s ease, transform 0.5s ease;
}
.reveal-section.is-visible {
  opacity: 1;
  transform: translateY(0);
}
</style>
