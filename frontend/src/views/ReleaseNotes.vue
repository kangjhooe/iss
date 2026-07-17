<template>
  <div class="release-page">
    <nav class="navbar">
      <div class="navbar-inner">
        <router-link to="/" class="navbar-brand">
          <div class="navbar-logo">
            <AppLogo :size="36" />
          </div>
          <span class="navbar-title">{{ appName }}</span>
        </router-link>
        <div class="navbar-actions">
          <router-link to="/" class="nav-link">Beranda</router-link>
          <router-link to="/login" class="btn btn-ghost">Masuk</router-link>
          <router-link to="/register" class="btn btn-primary">Daftar</router-link>
        </div>
      </div>
    </nav>

    <main class="release-main">
      <section class="release-hero">
        <div class="hero-glow hero-glow--one"></div>
        <div class="hero-glow hero-glow--two"></div>
        <div class="release-inner release-hero__inner">
          <div class="release-eyebrow">
            <span class="eyebrow-dot"></span>
            Terus bertumbuh bersama Anda
          </div>
          <h1>Setiap pembaruan,<br><span>selangkah yang lebih baik.</span></h1>
          <p>Rasakan fitur baru, peningkatan performa, dan penyempurnaan terbaru yang kami hadirkan untuk {{ appName }}.</p>
          <div v-if="!loading && releases.length" class="hero-summary">
            <div class="hero-summary__item">
              <strong>{{ releases.length }}</strong>
              <span>rilis tercatat</span>
            </div>
            <div class="hero-summary__divider"></div>
            <div class="hero-summary__item">
              <strong>{{ releases[0]?.version ? `v${releases[0].version}` : 'Terbaru' }}</strong>
              <span>versi terkini</span>
            </div>
          </div>
        </div>
      </section>

      <div class="release-inner release-content">
        <div v-if="loading" class="release-loading" aria-busy="true" aria-label="Memuat catatan rilis">
          <div class="toolbar-skeleton skel"></div>
          <div v-for="i in 3" :key="i" class="release-card release-card--skeleton">
            <div class="skel skel-meta"></div>
            <div class="skel skel-title"></div>
            <div class="skel skel-line"></div>
            <div class="skel skel-line short"></div>
          </div>
        </div>

        <div v-else-if="error" class="release-empty">
          <div class="empty-icon empty-icon--error">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 9v4m0 4h.01M10.3 3.8 2.2 18a2 2 0 0 0 1.7 3h16.2a2 2 0 0 0 1.7-3L13.7 3.8a2 2 0 0 0-3.4 0Z"/></svg>
          </div>
          <h2>Gagal memuat pembaruan</h2>
          <p>{{ error }}</p>
          <button type="button" class="btn btn-primary" @click="loadReleases">Coba lagi</button>
        </div>

        <div v-else-if="releases.length === 0" class="release-empty">
          <div class="empty-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"/></svg>
          </div>
          <h2>Belum ada catatan rilis</h2>
          <p>Pembaruan aplikasi berikutnya akan ditampilkan di sini.</p>
        </div>

        <template v-else>
          <div class="release-toolbar">
            <div>
              <span class="section-kicker">Riwayat pembaruan</span>
              <h2>Yang terbaru dari {{ appName }}</h2>
            </div>
            <div class="release-controls">
              <label class="search-box">
                <span class="sr-only">Cari catatan rilis</span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                <input v-model.trim="searchQuery" type="search" placeholder="Cari pembaruan..." />
              </label>
              <select v-if="releaseYears.length > 1" v-model="activeYear" class="year-select" aria-label="Filter berdasarkan tahun">
                <option value="">Semua tahun</option>
                <option v-for="year in releaseYears" :key="year" :value="year">{{ year }}</option>
              </select>
            </div>
          </div>

          <ol v-if="filteredReleases.length" class="release-timeline">
            <li
              v-for="(item, index) in filteredReleases"
              :key="item.id"
              class="release-card"
              :class="{ 'release-card--latest': index === 0 && !searchQuery && !activeYear }"
            >
              <div class="timeline-marker">
                <svg v-if="index === 0 && !searchQuery && !activeYear" viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 2.2 4.5 5 .7-3.6 3.5.9 5-4.5-2.4-4.5 2.4.9-5-3.6-3.5 5-.7L12 3Z"/></svg>
                <span v-else></span>
              </div>
              <div class="release-card__top">
                <div class="release-meta">
                  <span v-if="index === 0 && !searchQuery && !activeYear" class="latest-label">Rilis terbaru</span>
                  <span class="release-date">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>
                    <time :datetime="item.released_at">{{ formatDate(item.released_at) }}</time>
                  </span>
                </div>
                <span v-if="item.version" class="release-version">v{{ item.version }}</span>
              </div>
              <h3 class="release-title">{{ item.title }}</h3>
              <ul class="release-items">
                <li v-for="(point, idx) in item.items" :key="idx">
                  <span class="check-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 12 3 3 7-7"/></svg>
                  </span>
                  <span>{{ point }}</span>
                </li>
              </ul>
            </li>
          </ol>

          <div v-else class="release-empty release-empty--compact">
            <div class="empty-icon">
              <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
            </div>
            <h2>Pembaruan tidak ditemukan</h2>
            <p>Coba gunakan kata kunci atau tahun yang berbeda.</p>
            <button type="button" class="btn btn-ghost btn-reset" @click="resetFilters">Reset pencarian</button>
          </div>
        </template>
      </div>
    </main>

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
          <span class="footer-version">v{{ appVersion }}</span>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import { appName, appTagline, appVersion } from '@/config/app'
