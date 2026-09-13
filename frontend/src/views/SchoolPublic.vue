<template>
  <div class="school-public-page">
    <a href="#main-content" class="skip-link">Langsung ke konten</a>

    <nav
      ref="navbarEl"
      class="navbar"
      :class="{ 'navbar--scrolled': scrolled, 'navbar--open': menuOpen }"
    >
      <div class="navbar-inner">
        <router-link :to="`/${npsn}`" class="navbar-brand" @click="closeMenu">
          <img v-if="institution?.logo_url" :src="institution.logo_url" alt="" class="navbar-logo-img" />
          <div v-else class="navbar-logo">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M9 22V12h6v10" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <span class="navbar-title">{{ institution?.name || 'Sekolah/Madrasah' }}</span>
        </router-link>

        <div class="navbar-links navbar-links--desktop">
          <a href="#tentang" class="nav-link">Tentang</a>
          <a href="#layanan" class="nav-link">Layanan</a>
          <a href="#berita" class="nav-link">Berita</a>
          <a href="#kontak" class="nav-link">Lokasi</a>
          <router-link :to="`/${npsn}/buku-tamu`" class="nav-link">Buku Tamu</router-link>
          <router-link
            v-if="admissionOpen"
            :to="`/${npsn}/daftar-ppdb`"
            class="nav-link"
          >{{ admissionLabel }}</router-link>
          <router-link to="/login" class="btn btn-primary">Masuk</router-link>
        </div>

        <button
          type="button"
          class="navbar-toggle"
          :aria-expanded="menuOpen"
          aria-controls="school-nav-drawer"
          :aria-label="menuOpen ? 'Tutup menu' : 'Buka menu'"
          @click="menuOpen = !menuOpen"
        >
          <span class="navbar-toggle-bar" :class="{ open: menuOpen }"></span>
          <span class="navbar-toggle-bar" :class="{ open: menuOpen }"></span>
          <span class="navbar-toggle-bar" :class="{ open: menuOpen }"></span>
        </button>
      </div>
    </nav>
    <div class="navbar-spacer" :style="{ height: `${navHeight}px` }" aria-hidden="true" />

    <Teleport to="body">
      <Transition name="school-nav-menu">
        <div
          v-if="menuOpen"
          class="navbar-backdrop"
          @click="closeMenu"
        />
      </Transition>
      <Transition name="school-nav-drawer">
        <div
          v-if="menuOpen"
          id="school-nav-drawer"
          class="navbar-drawer"
          role="dialog"
          aria-label="Menu navigasi"
        >
          <button type="button" class="navbar-drawer-close" aria-label="Tutup menu" @click="closeMenu">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
              <path d="M18 6L6 18M6 6l12 12" />
            </svg>
          </button>
          <a href="#tentang" class="navbar-drawer-link" @click="closeMenu">Tentang</a>
          <a href="#layanan" class="navbar-drawer-link" @click="closeMenu">Layanan</a>
          <a href="#berita" class="navbar-drawer-link" @click="closeMenu">Berita</a>
          <a href="#kontak" class="navbar-drawer-link" @click="closeMenu">Lokasi</a>
          <router-link :to="`/${npsn}/buku-tamu`" class="navbar-drawer-link" @click="closeMenu">Buku Tamu</router-link>
          <router-link
            v-if="admissionOpen"
            :to="`/${npsn}/daftar-ppdb`"
            class="navbar-drawer-link"
            @click="closeMenu"
          >{{ admissionLabel }}</router-link>
          <div class="navbar-drawer-auth">
            <router-link to="/login" class="btn btn-primary" @click="closeMenu">Masuk</router-link>
          </div>
        </div>
      </Transition>
    </Teleport>

    <div v-if="loading" class="skeleton-wrap" aria-busy="true" aria-label="Memuat data sekolah">
      <div class="skeleton-hero">
        <div class="skeleton-circle"></div>
        <div class="skeleton-line skeleton-line--lg"></div>
        <div class="skeleton-line skeleton-line--md"></div>
        <div class="skeleton-line skeleton-line--sm"></div>
      </div>
    </div>

    <div v-else-if="error" id="main-content" class="state-wrap state-error" tabindex="-1">
      <div class="state-icon state-icon-error">!</div>
      <h2>Sekolah/Madrasah tidak ditemukan</h2>
      <p>NPSN tidak valid atau sekolah/madrasah tidak aktif.</p>
      <router-link to="/" class="btn-outline">← Beranda</router-link>
    </div>

    <template v-else-if="institution">
      <SchoolPublicHero
        ref="heroRef"
        :institution="institution"
        :npsn="npsn"
        :admission-open="admissionOpen"
        :admission-label="admissionLabel"
      />

      <SchoolPublicAbout ref="aboutRef" :institution="institution" />
      <SchoolPublicServices
        ref="servicesRef"
        :npsn="npsn"
        :admission-open="admissionOpen"
        :admission-label="admissionLabel"
      />
      <SchoolPublicContent :npsn="npsn" />
      <SchoolPublicIdentity ref="identityRef" :institution="institution" />
      <SchoolPublicContact ref="contactRef" :institution="institution" />
      <SchoolPublicFooter
        ref="footerRef"
        :institution="institution"
        :npsn="npsn"
        :admission-open="admissionOpen"
        :admission-label="admissionLabel"
      />
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { schoolPublicApi } from '@/api/schoolPublic'
import { usePageMeta } from '@/composables/usePageMeta'
import { getInstitutionTypeLabel } from '@/utils/institution'
import SchoolPublicHero from './SchoolPublic/SchoolPublicHero.vue'
import SchoolPublicAbout from './SchoolPublic/SchoolPublicAbout.vue'
import SchoolPublicServices from './SchoolPublic/SchoolPublicServices.vue'
import SchoolPublicContent from './SchoolPublic/SchoolPublicContent.vue'
import SchoolPublicIdentity from './SchoolPublic/SchoolPublicIdentity.vue'
import SchoolPublicContact from './SchoolPublic/SchoolPublicContact.vue'
import SchoolPublicFooter from './SchoolPublic/SchoolPublicFooter.vue'

