<template>
  <Layout>
    <div class="laporan-bk-page">
      <div class="toolbar">
        <div class="mode-tabs" role="tablist" aria-label="Tampilan laporan BK">
          <button type="button" role="tab" :class="['mode-tab', { active: viewTab === 'ringkasan' }]" @click="switchTab('ringkasan')">
            Ringkasan
          </button>
          <button type="button" role="tab" :class="['mode-tab', { active: viewTab === 'skor' }]" @click="switchTab('skor')">
            Skor siswa
          </button>
          <button type="button" role="tab" :class="['mode-tab', { active: viewTab === 'catatan' }]" @click="switchTab('catatan')">
            Catatan
          </button>
        </div>
        <div class="header-actions">
          <div class="export-wrap" ref="exportWrapRef">
            <button type="button" class="btn-secondary btn-compact" :disabled="exporting || printing || loading" @click.stop="exportOpen = !exportOpen">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="12" cy="6" r="1.4" fill="currentColor"/>
                <circle cx="12" cy="12" r="1.4" fill="currentColor"/>
                <circle cx="12" cy="18" r="1.4" fill="currentColor"/>
              </svg>
              <span>{{ exporting || printing ? 'Menyiapkan...' : 'Cetak / Export' }}</span>
            </button>
            <div v-if="exportOpen" class="export-menu" role="menu">
              <button type="button" role="menuitem" :disabled="printing || loading" @click="printPdf">Cetak PDF</button>
              <button type="button" role="menuitem" :disabled="exporting || loading" @click="exportCsv">
                {{ isDetailTab ? 'Export detail CSV' : 'Export rekap CSV' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <p class="toolbar-hint">
        <template v-if="isHomeroomScoped">Menampilkan siswa di kelas yang Anda walikan saja.</template>
        <template v-else-if="viewTab === 'ringkasan'">Rekap kelas. Skor bersih = poin pelanggaran − poin prestasi.</template>
        <template v-else-if="viewTab === 'skor'">Skor per siswa = poin pelanggaran − poin prestasi.</template>
        <template v-else>Catatan pelanggaran, prestasi, dan konseling sesuai filter.</template>
      </p>

      <div class="filters">
        <label class="filter-field">
          <span>Tahun ajaran</span>
          <select v-model="filters.academic_year_id" class="filter-select" @change="onPeriodChange">
            <option value="">Semua</option>
            <option v-for="y in academicYears" :key="y.id" :value="String(y.id)">{{ y.name }}</option>
          </select>
        </label>
        <label class="filter-field">
          <span>Semester</span>
          <select v-model="filters.semester_id" class="filter-select" @change="reload">
            <option value="">Semua</option>
            <option v-for="s in filteredSemesters" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
          </select>
        </label>
        <label class="filter-field">
          <span>Kelas</span>
          <select v-model="filters.class_id" class="filter-select" @change="reload" :disabled="isHomeroomScoped && classes.length <= 1">
            <option v-if="!isHomeroomScoped" value="">Semua kelas</option>
            <option v-for="c in classes" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
          </select>
        </label>
        <button type="button" class="btn-ghost" :class="{ active: periodAdvanced }" @click="periodAdvanced = !periodAdvanced">
          Periode lanjutan
        </button>
      </div>

      <div v-if="periodAdvanced" class="filters filters-advanced">
        <label class="filter-field">
          <span>Bulan</span>
          <select v-model="filters.month" class="filter-select" @change="reload">
            <option value="">Semua bulan</option>
            <option v-for="m in 12" :key="m" :value="String(m)">{{ monthFullLabel(m) }}</option>
          </select>
        </label>
        <label class="filter-field">
          <span>Tahun kalender</span>
          <select v-model="filters.year" class="filter-select" @change="reload">
            <option v-for="y in chartYears" :key="y" :value="String(y)">{{ y }}</option>
          </select>
        </label>
      </div>

      <div v-if="activeChips.length" class="filter-chips">
        <button
          v-for="chip in activeChips"
          :key="chip.key"
          type="button"
          class="filter-chip"
          :disabled="chip.disabled"
          @click="clearChip(chip.key)"
        >
          {{ chip.label }}
          <span v-if="!chip.disabled" aria-hidden="true">×</span>
        </button>
      </div>

      <div v-if="loading" class="loading-state">
        <div class="loading-spinner"></div>
        <p>Memuat laporan BK...</p>
      </div>

      <!-- ========== RINGKASAN ========== -->
      <template v-else-if="viewTab === 'ringkasan' && report">
        <div class="stat-cards">
          <button type="button" class="stat-card" @click="openNotes('violations')">
            <span class="stat-label">Pelanggaran</span>
            <span class="stat-value">{{ report.summary?.total_violations ?? 0 }}</span>
            <span class="stat-sub">{{ report.summary?.total_violation_points ?? 0 }} poin</span>
          </button>
          <button type="button" class="stat-card stat-card-good" @click="openNotes('achievements')">
            <span class="stat-label">Prestasi</span>
            <span class="stat-value">{{ report.summary?.total_achievements ?? 0 }}</span>
            <span class="stat-sub">−{{ report.summary?.total_achievement_points ?? 0 }} poin</span>
          </button>
          <button type="button" class="stat-card stat-card-score" @click="switchTab('skor')">
            <span class="stat-label">Skor bersih</span>
            <span class="stat-value">{{ report.summary?.net_score ?? 0 }}</span>
            <span class="stat-sub">pelanggaran − prestasi</span>
          </button>
          <button type="button" class="stat-card" @click="openNotes('counseling')">
            <span class="stat-label">Konseling</span>
            <span class="stat-value">{{ report.summary?.total_counseling ?? 0 }}</span>
            <span class="stat-sub">sesi tercatat</span>
          </button>
        </div>

        <section class="report-section">
          <h3 class="section-title">Per kelas</h3>
          <div v-if="!report.by_class?.length" class="empty-state">
            <h3 class="empty-title">Belum ada data</h3>
            <p class="empty-desc">Tidak ada pelanggaran, prestasi, atau konseling untuk filter yang dipilih.</p>
          </div>
          <div v-else class="table-card">
            <div class="table-wrap">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Kelas</th>
                    <th class="th-num">Pelanggaran</th>
                    <th class="th-num">Prestasi</th>
                    <th class="th-num">Konseling</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="row in report.by_class"
                    :key="row.class_id ?? row.class_name"
                    class="row-clickable"
                    @click="drillToClass(row)"
                  >
                    <td>{{ row.class_name }}</td>
                    <td class="td-num">{{ row.violation_count }}</td>
                    <td class="td-num td-good">{{ row.achievement_count ?? 0 }}</td>
                    <td class="td-num">{{ row.counseling_count }}</td>
                    <td class="td-action">
                      <button type="button" class="link-btn" @click.stop="drillToClass(row)">Skor siswa →</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <section class="report-section">
          <div class="section-head">
            <h3 class="section-title">Tren {{ report.by_month?.year }}</h3>
            <select v-model="filters.year" class="filter-select filter-select-sm" @change="reloadSummaryOnly" title="Tahun kalender untuk grafik tren">
              <option v-for="y in chartYears" :key="'t'+y" :value="String(y)">{{ y }}</option>
            </select>
          </div>
          <div class="chart-box" v-if="monthChartData">
            <div class="chart-wrap">
              <Bar :data="monthChartData" :options="chartOptionsBar" />
            </div>
          </div>
        </section>

        <div class="split-tables">
          <section class="report-section">
            <h3 class="section-title">Jenis pelanggaran</h3>
            <div v-if="report.top_violation_types?.length" class="table-card">
              <div class="table-wrap">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Jenis</th>
                      <th class="th-num">Jumlah</th>
                      <th class="th-num">Poin</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="row in report.top_violation_types" :key="row.violation_type_id ?? row.type_name">
                      <td>{{ row.type_name }}</td>
                      <td class="td-num">{{ row.count }}</td>
                      <td class="td-num">{{ row.total_points }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <p v-else class="empty-desc muted">Belum ada catatan pelanggaran.</p>
          </section>
          <section class="report-section">
            <h3 class="section-title">Jenis prestasi</h3>
            <div v-if="report.top_achievement_types?.length" class="table-card">
              <div class="table-wrap">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Jenis</th>
                      <th class="th-num">Jumlah</th>
                      <th class="th-num">Poin</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="row in report.top_achievement_types" :key="row.achievement_type_id ?? row.type_name">
                      <td>{{ row.type_name }}</td>
                      <td class="td-num">{{ row.count }}</td>
                      <td class="td-num td-good">{{ row.total_points }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <p v-else class="empty-desc muted">Belum ada catatan prestasi.</p>
          </section>
        </div>
      </template>

      <!-- ========== SKOR SISWA ========== -->
      <template v-else-if="viewTab === 'skor'">
        <p class="section-hint">Contoh: 40 poin pelanggaran − 20 poin prestasi = <strong>20</strong>.</p>
        <div v-if="!detail?.by_student?.length" class="empty-state">
          <h3 class="empty-title">Belum ada data</h3>
          <p class="empty-desc">Tidak ada siswa dengan pelanggaran atau prestasi untuk filter ini.</p>
        </div>
        <div v-else class="table-card">
          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>NIS</th>
                  <th>Nama</th>
                  <th class="th-num">Pelanggaran</th>
                  <th class="th-num">Prestasi</th>
                  <th class="th-num">Skor</th>
                </tr>
              </thead>
              <tbody>
                <template v-for="group in bkStudentGroups" :key="'bk-' + group.key">
                  <tr class="group-row">
                    <td colspan="6">Kelas {{ group.name }} · {{ group.rows.length }} siswa</td>
                  </tr>
                  <tr v-for="(row, i) in group.rows" :key="row.student_id ?? (group.key + '-' + i)">
                    <td>{{ group.start + i + 1 }}</td>
                    <td>{{ row.nis || '—' }}</td>
                    <td>{{ row.student_name || '—' }}</td>
                    <td class="td-num">{{ row.violation_points ?? row.total_points }} <span class="td-muted">({{ row.violation_count }})</span></td>
                    <td class="td-num td-good">−{{ row.achievement_points ?? 0 }} <span class="td-muted">({{ row.achievement_count ?? 0 }})</span></td>
                    <td class="td-num td-total" :class="scoreClass(row.score)">{{ row.score ?? ((row.violation_points ?? row.total_points) - (row.achievement_points ?? 0)) }}</td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </div>
      </template>

      <!-- ========== CATATAN ========== -->
      <template v-else-if="viewTab === 'catatan'">
        <div class="notes-tabs" role="tablist" aria-label="Jenis catatan">
          <button type="button" :class="['notes-tab', { active: notesKind === 'violations' }]" @click="notesKind = 'violations'">
            Pelanggaran ({{ detail?.total ?? 0 }})
          </button>
          <button type="button" :class="['notes-tab', { active: notesKind === 'achievements' }]" @click="notesKind = 'achievements'">
            Prestasi ({{ detail?.achievements_total ?? detail?.achievements?.length ?? 0 }})
          </button>
          <button type="button" :class="['notes-tab', { active: notesKind === 'counseling' }]" @click="notesKind = 'counseling'">
            Konseling ({{ detail?.counseling_total ?? detail?.counseling?.length ?? 0 }})
          </button>
        </div>

        <p v-if="detail?.truncated" class="section-hint">Ditampilkan maksimal 2000 baris per jenis catatan.</p>

        <section v-if="notesKind === 'violations'" class="report-section">
          <div v-if="!detail?.items?.length" class="empty-state">
            <h3 class="empty-title">Belum ada pelanggaran</h3>
            <p class="empty-desc">Tidak ada catatan untuk filter yang dipilih.</p>
          </div>
          <div v-else class="table-card">
            <div class="table-wrap">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Tanggal</th>
                    <th>NIS</th>
                    <th>Nama</th>
                    <th>Kelas</th>
                    <th>Jenis</th>
                    <th class="th-num">Poin</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="row in detail.items" :key="row.id">
                    <td>{{ formatDate(row.violation_date) }}</td>
                    <td>{{ row.nis || '—' }}</td>
                    <td>{{ row.student_name || '—' }}</td>
                    <td>{{ row.class_name }}</td>
                    <td>{{ row.violation_type }}</td>
                    <td class="td-num">{{ row.point_weight }}</td>
                    <td><span class="badge">{{ statusLabel(row.status) }}</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <section v-else-if="notesKind === 'achievements'" class="report-section">
          <div v-if="!(detail?.achievements?.length)" class="empty-state">
            <h3 class="empty-title">Belum ada prestasi</h3>
            <p class="empty-desc">Tidak ada catatan prestasi untuk filter yang dipilih.</p>
          </div>
          <div v-else class="table-card">
            <div class="table-wrap">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Tanggal</th>
                    <th>NIS</th>
                    <th>Nama</th>
                    <th>Kelas</th>
                    <th>Jenis Prestasi</th>
                    <th class="th-num">Poin</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="row in detail.achievements" :key="row.id">
                    <td>{{ formatDate(row.achievement_date) }}</td>
                    <td>{{ row.nis || '—' }}</td>
                    <td>{{ row.student_name || '—' }}</td>
                    <td>{{ row.class_name }}</td>
                    <td>{{ row.achievement_type }}</td>
                    <td class="td-num td-good">−{{ row.point_value }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <section v-else class="report-section">
          <div v-if="!(detail?.counseling?.length)" class="empty-state">
            <h3 class="empty-title">Belum ada konseling</h3>
            <p class="empty-desc">Tidak ada sesi konseling untuk filter yang dipilih.</p>
          </div>
          <div v-else class="table-card">
            <div class="table-wrap">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Tanggal</th>
                    <th>NIS</th>
                    <th>Nama</th>
                    <th>Kelas</th>
                    <th>Jenis</th>
                    <th>Status</th>
                    <th>Konselor</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="row in detail.counseling" :key="row.id">
                    <td>{{ formatDate(row.session_date) }}</td>
                    <td>{{ row.nis || '—' }}</td>
                    <td>{{ row.student_name || '—' }}</td>
                    <td>{{ row.class_name }}</td>
                    <td>{{ row.counseling_type }}</td>
                    <td><span class="badge">{{ counselingStatusLabel(row.status) }}</span></td>
                    <td>{{ row.counselor_name || '—' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>
      </template>

      <div v-else-if="!loading" class="empty-state">
        <h3 class="empty-title">Gagal memuat laporan</h3>
        <p class="empty-desc">Coba ubah filter atau periksa koneksi.</p>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { Bar } from 'vue-chartjs'
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend } from 'chart.js'
import Layout from '@/components/Layout.vue'
import { bkReportApi } from '@/api/bkReport'
import { institutionApi } from '@/api/institution'
import { classApi } from '@/api/class'
import { semesterApi } from '@/api/semester'
import { useReferenceDataStore } from '@/stores/referenceData'
import { useAuthStore } from '@/stores/auth'
import { getPrincipalTitle, getNssLabel } from '@/utils/institution'
import { useToast } from '@/composables/useToast'

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

const toast = useToast()
const route = useRoute()
const referenceStore = useReferenceDataStore()
const authStore = useAuthStore()

const loading = ref(true)
const exporting = ref(false)
const printing = ref(false)
const exportOpen = ref(false)
const exportWrapRef = ref(null)
const viewTab = ref('ringkasan') // ringkasan | skor | catatan
const notesKind = ref('violations') // violations | achievements | counseling
const periodAdvanced = ref(false)
const report = ref(null)
const detail = ref(null)
const signers = ref({ principal: {}, bk: {} })
const classes = ref([])
const semesters = ref([])
const institution = ref(null)

const isHomeroomScoped = computed(() => authStore.user?.bk_scope === 'homeroom')
const homeroomClassIds = computed(() => (authStore.user?.homeroom_class_ids || []).map(Number))
const isDetailTab = computed(() => viewTab.value !== 'ringkasan')

const now = new Date()
const currentYear = now.getFullYear()
const chartYears = [currentYear, currentYear - 1, currentYear - 2]

const filters = ref({
  academic_year_id: '',
  semester_id: '',
  class_id: '',
  month: '',
  year: String(currentYear),
})

const academicYears = computed(() => referenceStore.academicYears || [])

const filteredSemesters = computed(() => {
  const yearId = filters.value.academic_year_id
  if (!yearId) return semesters.value
  return semesters.value.filter(s => String(s.academic_year_id) === String(yearId))
})

const MONTH_NAMES = [
  '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
]

const STATUS_LABELS = {
  dicatat: 'Dicatat',
  sanksi_diberikan: 'Sanksi Diberikan',
  follow_up: 'Follow Up',
  selesai: 'Selesai',
}

const COUNSELING_STATUS_LABELS = {
  berlangsung: 'Berlangsung',
  selesai: 'Selesai',
}

function monthFullLabel(m) {
  return MONTH_NAMES[m] || `Bulan ${m}`
}

function statusLabel(s) {
  return STATUS_LABELS[s] || s || '—'
}

function counselingStatusLabel(s) {
  return COUNSELING_STATUS_LABELS[s] || s || '—'
}

function scoreClass(score) {
  const n = Number(score)
  if (Number.isNaN(n)) return ''
  if (n > 40) return 'score-bad'
  if (n > 20) return 'score-warn'
  if (n <= 0) return 'score-good'
  return ''
}

function formatDate(iso) {
  if (!iso) return '—'
  const [y, m, d] = iso.split('-')
  if (!y || !m || !d) return iso
  return `${d}/${m}/${y}`
}

function groupRowsByClassName(rows) {
  const groups = []
  let current = null
  let index = 0
  for (const row of rows || []) {
    const name = row.class_name || 'Tanpa kelas'
    if (!current || current.name !== name) {
      current = { key: name, name, rows: [], start: index }
      groups.push(current)
    }
    current.rows.push(row)
    index += 1
  }
  return groups
}

const bkStudentGroups = computed(() => groupRowsByClassName(detail.value?.by_student || []))

const selectedClassName = computed(() => {
  const cls = classes.value.find(c => String(c.id) === String(filters.value.class_id))
  return cls?.name || ''
})

const activeChips = computed(() => {
  const chips = []
  if (filters.value.class_id && selectedClassName.value) {
    chips.push({
      key: 'class',
      label: `Kelas ${selectedClassName.value}`,
      disabled: isHomeroomScoped.value && classes.value.length <= 1,
    })
  }
  if (periodAdvanced.value && filters.value.month) {
    chips.push({
      key: 'month',
      label: `${monthFullLabel(Number(filters.value.month))} ${filters.value.year}`,
      disabled: false,
    })
  }
  return chips
})

const periodLabel = computed(() => {
  const parts = []
  parts.push(selectedClassName.value || 'Semua kelas')
  const yearName = academicYears.value.find(y => String(y.id) === String(filters.value.academic_year_id))?.name
  if (yearName) parts.push(yearName)
  const semName = filteredSemesters.value.find(s => String(s.id) === String(filters.value.semester_id))?.name
  if (semName) parts.push(semName)
  if (periodAdvanced.value && filters.value.month) {
    parts.push(`${monthFullLabel(Number(filters.value.month))} ${filters.value.year}`)
  }
  return parts.join(' · ')
})

function cleanParams({ forDetail = false } = {}) {
  const params = {
    academic_year_id: filters.value.academic_year_id,
    semester_id: filters.value.semester_id,
    class_id: filters.value.class_id,
    year: filters.value.year,
  }
  const applyCalendarPeriod = periodAdvanced.value && !!filters.value.month
  if (forDetail) {
    if (applyCalendarPeriod) {
      params.month = filters.value.month
    } else {
      delete params.year
    }
  } else if (applyCalendarPeriod) {
    params.month = filters.value.month
  }
  Object.keys(params).forEach((k) => {
    if (k === 'academic_year_id' || k === 'semester_id') return
    if (params[k] === '' || params[k] === null || params[k] === undefined) delete params[k]
  })
  return params
}

const monthChartData = computed(() => {
  const months = report.value?.by_month?.months
  if (!months?.length) return null
  return {
    labels: months.map(m => m.label || MONTH_NAMES[m.month]),
    datasets: [
      {
        label: 'Pelanggaran',
        data: months.map(m => m.violation_count),
        backgroundColor: 'rgba(239, 68, 68, 0.7)',
        borderRadius: 4,
      },
      {
        label: 'Prestasi',
        data: months.map(m => m.achievement_count ?? 0),
        backgroundColor: 'rgba(16, 185, 129, 0.7)',
        borderRadius: 4,
      },
      {
        label: 'Konseling',
        data: months.map(m => m.counseling_count),
        backgroundColor: 'rgba(14, 165, 233, 0.7)',
        borderRadius: 4,
      },
    ],
  }
})

const chartOptionsBar = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'bottom' } },
  scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
}

function captureSigners(payload) {
  if (payload?.signers) signers.value = payload.signers
}

async function loadSummary() {
  loading.value = true
  try {
    const res = await bkReportApi.getSummary(cleanParams({ forDetail: false }))
    report.value = res.data?.data ?? null
    captureSigners(report.value)
  } catch (e) {
    report.value = null
    toast.error('Gagal memuat ringkasan', e.formattedMessage || 'Periksa koneksi dan coba lagi.')
  } finally {
    loading.value = false
  }
}

async function loadDetail() {
  loading.value = true
  try {
    const res = await bkReportApi.getViolationDetail(cleanParams({ forDetail: true }))
    detail.value = res.data?.data ?? null
    captureSigners(detail.value)
  } catch (e) {
    detail.value = null
    toast.error('Gagal memuat detail', e.formattedMessage || 'Periksa koneksi dan coba lagi.')
  } finally {
    loading.value = false
  }
}

function invalidateCache() {
  report.value = null
  detail.value = null
}

async function reload() {
  invalidateCache()
  if (isDetailTab.value) await loadDetail()
  else await loadSummary()
}

async function reloadSummaryOnly() {
  report.value = null
  await loadSummary()
}

async function switchTab(tab) {
  if (viewTab.value === tab) return
  viewTab.value = tab
  if (tab === 'ringkasan') {
    if (!report.value) await loadSummary()
  } else if (!detail.value) {
    await loadDetail()
  }
}

async function openNotes(kind) {
  notesKind.value = kind
  await switchTab('catatan')
}

async function drillToClass(row) {
  if (row.class_id) {
    filters.value.class_id = String(row.class_id)
  }
  report.value = null
  detail.value = null
  viewTab.value = 'skor'
  await loadDetail()
}

async function clearChip(key) {
  if (key === 'class') {
    if (isHomeroomScoped.value && classes.value.length <= 1) return
    filters.value.class_id = ''
  }
  if (key === 'month') {
    filters.value.month = ''
  }
  await reload()
}

function closeExportMenu(event) {
  if (exportWrapRef.value && !exportWrapRef.value.contains(event.target)) {
    exportOpen.value = false
  }
}

async function exportCsv() {
  exportOpen.value = false
  exporting.value = true
  try {
    const isDetail = isDetailTab.value
    const res = isDetail
      ? await bkReportApi.exportViolations(cleanParams({ forDetail: true }))
      : await bkReportApi.export(cleanParams({ forDetail: false }))
    const url = window.URL.createObjectURL(new Blob([res.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute(
      'download',
      isDetail
        ? `laporan-bk-detail-${new Date().toISOString().slice(0, 10)}.csv`
        : `laporan-bk-per-kelas-${new Date().toISOString().slice(0, 10)}.csv`
    )
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    toast.success('Export berhasil diunduh')
  } catch (e) {
    toast.error('Gagal mengekspor', e.formattedMessage || 'Data tidak dapat diekspor.')
  } finally {
    exporting.value = false
  }
}

function escapeHtml(str) {
  return String(str ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
}

function buildPrintBodyHtml() {
  if (isDetailTab.value) {
    if (!detail.value) return '<p>Tidak ada data.</p>'

    const studentRows = (bkStudentGroups.value || []).flatMap((group) => {
      const header = `<tr class="group-row"><td colspan="9">Kelas ${escapeHtml(group.name)} (${group.rows.length} siswa)</td></tr>`
      const body = group.rows.map((row, i) => {
        const vPts = row.violation_points ?? row.total_points ?? 0
        const aPts = row.achievement_points ?? 0
        const score = row.score ?? (vPts - aPts)
        return `
      <tr>
        <td>${group.start + i + 1}</td>
        <td>${escapeHtml(row.nis || '—')}</td>
        <td>${escapeHtml(row.student_name || '—')}</td>
        <td>${escapeHtml(row.class_name)}</td>
        <td class="num">${escapeHtml(row.violation_count)}</td>
        <td class="num">${escapeHtml(vPts)}</td>
        <td class="num">${escapeHtml(row.achievement_count ?? 0)}</td>
        <td class="num">−${escapeHtml(aPts)}</td>
        <td class="num"><strong>${escapeHtml(score)}</strong></td>
      </tr>`
      }).join('')
      return header + body
    }).join('') || '<tr><td colspan="9">Belum ada data</td></tr>'

    const listRows = (detail.value.items || []).map((row) => `
      <tr>
        <td>${escapeHtml(formatDate(row.violation_date))}</td>
        <td>${escapeHtml(row.nis || '—')}</td>
        <td>${escapeHtml(row.student_name || '—')}</td>
        <td>${escapeHtml(row.class_name)}</td>
        <td>${escapeHtml(row.violation_type)}</td>
        <td>${escapeHtml(row.category)}</td>
        <td class="num">${escapeHtml(row.point_weight)}</td>
        <td>${escapeHtml(statusLabel(row.status))}</td>
        <td>${escapeHtml(row.reporter_name || '—')}</td>
      </tr>
    `).join('') || '<tr><td colspan="9">Belum ada data</td></tr>'

    const achRows = (detail.value.achievements || []).map((row) => `
      <tr>
        <td>${escapeHtml(formatDate(row.achievement_date))}</td>
        <td>${escapeHtml(row.nis || '—')}</td>
        <td>${escapeHtml(row.student_name || '—')}</td>
        <td>${escapeHtml(row.class_name)}</td>
        <td>${escapeHtml(row.achievement_type)}</td>
        <td class="num">−${escapeHtml(row.point_value)}</td>
        <td>${escapeHtml(row.giver_name || '—')}</td>
      </tr>
    `).join('') || '<tr><td colspan="7">Belum ada data</td></tr>'

    const counselingRows = (detail.value.counseling || []).map((row) => `
      <tr>
        <td>${escapeHtml(formatDate(row.session_date))}</td>
        <td>${escapeHtml(row.nis || '—')}</td>
        <td>${escapeHtml(row.student_name || '—')}</td>
        <td>${escapeHtml(row.class_name)}</td>
        <td>${escapeHtml(row.counseling_type)}</td>
        <td>${escapeHtml(counselingStatusLabel(row.status))}</td>
        <td>${escapeHtml(row.counselor_name || '—')}</td>
      </tr>
    `).join('') || '<tr><td colspan="7">Belum ada data</td></tr>'

    return `
      <p class="note">Skor = poin pelanggaran − poin prestasi. Contoh: 40 − 20 = 20.</p>
      <h2>1. Rekap Skor per Siswa</h2>
      <table>
        <thead>
          <tr>
            <th>#</th><th>NIS</th><th>Nama</th><th>Kelas</th>
            <th>Jml Pelanggaran</th><th>Poin Pelanggaran</th>
            <th>Jml Prestasi</th><th>Poin Prestasi</th><th>Skor</th>
          </tr>
        </thead>
        <tbody>${studentRows}</tbody>
      </table>
      <h2>2. Daftar Pelanggaran</h2>
      <table>
        <thead>
          <tr>
            <th>Tanggal</th><th>NIS</th><th>Nama</th><th>Kelas</th>
            <th>Jenis</th><th>Kategori</th><th>Poin</th><th>Status</th><th>Pelapor</th>
          </tr>
        </thead>
        <tbody>${listRows}</tbody>
      </table>
      <h2>3. Daftar Prestasi</h2>
      <table>
        <thead>
          <tr>
            <th>Tanggal</th><th>NIS</th><th>Nama</th><th>Kelas</th>
            <th>Jenis Prestasi</th><th>Poin</th><th>Pemberi</th>
          </tr>
        </thead>
        <tbody>${achRows}</tbody>
      </table>
      <h2>4. Daftar Konseling</h2>
      <table>
        <thead>
          <tr>
            <th>Tanggal</th><th>NIS</th><th>Nama</th><th>Kelas</th>
            <th>Jenis</th><th>Status</th><th>Konselor</th>
          </tr>
        </thead>
        <tbody>${counselingRows}</tbody>
      </table>
    `
  }

  if (!report.value) return '<p>Tidak ada data.</p>'
  const s = report.value.summary || {}
  const classRows = (report.value.by_class || []).map((row) => `
    <tr>
      <td>${escapeHtml(row.class_name)}</td>
      <td class="num">${escapeHtml(row.violation_count)}</td>
      <td class="num">${escapeHtml(row.achievement_count ?? 0)}</td>
      <td class="num">${escapeHtml(row.counseling_count)}</td>
    </tr>
  `).join('') || '<tr><td colspan="4">Belum ada data</td></tr>'

  const monthRows = (report.value.by_month?.months || []).map((m) => `
    <tr>
      <td>${escapeHtml(monthFullLabel(m.month))}</td>
      <td class="num">${escapeHtml(m.violation_count)}</td>
      <td class="num">${escapeHtml(m.achievement_count ?? 0)}</td>
      <td class="num">${escapeHtml(m.counseling_count)}</td>
    </tr>
  `).join('') || '<tr><td colspan="4">Belum ada data</td></tr>'

  const typeRows = (report.value.top_violation_types || []).map((row, idx) => `
    <tr>
      <td>${idx + 1}</td>
      <td>${escapeHtml(row.type_name)}</td>
      <td>${escapeHtml(row.category)}</td>
      <td class="num">${escapeHtml(row.count)}</td>
      <td class="num">${escapeHtml(row.total_points)}</td>
    </tr>
  `).join('') || '<tr><td colspan="5">Belum ada data</td></tr>'

  const achTypeRows = (report.value.top_achievement_types || []).map((row, idx) => `
    <tr>
      <td>${idx + 1}</td>
      <td>${escapeHtml(row.type_name)}</td>
      <td>${escapeHtml(row.category || '—')}</td>
      <td class="num">${escapeHtml(row.count)}</td>
      <td class="num">${escapeHtml(row.total_points)}</td>
    </tr>
  `).join('') || '<tr><td colspan="5">Belum ada data</td></tr>'

  return `
    <div class="stats">
      <div class="stat"><div class="stat-label">Total Pelanggaran</div><div class="stat-value">${s.total_violations ?? 0}</div></div>
      <div class="stat"><div class="stat-label">Total Prestasi</div><div class="stat-value">${s.total_achievements ?? 0}</div></div>
      <div class="stat"><div class="stat-label">Skor Bersih</div><div class="stat-value">${s.net_score ?? 0}</div></div>
      <div class="stat"><div class="stat-label">Total Konseling</div><div class="stat-value">${s.total_counseling ?? 0}</div></div>
    </div>
    <p class="note">Skor bersih = poin pelanggaran − poin prestasi.</p>
    <h2>1. Rekap per Kelas</h2>
    <table>
      <thead><tr><th>Kelas</th><th>Pelanggaran</th><th>Prestasi</th><th>Konseling</th></tr></thead>
      <tbody>${classRows}</tbody>
    </table>
    <h2>2. Rekap per Bulan (${escapeHtml(report.value.by_month?.year || filters.value.year)})</h2>
    <table>
      <thead><tr><th>Bulan</th><th>Pelanggaran</th><th>Prestasi</th><th>Konseling</th></tr></thead>
      <tbody>${monthRows}</tbody>
    </table>
    <h2>3. Top Jenis Pelanggaran</h2>
    <table>
      <thead><tr><th>#</th><th>Jenis</th><th>Kategori</th><th>Jumlah</th><th>Total Poin</th></tr></thead>
      <tbody>${typeRows}</tbody>
    </table>
    <h2>4. Top Jenis Prestasi</h2>
    <table>
      <thead><tr><th>#</th><th>Jenis</th><th>Kategori</th><th>Jumlah</th><th>Total Poin</th></tr></thead>
      <tbody>${achTypeRows}</tbody>
    </table>
  `
}

function printPdf() {
  exportOpen.value = false
  const hasData = isDetailTab.value ? !!detail.value : !!report.value
  if (!hasData) {
    toast.error('Gagal', 'Tidak ada data untuk dicetak')
    return
  }

  printing.value = true
  try {
    const inst = institution.value || {}
    const instName = inst.name || 'Sekolah'
    const fullAddress = [
      inst.address,
      inst.village ? `Desa/Kel. ${inst.village}` : '',
      inst.sub_district ? `Kec. ${inst.sub_district}` : '',
      inst.district,
      inst.province,
      inst.postal_code,
    ].filter(Boolean).join(', ')
    const title = isDetailTab.value
      ? 'Laporan BK — Skor Siswa & Catatan'
      : 'Laporan BK — Ringkasan'
    const createdAt = new Date().toLocaleDateString('id-ID', {
      day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit',
    })
    const placeDate = `${inst.district || inst.city || '........................'}, ${new Date().toLocaleDateString('id-ID', {
      day: 'numeric', month: 'long', year: 'numeric',
    })}`
    const filename = `Laporan_BK_${isDetailTab.value ? 'Detail' : 'Ringkasan'}_${new Date().toISOString().slice(0, 10)}.pdf`

    const principalRole = signers.value?.principal?.role || getPrincipalTitle(inst.level)
    const principalName = signers.value?.principal?.name || inst.principal_name || ''
    const principalNip = signers.value?.principal?.nip || inst.principal_nip || ''
    const bkRole = signers.value?.bk?.role || 'Guru Bimbingan Konseling'
    const bkName = signers.value?.bk?.name || ''
    const bkNip = signers.value?.bk?.nip || ''

    const content = `<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>${escapeHtml(filename)}</title>
  <style>
    * { box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #111; margin: 16px; }
    h1 { font-size: 16px; margin: 0 0 4px; text-align: center; }
    .kop { border-bottom: 3px double #111; padding: 0 8px 8px; margin-bottom: 10px; }
    .kop-inner { display: grid; grid-template-columns: 76px 1fr 76px; align-items: center; min-height: 70px; }
    .kop-logo { width: 66px; height: 66px; object-fit: contain; }
    .kop-text { min-width: 0; text-align: center; }
    .foundation { overflow: hidden; font-family: "Times New Roman", serif; font-size: 14px; font-weight: 600; line-height: 1.15; text-transform: uppercase; text-overflow: ellipsis; white-space: nowrap; letter-spacing: 0.02em; }
    .school { font-family: "Times New Roman", serif; font-size: 18px; font-weight: 700; text-transform: uppercase; }
    .school-address { font-size: 10px; line-height: 1.35; margin-top: 3px; }
    .school-info { font-size: 9px; margin-top: 2px; }
    .subtitle { text-align: center; color: #444; margin-bottom: 12px; }
    .period { text-align: center; margin-bottom: 16px; font-size: 11px; }
    h2 { font-size: 12px; margin: 18px 0 8px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
    table { width: calc(100% - 2px); max-width: calc(100% - 2px); border-collapse: collapse; margin-bottom: 8px; }
    th, td { border: 1px solid #333; padding: 4px 6px; text-align: left; vertical-align: top; }
    th { background: #eee; font-size: 10px; text-transform: uppercase; }
    tr.group-row td { background: #e2e8f0; font-weight: 700; text-transform: none; }
    td.num, th.num { text-align: right; }
    .stats { display: flex; gap: 8px; margin-bottom: 12px; }
    .stat { flex: 1; border: 1px solid #333; padding: 8px; text-align: center; }
    .stat-label { font-size: 9px; text-transform: uppercase; color: #555; }
    .stat-value { font-size: 16px; font-weight: 700; margin-top: 2px; }
    .note { font-size: 10px; color: #444; margin: 0 0 10px; }
    .printed-at { font-size: 9px; color: #555; margin-top: 18px; }
    .sig-wrap { display: table; width: 100%; margin-top: 28px; page-break-inside: avoid; }
    .sig-col { display: table-cell; width: 50%; vertical-align: top; }
    .sig { text-align: center; min-width: 220px; }
    .sig-col-right { text-align: right; }
    .sig-col-right .sig { display: inline-block; text-align: center; }
    .sig-place, .sig-role { font-size: 10px; line-height: 1.35; }
    .sig-space { height: 56px; }
    .sig-name { font-size: 11px; font-weight: 700; text-decoration: underline; }
    .sig-nip { font-size: 9px; margin-top: 2px; }
    @media print {
      @page { size: A4 ${isDetailTab.value ? 'landscape' : 'portrait'}; margin: 10mm 12mm 10mm 10mm; }
      body { margin: 0; padding-right: 1px; }
    }
  </style>
</head>
<body>
  <header class="kop">
    <div class="kop-inner">
      <div>${inst.logo ? `<img src="${escapeHtml(inst.logo)}" alt="Logo institusi" class="kop-logo" />` : ''}</div>
      <div class="kop-text">
        ${inst.foundation_name ? `<div class="foundation">${escapeHtml(inst.foundation_name)}</div>` : ''}
        <div class="school">${escapeHtml(instName)}</div>
        <div class="school-address">${escapeHtml(fullAddress || '-')}</div>
        <div class="school-info">
          NPSN: ${escapeHtml(inst.npsn || '-')}
          ${inst.nss ? ` · ${getNssLabel(inst.level)}: ${escapeHtml(inst.nss)}` : ''}
          ${inst.phone ? ` · Telp: ${escapeHtml(inst.phone)}` : ''}
          ${inst.email ? ` · Email: ${escapeHtml(inst.email)}` : ''}
          ${inst.website ? ` · ${escapeHtml(inst.website)}` : ''}
        </div>
      </div>
      <div></div>
    </div>
  </header>
  <h1>${escapeHtml(title)}</h1>
  <div class="subtitle">Bimbingan Konseling</div>
  <div class="period"><strong>Periode / Filter:</strong> ${escapeHtml(periodLabel.value)}</div>
  ${buildPrintBodyHtml()}
  <div class="printed-at">Dicetak pada: ${escapeHtml(createdAt)}</div>
  <div class="sig-wrap">
    <div class="sig-col">
      <div class="sig">
        <div class="sig-place">&nbsp;</div>
        <div class="sig-role">Mengetahui,<br>${escapeHtml(principalRole)}</div>
        <div class="sig-space"></div>
        <div class="sig-name">${escapeHtml(principalName || '___________________')}</div>
        <div class="sig-nip">NIP. ${escapeHtml(principalNip || '___________________')}</div>
      </div>
    </div>
    <div class="sig-col sig-col-right">
      <div class="sig">
        <div class="sig-place">${escapeHtml(placeDate)}</div>
        <div class="sig-role">${escapeHtml(bkRole)}</div>
        <div class="sig-space"></div>
        <div class="sig-name">${escapeHtml(bkName || '___________________')}</div>
        <div class="sig-nip">NIP. ${escapeHtml(bkNip || '___________________')}</div>
      </div>
    </div>
  </div>
</body>
</html>`

    const printWindow = window.open('', '_blank')
    if (!printWindow) {
      toast.error('Gagal', 'Popup diblokir. Izinkan popup untuk mencetak.')
      return
    }
    printWindow.document.write(content)
    printWindow.document.close()
    setTimeout(() => {
      printWindow.print()
      printWindow.document.title = filename
    }, 250)
  } catch (err) {
    if (import.meta.env.DEV) console.error('Error printing PDF:', err)
    toast.error('Gagal', 'Gagal menyiapkan cetak PDF')
  } finally {
    printing.value = false
  }
}

async function onPeriodChange() {
  filters.value.semester_id = ''
  await loadClasses()
  await reload()
}

async function loadClasses() {
  try {
    const params = { per_page: 200 }
    if (filters.value.academic_year_id) params.academic_year_id = filters.value.academic_year_id
    if (filters.value.semester_id) params.semester_id = filters.value.semester_id
    const res = await classApi.getAll(params)
    let list = res.data?.data || []
    if (isHomeroomScoped.value && homeroomClassIds.value.length) {
      const allowed = new Set(homeroomClassIds.value)
      list = list.filter(c => allowed.has(Number(c.id)))
    }
    classes.value = list
    if (isHomeroomScoped.value) {
      if (list.length === 1) {
        filters.value.class_id = String(list[0].id)
      } else if (filters.value.class_id && !list.some(c => String(c.id) === String(filters.value.class_id))) {
        filters.value.class_id = list[0] ? String(list[0].id) : ''
      }
    }
  } catch {
    classes.value = []
  }
}

async function loadSemesters() {
  try {
    const res = await semesterApi.getAll({ per_page: 100 })
    semesters.value = res.data?.data || []
  } catch {
    semesters.value = []
  }
}

async function loadDefaults() {
  try {
    const res = await institutionApi.getMy()
    institution.value = res.data?.data || res.data
    if (institution.value?.active_academic_year_id) {
      filters.value.academic_year_id = String(institution.value.active_academic_year_id)
    }
    if (institution.value?.active_semester_id) {
      filters.value.semester_id = String(institution.value.active_semester_id)
    }
  } catch {
    // ignore
  }
}

onMounted(async () => {
  document.addEventListener('click', closeExportMenu)
  await Promise.all([
    loadDefaults(),
    referenceStore.getAcademicYears(),
    loadSemesters(),
  ])
  await loadClasses()
  const qClassId = route.query.class_id ? String(route.query.class_id) : ''
  if (qClassId && classes.value.some((c) => String(c.id) === qClassId)) {
    filters.value.class_id = qClassId
  }
  const qTab = String(route.query.tab || '')
  if (qTab === 'detail' || qTab === 'skor') viewTab.value = 'skor'
  else if (qTab === 'catatan') viewTab.value = 'catatan'
  const qNotes = String(route.query.notes || '')
  if (['violations', 'achievements', 'counseling'].includes(qNotes)) {
    notesKind.value = qNotes
  }
  if (isDetailTab.value) await loadDetail()
  else await loadSummary()
})

onUnmounted(() => {
  document.removeEventListener('click', closeExportMenu)
})
</script>

<style scoped>
.laporan-bk-page {
  width: 100%;
  max-width: 100%;
  min-height: 100%;
  padding: 1.5rem;
  margin: 0 auto;
  background: linear-gradient(180deg, #eff6ff 0%, #f8fafc 20%, #f1f5f9 100%);
}
.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 0.5rem;
}
.mode-tabs {
  display: flex;
  gap: 0.25rem;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 0.25rem;
}
.mode-tab {
  border: none;
  background: transparent;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.9rem;
  color: #64748b;
  cursor: pointer;
}
.mode-tab.active {
  background: #2563eb;
  color: #fff;
}
.toolbar-hint {
  margin: 0 0 1rem;
  color: #64748b;
  font-size: 0.875rem;
}
.header-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}
.export-wrap { position: relative; }
.export-menu {
  position: absolute; right: 0; top: calc(100% + 6px); z-index: 20; min-width: 200px;
  background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
  box-shadow: 0 12px 28px rgba(15,23,42,.12); padding: .35rem;
}
.export-menu button {
  display: block; width: 100%; text-align: left; border: none; background: transparent;
  padding: .55rem .7rem; border-radius: 8px; cursor: pointer; color: #0f172a; font-size: .88rem;
}
.export-menu button:hover { background: #eff6ff; color: #1d4ed8; }
.export-menu button:disabled { opacity: .55; cursor: not-allowed; }
.filters {
  display: flex;
  flex-wrap: wrap;
  gap: 0.65rem 0.75rem;
  align-items: flex-end;
  margin-bottom: 0.75rem;
}
.filters-advanced {
  padding: 0.75rem 0.85rem;
  background: #fff;
  border: 1px dashed #cbd5e1;
  border-radius: 10px;
  margin-bottom: 0.85rem;
}
.filter-field {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  min-width: 0;
}
.filter-field span {
  font-size: 0.72rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}
.filter-select {
  padding: 0.5rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  font-size: 0.875rem;
  color: #334155;
  min-width: 0;
}
.filter-select-sm { font-size: 0.8rem; padding: 0.3rem 0.5rem; }
.btn-ghost {
  border: 1px dashed #cbd5e1;
  background: #fff;
  color: #475569;
  border-radius: 8px;
  padding: 0.5rem 0.75rem;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
}
.btn-ghost.active {
  border-style: solid;
  border-color: #93c5fd;
  background: #eff6ff;
  color: #1d4ed8;
}
.filter-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
  margin-bottom: 1rem;
}
.filter-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  border: 1px solid #bfdbfe;
  background: #eff6ff;
  color: #1e40af;
  border-radius: 999px;
  padding: 0.2rem 0.65rem;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
}
.filter-chip:disabled { cursor: default; opacity: 0.85; }
.stat-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 0.75rem;
  margin-bottom: 1.25rem;
}
.stat-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1rem 1.15rem;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  text-align: left;
  cursor: pointer;
}
.stat-card:hover { border-color: #93c5fd; box-shadow: 0 0 0 3px #dbeafe; }
.stat-card-good { border-color: #a7f3d0; background: #f0fdf4; }
.stat-card-score { border-color: #fde68a; background: #fffbeb; }
.stat-sub { font-size: 0.7rem; color: #64748b; }
.stat-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}
.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #0f172a;
}
.section-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 0.65rem;
}
.section-title {
  margin: 0 0 0.65rem;
  font-size: 0.95rem;
  color: #334155;
}
.section-head .section-title { margin: 0; }
.split-tables {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}
.notes-tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
  margin-bottom: 0.85rem;
}
.notes-tab {
  border: 1px solid #e2e8f0;
  background: #fff;
  border-radius: 999px;
  padding: 0.4rem 0.85rem;
  font-size: 0.82rem;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
}
.notes-tab.active {
  background: #2563eb;
  border-color: #2563eb;
  color: #fff;
}
.report-section { margin-top: 0.5rem; }
.table-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
}
.table-wrap { overflow-x: auto; }
.data-table {
  width: 100%;
  border-collapse: collapse;
}
.data-table th,
.data-table td {
  padding: 0.85rem 1.1rem;
  text-align: left;
  border-bottom: 1px solid #f1f5f9;
  white-space: nowrap;
}
.data-table th {
  background: #f1f5f9;
  font-weight: 600;
  font-size: 0.8rem;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}
.data-table tbody tr:hover { background: #f8fafc; }
.group-row td {
  background: #f1f5f9;
  font-weight: 700;
  font-size: 12px;
  color: #334155;
  text-transform: none;
  letter-spacing: 0;
}
.row-clickable { cursor: pointer; }
.row-clickable:hover { background: #eff6ff !important; }
.th-num, .td-num { text-align: right; font-variant-numeric: tabular-nums; }
.td-total { font-weight: 700; }
.td-good { color: #059669; font-weight: 600; }
.td-muted { color: #94a3b8; font-weight: 500; font-size: 0.75rem; }
.score-bad { color: #b91c1c; }
.score-warn { color: #c2410c; }
.score-good { color: #047857; }
.section-hint {
  margin: 0 0 0.75rem;
  font-size: 0.85rem;
  color: #64748b;
}
.td-action { text-align: right; }
.link-btn {
  border: none;
  background: none;
  color: #2563eb;
  font-weight: 600;
  font-size: 0.8rem;
  cursor: pointer;
  padding: 0;
}
.link-btn:hover { text-decoration: underline; }
.badge {
  display: inline-block;
  padding: 0.15rem 0.5rem;
  border-radius: 999px;
  background: #f1f5f9;
  font-size: 0.75rem;
  color: #475569;
  text-transform: capitalize;
}
.chart-box {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1rem 1.15rem;
}
.chart-wrap { height: 260px; }
.empty-state {
  text-align: center;
  padding: 2.5rem 1rem;
  background: #fff;
  border: 1px dashed #cbd5e1;
  border-radius: 12px;
}
.empty-title {
  margin: 0 0 0.35rem;
  font-size: 1.1rem;
  color: #334155;
}
.empty-desc {
  margin: 0;
  color: #64748b;
  font-size: 0.9rem;
}
.empty-desc.muted { padding: 0.75rem 0; }
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  padding: 3rem 1rem;
  color: #64748b;
}
.loading-spinner {
  width: 36px;
  height: 36px;
  border: 3px solid #e2e8f0;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
.btn-secondary {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.55rem 0.9rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  background: #fff;
  color: #334155;
  border: 1px solid #e2e8f0;
  cursor: pointer;
}
.btn-compact { white-space: nowrap; }
.btn-secondary:disabled { opacity: 0.6; cursor: not-allowed; }
@media (max-width: 900px) {
  .split-tables { grid-template-columns: 1fr; }
  .stat-cards { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 600px) {
  .laporan-bk-page { padding: 1rem; }
  .stat-cards { grid-template-columns: 1fr; }
  .chart-wrap { height: 220px; }
}
</style>
