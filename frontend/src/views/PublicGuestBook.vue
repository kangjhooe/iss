<template>
  <div class="guest-book-page">
    <nav class="navbar">
      <div class="navbar-inner">
        <router-link :to="`/${npsn}`" class="navbar-brand">
          <img v-if="institution?.logo_url" :src="institution.logo_url" alt="" class="navbar-logo-img" />
          <div v-else class="navbar-logo" aria-hidden="true">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M9 22V12h6v10" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <span class="navbar-title">{{ institution?.name || 'Buku Tamu' }}</span>
        </router-link>
        <div class="navbar-links">
          <router-link :to="`/${npsn}`" class="nav-link">Beranda Sekolah</router-link>
          <router-link to="/login" class="btn btn-primary">Masuk</router-link>
        </div>
      </div>
    </nav>

    <main class="main">
      <div v-if="pageLoading" class="state-wrap">Memuat...</div>
      <div v-else-if="pageError" class="state-wrap state-error">
        <h2>Sekolah tidak ditemukan</h2>
        <p>{{ pageError }}</p>
        <router-link to="/" class="back-link">← Beranda</router-link>
      </div>
      <SchoolPublicGuestForm
        v-else
        :npsn="npsn"
        standalone
      />
    </main>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { schoolPublicApi } from '@/api/schoolPublic'
import { usePageMeta } from '@/composables/usePageMeta'
import SchoolPublicGuestForm from './SchoolPublic/SchoolPublicGuestForm.vue'

const route = useRoute()
const pageMeta = usePageMeta()

const npsn = computed(() => String(route.params.npsn || ''))
const institution = ref(null)
const pageLoading = ref(true)
const pageError = ref('')

async function loadInstitution() {
  if (!npsn.value) return
  pageLoading.value = true
  pageError.value = ''
  try {
    const res = await schoolPublicApi.getInstitution(npsn.value)
    institution.value = res.data?.data ?? res.data ?? null
    if (!institution.value) {
      pageError.value = 'NPSN tidak valid atau sekolah tidak aktif.'
      return
    }
    pageMeta.setMeta({
      title: `Buku Tamu — ${institution.value.name}`,
      description: `Catat kunjungan Anda ke ${institution.value.name} melalui buku tamu digital.`,
      image: institution.value.logo_url || null
    })
  } catch (e) {
    institution.value = null
    pageError.value = e.response?.data?.message || 'Gagal memuat data sekolah.'
  } finally {
    pageLoading.value = false
  }
}

onMounted(loadInstitution)
watch(npsn, loadInstitution)
onBeforeUnmount(() => pageMeta.clear())
</script>

<style scoped>
.guest-book-page {
  min-height: 100vh;
  background: #f8fafc;
}

.navbar {
  position: sticky;
  top: 0;
  z-index: 20;
  background: rgba(255, 255, 255, 0.94);
  backdrop-filter: blur(10px);
  border-bottom: 1px solid #e2e8f0;
}

.navbar-inner {
  max-width: 1000px;
  margin: 0 auto;
  padding: 0.75rem 1rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.navbar-brand {
  display: inline-flex;
  align-items: center;
  gap: 0.65rem;
  text-decoration: none;
  color: #0f172a;
  font-weight: 700;
  min-width: 0;
}

.navbar-logo-img {
  width: 36px;
  height: 36px;
  object-fit: contain;
  border-radius: 8px;
  flex-shrink: 0;
}

.navbar-logo {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  background: #ecfdf5;
  display: grid;
  place-items: center;
  flex-shrink: 0;
}

.navbar-title {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 0.975rem;
}

.navbar-links {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-shrink: 0;
}

.nav-link {
  color: #047857;
  text-decoration: none;
  font-weight: 500;
  font-size: 0.9rem;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.5rem 0.9rem;
  border-radius: 8px;
  border: none;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  font-size: 0.875rem;
}

.btn-primary {
  background: #059669;
  color: #fff;
}

.btn-primary:hover {
  background: #047857;
}

.main {
  max-width: 1000px;
  margin: 0 auto;
  padding: 0.5rem 0 2rem;
}

.state-wrap {
  text-align: center;
  padding: 3rem 1.25rem;
  color: #64748b;
}

.state-error h2 {
  margin: 0 0 0.5rem;
  color: #0f172a;
  font-size: 1.25rem;
}

.state-error p {
  margin: 0 0 1rem;
}

.back-link {
  display: inline-block;
  color: #059669;
  text-decoration: none;
  font-weight: 600;
}

@media (max-width: 640px) {
  .navbar-title { max-width: 140px; }
  .nav-link { display: none; }
}
</style>