import AppLogo from '@/components/AppLogo.vue'
import { releasesApi } from '@/api/releases'

const currentYear = computed(() => new Date().getFullYear())
const loading = ref(true)
const error = ref('')
const releases = ref([])
const searchQuery = ref('')
const activeYear = ref('')

const releaseYears = computed(() => [...new Set(
  releases.value
    .map(item => item.released_at?.slice(0, 4))
    .filter(Boolean)
)].sort((a, b) => b.localeCompare(a)))

const filteredReleases = computed(() => {
  const query = searchQuery.value.toLocaleLowerCase('id-ID')

  return releases.value.filter((item) => {
    const matchesYear = !activeYear.value || item.released_at?.startsWith(activeYear.value)
    const searchableText = [item.title, item.version, ...(item.items || [])]
      .filter(Boolean)
      .join(' ')
      .toLocaleLowerCase('id-ID')

    return matchesYear && (!query || searchableText.includes(query))
  })
})

const formatDate = (iso) => {
  if (!iso) return ''
  return new Date(iso + 'T00:00:00').toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
}

const loadReleases = async () => {
  loading.value = true
  error.value = ''
  try {
    const res = await releasesApi.getPublicReleases({ per_page: 50 })
    releases.value = res.data?.data || []
  } catch (err) {
    error.value = err.response?.data?.message || 'Tidak dapat memuat catatan rilis'
    releases.value = []
  } finally {
    loading.value = false
  }
}

const resetFilters = () => {
  searchQuery.value = ''
  activeYear.value = ''
}

onMounted(loadReleases)
</script>

<style scoped>
.release-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #f8fafc;
  color: #0f172a;
}

.navbar {
  position: sticky;
  top: 0;
  z-index: 50;
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(10px);
  border-bottom: 1px solid #e2e8f0;
}

.navbar-inner {
  max-width: 960px;
  margin: 0 auto;
  padding: 14px 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
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
  color: #059669;
}

.navbar-actions {
  display: flex;
  gap: 12px;
  align-items: center;
}

.nav-link {
  color: #64748b;
  text-decoration: none;
  font-size: 15px;
  font-weight: 500;
  padding: 8px 4px;
}

.nav-link:hover {
  color: #059669;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 10px 18px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
  border: none;
  cursor: pointer;
  transition: background 0.2s, color 0.2s;
}

.btn-ghost {
  background: transparent;
  color: #475569;
}

.btn-ghost:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.btn-primary {
  background: #059669;
  color: #fff;
}

.btn-primary:hover {
  background: #047857;
}

.release-main {
  flex: 1;
  padding: 48px 24px 64px;
}

.release-inner {
  max-width: 720px;
  margin: 0 auto;
}

.release-header {
  margin-bottom: 32px;
}

