<template>    <div class="adoption-page">
      <div class="page-header">
        <div>
          <h2>Monitoring Adopsi</h2>
          <p>Adopsi modul, usage, dan churn lintas sekolah</p>
        </div>
        <div class="header-actions">
          <select v-model="inactiveDays" class="filter-select" @change="loadData">
            <option :value="7">Tidak aktif ≥ 7 hari</option>
            <option :value="30">Tidak aktif ≥ 30 hari</option>
            <option :value="90">Tidak aktif ≥ 90 hari</option>
          </select>
          <select v-model="usageDays" class="filter-select" @change="loadData">
            <option :value="7">Usage 7 hari</option>
            <option :value="14">Usage 14 hari</option>
            <option :value="30">Usage 30 hari</option>
            <option :value="60">Usage 60 hari</option>
          </select>
          <button type="button" class="btn-secondary btn-compact" :disabled="loading" @click="loadData">Muat Ulang</button>
        </div>
      </div>

      <div v-if="loading" class="loading-wrap">
        <LoadingSkeleton type="table" :rows="6" :columns="5" />
      </div>

      <template v-else>
        <div class="stats-grid">
          <div class="stat-card">
            <span class="stat-label">Institusi Aktif</span>
            <strong>{{ summary.active_institutions }}</strong>
          </div>
          <div class="stat-card warn">
            <span class="stat-label">Tanpa Aktivitas</span>
            <strong>{{ summary.inactive_activity_count }}</strong>
          </div>
          <div class="stat-card danger">
            <span class="stat-label">Risiko Churn</span>
            <strong>{{ summary.at_risk_count }}</strong>
          </div>
          <div class="stat-card">
            <span class="stat-label">Event {{ summary.usage_days }}h</span>
            <strong>{{ formatNumber(summary.events_period) }}</strong>
          </div>
          <div class="stat-card">
            <span class="stat-label">Sekolah Aktif (periode)</span>
            <strong>{{ formatNumber(summary.active_institutions_period) }}</strong>
          </div>
          <div class="stat-card danger">
            <span class="stat-label">Dibekukan</span>
            <strong>{{ summary.churned_count }}</strong>
          </div>
        </div>

        <div class="tab-shell">
          <nav class="section-nav" role="tablist" aria-label="Monitoring adopsi">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              type="button"
              :class="['sec-btn', { active: activeTab === tab.id }]"
              @click="activeTab = tab.id"
            >
              <span class="sec-icon" aria-hidden="true">
                <svg v-if="tab.id === 'adoption'" width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 19V5a1 1 0 0 1 1-1h10l5 5v10a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1z" stroke="currentColor" stroke-width="2"/><path d="M14 4v5h5M8 13h8M8 17h5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                <svg v-else-if="tab.id === 'usage'" width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 19V5M4 19h16M8 16l3-5 2 3 3-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 9v4M12 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M10.3 4.3 2.8 17a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
              </span>
              <span class="sec-label">{{ tab.label }}</span>
            </button>
          </nav>
          <div class="tab-main">

        <!-- ADOPSI -->
        <template v-if="activeTab === 'adoption'">
          <section class="panel">
            <div class="panel-header">
              <div>
                <h3>Adopsi Modul</h3>
                <p class="panel-sub">Akses (permission) vs penggunaan nyata (audit) dalam {{ summary.usage_days }} hari</p>
              </div>
              <select v-model="moduleSort" class="filter-select">
                <option value="adoption">Urut: akses</option>
                <option value="usage">Urut: usage</option>
                <option value="events">Urut: event</option>
                <option value="label">Urut: nama</option>
              </select>
            </div>
            <div class="module-list">
              <div v-for="m in sortedModules" :key="m.key" class="module-row module-row-deep">
                <div class="module-meta">
                  <strong>{{ m.label }}</strong>
                  <span>
                    Akses: {{ m.institutions_count }} institusi · {{ m.users_count }} user
                    <template v-if="m.has_usage_tracking">
                      · Usage: {{ m.usage_institutions_count }} institusi · {{ formatNumber(m.events_count) }} event
                    </template>
                    <template v-else>
                      · Usage: belum terlacak
                    </template>
                  </span>
                </div>
                <div class="dual-bars">
                  <div class="dual-bar-row">
                    <span class="dual-label">Akses</span>
                    <div class="module-bar-wrap">
                      <div class="module-bar" :style="{ width: Math.min(m.adoption_pct, 100) + '%' }"></div>
                    </div>
                    <span class="module-pct">{{ m.adoption_pct }}%</span>
                  </div>
                  <div class="dual-bar-row">
                    <span class="dual-label">Usage</span>
                    <div class="module-bar-wrap">
                      <div
                        class="module-bar module-bar-usage"
                        :class="{ muted: !m.has_usage_tracking }"
                        :style="{ width: Math.min(m.usage_pct || 0, 100) + '%' }"
                      ></div>
                    </div>
                    <span class="module-pct">{{ m.has_usage_tracking ? m.usage_pct + '%' : '—' }}</span>
                  </div>
                </div>
              </div>
              <p v-if="modules.length === 0" class="empty-hint">Belum ada data permission guru/staff.</p>
            </div>
          </section>
        </template>

        <!-- USAGE -->
        <template v-else-if="activeTab === 'usage'">
          <div class="panels-grid">
            <section class="panel">
              <div class="panel-header">
                <h3>Tren Aktivitas {{ usage.days }} Hari</h3>
              </div>
              <div class="trend-meta">
                <span>Rata-rata {{ formatNumber(usage.totals?.avg_daily_events) }} event/hari</span>
                <span>{{ formatNumber(usage.totals?.avg_daily_institutions) }} sekolah aktif/hari</span>
              </div>
              <div class="trend-chart" role="img" :aria-label="'Tren aktivitas ' + usage.days + ' hari'">
                <div
                  v-for="point in usage.trend"
                  :key="point.date"
                  class="trend-col"
                  :title="`${point.date}: ${point.events} event, ${point.institutions} sekolah`"
                >
                  <div class="trend-bar" :style="{ height: trendHeight(point.events) + '%' }"></div>
                </div>
              </div>
              <div class="trend-axis">
                <span>{{ usage.trend?.[0]?.date || '' }}</span>
                <span>{{ usage.trend?.[usage.trend.length - 1]?.date || '' }}</span>
              </div>
            </section>

            <section class="panel">
              <div class="panel-header">
                <h3>Modul Terpakai</h3>
              </div>
              <div class="module-list compact">
                <div v-for="m in usage.by_module" :key="m.key" class="module-row">
                  <div class="module-meta">
                    <strong>{{ m.label }}</strong>
                    <span>{{ m.institutions_count }} institusi</span>
                  </div>
                  <div class="module-bar-wrap">
                    <div class="module-bar module-bar-usage" :style="{ width: usageModuleWidth(m.events_count) + '%' }"></div>
                  </div>
                  <span class="module-pct">{{ formatNumber(m.events_count) }}</span>
                </div>
                <p v-if="!(usage.by_module || []).length" class="empty-hint">Belum ada aktivitas terlacak.</p>
              </div>
            </section>
          </div>
        </template>

        <!-- CHURN -->
        <template v-else-if="activeTab === 'churn'">
          <div class="stats-grid churn-stats">
            <div class="stat-card">
              <span class="stat-label">Churn Rate (periode)</span>
              <strong>{{ churn.churn_rate_pct }}%</strong>
            </div>
            <div class="stat-card danger">
              <span class="stat-label">Baru Dibekukan</span>
              <strong>{{ churn.recently_churned_count }}</strong>
            </div>
            <div class="stat-card">
              <span class="stat-label">Institusi Baru</span>
              <strong>{{ churn.new_institutions_count }}</strong>
            </div>
            <div class="stat-card" :class="churn.net_institutions >= 0 ? '' : 'danger'">
              <span class="stat-label">Net Institusi</span>
              <strong>{{ churn.net_institutions >= 0 ? '+' : '' }}{{ churn.net_institutions }}</strong>
            </div>
            <div class="stat-card warn">
              <span class="stat-label">Menurun ≥40%</span>
              <strong>{{ churn.declining_count }}</strong>
            </div>
          </div>

          <div class="risk-legend">
            <span class="risk-pill high">Tinggi {{ churn.risk_breakdown?.high || 0 }}</span>
            <span class="risk-pill medium">Sedang {{ churn.risk_breakdown?.medium || 0 }}</span>
            <span class="risk-pill low">Rendah {{ churn.risk_breakdown?.low || 0 }}</span>
            <span class="risk-pill churned">Dibekukan {{ churn.risk_breakdown?.churned || 0 }}</span>
          </div>

          <div class="panels-grid">
            <section class="panel">
              <div class="panel-header"><h3>Sekolah Berisiko</h3></div>
              <div class="mini-list">
                <div v-for="inst in churn.at_risk" :key="'risk-' + inst.id" class="mini-row">
                  <div>
                    <strong>{{ inst.name }}</strong>
                    <div class="sub">{{ formatNumber(inst.events_current) }} event · Δ {{ formatChange(inst.events_change_pct) }}</div>
                  </div>
                  <span class="risk-pill" :class="inst.churn_risk">{{ riskLabel(inst.churn_risk) }}</span>
                </div>
                <p v-if="!(churn.at_risk || []).length" class="empty-hint">Tidak ada sekolah berisiko.</p>
              </div>
            </section>

            <section class="panel">
              <div class="panel-header"><h3>Aktivitas Menurun</h3></div>
              <div class="mini-list">
                <div v-for="inst in churn.declining" :key="'dec-' + inst.id" class="mini-row">
                  <div>
                    <strong>{{ inst.name }}</strong>
                    <div class="sub">{{ formatNumber(inst.events_previous) }} → {{ formatNumber(inst.events_current) }}</div>
                  </div>
                  <span class="change-neg">{{ formatChange(inst.events_change_pct) }}</span>
                </div>
                <p v-if="!(churn.declining || []).length" class="empty-hint">Tidak ada penurunan signifikan.</p>
              </div>
            </section>
          </div>
        </template>

        <!-- INSTITUTIONS TABLE (all tabs) -->
        <section class="panel">
          <div class="panel-header">
            <h3>Aktivitas per Institusi</h3>
            <div class="table-filters">
              <select v-model="riskFilter" class="filter-select">
                <option value="">Semua risiko</option>
                <option value="high">Risiko tinggi</option>
                <option value="medium">Risiko sedang</option>
                <option value="low">Risiko rendah</option>
                <option value="churned">Dibekukan</option>
              </select>
              <input v-model="search" class="search-input" placeholder="Cari institusi..." />
            </div>
          </div>
          <div class="table-container">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Institusi</th>
                  <th>Siswa</th>
                  <th>Guru</th>
                  <th>Event</th>
                  <th>Δ Usage</th>
                  <th>Modul</th>
                  <th>Aktivitas Terakhir</th>
                  <th>Risiko</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="inst in filteredInstitutions"
                  :key="inst.id"
                  :class="{ 'row-warn': inst.churn_risk === 'high' || inst.churn_risk === 'medium' }"
                >
                  <td>
                    <strong>{{ inst.name }}</strong>
                    <div class="sub">{{ inst.npsn || '—' }} · {{ inst.level || '—' }}</div>
                  </td>
                  <td>{{ formatNumber(inst.active_students_count) }}</td>
                  <td>{{ formatNumber(inst.active_teachers_count) }}</td>
                  <td>
                    <strong>{{ formatNumber(inst.events_current) }}</strong>
                    <div class="sub">{{ inst.avg_events_per_day }}/hari</div>
                  </td>
                  <td>
                    <span :class="changeClass(inst.events_change_pct)">{{ formatChange(inst.events_change_pct) }}</span>
                  </td>
                  <td>{{ inst.modules_used }}</td>
                  <td>{{ formatRelative(inst.last_activity_at) }}</td>
                  <td>
                    <span class="risk-pill" :class="inst.churn_risk">{{ riskLabel(inst.churn_risk) }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
            <p v-if="filteredInstitutions.length === 0" class="empty-hint">Tidak ada institusi.</p>
          </div>
        </section>
          </div>
        </div>
      </template>
    </div></template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { superAdminPlatformApi } from '@/api/superAdminPlatform'
import { useToast } from '@/composables/useToast'

const toast = useToast()
const loading = ref(true)
const inactiveDays = ref(30)
const usageDays = ref(30)
const search = ref('')
const riskFilter = ref('')
const activeTab = ref('adoption')
const moduleSort = ref('adoption')

const tabs = [
  { id: 'adoption', label: 'Adopsi Modul' },
  { id: 'usage', label: 'Usage' },
  { id: 'churn', label: 'Churn' }
]

const summary = ref({
  active_institutions: 0,
  inactive_activity_count: 0,
  no_admin_count: 0,
  at_risk_count: 0,
  churned_count: 0,
  total_students: 0,
  total_teachers: 0,
  events_period: 0,
  active_institutions_period: 0,
  usage_days: 30
})
const modules = ref([])
const institutions = ref([])
const usage = ref({ days: 30, trend: [], by_module: [], totals: {} })
const churn = ref({
  churn_rate_pct: 0,
  recently_churned_count: 0,
  new_institutions_count: 0,
  net_institutions: 0,
  declining_count: 0,
  risk_breakdown: {},
  at_risk: [],
  declining: []
})

const sortedModules = computed(() => {
  const list = [...modules.value]
  const sort = moduleSort.value
  list.sort((a, b) => {
    if (sort === 'usage') return (b.usage_pct || 0) - (a.usage_pct || 0)
    if (sort === 'events') return (b.events_count || 0) - (a.events_count || 0)
    if (sort === 'label') return (a.label || '').localeCompare(b.label || '', 'id')
    return (b.adoption_pct || 0) - (a.adoption_pct || 0)
  })
  return list
})

const filteredInstitutions = computed(() => {
  const q = search.value.trim().toLowerCase()
  return institutions.value.filter((i) => {
    if (riskFilter.value && i.churn_risk !== riskFilter.value) return false
    if (!q) return true
    return (i.name || '').toLowerCase().includes(q) || (i.npsn || '').toLowerCase().includes(q)
  })
})

const maxTrendEvents = computed(() => {
  const values = (usage.value.trend || []).map((p) => p.events || 0)
  return Math.max(1, ...values)
})

const maxModuleEvents = computed(() => {
  const values = (usage.value.by_module || []).map((m) => m.events_count || 0)
  return Math.max(1, ...values)
})

const formatNumber = (n) => new Intl.NumberFormat('id-ID').format(n || 0)

const formatChange = (pct) => {
  if (pct === null || pct === undefined) return '—'
  const sign = pct > 0 ? '+' : ''
  return `${sign}${pct}%`
}

const changeClass = (pct) => {
  if (pct === null || pct === undefined) return ''
  if (pct > 5) return 'change-pos'
  if (pct < -5) return 'change-neg'
  return ''
}

const riskLabel = (risk) => ({
  high: 'Tinggi',
  medium: 'Sedang',
  low: 'Rendah',
  churned: 'Dibekukan'
}[risk] || risk)

const trendHeight = (events) => Math.max(4, Math.round((events / maxTrendEvents.value) * 100))
const usageModuleWidth = (events) => Math.max(4, Math.round((events / maxModuleEvents.value) * 100))

const formatRelative = (iso) => {
  if (!iso) return 'Belum ada'
  const date = new Date(iso)
  const mins = Math.floor((Date.now() - date.getTime()) / 60000)
  if (mins < 60) return `${Math.max(mins, 0)} mnt lalu`
  const hours = Math.floor(mins / 60)
  if (hours < 24) return `${hours} jam lalu`
  const days = Math.floor(hours / 24)
  if (days < 30) return `${days} hari lalu`
  return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

const loadData = async () => {
  loading.value = true
  try {
    const res = await superAdminPlatformApi.getAdoption({
      inactive_days: inactiveDays.value,
      usage_days: usageDays.value
    })
    const data = res.data?.data || {}
    summary.value = { ...summary.value, ...(data.summary || {}) }
    modules.value = data.modules || []
    institutions.value = data.institutions || []
    usage.value = data.usage || { days: usageDays.value, trend: [], by_module: [], totals: {} }
    churn.value = data.churn || churn.value
  } catch (err) {
    toast.error('Gagal', err.response?.data?.message || 'Gagal memuat monitoring')
  } finally {
    loading.value = false
  }
}

onMounted(loadData)
</script>

<style scoped>
.adoption-page { width: 100%; }
.page-header {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}
.page-header h2 { margin: 0 0 4px; font-size: 24px; color: #0f172a; }
.page-header p { margin: 0; color: #64748b; font-size: 14px; }
.header-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 12px;
  margin-bottom: 16px;
}
.stat-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 16px;
}
.stat-card.warn { border-color: #fcd34d; }
.stat-card.danger { border-color: #fca5a5; }
.stat-label { display: block; font-size: 12px; color: #64748b; margin-bottom: 6px; }
.stat-card strong { font-size: 22px; color: #0f172a; }
.tab-shell {
  display: grid;
  grid-template-columns: 188px minmax(0, 1fr);
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
  overflow: hidden;
  min-height: 360px;
}
.section-nav {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 12px;
  background: #f8fafc;
  border-right: 1px solid #eef2f7;
}
.sec-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 9px 10px;
  border: none;
  background: transparent;
  border-radius: 10px;
  cursor: pointer;
  color: #64748b;
  text-align: left;
}
.sec-btn:hover:not(.active) { background: #fff; color: #0f172a; }
.sec-btn.active { background: #fff; color: #065f46; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06), 0 0 0 1px #e2e8f0; }
.sec-icon {
  display: inline-flex; align-items: center; justify-content: center;
  width: 32px; height: 32px; border-radius: 8px; background: #ecfdf5; color: #059669; flex-shrink: 0;
}
.sec-btn.active .sec-icon { background: #d1fae5; color: #047857; }
.sec-label { font-size: 13.5px; font-weight: 600; letter-spacing: -0.01em; line-height: 1.3; }
.tab-main { min-width: 0; padding: 14px 16px 16px; }
@media (max-width: 768px) {
  .tab-shell { grid-template-columns: 1fr; min-height: 0; }
  .section-nav {
    flex-direction: row; overflow-x: auto; border-right: none; border-bottom: 1px solid #eef2f7;
    -webkit-overflow-scrolling: touch; scrollbar-width: none;
  }
  .section-nav::-webkit-scrollbar { display: none; }
  .sec-btn { width: auto; flex: 1 0 auto; }
}
.panel {
  background: transparent;
  border: none;
  border-radius: 0;
  padding: 0 0 16px;
  margin-bottom: 16px;
}
.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  margin-bottom: 14px;
  flex-wrap: wrap;
}
.panel-header h3 { margin: 0; font-size: 16px; color: #0f172a; }
.panel-sub { margin: 4px 0 0; font-size: 12px; color: #94a3b8; }
.panels-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
  margin-bottom: 16px;
}
.module-row {
  display: grid;
  grid-template-columns: minmax(140px, 1.2fr) 1fr 64px;
  gap: 12px;
  align-items: center;
  padding: 10px 0;
  border-top: 1px solid #f1f5f9;
}
.module-row-deep {
  grid-template-columns: minmax(180px, 1.1fr) 1.4fr;
  align-items: start;
}
.module-meta { display: flex; flex-direction: column; gap: 2px; }
.module-meta span { font-size: 12px; color: #94a3b8; }
.dual-bars { display: flex; flex-direction: column; gap: 6px; }
.dual-bar-row {
  display: grid;
  grid-template-columns: 44px 1fr 48px;
  gap: 8px;
  align-items: center;
}
.dual-label { font-size: 11px; color: #94a3b8; }
.module-bar-wrap {
  height: 8px;
  background: #f1f5f9;
  border-radius: 999px;
  overflow: hidden;
}
.module-bar {
  height: 100%;
  background: #059669;
  border-radius: 999px;
}
.module-bar-usage { background: #2563eb; }
.module-bar-usage.muted { background: #cbd5e1; width: 0 !important; }
.module-pct { text-align: right; font-size: 13px; font-weight: 600; color: #334155; }
.search-input, .filter-select {
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 8px 12px;
  font-size: 13px;
}
.table-filters { display: flex; gap: 8px; flex-wrap: wrap; }
.table-container { overflow: auto; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td {
  padding: 12px 10px;
  border-bottom: 1px solid #f1f5f9;
  text-align: left;
  font-size: 14px;
}
.data-table th {
  font-size: 12px;
  color: #64748b;
  text-transform: uppercase;
}
.sub { font-size: 12px; color: #94a3b8; }
.row-warn td { background: #fffbeb; }
.trend-meta {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
  font-size: 12px;
  color: #64748b;
  margin-bottom: 12px;
}
.trend-chart {
  display: flex;
  align-items: flex-end;
  gap: 3px;
  height: 140px;
  padding: 8px 0;
  border-bottom: 1px solid #e2e8f0;
}
.trend-col {
  flex: 1;
  height: 100%;
  display: flex;
  align-items: flex-end;
  min-width: 0;
}
.trend-bar {
  width: 100%;
  background: linear-gradient(180deg, #3b82f6, #1d4ed8);
  border-radius: 4px 4px 0 0;
  min-height: 4px;
}
.trend-axis {
  display: flex;
  justify-content: space-between;
  margin-top: 6px;
  font-size: 11px;
  color: #94a3b8;
}
.mini-list { display: flex; flex-direction: column; gap: 10px; }
.mini-row {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: center;
  padding-bottom: 10px;
  border-bottom: 1px solid #f1f5f9;
}
.risk-legend {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-bottom: 16px;
}
.risk-pill {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 600;
}
.risk-pill.high { background: rgba(239,68,68,.12); color: #dc2626; }
.risk-pill.medium { background: rgba(245,158,11,.15); color: #b45309; }
.risk-pill.low { background: rgba(16,185,129,.12); color: #059669; }
.risk-pill.churned { background: rgba(100,116,139,.15); color: #475569; }
.change-pos { color: #059669; font-weight: 600; }
.change-neg { color: #dc2626; font-weight: 600; }
.empty-hint { color: #94a3b8; font-size: 14px; margin: 8px 0 0; }
.btn-secondary {
  border: 1px solid #e2e8f0;
  background: white;
  border-radius: 10px;
  padding: 8px 12px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}
@media (max-width: 900px) {
  .panels-grid { grid-template-columns: 1fr; }
}
@media (max-width: 700px) {
  .module-row, .module-row-deep { grid-template-columns: 1fr; }
  .page-header { flex-direction: column; align-items: stretch; }
  .header-actions { width: 100%; flex-direction: column; }
  .header-actions > * { width: 100%; }
  .panel-header { flex-direction: column; align-items: stretch; }
  .search-input, .filter-select { width: 100%; }
  .stats-grid { grid-template-columns: 1fr 1fr; }
  .table-container { overflow-x: auto; -webkit-overflow-scrolling: touch; }
  .data-table { min-width: 760px; }
}
@media (max-width: 480px) {
  .page-header h2 { font-size: 1.25rem; }
  .panel { padding: 14px; }
  .stats-grid { grid-template-columns: 1fr; }
}
</style>
