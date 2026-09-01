<template>    <div class="ppdb-page">
      <header class="page-header">
        <div class="header-content">
          <div class="header-left">
            <div>
              <h1 class="page-title">Ringkasan PPDB</h1>
              <p class="page-subtitle">Periode aktif, antrean verifikasi, dan tautan daftar publik</p>
            </div>
          </div>
          <div class="header-actions">
            <router-link to="/ppdb/pendaftar" class="btn-header-secondary">Data pendaftar</router-link>
            <button type="button" class="btn-header-primary" :disabled="!publicRegisterUrl" @click="copyPublicLink">
              {{ copied ? 'Link disalin' : 'Salin link daftar publik' }}
            </button>
          </div>
        </div>
      </header>

      <main class="page-main">
        <div v-if="loading" class="loading-wrap content-card">
          <LoadingSkeleton type="table" :rows="4" :columns="4" />
        </div>
        <template v-else>
          <section v-if="activePeriod" class="content-card period-banner">
            <div class="period-main">
              <div class="period-kicker">Periode aktif</div>
              <h2 class="period-name">{{ activePeriod.name }}</h2>
              <p class="period-meta">
                <span v-if="academicYearLabel">{{ academicYearLabel }}</span>
                <span v-if="periodDateRange">{{ periodDateRange }}</span>
                <span v-if="activePeriod.applicants_count != null">{{ activePeriod.applicants_count }} calon pada periode ini</span>
              </p>
            </div>
            <div class="period-side">
              <span :class="['status-badge', 'status-' + activePeriod.status]">{{ statusPeriodLabel(activePeriod.status) }}</span>
              <router-link to="/ppdb/konfigurasi" class="period-link">Atur periode →</router-link>
            </div>
          </section>
          <section v-else class="empty-state content-card empty-state-sm">
            <h3>Belum ada periode PPDB</h3>
            <p>Buat gelombang pendaftaran terlebih dahulu agar calon bisa mendaftar.</p>
            <router-link to="/ppdb/konfigurasi" class="btn-primary">Atur periode & jalur</router-link>
          </section>

          <div class="summary-grid">
            <div class="summary-card">
              <div class="summary-icon summary-icon-emerald" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </div>
              <div>
                <span class="summary-label">Total calon</span>
                <strong class="summary-value">{{ totals.total }}</strong>
                <span class="summary-hint">Semua periode</span>
              </div>
            </div>
            <router-link to="/ppdb/pendaftar?needs_verification=1" class="summary-card summary-card-link" :class="{ 'is-alert': totals.needs_verification > 0 }">
              <div class="summary-icon summary-icon-amber" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M9 11l3 3L22 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </div>
              <div>
                <span class="summary-label">Perlu verifikasi</span>
                <strong class="summary-value">{{ totals.needs_verification }}</strong>
                <span class="summary-hint">Buka daftar →</span>
              </div>
            </router-link>
            <router-link to="/ppdb/pendaftar?needs_result=1" class="summary-card summary-card-link" :class="{ 'is-alert': totals.needs_result > 0 }">
              <div class="summary-icon summary-icon-sky" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 20V10M6 20V4M18 20v-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </div>
              <div>
                <span class="summary-label">Perlu set hasil</span>
                <strong class="summary-value">{{ totals.needs_result }}</strong>
                <span class="summary-hint">Buka daftar →</span>
              </div>
            </router-link>
            <router-link to="/ppdb/pembayaran?payment_status=unpaid" class="summary-card summary-card-link" :class="{ 'is-warn': totals.unpaid > 0 }">
              <div class="summary-icon summary-icon-rose" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><rect x="2" y="5" width="20" height="14" rx="2" stroke="currentColor" stroke-width="2"/><path d="M2 10h20" stroke="currentColor" stroke-width="2"/></svg>
              </div>
              <div>
                <span class="summary-label">Belum bayar</span>
                <strong class="summary-value">{{ totals.unpaid }}</strong>
                <span class="summary-hint">Kelola pembayaran →</span>
              </div>
            </router-link>
            <div class="summary-card">
              <div class="summary-icon summary-icon-emerald" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </div>
              <div>
                <span class="summary-label">Sudah lunas</span>
                <strong class="summary-value">{{ totals.paid }}</strong>
                <span class="summary-hint">Pembayaran tercatat</span>
              </div>
            </div>
          </div>

          <section class="content-card">
            <div class="section-head">
              <h3 class="section-title">Aksi cepat</h3>
              <span v-if="attentionCount" class="attention-pill">{{ attentionCount }} perlu perhatian</span>
            </div>
            <div class="actions-grid">
              <router-link to="/ppdb/konfigurasi" class="action-card">
                <div class="action-icon" aria-hidden="true">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 01-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <div>
                  <h4>Atur periode & jalur</h4>
                  <span>Gelombang, kuota, label publik</span>
                </div>
              </router-link>
              <router-link to="/ppdb/pendaftar" class="action-card">
                <div class="action-icon" aria-hidden="true">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/></svg>
                </div>
                <div>
                  <h4>Data pendaftar</h4>
                  <span>Verifikasi & hasil seleksi</span>
                </div>
              </router-link>
              <router-link to="/ppdb/statistik" class="action-card">
                <div class="action-icon" aria-hidden="true">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M18 20V10M12 20V4M6 20v-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </div>
                <div>
                  <h4>Lihat statistik</h4>
                  <span>Rekap status dan kuota</span>
                </div>
              </router-link>
              <router-link to="/ppdb/pembayaran" class="action-card">
                <div class="action-icon" aria-hidden="true">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><rect x="2" y="5" width="20" height="14" rx="2" stroke="currentColor" stroke-width="2"/><path d="M2 10h20" stroke="currentColor" stroke-width="2"/></svg>
                </div>
                <div>
                  <h4>Pembayaran</h4>
                  <span>Biaya periode & status bayar</span>
                </div>
              </router-link>
            </div>
            <div class="public-box">
              <div class="public-box-text">
                <span class="public-label">Link daftar publik</span>
                <p v-if="publicRegisterUrl" class="public-url">{{ publicRegisterUrl }}</p>
                <p v-else class="public-url muted">NPSN institusi belum tersedia — link publik tidak dapat dibuat.</p>
              </div>
              <button type="button" class="btn-secondary" :disabled="!publicRegisterUrl" @click="copyPublicLink">
                {{ copied ? 'Disalin' : 'Salin' }}
              </button>
            </div>
          </section>

          <div class="split-grid">
            <AppChart title="Calon per status" subtitle="Semua periode" type="doughnut" :chart-data="statusChart" />
            <section class="content-card">
              <div class="section-head">
                <h3 class="section-title">Jalur ({{ channels.length }})</h3>
                <router-link to="/ppdb/konfigurasi" class="section-link">Kelola</router-link>
              </div>
              <div v-if="channels.length === 0" class="empty-inline">
                Belum ada jalur. <router-link to="/ppdb/konfigurasi">Tambah di Konfigurasi</router-link>
              </div>
              <div v-else class="table-wrap">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Kode</th>
                      <th>Nama</th>
                      <th>Kuota</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="c in channels" :key="c.id">
                      <td><code>{{ c.code }}</code></td>
                      <td>{{ c.name }}</td>
                      <td>{{ c.quota ?? '—' }}</td>
                      <td>
                        <span :class="['pill', c.is_active ? 'pill-on' : 'pill-off']">{{ c.is_active ? 'Aktif' : 'Nonaktif' }}</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </section>
          </div>
        </template>
      </main>
    </div></template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import AppChart from '@/components/AppChart.vue'
