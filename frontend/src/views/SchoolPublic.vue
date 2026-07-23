<template>
  <div class="school-public-page">
    <a href="#main-content" class="skip-link">Langsung ke konten</a>

    <nav class="navbar" :class="{ 'navbar--scrolled': scrolled, 'navbar--open': menuOpen }">
      <div class="navbar-inner">
        <router-link :to="`/${npsn}`" class="navbar-brand" @click="menuOpen = false">
          <img v-if="institution?.logo_url" :src="institution.logo_url" alt="" class="navbar-logo-img" />
          <div v-else class="navbar-logo">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M9 22V12h6v10" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <span class="navbar-title">{{ institution?.name || 'Sekolah/Madrasah' }}</span>
        </router-link>

        <button
          type="button"
          class="navbar-toggle"
          :aria-expanded="menuOpen"
          aria-controls="school-nav-menu"
          aria-label="Menu navigasi"
          @click="menuOpen = !menuOpen"
        >
          <span class="navbar-toggle-bar"></span>
          <span class="navbar-toggle-bar"></span>
          <span class="navbar-toggle-bar"></span>
        </button>

        <div id="school-nav-menu" class="navbar-links">
          <a href="#tentang" class="nav-link" @click="menuOpen = false">Tentang</a>
          <a href="#layanan" class="nav-link" @click="menuOpen = false">Layanan</a>
          <a href="#kontak" class="nav-link" @click="menuOpen = false">Lokasi</a>
          <router-link :to="`/${npsn}/buku-tamu`" class="nav-link" @click="menuOpen = false">Buku Tamu</router-link>
          <router-link :to="`/${npsn}/daftar-ppdb`" class="nav-link" @click="menuOpen = false">PPDB</router-link>
          <router-link to="/login" class="btn btn-primary" @click="menuOpen = false">Masuk</router-link>
        </div>
      </div>
    </nav>

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
      />

      <SchoolPublicAbout ref="aboutRef" :institution="institution" />
      <SchoolPublicServices ref="servicesRef" :npsn="npsn" />
      <SchoolPublicIdentity ref="identityRef" :institution="institution" />
      <SchoolPublicContact ref="contactRef" :institution="institution" />
      <SchoolPublicFooter ref="footerRef" :institution="institution" :npsn="npsn" />
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

const heroRef = ref(null)
const aboutRef = ref(null)
const servicesRef = ref(null)
const identityRef = ref(null)
const contactRef = ref(null)
const footerRef = ref(null)
const scrolled = ref(false)

let revealObserver = null

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
  }
}

onMounted(() => {
  fetchInstitution()
  window.addEventListener('scroll', onScroll, { passive: true })
  window.addEventListener('resize', onResize, { passive: true })
})

watch(npsn, () => fetchInstitution())
watch(institution, (val) => {
  if (val) {
    nextTick(() => setTimeout(setupReveal, 120))
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
  scrolled.value = window.scrollY > 24
}

function onResize() {
  if (window.innerWidth > 860) menuOpen.value = false
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
  position: sticky;
  top: 0;
  z-index: 100;
  background: rgba(255, 255, 255, 0.86);
  backdrop-filter: blur(12px);
  border-bottom: 1px solid transparent;
  transition: background 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
}
.navbar--scrolled {
  background: rgba(255, 255, 255, 0.96);
  border-bottom-color: #e2e8f0;
  box-shadow: 0 1px 16px rgba(0, 0, 0, 0.04);
}
.navbar-inner {
  max-width: 1100px;
  margin: 0 auto;
  padding: 12px 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  position: relative;
  z-index: 10;
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
  max-width: min(280px, 42vw);
  font-size: 0.975rem;
}
.navbar-toggle {
  display: none;
  flex-direction: column;
  justify-content: center;
  gap: 5px;
  width: 44px;
  height: 44px;
  padding: 10px;
  border: none;
  background: transparent;
  cursor: pointer;
  border-radius: 10px;
}
.navbar-toggle:hover { background: #f1f5f9; }
.navbar-toggle-bar {
  display: block;
  width: 100%;
  height: 2px;
  background: #334155;
  border-radius: 2px;
  transition: transform 0.2s, opacity 0.2s;
}
.navbar--open .navbar-toggle-bar:nth-child(1) {
  transform: translateY(7px) rotate(45deg);
}
.navbar--open .navbar-toggle-bar:nth-child(2) { opacity: 0; }
.navbar--open .navbar-toggle-bar:nth-child(3) {
  transform: translateY(-7px) rotate(-45deg);
}
.navbar-links {
  display: flex;
  gap: 8px 18px;
  align-items: center;
  flex-wrap: wrap;
}
.nav-link {
  color: #64748b;
  text-decoration: none;
  font-size: 0.9rem;
  font-weight: 500;
  transition: color 0.2s;
  padding: 0.35rem 0;
}
.nav-link:hover { color: #059669; }
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
  .navbar-links {
    display: none;
    position: absolute;
    top: calc(100% + 1px);
    left: 0;
    right: 0;
    flex-direction: column;
    align-items: stretch;
    gap: 0;
    padding: 0.5rem 1rem 1rem;
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
  }
  .navbar--open .navbar-links { display: flex; }
  .nav-link {
    padding: 0.85rem 0.5rem;
    border-bottom: 1px solid #f1f5f9;
  }
  .navbar-links .btn {
    margin-top: 0.5rem;
    width: 100%;
  }
}
</style>