.release-header h1 {
  margin: 0 0 8px;
  font-size: 32px;
  font-weight: 700;
  letter-spacing: -0.02em;
  color: #0f172a;
}

.release-header p {
  margin: 0;
  color: #64748b;
  font-size: 16px;
}

.release-timeline {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 16px;
  border-left: 2px solid #d1fae5;
  padding-left: 24px;
}

.release-card {
  position: relative;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 20px 24px;
}

.release-card::before {
  content: '';
  position: absolute;
  left: -33px;
  top: 28px;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: #059669;
  border: 3px solid #ecfdf5;
}

.release-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
  margin-bottom: 8px;
  font-size: 13px;
  color: #64748b;
}

.release-version {
  display: inline-flex;
  align-items: center;
  padding: 2px 8px;
  border-radius: 999px;
  background: #ecfdf5;
  color: #047857;
  font-weight: 600;
  font-size: 12px;
}

.release-title {
  margin: 0 0 12px;
  font-size: 18px;
  font-weight: 650;
  color: #0f172a;
}

.release-items {
  margin: 0;
  padding-left: 18px;
  color: #334155;
  font-size: 15px;
  line-height: 1.55;
}

.release-items li + li {
  margin-top: 6px;
}

.release-empty {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 40px 24px;
  text-align: center;
}

.release-empty h2 {
  margin: 0 0 8px;
  font-size: 20px;
}

.release-empty p {
  margin: 0 0 16px;
  color: #64748b;
}

.release-loading {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.release-card--skeleton {
  border-left: none;
  padding-left: 24px;
}

.release-card--skeleton::before {
  display: none;
}

.skel {
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.2s ease-in-out infinite;
  border-radius: 6px;
  height: 14px;
  margin-bottom: 10px;
}

.skel-meta { width: 40%; height: 12px; }
.skel-title { width: 70%; height: 18px; }
.skel-line { width: 90%; }
.skel-line.short { width: 55%; margin-bottom: 0; }

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

.footer {
  background: #0f172a;
  color: #cbd5e1;
  margin-top: auto;
}

.footer-accent {
  height: 3px;
  background: linear-gradient(90deg, #059669, #34d399);
}

.footer-inner {
  max-width: 960px;
  margin: 0 auto;
  padding: 28px 24px;
}

.footer-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}

.footer-name {
  display: block;
  font-weight: 600;
  color: #f8fafc;
}

.footer-tagline {
  margin: 2px 0 0;
  font-size: 13px;
  color: #94a3b8;
}

.footer-bottom {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
  font-size: 13px;
  color: #94a3b8;
}

.footer-sep {
  opacity: 0.6;
}

.footer-version {
  color: #a7f3d0;
}

.release-page {
  --green-700: #047857;
  --green-600: #059669;
  --green-500: #10b981;
  --green-100: #d1fae5;
  --green-50: #ecfdf5;
  background: #f7f9f8;
}

.release-main {
  padding: 0 24px 80px;
  overflow: hidden;
}

.release-inner {
  max-width: 800px;
}

.release-hero {
  position: relative;
  width: calc(100% + 48px);
  margin-left: -24px;
  overflow: hidden;
  border-bottom: 1px solid rgba(5, 150, 105, 0.12);
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.35), rgba(236, 253, 245, 0.72)),
    radial-gradient(circle at 15% 20%, rgba(167, 243, 208, 0.6), transparent 38%);
}

.release-hero::after {
  content: '';
  position: absolute;
  inset: 0;
  opacity: 0.28;
  background-image: radial-gradient(rgba(5, 150, 105, 0.22) 0.7px, transparent 0.7px);
  background-size: 16px 16px;
  mask-image: linear-gradient(to bottom, black, transparent 78%);
  pointer-events: none;
}

.release-hero__inner {
  position: relative;
  z-index: 2;
  padding: 72px 0 64px;
  text-align: center;
}

.hero-glow {
  position: absolute;
  border-radius: 999px;
  filter: blur(8px);
  pointer-events: none;
}

.hero-glow--one {
  width: 280px;
  height: 280px;
  top: -160px;
  right: 5%;
  background: rgba(52, 211, 153, 0.18);
}