const route = useRoute()
const npsn = computed(() => route.params.npsn)
const pageMeta = usePageMeta()

const institution = ref(null)
const loading = ref(true)
const error = ref('')
const menuOpen = ref(false)
const navbarEl = ref(null)
const navHeight = ref(64)
const scrolled = ref(false)
let scrollRaf = 0
let resizeObserver = null

const admissionOpen = computed(() => !!institution.value?.admission_open)
const admissionLabel = computed(() => institution.value?.admission_label || 'PPDB')

const heroRef = ref(null)
const aboutRef = ref(null)
const servicesRef = ref(null)
const identityRef = ref(null)
const contactRef = ref(null)
const footerRef = ref(null)

let revealObserver = null

function closeMenu() {
  menuOpen.value = false
}

function measureNavHeight() {
  if (!navbarEl.value) return
  const next = Math.ceil(navbarEl.value.getBoundingClientRect().height)
  if (next > 0) navHeight.value = next
}

function onMenuKeydown(e) {
  if (e.key === 'Escape') closeMenu()
}

async function fetchInstitution() {
  if (!npsn.value) return
  loading.value = true
  error.value = ''
  menuOpen.value = false
  try {
    const res = await schoolPublicApi.getInstitution(npsn.value)
    const raw = res.data?.data ?? res.data
    if (!raw) {
      institution.value = null
      return
    }
    institution.value = { ...raw }
  } catch (e) {
    const msg = e.response?.data?.message || 'Gagal memuat data sekolah.'
    error.value = msg
    institution.value = null
  } finally {
    loading.value = false
    nextTick(measureNavHeight)
  }
}

