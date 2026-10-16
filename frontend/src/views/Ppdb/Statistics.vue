<template>    <div class="ppdb-page">
      <header class="page-header">
        <div class="header-bg" aria-hidden="true"></div>
        <div class="header-content">
          <div class="header-left">
            <div>
              <h1 class="page-title">Statistik PPDB</h1>
              <p class="page-subtitle">Rekap calon per periode dan jalur</p>
            </div>
          </div>
        </div>
      </header>

      <main class="page-main">
        <div class="filters-bar content-card">
          <label class="filter-label">Periode</label>
          <select v-model="statsPeriodId" class="filter-select" @change="loadStatistics">
            <option value="">Pilih periode</option>
            <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
          </select>
        </div>

        <div v-if="statsLoading" class="loading-wrap content-card">
          <LoadingSkeleton type="table" :rows="4" :columns="4" />
        </div>
        <div v-else-if="!statsPeriodId" class="empty-state content-card empty-state-sm">
          <p>Pilih periode untuk melihat statistik.</p>
        </div>
        <div v-else-if="statsData" class="stats-dashboard content-card">
          <div class="stats-cards">
            <div class="stats-card stats-total">
              <span class="stats-value">{{ statsData.total }}</span>
              <span class="stats-label">Total Calon</span>
            </div>
            <div v-for="key in highlightStatuses" :key="key" class="stats-card">
              <span class="stats-value stats-value-sm">{{ (statsData.by_status || {})[key] || 0 }}</span>
              <span class="stats-label">{{ statusApplicantLabels[key] || key }}</span>
            </div>
          </div>

          <template v-if="statsData.total === 0">
            <p class="empty-stats-msg">Belum ada calon peserta didik pada periode ini.</p>
          </template>
          <template v-else>
            <div class="charts-grid">
              <AppChart title="Per status" type="doughnut" :chart-data="statusChart" />
              <AppChart title="Kuota vs pendaftar" type="bar" :chart-data="channelChart" :options="chartOptionsBarGrouped" />
              <AppChart class="charts-span" title="Pendaftar per hari" type="line" :chart-data="dailyChart" />
            </div>

            <div class="stats-section">
              <h4>Per Status</h4>
              <div v-if="Object.keys(statsData.by_status || {}).length" class="stats-grid">
                <div v-for="(count, status) in (statsData.by_status || {})" :key="status" class="stats-row">
                  <span class="status-badge" :class="'status-' + status">{{ statusApplicantLabels[status] || status }}</span>
                  <strong>{{ count }}</strong>
                </div>
              </div>
              <p v-else class="stats-empty">—</p>
            </div>

            <div class="stats-section">
              <h4>Per Jalur & kuota</h4>
              <table v-if="channelRows.length" class="data-table data-table-compact data-table-quota">
                <thead>
                  <tr>
                    <th>Jalur</th>
                    <th>Jumlah</th>
                    <th>Kuota</th>
                    <th>Isi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="row in channelRows" :key="row.channel_id">
                    <td>{{ row.channel_name }}</td>
                    <td>{{ row.count }}</td>
                    <td>{{ row.quota ?? '—' }}</td>
                    <td>
                      <div v-if="row.quota" class="kuota-bar-wrap" :title="row.pct + '%'">
                        <div class="kuota-bar" :style="{ width: Math.min(row.pct, 100) + '%' }" :class="{ 'kuota-full': row.pct >= 100 }"></div>
                        <span class="kuota-pct">{{ row.pct }}%</span>
                      </div>
                      <span v-else class="stats-empty">—</span>
                    </td>
                  </tr>
                </tbody>
              </table>
              <p v-else class="stats-empty">—</p>
            </div>
          </template>
        </div>
      </main>
    </div></template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import AppChart from '@/components/AppChart.vue'
import { ppdbPeriodApi, ppdbChannelApi } from '@/api/ppdb'
import { useToast } from '@/composables/useToast'
import { doughnutFromEntries, lineFromSeries, chartOptionsBarGrouped, CHART_PALETTE } from '@/composables/useChart'
import { statusApplicantLabels } from './ppdbConstants'
import './ppdb.css'