.hero-glow--two {
  width: 180px;
  height: 180px;
  left: 7%;
  bottom: -120px;
  background: rgba(16, 185, 129, 0.13);
}

.release-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 20px;
  padding: 7px 12px;
  border: 1px solid rgba(5, 150, 105, 0.18);
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.72);
  color: var(--green-700);
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  box-shadow: 0 6px 24px rgba(5, 150, 105, 0.06);
}

.eyebrow-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: var(--green-500);
  box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.12);
}

.release-hero h1 {
  margin: 0 auto 18px;
  max-width: 680px;
  color: #0f172a;
  font-size: clamp(36px, 5vw, 54px);
  font-weight: 780;
  line-height: 1.08;
  letter-spacing: -0.045em;
}

.release-hero h1 span {
  color: var(--green-600);
}

.release-hero p {
  max-width: 610px;
  margin: 0 auto;
  color: #526174;
  font-size: 17px;
  line-height: 1.7;
}

.hero-summary {
  display: inline-flex;
  align-items: center;
  gap: 20px;
  margin-top: 32px;
  padding: 12px 18px;
  border: 1px solid rgba(5, 150, 105, 0.14);
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.72);
  box-shadow: 0 12px 35px rgba(15, 23, 42, 0.06);
  backdrop-filter: blur(10px);
}

.hero-summary__item {
  display: flex;
  align-items: baseline;
  gap: 7px;
}

.hero-summary__item strong {
  color: #0f172a;
  font-size: 16px;
}

.hero-summary__item span {
  color: #64748b;
  font-size: 12px;
}

.hero-summary__divider {
  width: 1px;
  height: 22px;
  background: #dbe7e2;
}

.release-content {
  padding-top: 52px;
}

.release-toolbar {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 24px;
  margin-bottom: 30px;
}

.section-kicker {
  display: block;
  margin-bottom: 6px;
  color: var(--green-600);
  font-size: 12px;
  font-weight: 750;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.release-toolbar h2 {
  margin: 0;
  color: #0f172a;
  font-size: 25px;
  letter-spacing: -0.025em;
}

.release-controls {
  display: flex;
  gap: 10px;
}

.search-box {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 210px;
  height: 42px;
  padding: 0 12px;
  border: 1px solid #dce4e1;
  border-radius: 11px;
  background: #fff;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.search-box:focus-within {
  border-color: #6ee7b7;
  box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
}

.search-box svg,
.release-date svg {
  width: 17px;
  flex: 0 0 auto;
  fill: none;
  stroke: currentColor;
  stroke-width: 1.8;
  stroke-linecap: round;
  stroke-linejoin: round;
  color: #94a3b8;
}

.search-box input {
  width: 100%;
  padding: 0;
  border: 0;
  outline: 0;
  background: transparent;
  color: #1e293b;
  font: inherit;
  font-size: 13px;
}

.search-box input::placeholder {
  color: #94a3b8;
}

.year-select {
  height: 42px;
  padding: 0 30px 0 12px;
  border: 1px solid #dce4e1;
  border-radius: 11px;
  outline: none;
  background: #fff;
  color: #475569;
  font: inherit;
  font-size: 13px;
}

.year-select:focus {
  border-color: #6ee7b7;
  box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
}

.release-timeline {
  gap: 20px;
  padding-left: 35px;
  border-left: 1px solid #cceadd;
}

.release-card {
  border-color: #e0e8e5;
  border-radius: 18px;
  padding: 26px 28px 27px;
  box-shadow: 0 5px 18px rgba(15, 23, 42, 0.025);
  transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
}

.release-card:hover {
  transform: translateY(-2px);
  border-color: #b9ddce;
  box-shadow: 0 14px 36px rgba(15, 23, 42, 0.07);
}

.release-card::before {
  display: none;
}

.release-card--latest {
  border-color: rgba(5, 150, 105, 0.28);
  background:
    linear-gradient(135deg, rgba(236, 253, 245, 0.72), transparent 42%),
    #fff;
  box-shadow: 0 14px 40px rgba(5, 150, 105, 0.08);
}

.timeline-marker {
  position: absolute;
  z-index: 2;
  left: -47px;
  top: 28px;
  display: grid;
  width: 22px;
  height: 22px;
  place-items: center;
  border: 4px solid #f7f9f8;
  border-radius: 50%;
  background: #a7dcca;
}

.timeline-marker span {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--green-600);
}