onMounted(() => {
  fetchInstitution()
  window.addEventListener('scroll', onScroll, { passive: true })
  window.addEventListener('resize', onResize, { passive: true })
  window.addEventListener('keydown', onMenuKeydown)
  nextTick(() => {
    measureNavHeight()
    if (typeof ResizeObserver !== 'undefined' && navbarEl.value) {
      resizeObserver = new ResizeObserver(() => measureNavHeight())
      resizeObserver.observe(navbarEl.value)
    }
  })
})

watch(menuOpen, (open) => {
  if (typeof document === 'undefined') return
  document.body.style.overflow = open ? 'hidden' : ''
})

watch(npsn, () => fetchInstitution())
watch(scrolled, () => nextTick(measureNavHeight))
watch(institution, (val) => {
  if (val) {
    nextTick(() => {
      measureNavHeight()
      setTimeout(setupReveal, 120)
    })
    const baseUrl = typeof window !== 'undefined' ? window.location.origin + route.fullPath : ''
    pageMeta.setMeta({
      title: `${val.name} - Profil ${getInstitutionTypeLabel(val.level) || 'Sekolah/Madrasah'}`,
      description: (val.description || val.address || `${val.name}, ${val.level || ''} ${val.type || ''}. NPSN ${val.npsn || ''}`).slice(0, 160),
      image: val.logo_url || null,
      url: baseUrl
    })
    setJsonLd(val, baseUrl)
  }
}, { flush: 'post' })

function setJsonLd(inst, url) {
  if (typeof document === 'undefined') return
  removeJsonLd()
  const script = document.createElement('script')
  script.type = 'application/ld+json'
  script.id = 'school-public-jsonld'
  script.textContent = JSON.stringify({
    '@context': 'https://schema.org',
    '@type': 'School',
    name: inst.name,
    identifier: inst.npsn ? { '@type': 'PropertyValue', propertyID: 'NPSN', value: inst.npsn } : undefined,
    description: inst.description || undefined,
    address: inst.address ? { '@type': 'PostalAddress', streetAddress: inst.address } : undefined,
    telephone: inst.phone || undefined,
    email: inst.email || undefined,
    url: inst.website || url,
    image: inst.logo_url || undefined,
    geo: (inst.latitude && inst.longitude) ? { '@type': 'GeoCoordinates', latitude: inst.latitude, longitude: inst.longitude } : undefined,
    principal: inst.principal_name ? { '@type': 'Person', name: inst.principal_name } : undefined
  })
  document.head.appendChild(script)
}

function removeJsonLd() {
  const el = document.getElementById('school-public-jsonld')
  if (el) el.remove()
}

function onScroll() {
  if (scrollRaf) return
  scrollRaf = requestAnimationFrame(() => {
    scrollRaf = 0
    scrolled.value = window.scrollY > 12
  })
}

function onResize() {
  if (window.innerWidth > 860) closeMenu()
  measureNavHeight()
}

function setupReveal() {
  if (revealObserver) {
    revealObserver.disconnect()
    revealObserver = null
  }
  const refs = [aboutRef, servicesRef, identityRef, contactRef, footerRef]
  revealObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) entry.target.classList.add('is-visible')
      })
    },
    { rootMargin: '0px 0px -48px 0px', threshold: 0.08 }
  )
  refs.forEach((r) => {
    const el = r.value?.$el
    if (el) revealObserver.observe(el)
  })
}

onBeforeUnmount(() => {
  pageMeta.clear()
  removeJsonLd()
  window.removeEventListener('scroll', onScroll)
  window.removeEventListener('resize', onResize)
  window.removeEventListener('keydown', onMenuKeydown)
  if (scrollRaf) cancelAnimationFrame(scrollRaf)
  resizeObserver?.disconnect()
  resizeObserver = null
  document.body.style.overflow = ''
  if (revealObserver) revealObserver.disconnect()
})
</script>

<style scoped>
.school-public-page {
  min-height: 100vh;
  background: #f8fafc;
  position: relative;
  scroll-behavior: smooth;
}