const toast = useToast()
const periods = ref([])
const channels = ref([])
const statsPeriodId = ref('')
const statsData = ref(null)
const statsLoading = ref(false)
const highlightStatuses = ['passed', 'reserve', 'failed', 'verified']

const channelRows = computed(() => {
  const rows = statsData.value?.by_channel || []
  return rows.map((row) => {
    const ch = channels.value.find(c => c.id === row.channel_id)
    const quota = ch?.quota ?? null
    const count = row.count ?? 0
    const pct = quota ? Math.round((count / quota) * 100) : 0
    return { ...row, quota, pct }
  })
})

const statusChart = computed(() => {
  const byStatus = statsData.value?.by_status || {}
  return doughnutFromEntries(
    Object.entries(byStatus).map(([key, count], i) => ({
      label: statusApplicantLabels[key] || key,
      value: count,
      color: CHART_PALETTE[i % CHART_PALETTE.length],
    }))
  )
})

const channelChart = computed(() => {
  const rows = channelRows.value
  if (!rows.length) return null
  return {
    labels: rows.map((r) => r.channel_name),
    datasets: [
      {
        label: 'Pendaftar',
        data: rows.map((r) => r.count),
        backgroundColor: '#059669',
        borderRadius: 4,
      },
      {
        label: 'Kuota',
        data: rows.map((r) => r.quota || 0),
        backgroundColor: '#94a3b8',
        borderRadius: 4,
      },
    ],
  }
})

const dailyChart = computed(() => {
  const rows = statsData.value?.by_day || []
  if (!rows.length) return null
  return lineFromSeries(
    rows.map((r) => r.date),
    rows.map((r) => r.count),
    'Pendaftar'
  )
})

async function loadPeriods() {
  try {
    const res = await ppdbPeriodApi.getAll({ per_page: 100 })
    periods.value = res.data.data || []
    const open = periods.value.find(p => p.status === 'open')
    if (open && !statsPeriodId.value) {
      statsPeriodId.value = open.id
      await loadStatistics()
    }
  } catch (e) {
    toast.error('Gagal memuat periode', e.formattedMessage || 'Coba lagi.')
  }
}

async function loadChannels() {
  try {
    const res = await ppdbChannelApi.getAll({ active_only: false })
    channels.value = res.data.data || []
  } catch {
    channels.value = []
  }
}

async function loadStatistics() {
  const id = statsPeriodId.value
  if (!id) {
    statsData.value = null
    return
  }
  statsLoading.value = true
  statsData.value = null
  try {
    const res = await ppdbPeriodApi.getStatistics(id)
    statsData.value = res.data?.data || res.data
  } catch (e) {
    toast.error('Gagal memuat statistik PPDB', e.formattedMessage || 'Statistik tidak dapat dimuat.')
  } finally {
    statsLoading.value = false
  }
}

onMounted(async () => {
  await Promise.all([loadPeriods(), loadChannels()])
})
</script>

<style scoped>
.stats-value-sm { font-size: 1.5rem !important; }
.data-table-quota { max-width: 640px; }
.kuota-bar-wrap {
  position: relative;
  height: 1.35rem;
  background: #f1f5f9;
  border-radius: 6px;
  overflow: hidden;
  min-width: 100px;
}
.kuota-bar {
  height: 100%;
  background: linear-gradient(90deg, #34d399, #059669);
  border-radius: 6px;
}
.kuota-bar.kuota-full { background: linear-gradient(90deg, #f59e0b, #dc2626); }
.kuota-pct {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.72rem;
  font-weight: 700;
  color: #1e293b;
}
.charts-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}
.charts-span { grid-column: 1 / -1; }
@media (max-width: 900px) {
  .charts-grid { grid-template-columns: 1fr; }
  .charts-span { grid-column: auto; }
}
</style>