import { ppdbApi } from '@/api/ppdb'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import { doughnutFromEntries, CHART_PALETTE } from '@/composables/useChart'
import { statusPeriodLabel, statusApplicantLabels, formatDate } from './ppdbConstants'
import './ppdb.css'

const toast = useToast()
const authStore = useAuthStore()
const loading = ref(true)
const activePeriod = ref(null)
const channels = ref([])
const byStatus = ref({})
const totals = ref({ total: 0, needs_verification: 0, needs_result: 0, unpaid: 0, paid: 0 })
const copied = ref(false)

const publicRegisterUrl = computed(() => {
  const npsn = authStore.activeInstitution?.npsn
  if (!npsn || typeof window === 'undefined') return ''
  return `${window.location.origin}/${npsn}/daftar-ppdb`
})

const academicYearLabel = computed(() => {
  const year = activePeriod.value?.academic_year
  if (!year) return ''
  return year.code || year.name || ''
})

const periodDateRange = computed(() => {
  const period = activePeriod.value
  if (!period?.open_date && !period?.close_date) return ''
  return `${formatDate(period.open_date)} – ${formatDate(period.close_date)}`
})

const attentionCount = computed(() =>
  Number(totals.value.needs_verification || 0)
  + Number(totals.value.needs_result || 0)
  + Number(totals.value.unpaid || 0)
)

