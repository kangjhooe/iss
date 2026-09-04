<template>
  <div class="panduan-page">
    <PublicNavbar />

    <main class="panduan-main">
      <section class="panduan-hero">
        <div class="hero-glow hero-glow--one"></div>
        <div class="hero-glow hero-glow--two"></div>
        <div class="panduan-inner panduan-hero__inner">
          <div class="panduan-eyebrow">
            <span class="eyebrow-dot"></span>
            Dokumentasi penggunaan
          </div>
          <h1>Panduan menjalankan<br><span>{{ appName }}</span></h1>
          <p>
            Pilih peran Anda untuk melihat langkah-langkah memakai aplikasi.
            Panduan tersedia untuk Admin, Guru & Staf, Siswa, dan Orang Tua.
          </p>
        </div>
      </section>

      <div class="panduan-inner panduan-content">
        <div class="role-grid">
          <component
            :is="role.available ? 'router-link' : 'div'"
            v-for="role in guideRoles"
            :key="role.slug"
            :to="role.available ? role.to : undefined"
            class="role-card"
            :class="[
              `role-card--${role.accent}`,
              { 'role-card--disabled': !role.available }
            ]"
            @mouseenter="role.available && prefetchRole(role.slug)"
            @focus="role.available && prefetchRole(role.slug)"
          >
            <div class="role-card__top">
              <span class="role-badge" :class="`role-badge--${role.accent}`">
                {{ role.available ? 'Tersedia' : 'Segera hadir' }}
              </span>
            </div>
            <h2 class="role-title">{{ role.title }}</h2>
            <p class="role-desc">{{ role.desc }}</p>
            <span v-if="role.available" class="role-cta">Buka panduan →</span>
            <span v-else class="role-cta role-cta--muted">Belum tersedia</span>
          </component>
        </div>
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
          <router-link to="/catatan-rilis" class="footer-version">v{{ appVersion }}</router-link>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { appName, appTagline, appVersion } from '@/config/app'
import AppLogo from '@/components/AppLogo.vue'
import PublicNavbar from '@/components/PublicNavbar.vue'
import { guideRoles } from '@/content/guides'
import { usePageMeta } from '@/composables/usePageMeta'
import { absoluteUrl } from '@/utils/seo'

const currentYear = computed(() => new Date().getFullYear())
const { setMeta } = usePageMeta()

const PREFETCHERS = {
  admin: () => import('@/views/PanduanAdmin.vue'),
  guru: () => import('@/views/PanduanGuru.vue'),
  siswa: () => import('@/views/PanduanSiswa.vue'),
  'orang-tua': () => import('@/views/PanduanOrangTua.vue'),
}
const prefetched = new Set()

function prefetchRole(slug) {
  const load = PREFETCHERS[slug]
  if (!load || prefetched.has(slug)) return
  prefetched.add(slug)
  load().catch(() => {
    prefetched.delete(slug)
  })
}

onMounted(() => {
  setMeta({
    title: `Panduan · ${appName}`,
    description: `Panduan menjalankan ${appName} untuk admin, guru, siswa, dan orang tua.`,
    url: absoluteUrl('/panduan'),
  })
})
</script>

<style scoped>
.panduan-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #f8fafc;
  color: #0f172a;
}

.panduan-main {
  flex: 1;
}

.panduan-inner {
  max-width: 1080px;
  margin: 0 auto;
  padding: 0 20px;
}

.panduan-hero {
  position: relative;
  overflow: hidden;
  padding: 56px 0 40px;
  background: linear-gradient(165deg, #ecfdf5 0%, #f0fdfa 40%, #f8fafc 100%);
}

.hero-glow {
  position: absolute;
  border-radius: 999px;
  filter: blur(60px);
  pointer-events: none;
}

.hero-glow--one {
  width: 280px;
  height: 280px;
  top: -80px;
  right: 10%;
  background: rgba(45, 212, 191, 0.35);
}

.hero-glow--two {
  width: 220px;
  height: 220px;
  bottom: -60px;
  left: 5%;
  background: rgba(16, 185, 129, 0.2);
}

.panduan-hero__inner {
  position: relative;
}

.panduan-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 14px;
  padding: 6px 12px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.8);
  border: 1px solid #d1fae5;
  color: #0f766e;
  font-size: 0.8rem;
  font-weight: 600;
}

.eyebrow-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #10b981;
}

.panduan-hero h1 {
  margin: 0 0 12px;
  font-size: clamp(1.75rem, 4vw, 2.5rem);
  line-height: 1.2;
  letter-spacing: -0.03em;
  font-weight: 800;
}

.panduan-hero h1 span {
  color: #0f766e;
}

.panduan-hero p {
  margin: 0;
  max-width: 540px;
  color: #475569;
  font-size: 1.05rem;
  line-height: 1.6;
}

.panduan-content {
  padding-top: 28px;
  padding-bottom: 64px;
}

.role-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.role-card {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 22px 22px 20px;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  background: #fff;
  text-decoration: none;
  color: inherit;
  box-shadow: 0 4px 14px rgba(15, 23, 42, 0.03);
  transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}

a.role-card:hover {
  transform: translateY(-2px);
  border-color: #99f6e4;
  box-shadow: 0 10px 24px rgba(13, 148, 136, 0.1);
}

a.role-card--blue:hover {
  border-color: #93c5fd;
  box-shadow: 0 10px 24px rgba(37, 99, 235, 0.12);
}

.role-card--disabled {
  opacity: 0.72;
  cursor: default;
  background: #f8fafc;
}

.role-card__top {
  display: flex;
}

.role-badge {
  display: inline-flex;
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.02em;
  text-transform: uppercase;
}

.role-badge--teal {
  color: #0f766e;
  background: #ccfbf1;
}

.role-badge--blue {
  color: #1d4ed8;
  background: #dbeafe;
}

.role-badge--amber,
.role-badge--violet {
  color: #64748b;
  background: #e2e8f0;
}

.role-title {
  margin: 0;
  font-size: 1.2rem;
  font-weight: 750;
  letter-spacing: -0.02em;
}

.role-desc {
  margin: 0;
  color: #64748b;
  font-size: 0.95rem;
  line-height: 1.55;
  flex: 1;
}

.role-cta {
  margin-top: 6px;
  font-size: 0.9rem;
  font-weight: 650;
  color: #0f766e;
}

.role-card--blue .role-cta {
  color: #1d4ed8;
}

.role-cta--muted {
  color: #94a3b8;
}

.footer {
  margin-top: auto;
  border-top: 1px solid #e2e8f0;
  background: #fff;
}

.footer-accent {
  height: 3px;
  background: linear-gradient(90deg, #0d9488, #059669, #34d399);
}

.footer-inner {
  max-width: 1080px;
  margin: 0 auto;
  padding: 22px 20px 28px;
}

.footer-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 14px;
}

.footer-name {
  font-weight: 700;
}

.footer-tagline {
  margin: 2px 0 0;
  color: #64748b;
  font-size: 0.85rem;
}

.footer-bottom {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
  color: #64748b;
  font-size: 0.85rem;
}

.footer-sep {
  opacity: 0.5;
}

.footer-version {
  color: #0f766e;
  text-decoration: none;
  font-weight: 600;
}

@media (max-width: 720px) {
  .role-grid {
    grid-template-columns: 1fr;
  }

  .navbar-actions .nav-link {
    display: none;
  }

  .panduan-hero {
    padding: 40px 0 28px;
  }
}
</style>
