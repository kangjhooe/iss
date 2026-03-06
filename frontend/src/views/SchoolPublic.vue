<template>
  <div class="school-public-page">
    <a href="#main-content" class="skip-link">Langsung ke konten</a>
    <div class="page-bg" aria-hidden="true">
      <div class="blob blob-1"></div>
      <div class="blob blob-2"></div>
      <div class="blob blob-3"></div>
    </div>

    <!-- Navbar -->
    <nav class="navbar" :class="{ 'navbar--scrolled': scrolled }">
      <div class="navbar-inner">
        <router-link :to="`/${npsn}`" class="navbar-brand">
          <img v-if="institution?.logo_url" :src="institution.logo_url" alt="" class="navbar-logo-img" />
          <div v-else class="navbar-logo">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M9 22V12h6v10" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <span class="navbar-title">{{ institution?.name || 'Sekolah/Madrasah' }}</span>
        </router-link>
        <div class="navbar-links">
          <router-link :to="`/${npsn}/daftar-ppdb`" class="nav-link">Daftar PPDB</router-link>
          <router-link to="/login" class="btn btn-primary">Masuk</router-link>
        </div>
      </div>
    </nav>

    <!-- Loading / Error -->
    <div id="main-content" v-if="loading" class="state-wrap state-loading card" tabindex="-1">
      <div class="spinner"></div>
      <p>Memuat data sekolah/madrasah...</p>
    </div>
    <div v-else-if="error" id="main-content" class="state-wrap state-error card" tabindex="-1">
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

      <SchoolPublicIdentity ref="identityRef" :institution="institution" />
      <SchoolPublicProfile ref="profileRef" :institution="institution" />
      <SchoolPublicVision ref="visionRef" :institution="institution" />
      <SchoolPublicContact ref="contactRef" :institution="institution" />
      <SchoolPublicGuestForm ref="bukuTamuRef" :npsn="npsn" />
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
import SchoolPublicIdentity from './SchoolPublic/SchoolPublicIdentity.vue'
import SchoolPublicVision from './SchoolPublic/SchoolPublicVision.vue'
import SchoolPublicProfile from './SchoolPublic/SchoolPublicProfile.vue'
import SchoolPublicGuestForm from './SchoolPublic/SchoolPublicGuestForm.vue'
import SchoolPublicContact from './SchoolPublic/SchoolPublicContact.vue'
import SchoolPublicFooter from './SchoolPublic/SchoolPublicFooter.vue'

const route = useRoute()
const npsn = computed(() => route.params.npsn)
const pageMeta = usePageMeta()

const institution = ref(null)
const loading = ref(true)
const error = ref('')

const heroRef = ref(null)
const identityRef = ref(null)
const visionRef = ref(null)
const profileRef = ref(null)
const bukuTamuRef = ref(null)
const contactRef = ref(null)
const footerRef = ref(null)
const scrolled = ref(false)

async function fetchInstitution() {
  if (!npsn.value) return
  loading.value = true
  error.value = ''
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
})
watch(npsn, () => fetchInstitution())
watch(institution, (val) => {
  if (val) {
    nextTick(() => setTimeout(setupReveal, 150))
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

function setupReveal() {
  const refs = [identityRef, profileRef, visionRef, contactRef, bukuTamuRef, footerRef]
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) entry.target.classList.add('is-visible')
      })
    },
    { rootMargin: '0px 0px -60px 0px', threshold: 0.1 }
  )
  refs.forEach((r) => {
    const el = r.value?.$el
    if (el) observer.observe(el)
  })
}

onBeforeUnmount(() => {
  pageMeta.clear()
  removeJsonLd()
})
</script>