const statusChart = computed(() => {
  const entries = Object.entries(byStatus.value || {})
  return doughnutFromEntries(
    entries.map(([key, count], i) => ({
      label: statusApplicantLabels[key] || key,
      value: count,
      color: CHART_PALETTE[i % CHART_PALETTE.length],
    }))
  )
})

async function load() {
  loading.value = true
  try {
    const res = await ppdbApi.getSummary()
    const data = res.data?.data || {}
    activePeriod.value = data.active_period?.name ? data.active_period : (data.active_period?.data || null)
    const rawChannels = data.channels
    channels.value = Array.isArray(rawChannels) ? rawChannels : (rawChannels?.data || [])
    byStatus.value = data.by_status || {}
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
.period-banner {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}
.period-kicker {
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: #64748b;
}
.period-name {
  margin: 0.2rem 0 0.35rem;
  font-size: 1.2rem;
  color: #0f172a;
}
.period-meta {
  margin: 0;
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem 0.85rem;
  color: #64748b;
  font-size: 0.9rem;
}
.period-side {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.5rem;
}
.period-link {
  font-size: 0.85rem;
  font-weight: 600;
  color: #047857;
  text-decoration: none;
}
.period-link:hover { text-decoration: underline; }

.summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 0.85rem;
}
.summary-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 1rem 1.1rem;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
  display: flex;
  align-items: flex-start;
  gap: 0.85rem;
  text-decoration: none;
  color: inherit;
}
.summary-card-link:hover {
  border-color: #a7f3d0;
  box-shadow: 0 6px 16px rgba(5, 150, 105, 0.1);
}
.summary-card.is-alert { border-color: #fde68a; background: #fffbeb; }
.summary-card.is-warn { border-color: #fecaca; background: #fef2f2; }
.summary-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.summary-icon-emerald { background: rgba(5, 150, 105, 0.12); color: #059669; }
.summary-icon-amber { background: rgba(245, 158, 11, 0.12); color: #d97706; }
.summary-icon-sky { background: rgba(14, 165, 233, 0.12); color: #0284c7; }
.summary-icon-rose { background: rgba(239, 68, 68, 0.1); color: #e11d48; }
.summary-label { display: block; font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.03em; }
.summary-value { display: block; font-size: 1.5rem; font-weight: 800; color: #0f172a; line-height: 1.15; margin: 0.15rem 0; }
.summary-hint { font-size: 0.8rem; color: #64748b; }

.section-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 1rem;
}
.section-title { margin: 0; font-size: 1.05rem; color: #0f172a; }
.section-link {
  font-size: 0.85rem;
  font-weight: 600;
  color: #047857;
  text-decoration: none;
}
.section-link:hover { text-decoration: underline; }
.attention-pill {
  font-size: 0.75rem;
  font-weight: 600;
  color: #b45309;
  background: #fffbeb;
  border: 1px solid #fde68a;
  padding: 0.2rem 0.65rem;
  border-radius: 999px;
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 0.75rem;
}
.action-card {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.9rem 1rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  text-decoration: none;
  color: inherit;
  min-height: 72px;
}
.action-card:hover {
  background: #fff;
  border-color: #a7f3d0;
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.08);
}
.action-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: rgba(5, 150, 105, 0.1);
  color: #059669;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.action-card h4 { margin: 0 0 0.15rem; font-size: 0.9rem; color: #0f172a; }
.action-card span { font-size: 0.78rem; color: #64748b; }

.public-box {
  margin-top: 1rem;
  padding: 0.85rem 1rem;
  border: 1px dashed #cbd5e1;
  border-radius: 12px;
  background: #f8fafc;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  flex-wrap: wrap;
}
.public-label { display: block; font-size: 0.75rem; font-weight: 600; color: #64748b; margin-bottom: 0.2rem; }
.public-url { margin: 0; font-size: 0.85rem; color: #334155; word-break: break-all; }
.public-url.muted, .muted { color: #94a3b8; }
.public-box .btn-secondary:disabled { opacity: 0.55; cursor: not-allowed; }

.split-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1.1fr);
  gap: 1rem;
  align-items: start;
}
.empty-inline { color: #64748b; font-size: 0.95rem; }
.pill {
  display: inline-flex;
  align-items: center;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.2rem 0.55rem;
  border-radius: 999px;
}
.pill-on { background: #d1fae5; color: #065f46; }
.pill-off { background: #f1f5f9; color: #64748b; }

@media (max-width: 900px) {
  .split-grid { grid-template-columns: 1fr; }
  .period-side { align-items: flex-start; }
}
</style>