.skip-link {
  position: absolute;
  top: -100px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 1000;
  padding: 0.5rem 1rem;
  background: #059669;
  color: #fff;
  font-weight: 600;
  text-decoration: none;
  border-radius: 8px;
  font-size: 0.875rem;
  transition: top 0.2s;
}
.skip-link:focus {
  top: 12px;
  outline: 2px solid #047857;
  outline-offset: 2px;
}

.navbar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 100;
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  border-bottom: 1px solid transparent;
  padding-top: env(safe-area-inset-top, 0);
  transition:
    background 0.25s ease,
    border-color 0.25s ease,
    box-shadow 0.25s ease;
}
.navbar--scrolled {
  background: rgba(255, 255, 255, 0.86);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  border-bottom-color: #e2e8f0;
  box-shadow:
    0 1px 0 rgba(15, 23, 42, 0.04),
    0 10px 28px -16px rgba(15, 23, 42, 0.18);
}
.navbar-spacer {
  flex-shrink: 0;
  width: 100%;
}
.navbar-inner {
  max-width: 1100px;
  margin: 0 auto;
  padding: 12px 24px;
  padding-left: max(24px, env(safe-area-inset-left));
  padding-right: max(24px, env(safe-area-inset-right));
  display: flex;
  align-items: center;
  gap: 16px;
  transition: padding 0.25s ease;
}
.navbar--scrolled .navbar-inner {
  padding-top: 8px;
  padding-bottom: 8px;
}
.navbar-brand {
  display: flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
  color: #1e293b;
  font-weight: 600;
  font-size: 16px;
  min-width: 0;
  flex-shrink: 1;
  transition: color 0.2s;
}
.navbar-brand:hover { color: #059669; }
.navbar-logo-img {
  width: 36px;
  height: 36px;
  object-fit: contain;
  border-radius: 8px;
  flex-shrink: 0;
}
.navbar-logo { flex-shrink: 0; display: flex; }
.navbar-title {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: min(320px, 48vw);
  font-size: 0.975rem;
}
.navbar-toggle {
  display: none;
  margin-left: auto;
  flex-direction: column;
  justify-content: center;
  gap: 5px;
  width: 44px;
  min-width: 44px;
  height: 44px;
  padding: 10px;
  border: none;
  background: transparent;
  cursor: pointer;
  border-radius: 8px;
  color: #1e293b;
  -webkit-tap-highlight-color: transparent;
  touch-action: manipulation;
}
.navbar-toggle:hover { background: #f1f5f9; }
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
.navbar-toggle-bar.open:nth-child(2) { opacity: 0; }
.navbar-toggle-bar.open:nth-child(3) {
  transform: translateY(-7px) rotate(-45deg);
}
.navbar-links--desktop {
  display: flex;
  gap: 4px 18px;
  align-items: center;
  flex-wrap: nowrap;
  margin-left: auto;
  min-width: 0;
}
.nav-link {
  position: relative;
  color: #64748b;
  text-decoration: none;
  font-size: 0.9rem;
  font-weight: 500;
  transition: color 0.2s;
  padding: 8px 4px;
  min-height: 44px;
  display: inline-flex;
  align-items: center;
  -webkit-tap-highlight-color: transparent;
}
.nav-link::after {
  content: '';
  position: absolute;
  left: 4px;
  right: 4px;
  bottom: 6px;
  height: 1.5px;
  background: #059669;
  border-radius: 1px;
  transform: scaleX(0);
  transform-origin: center;
  transition: transform 0.2s ease;
  pointer-events: none;
}
.nav-link:hover,
.nav-link.router-link-active {
  color: #059669;
}
.nav-link:hover::after,
.nav-link.router-link-active::after {
  transform: scaleX(1);
}
.btn {
  padding: 9px 18px;
  min-height: 40px;
  border-radius: 10px;
  font-size: 0.875rem;
  font-weight: 600;
  text-decoration: none;
  transition: background 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  border: none;
  flex-shrink: 0;
  -webkit-tap-highlight-color: transparent;
}
.btn-primary {
  background: #059669;
  color: white;
  box-shadow: 0 2px 10px rgba(5, 150, 105, 0.28);
}
.btn-primary:hover {
  background: #047857;
  transform: translateY(-1px);
}

.navbar-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  z-index: 199;
  -webkit-tap-highlight-color: transparent;
}
.navbar-drawer {
  position: fixed;
  top: 0;
  right: 0;
  bottom: 0;
  width: min(300px, 85vw);
  background: #ffffff;
  z-index: 200;
  padding: 72px 24px 24px;
  padding-top: max(72px, calc(env(safe-area-inset-top) + 56px));
  padding-right: max(24px, env(safe-area-inset-right));
  padding-bottom: max(24px, env(safe-area-inset-bottom));
  display: flex;
  flex-direction: column;
  gap: 4px;
  box-shadow: -4px 0 24px rgba(0, 0, 0, 0.12);
}
.navbar-drawer-close {
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
.navbar-drawer-close:hover {
  background: #ecfdf5;
  color: #059669;
}
.navbar-drawer-link {
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
.navbar-drawer-link:hover,
.navbar-drawer-link.router-link-active {
  background: #f1f5f9;
  color: #059669;
}
.navbar-drawer-auth {
  margin-top: 12px;
  padding-top: 16px;
  border-top: 1px solid #e2e8f0;
}
.navbar-drawer-auth .btn {
  width: 100%;
  min-height: 48px;
}

.school-nav-menu-enter-active,
.school-nav-menu-leave-active {
  transition: opacity 0.2s ease;
}
.school-nav-menu-enter-from,
.school-nav-menu-leave-to {
  opacity: 0;
}
.school-nav-drawer-enter-active,
.school-nav-drawer-leave-active {
  transition: transform 0.25s ease;
}
.school-nav-drawer-enter-from,
.school-nav-drawer-leave-to {
  transform: translateX(100%);
}

.skeleton-wrap { position: relative; z-index: 1; }
.skeleton-hero {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
  padding: 100px 24px 80px;
  background: linear-gradient(165deg, #064e3b 0%, #047857 50%, #134e4a 100%);
  min-height: 420px;
}
.skeleton-circle {
  width: 88px;
  height: 88px;
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.18);
  animation: pulse 1.2s ease-in-out infinite;
}
.skeleton-line {
  height: 14px;
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.18);
  animation: pulse 1.2s ease-in-out infinite;
}
.skeleton-line--lg { width: min(420px, 70%); height: 28px; }
.skeleton-line--md { width: min(320px, 55%); }
.skeleton-line--sm { width: min(200px, 40%); margin-top: 12px; height: 40px; border-radius: 12px; }
@keyframes pulse {
  0%, 100% { opacity: 0.55; }
  50% { opacity: 0.9; }
}

.state-wrap {
  max-width: 480px;
  margin: 64px auto;
  text-align: center;
  padding: 2.5rem 1.75rem;
  position: relative;
  z-index: 1;
  background: #fff;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 4px 24px rgba(15, 23, 42, 0.06);
}
.state-icon {
  width: 56px;
  height: 56px;
  margin: 0 auto 1.25rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 1.4rem;
}
.state-icon-error { background: #fee2e2; color: #b91c1c; }
.state-error { color: #b91c1c; }
.state-error h2 { margin: 0 0 0.5rem; font-size: 1.25rem; color: #0f172a; }
.state-error p { margin: 0 0 1.25rem; color: #64748b; }
.btn-outline {
  display: inline-block;
  padding: 0.7rem 1.35rem;
  background: transparent;
  color: #059669;
  font-weight: 600;
  text-decoration: none;
  border-radius: 10px;
  font-size: 0.95rem;
  border: 2px solid #059669;
  transition: background 0.2s, color 0.2s;
}
.btn-outline:hover { background: #ecfdf5; color: #047857; }

@media (max-width: 860px) {
  .navbar-toggle { display: flex; }
  .navbar-links--desktop { display: none; }
  .navbar-title { max-width: min(240px, 55vw); }
}
</style>
