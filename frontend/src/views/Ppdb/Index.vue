<template>
  <Layout>
    <div class="ppdb-page">
      <header class="page-header">
        <div class="header-bg" aria-hidden="true"></div>
        <div class="header-content">
          <div class="header-left">
            <div>
              <h1 class="page-title">PPDB</h1>
              <p class="page-subtitle">Ringkasan penerimaan peserta didik baru</p>
            </div>
          </div>
        </div>
      </header>

      <main class="page-main">
        <div v-if="loading" class="loading-wrap content-card">
          <LoadingSkeleton type="table" :rows="3" :columns="4" />
        </div>
        <template v-else>
          <div class="summary-grid">
            <div class="summary-card">
              <span class="summary-label">Periode aktif</span>
              <strong class="summary-value summary-value-text">{{ activePeriod?.name || '—' }}</strong>
              <span class="summary-hint">{{ activePeriod ? statusPeriodLabel(activePeriod.status) : 'Belum ada periode dibuka' }}</span>
            </div>
            <div class="summary-card">
              <span class="summary-label">Total calon</span>
              <strong class="summary-value">{{ totals.total }}</strong>
              <span class="summary-hint">Semua periode</span>
            </div>
            <router-link to="/ppdb/pendaftar?needs_verification=1" class="summary-card summary-card-link">
              <span class="summary-label">Perlu verifikasi</span>
              <strong class="summary-value">{{ totals.needs_verification }}</strong>
              <span class="summary-hint">Buka daftar →</span>
            </router-link>
            <router-link to="/ppdb/pendaftar?needs_result=1" class="summary-card summary-card-link">
              <span class="summary-label">Perlu set hasil</span>
              <strong class="summary-value">{{ totals.needs_result }}</strong>
              <span class="summary-hint">Buka daftar →</span>
            </router-link>
            <router-link to="/ppdb/pembayaran?payment_status=unpaid" class="summary-card summary-card-link">
              <span class="summary-label">Belum bayar</span>
              <strong class="summary-value">{{ totals.unpaid }}</strong>
              <span class="summary-hint">Kelola pembayaran →</span>
            </router-link>
          </div>

          <div class="content-card summary-actions">
            <h3 class="section-title">Aksi cepat</h3>
            <div class="quick-links">
              <router-link to="/ppdb/konfigurasi" class="quick-link">Atur periode & jalur</router-link>
              <router-link to="/ppdb/pendaftar" class="quick-link">Data pendaftar</router-link>
              <router-link to="/ppdb/statistik" class="quick-link">Lihat statistik</router-link>
              <router-link to="/ppdb/pembayaran" class="quick-link">Pembayaran</router-link>
              <button type="button" class="quick-link quick-link-btn" :disabled="!publicRegisterUrl" @click="copyPublicLink">
                {{ copied ? 'Link disalin!' : 'Salin link daftar publik' }}
              </button>
            </div>
            <p v-if="publicRegisterUrl" class="public-url">{{ publicRegisterUrl }}</p>
            <p v-else class="public-url muted">NPSN institusi belum tersedia — link publik tidak dapat dibuat.</p>
          </div>

          <div class="content-card">
            <h3 class="section-title">Jalur ({{ channels.length }})</h3>
            <div v-if="channels.length === 0" class="empty-inline">
              Belum ada jalur. <router-link to="/ppdb/konfigurasi">Tambah di Konfigurasi</router-link>
            </div>
            <ul v-else class="channel-list">
              <li v-for="c in channels" :key="c.id">
                <code>{{ c.code }}</code>
                <span>{{ c.name }}</span>
                <span class="muted">Kuota: {{ c.quota ?? '—' }}</span>
                <span :class="['pill', c.is_active ? 'pill-on' : 'pill-off']">{{ c.is_active ? 'Aktif' : 'Nonaktif' }}</span>
              </li>
            </ul>
          </div>
        </template>
      </main>
    </div>
  </Layout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import Layout from '@/components/Layout.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { ppdbApi } from '@/api/ppdb'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import { statusPeriodLabel } from './ppdbConstants'
import './ppdb.css'

const toast = useToast()
const authStore = useAuthStore()
const loading = ref(true)
const activePeriod = ref(null)
const channels = ref([])
const totals = ref({ total: 0, needs_verification: 0, needs_result: 0, unpaid: 0, paid: 0 })
const copied = ref(false)

const publicRegisterUrl = computed(() => {
  const npsn = authStore.activeInstitution?.npsn
  if (!npsn || typeof window === 'undefined') return ''
  return `${window.location.origin}/${npsn}/daftar-ppdb`
})

async function load() {
  loading.value = true
  try {
    const res = await ppdbApi.getSummary()
    const data = res.data?.data || {}
    activePeriod.value = data.active_period || null
    channels.value = data.channels || []
    totals.value = {
      total: data.totals?.total ?? 0,
      needs_verification: data.totals?.needs_verification ?? 0,
      needs_result: data.totals?.needs_result ?? 0,
      unpaid: data.totals?.unpaid ?? 0,
      paid: data.totals?.paid ?? 0,
    }
  } catch (e) {
    toast.error('Gagal memuat ringkasan PPDB', e.formattedMessage || 'Coba muat ulang halaman.')
  } finally {
    loading.value = false
  }
}

async function copyPublicLink() {
  if (!publicRegisterUrl.value) return
  try {
    await navigator.clipboard.writeText(publicRegisterUrl.value)
    copied.value = true
    toast.success('Link daftar PPDB disalin')
    setTimeout(() => { copied.value = false }, 2000)
  } catch {
    toast.error('Gagal menyalin link')
  }
}

onMounted(load)
</script>

<style scoped>
.summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 1rem;
}
.summary-card {
  background: #fff;
  border: 1px solid #f1f5f9;
  border-radius: 16px;
  padding: 1.25rem 1.35rem;
  box-shadow: 0 4px 12px rgba(0,0,0,0.04);
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  text-decoration: none;
  color: inherit;
}
.summary-card-link:hover {
  border-color: #a7f3d0;
  box-shadow: 0 6px 16px rgba(5, 150, 105, 0.12);
}
.summary-label { font-size: 0.8rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.03em; }
.summary-value { font-size: 1.75rem; font-weight: 800; color: #047857; line-height: 1.1; }
.summary-value-text { font-size: 1.15rem; }
.summary-hint { font-size: 0.85rem; color: #64748b; }
.section-title { margin: 0 0 1rem; font-size: 1.05rem; color: #1e293b; }
.quick-links { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.quick-link, .quick-link-btn {
  display: inline-flex;
  align-items: center;
  padding: 0.55rem 1rem;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #047857;
  font-weight: 600;
  font-size: 0.9rem;
  text-decoration: none;
  cursor: pointer;
}
.quick-link:hover, .quick-link-btn:hover:not(:disabled) { border-color: #059669; background: #ecfdf5; }
.quick-link-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.public-url { margin: 0.85rem 0 0; font-size: 0.85rem; color: #475569; word-break: break-all; }
.public-url.muted, .muted { color: #94a3b8; }
.empty-inline { color: #64748b; font-size: 0.95rem; }
.channel-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0.65rem; }
.channel-list li {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.75rem;
  padding: 0.65rem 0;
  border-bottom: 1px solid #f1f5f9;
}
.channel-list li:last-child { border-bottom: none; }
.pill {
  margin-left: auto;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.2rem 0.55rem;
  border-radius: 999px;
}
.pill-on { background: #d1fae5; color: #065f46; }
.pill-off { background: #f1f5f9; color: #64748b; }
</style>