<style scoped>
.school-public-page {
  min-height: 100vh;
  background: #f1f5f9;
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

.page-bg {
  position: fixed;
  inset: 0;
  z-index: 0;
  overflow: hidden;
  pointer-events: none;
}
.blob {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  opacity: 0.35;
  will-change: transform;
}
@keyframes blobFloat1 {
  0%, 100% { transform: translate(0, 0) scale(1); }
  33% { transform: translate(20px, -15px) scale(1.05); }
  66% { transform: translate(-10px, 10px) scale(0.98); }
}
@keyframes blobFloat2 {
  0%, 100% { transform: translate(0, 0) scale(1); }
  50% { transform: translate(-15px, -20px) scale(1.03); }
}
@keyframes blobFloat3 {
  0%, 100% { transform: translate(0, 0); }
  50% { transform: translate(10px, 15px); }
}
.blob-1 {
  width: 420px; height: 420px; background: #a7f3d0; top: -120px; right: -80px;
  animation: blobFloat1 18s ease-in-out infinite;
}
.blob-2 {
  width: 320px; height: 320px; background: #99f6e4; bottom: 15%; left: -100px;
  animation: blobFloat2 22s ease-in-out infinite;
}
.blob-3 {
  width: 260px; height: 260px; background: #ccfbf1; bottom: -40px; right: 15%;
  animation: blobFloat3 16s ease-in-out infinite;
}

.navbar {
  position: sticky;
  top: 0;
  z-index: 100;
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(12px);
  border-bottom: 1px solid transparent;
  transition: background 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
}
.navbar--scrolled {
  background: rgba(255, 255, 255, 0.95);
  border-bottom-color: #e2e8f0;
  box-shadow: 0 1px 20px rgba(0, 0, 0, 0.04);
}
.navbar-inner {
  max-width: 1100px;
  margin: 0 auto;
  padding: 14px 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  flex-wrap: wrap;
  position: relative;
  z-index: 10;
}
.navbar-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  text-decoration: none;
  color: #1e293b;
  font-weight: 600;
  font-size: 18px;
  transition: color 0.2s, transform 0.2s;
}
.navbar-brand:hover { color: #059669; transform: translateY(-1px); }
.navbar-logo img { width: 36px; height: 36px; object-fit: contain; }
.navbar-logo-img { width: 36px; height: 36px; object-fit: contain; border-radius: 8px; transition: transform 0.2s; }
.navbar-brand:hover .navbar-logo-img { transform: scale(1.05); }
.navbar-title { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 220px; font-size: 1.05rem; }
.navbar-links { display: flex; gap: 16px; align-items: center; flex-wrap: wrap; }
.nav-link {
  color: #64748b;
  text-decoration: none;
  font-size: 15px;
  font-weight: 500;
  transition: color 0.2s, transform 0.15s;
}
.nav-link:hover { color: #059669; transform: translateY(-1px); }
.btn {
  padding: 10px 20px;
  min-height: 44px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 500;
  text-decoration: none;
  transition: all 0.25s ease;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  border: none;
}
.btn-primary { background: linear-gradient(145deg, #059669 0%, #047857 100%); color: white; box-shadow: 0 4px 14px rgba(5, 150, 105, 0.35); }
.btn-primary:hover { background: linear-gradient(145deg, #047857 0%, #065f46 100%); box-shadow: 0 8px 24px rgba(5, 150, 105, 0.4); transform: translateY(-2px); }

.state-wrap {
  max-width: 480px;
  margin: 48px auto;
  text-align: center;
  padding: 3rem 2rem;
  position: relative;
  z-index: 1;
}
.card {
  background: #fff;
  border-radius: 16px;
  padding: 2rem 1.75rem;
  box-shadow: 0 4px 24px rgba(15, 23, 42, 0.06), 0 1px 3px rgba(15, 23, 42, 0.04);
  border: 1px solid rgba(226, 232, 240, 0.8);
}
.state-loading { color: #64748b; }
.state-loading .spinner {
  width: 44px;
  height: 44px;
  margin: 0 auto 1.25rem;
  border: 3px solid #e9d5ff;
  border-top-color: #059669;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
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
.state-error h2 { margin: 0 0 0.5rem; font-size: 1.25rem; }
.state-error p { margin: 0 0 1rem; }
.btn-outline {
  display: inline-block;
  padding: 0.75rem 1.5rem;
  background: transparent;
  color: #059669;
  font-weight: 600;
  text-decoration: none;
  border-radius: 12px;
  font-size: 0.95rem;
  border: 2px solid #059669;
  transition: background 0.2s, color 0.2s;
}
.btn-outline:hover { background: #ecfdf5; color: #047857; }
</style>