.timeline-marker svg {
  width: 12px;
  fill: var(--green-600);
  stroke: var(--green-600);
  stroke-linejoin: round;
}

.release-card--latest .timeline-marker {
  background: #d1fae5;
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.release-card__top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 12px;
}

.release-meta {
  gap: 12px;
  margin: 0;
}

.latest-label {
  padding: 4px 8px;
  border-radius: 6px;
  background: var(--green-600);
  color: #fff;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.055em;
  text-transform: uppercase;
}

.release-date {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: #64748b;
}

.release-version {
  flex: 0 0 auto;
  padding: 5px 10px;
  border: 1px solid #c9f1df;
  background: var(--green-50);
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 11px;
  letter-spacing: 0.01em;
}

.release-title {
  margin: 0 0 17px;
  font-size: 21px;
  font-weight: 720;
  letter-spacing: -0.02em;
}

.release-items {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 0;
  list-style: none;
  line-height: 1.6;
}

.release-items li {
  display: flex;
  align-items: flex-start;
  gap: 10px;
}

.release-items li + li {
  margin-top: 0;
}

.check-icon {
  display: grid;
  flex: 0 0 auto;
  width: 20px;
  height: 20px;
  margin-top: 2px;
  place-items: center;
  border-radius: 50%;
  background: var(--green-50);
  color: var(--green-600);
}

.check-icon svg {
  width: 13px;
  fill: none;
  stroke: currentColor;
  stroke-width: 2.2;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.release-empty {
  border-color: #e0e8e5;
  padding: 54px 24px;
  box-shadow: 0 8px 30px rgba(15, 23, 42, 0.035);
}

.release-empty--compact {
  padding: 42px 24px;
}

.empty-icon {
  display: grid;
  width: 48px;
  height: 48px;
  margin: 0 auto 16px;
  place-items: center;
  border-radius: 14px;
  background: var(--green-50);
  color: var(--green-600);
}

.empty-icon--error {
  background: #fff1f2;
  color: #e11d48;
}

.empty-icon svg {
  width: 23px;
  fill: none;
  stroke: currentColor;
  stroke-width: 1.8;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.btn-reset {
  border: 1px solid #dce4e1;
}

.release-loading {
  gap: 20px;
}

.toolbar-skeleton {
  width: 55%;
  height: 58px;
  margin-bottom: 10px;
}

.release-card--skeleton {
  min-height: 150px;
}

.skel {
  background: linear-gradient(90deg, #edf2f0 25%, #e0e9e5 50%, #edf2f0 75%);
  background-size: 200% 100%;
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

@media (max-width: 640px) {
  .release-main {
    padding: 0 16px 56px;
  }
  .release-hero {
    width: calc(100% + 32px);
    margin-left: -16px;
  }
  .release-hero__inner {
    padding: 52px 20px 48px;
  }
  .release-hero h1 {
    font-size: 36px;
  }
  .release-hero p {
    font-size: 15px;
  }
  .hero-summary {
    gap: 12px;
    padding: 11px 13px;
  }
  .hero-summary__item {
    display: grid;
    gap: 1px;
    text-align: left;
  }
  .release-content {
    padding-top: 38px;
  }
  .release-toolbar {
    align-items: stretch;
    flex-direction: column;
    gap: 18px;
  }
  .release-toolbar h2 {
    font-size: 22px;
  }
  .release-controls,
  .search-box {
    width: 100%;
  }
  .year-select {
    flex: 0 0 auto;
  }
  .release-timeline {
    padding-left: 23px;
  }
  .release-card {
    padding: 22px 20px;
  }
  .timeline-marker {
    left: -35px;
  }
  .release-card__top {
    gap: 10px;
  }
  .release-meta {
    align-items: flex-start;
    flex-direction: column;
    gap: 8px;
  }
  .release-title {
    font-size: 19px;
  }
  .release-items {
    font-size: 14px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .release-card,
  .btn,
  .skel {
    animation: none;
    transition: none;
  }
}
</style>
