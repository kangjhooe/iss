<template>
  <Layout>
    <div class="laporan-bk-page">
      <div class="toolbar">
        <div class="mode-tabs">
          <button type="button" :class="['mode-tab', { active: viewMode === 'ringkasan' }]" @click="switchMode('ringkasan')">
            Ringkasan
          </button>
          <button type="button" :class="['mode-tab', { active: viewMode === 'detail' }]" @click="switchMode('detail')">
            Detail & Skor
          </button>
        </div>
        <div class="header-actions">
          <button type="button" class="btn-secondary btn-compact" :disabled="exporting || loading" @click="exportCsv">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M7 10L12 15L17 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>{{ exporting ? 'Mengekspor...' : (viewMode === 'detail' ? 'Export Detail CSV' : 'Export Rekap CSV') }}</span>
          </button>
          <button type="button" class="btn-secondary btn-compact" :disabled="printing || loading" @click="printPdf">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M6 9V2H18V9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M6 18H4C3.46957 18 2.96086 17.7893 2.58579 17.4142C2.21071 17.0391 2 16.5304 2 16V11C2 10.4696 2.21071 9.96086 2.58579 9.58579C2.96086 9.21071 3.46957 9 4 9H20C20.5304 9 21.0391 9.21071 21.4142 9.58579C21.7893 9.96086 22 10.4696 22 11V16C22 16.5304 21.7893 17.0391 21.4142 17.4142C21.0391 17.7893 20.5304 18 20 18H18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M18 14H6V22H18V14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>{{ printing ? 'Menyiapkan...' : 'Cetak PDF' }}</span>
          </button>
          <button type="button" class="btn-primary btn-compact" :disabled="loading" @click="reload">
            <span>Muat Ulang</span>
          </button>
        </div>
      </div>

      <p class="toolbar-hint">
        <template v-if="isHomeroomScoped">
          Menampilkan laporan BK untuk siswa di kelas yang Anda walikan saja.
        </template>
        <template v-else-if="viewMode === 'ringkasan'">
          Rekap agregat pelanggaran, prestasi, dan konseling. Skor = poin pelanggaran − poin prestasi.
        </template>
        <template v-else>
          Detail pelanggaran & prestasi, plus rekap skor per siswa (pelanggaran − prestasi).
        </template>
      </p>

      <div class="filters filters-inline">
        <select v-model="filters.academic_year_id" class="filter-select" @change="onPeriodChange">
          <option value="">Semua Tahun Ajaran</option>
          <option v-for="y in academicYears" :key="y.id" :value="String(y.id)">{{ y.name }}</option>
        </select>
        <select v-model="filters.semester_id" class="filter-select" @change="reload">
          <option value="">Semua Semester</option>
          <option v-for="s in filteredSemesters" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
        </select>
        <select v-model="filters.class_id" class="filter-select" @change="reload" :disabled="isHomeroomScoped && classes.length <= 1">
          <option v-if="!isHomeroomScoped" value="">Semua Kelas</option>
          <option v-for="c in classes" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
        </select>

        <template v-if="viewMode === 'detail'">
          <select v-model="filters.month" class="filter-select" @change="reload">
            <option value="">Semua Bulan</option>
            <option v-for="m in 12" :key="m" :value="String(m)">{{ monthFullLabel(m) }}</option>
          </select>
          <select v-model="filters.year" class="filter-select" @change="reload">
            <option v-for="y in chartYears" :key="y" :value="String(y)">{{ y }}</option>
          </select>
        </template>
        <template v-else>
          <select v-model="filters.year" class="filter-select" @change="reload" title="Tahun kalender untuk tren bulanan">
            <option v-for="y in chartYears" :key="y" :value="String(y)">Tren {{ y }}</option>
          </select>
        </template>
      </div>

      <div v-if="loading" class="loading-state">
        <div class="loading-spinner"></div>
        <p>Memuat laporan BK...</p>
      </div>

      <!-- ========== RINGKASAN ========== -->
      <template v-else-if="viewMode === 'ringkasan' && report">
        <div class="stat-cards">
          <div class="stat-card">
            <span class="stat-label">Total Pelanggaran</span>
            <span class="stat-value">{{ report.summary?.total_violations ?? 0 }}</span>
          </div>
          <div class="stat-card stat-card-good">
            <span class="stat-label">Total Prestasi</span>
            <span class="stat-value">{{ report.summary?.total_achievements ?? 0 }}</span>
          </div>
          <div class="stat-card">
            <span class="stat-label">Poin Pelanggaran</span>
            <span class="stat-value">{{ report.summary?.total_violation_points ?? 0 }}</span>
          </div>
          <div class="stat-card stat-card-good">
            <span class="stat-label">Poin Prestasi</span>
            <span class="stat-value">−{{ report.summary?.total_achievement_points ?? 0 }}</span>
          </div>
          <div class="stat-card stat-card-score">
            <span class="stat-label">Skor Bersih</span>
            <span class="stat-value">{{ report.summary?.net_score ?? 0 }}</span>
            <span class="stat-sub">pelanggaran − prestasi</span>
          </div>
          <div class="stat-card">
            <span class="stat-label">Total Konseling</span>
            <span class="stat-value">{{ report.summary?.total_counseling ?? 0 }}</span>
          </div>
        </div>

        <div class="nav-tabs-wrap">
          <nav class="nav-tabs" aria-label="Tab ringkasan BK">
            <button type="button" :class="['nav-tab', { active: summaryTab === 'class' }]" @click="summaryTab = 'class'">
              <span class="nav-tab-label">Per Kelas</span>
              <span class="nav-tab-hint">Klik baris untuk detail</span>
            </button>
            <button type="button" :class="['nav-tab', { active: summaryTab === 'month' }]" @click="summaryTab = 'month'">
              <span class="nav-tab-label">Per Bulan</span>
              <span class="nav-tab-hint">Tren tahun {{ report.by_month?.year }}</span>
            </button>
            <button type="button" :class="['nav-tab', { active: summaryTab === 'types' }]" @click="summaryTab = 'types'">
              <span class="nav-tab-label">Jenis Pelanggaran</span>
              <span class="nav-tab-hint">Top jenis terbanyak</span>
            </button>
            <button type="button" :class="['nav-tab', { active: summaryTab === 'achievements' }]" @click="summaryTab = 'achievements'">
              <span class="nav-tab-label">Jenis Prestasi</span>
              <span class="nav-tab-hint">Top prestasi</span>
            </button>
          </nav>
        </div>

        <section v-if="summaryTab === 'class'" class="report-section">
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
                      <button type="button" class="link-btn" @click.stop="drillToClass(row)">Lihat detail →</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <section v-else-if="summaryTab === 'month'" class="report-section">
          <div class="charts-row">
            <div class="chart-box">
              <h4>Tren per bulan ({{ report.by_month?.year }})</h4>
              <div class="chart-wrap" v-if="monthChartData">
                <Bar :data="monthChartData" :options="chartOptionsBar" />
              </div>
            </div>
          </div>
          <div class="table-card" style="margin-top: 1rem">
            <div class="table-wrap">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Bulan</th>
                    <th class="th-num">Pelanggaran</th>
                    <th class="th-num">Prestasi</th>
                    <th class="th-num">Konseling</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="m in report.by_month?.months || []"
                    :key="m.month"
                    class="row-clickable"
                    @click="drillToMonth(m)"
                  >
                    <td>{{ monthFullLabel(m.month) }}</td>
                    <td class="td-num">{{ m.violation_count }}</td>
                    <td class="td-num td-good">{{ m.achievement_count ?? 0 }}</td>
                    <td class="td-num">{{ m.counseling_count }}</td>
                    <td class="td-action">
                      <button type="button" class="link-btn" @click.stop="drillToMonth(m)">Lihat detail →</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <section v-else-if="summaryTab === 'types'" class="report-section">
          <div class="charts-row">
            <div class="chart-box chart-box--pie">
              <h4>Distribusi jenis pelanggaran</h4>
              <div class="chart-wrap chart-wrap-pie" v-if="typeChartData">
                <Doughnut :data="typeChartData" :options="chartOptionsDoughnut" />
              </div>
              <p v-else class="empty-desc">Belum ada data jenis pelanggaran.</p>
            </div>
          </div>
          <div v-if="report.top_violation_types?.length" class="table-card" style="margin-top: 1rem">
            <div class="table-wrap">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Jenis Pelanggaran</th>
                    <th>Kategori</th>
                    <th class="th-num">Jumlah</th>
                    <th class="th-num">Total Poin</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(row, idx) in report.top_violation_types" :key="row.violation_type_id ?? row.type_name">
                    <td>{{ idx + 1 }}</td>
                    <td>{{ row.type_name }}</td>
                    <td><span class="badge">{{ row.category }}</span></td>
                    <td class="td-num">{{ row.count }}</td>
                    <td class="td-num">{{ row.total_points }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div v-else class="empty-state">
            <h3 class="empty-title">Belum ada data</h3>
            <p class="empty-desc">Tidak ada catatan pelanggaran untuk filter yang dipilih.</p>
          </div>
        </section>

        <section v-else class="report-section">
          <div v-if="report.top_achievement_types?.length" class="table-card">
            <div class="table-wrap">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Jenis Prestasi</th>
                    <th>Kategori</th>
                    <th class="th-num">Jumlah</th>
                    <th class="th-num">Total Poin</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(row, idx) in report.top_achievement_types" :key="row.achievement_type_id ?? row.type_name">
                    <td>{{ idx + 1 }}</td>
                    <td>{{ row.type_name }}</td>
                    <td><span class="badge">{{ row.category || '—' }}</span></td>
                    <td class="td-num">{{ row.count }}</td>
                    <td class="td-num td-good">{{ row.total_points }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div v-else class="empty-state">
            <h3 class="empty-title">Belum ada data prestasi</h3>
            <p class="empty-desc">Tidak ada catatan prestasi untuk filter yang dipilih.</p>
          </div>
        </section>
      </template>

      <!-- ========== DETAIL ========== -->
      <template v-else-if="viewMode === 'detail'">
        <div v-if="detail" class="detail-meta">
          <span class="meta-chip">{{ detailFilterLabel }}</span>
          <span class="meta-chip">{{ detail.total }} pelanggaran</span>
          <span class="meta-chip meta-good">{{ detail.achievements_total ?? detail.achievements?.length ?? 0 }} prestasi</span>
          <span v-if="detail.truncated" class="meta-chip meta-warn">Ditampilkan maks. 2000 baris</span>
        </div>

        <div class="nav-tabs-wrap">
          <nav class="nav-tabs" aria-label="Tab detail BK">
            <button type="button" :class="['nav-tab', { active: detailTab === 'students' }]" @click="detailTab = 'students'">
              <span class="nav-tab-label">Rekap Skor Siswa</span>
              <span class="nav-tab-hint">Pelanggaran − prestasi</span>
            </button>
            <button type="button" :class="['nav-tab', { active: detailTab === 'list' }]" @click="detailTab = 'list'">
              <span class="nav-tab-label">Daftar Pelanggaran</span>
              <span class="nav-tab-hint">Transaksi per tanggal</span>
            </button>
            <button type="button" :class="['nav-tab', { active: detailTab === 'achievements' }]" @click="detailTab = 'achievements'">
              <span class="nav-tab-label">Daftar Prestasi</span>
              <span class="nav-tab-hint">Poin pengurang</span>
            </button>
          </nav>
        </div>

        <section v-if="detailTab === 'students'" class="report-section">
          <p class="section-hint">Skor = poin pelanggaran − poin prestasi. Contoh: 40 − 20 = <strong>20</strong>.</p>
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
                    <th>Kelas</th>
                    <th class="th-num">Jml Pelanggaran</th>
                    <th class="th-num">Poin Pelanggaran</th>
                    <th class="th-num">Jml Prestasi</th>
                    <th class="th-num">Poin Prestasi</th>
                    <th class="th-num">Skor</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(row, idx) in detail.by_student" :key="row.student_id ?? idx">
                    <td>{{ idx + 1 }}</td>
                    <td>{{ row.nis || '—' }}</td>
                    <td>{{ row.student_name || '—' }}</td>
                    <td>{{ row.class_name }}</td>
                    <td class="td-num">{{ row.violation_count }}</td>
                    <td class="td-num">{{ row.violation_points ?? row.total_points }}</td>
                    <td class="td-num td-good">{{ row.achievement_count ?? 0 }}</td>
                    <td class="td-num td-good">−{{ row.achievement_points ?? 0 }}</td>
                    <td class="td-num td-total" :class="scoreClass(row.score)">{{ row.score ?? ((row.violation_points ?? row.total_points) - (row.achievement_points ?? 0)) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <section v-else-if="detailTab === 'list'" class="report-section">
          <div v-if="!detail?.items?.length" class="empty-state">
            <h3 class="empty-title">Belum ada pelanggaran</h3>
            <p class="empty-desc">Tidak ada catatan untuk filter kelas/bulan yang dipilih.</p>
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
                    <th>Kategori</th>
                    <th class="th-num">Poin</th>
                    <th>Status</th>
                    <th>Pelapor</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="row in detail.items" :key="row.id">
                    <td>{{ formatDate(row.violation_date) }}</td>
                    <td>{{ row.nis || '—' }}</td>
                    <td>{{ row.student_name || '—' }}</td>
                    <td>{{ row.class_name }}</td>
                    <td>{{ row.violation_type }}</td>
                    <td><span class="badge">{{ row.category }}</span></td>
                    <td class="td-num">{{ row.point_weight }}</td>
                    <td><span class="badge">{{ statusLabel(row.status) }}</span></td>
                    <td>{{ row.reporter_name || '—' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <section v-else class="report-section">
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
                    <th>Pemberi</th>
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
                    <td>{{ row.giver_name || '—' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>
      </template>

      <div v-else-if="!loading" class="empty-state">
        <h3 class="empty-title">Gagal memuat laporan</h3>
        <p class="empty-desc">Coba muat ulang atau periksa koneksi.</p>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { Bar, Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, ArcElement, Title, Tooltip, Legend } from 'chart.js'
import Layout from '@/components/Layout.vue'
import { bkReportApi } from '@/api/bkReport'
import { institutionApi } from '@/api/institution'
import { classApi } from '@/api/class'
import { semesterApi } from '@/api/semester'
import { useReferenceDataStore } from '@/stores/referenceData'
import { useAuthStore } from '@/stores/auth'
import { getPrincipalTitle, getNssLabel } from '@/utils/institution'
import { useToast } from '@/composables/useToast'

ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, Title, Tooltip, Legend)

const toast = useToast()
const route = useRoute()
const referenceStore = useReferenceDataStore()
const authStore = useAuthStore()

const loading = ref(true)
const exporting = ref(false)
const printing = ref(false)
const viewMode = ref('ringkasan') // ringkasan | detail
const summaryTab = ref('class')
const detailTab = ref('students')
const report = ref(null)
const detail = ref(null)
const classes = ref([])
const semesters = ref([])
const institution = ref(null)

const isHomeroomScoped = computed(() => authStore.user?.bk_scope === 'homeroom')
const homeroomClassIds = computed(() => (authStore.user?.homeroom_class_ids || []).map(Number))

const now = new Date()
const currentYear = now.getFullYear()
const currentMonth = now.getMonth() + 1
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

function monthFullLabel(m) {
  return MONTH_NAMES[m] || `Bulan ${m}`
}

function statusLabel(s) {
  return STATUS_LABELS[s] || s || '—'
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

const detailFilterLabel = computed(() => {
  const parts = []
  const cls = classes.value.find(c => String(c.id) === String(filters.value.class_id))
  parts.push(cls?.name || 'Semua kelas')
  if (filters.value.month) {
    parts.push(`${monthFullLabel(Number(filters.value.month))} ${filters.value.year}`)
  } else {
    parts.push(`Tahun ${filters.value.year || currentYear}`)
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
  if (forDetail && filters.value.month) {
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

const typeChartData = computed(() => {
  const types = report.value?.top_violation_types
  if (!types?.length) return null
  const palette = ['#ef4444', '#f97316', '#eab308', '#22c55e', '#06b6d4', '#3b82f6', '#8b5cf6', '#ec4899', '#64748b', '#14b8a6']
  return {
    labels: types.map(t => t.type_name),
    datasets: [{
      data: types.map(t => t.count),
      backgroundColor: types.map((_, i) => palette[i % palette.length]),
    }],
  }
})

const chartOptionsBar = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'bottom' } },
  scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
}

const chartOptionsDoughnut = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'right' } },
}

async function loadSummary() {
  loading.value = true
  try {
    const res = await bkReportApi.getSummary(cleanParams({ forDetail: false }))
    report.value = res.data?.data ?? null
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
  } catch (e) {
    detail.value = null
    toast.error('Gagal memuat detail', e.formattedMessage || 'Periksa koneksi dan coba lagi.')
  } finally {
    loading.value = false
  }
}

async function reload() {
  if (viewMode.value === 'detail') await loadDetail()
  else await loadSummary()
}

async function switchMode(mode) {
  if (viewMode.value === mode) return
  viewMode.value = mode
  if (mode === 'detail' && !filters.value.month) {
    filters.value.month = String(currentMonth)
  }
  await reload()
}

async function drillToClass(row) {
  if (row.class_id) {
    filters.value.class_id = String(row.class_id)
  }
  if (!filters.value.month) {
    filters.value.month = String(currentMonth)
  }
  viewMode.value = 'detail'
  detailTab.value = 'students'
  await loadDetail()
}

async function drillToMonth(m) {
  filters.value.month = String(m.month)
  if (report.value?.by_month?.year) {
    filters.value.year = String(report.value.by_month.year)
  }
  viewMode.value = 'detail'
  detailTab.value = 'students'
  await loadDetail()
}

async function exportCsv() {
  exporting.value = true
  try {
    const isDetail = viewMode.value === 'detail'
    const res = isDetail
      ? await bkReportApi.exportViolations(cleanParams({ forDetail: true }))
      : await bkReportApi.export(cleanParams({ forDetail: false }))
    const url = window.URL.createObjectURL(new Blob([res.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute(
      'download',
      isDetail
        ? `laporan-bk-detail-pelanggaran-${new Date().toISOString().slice(0, 10)}.csv`
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
  if (viewMode.value === 'detail') {
    if (!detail.value) return '<p>Tidak ada data.</p>'

    const studentRows = (detail.value.by_student || []).map((row, idx) => {
      const vPts = row.violation_points ?? row.total_points ?? 0
      const aPts = row.achievement_points ?? 0
      const score = row.score ?? (vPts - aPts)
      return `
      <tr>
        <td>${idx + 1}</td>
        <td>${escapeHtml(row.nis || '—')}</td>
        <td>${escapeHtml(row.student_name || '—')}</td>
        <td>${escapeHtml(row.class_name)}</td>
        <td class="num">${escapeHtml(row.violation_count)}</td>
        <td class="num">${escapeHtml(vPts)}</td>
        <td class="num">${escapeHtml(row.achievement_count ?? 0)}</td>
        <td class="num">−${escapeHtml(aPts)}</td>
        <td class="num"><strong>${escapeHtml(score)}</strong></td>
      </tr>`
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
    `
  }

  // Ringkasan
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
      <div class="stat"><div class="stat-label">Poin Pelanggaran</div><div class="stat-value">${s.total_violation_points ?? 0}</div></div>
      <div class="stat"><div class="stat-label">Poin Prestasi</div><div class="stat-value">−${s.total_achievement_points ?? 0}</div></div>
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
  const hasData = viewMode.value === 'detail' ? !!detail.value : !!report.value
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
    const title = viewMode.value === 'detail'
      ? 'Laporan BK — Detail & Skor Siswa'
      : 'Laporan BK — Ringkasan'
    const periodLabel = viewMode.value === 'detail'
      ? detailFilterLabel.value
      : [
          classes.value.find(c => String(c.id) === String(filters.value.class_id))?.name || 'Semua kelas',
          academicYears.value.find(y => String(y.id) === String(filters.value.academic_year_id))?.name || 'Semua tahun ajaran',
          `Tren ${filters.value.year || currentYear}`,
        ].join(' · ')

    const createdAt = new Date().toLocaleDateString('id-ID', {
      day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit',
    })
    const placeDate = `${inst.district || inst.city || '........................'}, ${new Date().toLocaleDateString('id-ID', {
      day: 'numeric', month: 'long', year: 'numeric',
    })}`
    const filename = `Laporan_BK_${viewMode.value === 'detail' ? 'Detail' : 'Ringkasan'}_${new Date().toISOString().slice(0, 10)}.pdf`

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
    td.num, th.num { text-align: right; }
    .stats { display: flex; gap: 8px; margin-bottom: 12px; }
    .stat { flex: 1; border: 1px solid #333; padding: 8px; text-align: center; }
    .stat-label { font-size: 9px; text-transform: uppercase; color: #555; }
    .stat-value { font-size: 16px; font-weight: 700; margin-top: 2px; }
    .note { font-size: 10px; color: #444; margin: 0 0 10px; }
    .footer { display: flex; justify-content: space-between; margin-top: 28px; page-break-inside: avoid; }
    .footer-right { text-align: center; min-width: 220px; }
    .sig-space { height: 56px; }
    @media print {
      @page { size: A4 ${viewMode.value === 'detail' ? 'landscape' : 'portrait'}; margin: 10mm 12mm 10mm 10mm; }
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
  <div class="period"><strong>Periode / Filter:</strong> ${escapeHtml(periodLabel)}</div>
  ${buildPrintBodyHtml()}
  <div class="footer">
    <div>
      <strong>Dibuat pada:</strong><br>${escapeHtml(createdAt)}
    </div>
    <div class="footer-right">
      ${escapeHtml(placeDate)}<br>
      ${escapeHtml(getPrincipalTitle(inst.level))}
      <div class="sig-space"></div>
      <strong>${escapeHtml(inst.principal_name || '___________________')}</strong>
      <br>NIP. ${escapeHtml(inst.principal_nip || '___________________')}
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
  if (route.query.tab === 'detail') {
    await switchMode('detail')
  } else {
    await loadSummary()
  }
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
.filters-inline {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 1.25rem;
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
.stat-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
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
}
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
.nav-tabs-wrap { margin-bottom: 1rem; }
.nav-tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 0.35rem;
}
.nav-tab {
  flex: 1;
  min-width: 140px;
  text-align: left;
  border: none;
  background: transparent;
  border-radius: 8px;
  padding: 0.65rem 0.9rem;
  cursor: pointer;
  transition: background 0.15s ease;
}
.nav-tab:hover { background: #f1f5f9; }
.nav-tab.active {
  background: #eff6ff;
  box-shadow: inset 0 0 0 1px #bfdbfe;
}
.nav-tab-label {
  display: block;
  font-weight: 600;
  font-size: 0.9rem;
  color: #1e293b;
}
.nav-tab-hint {
  display: block;
  font-size: 0.75rem;
  color: #64748b;
  margin-top: 0.15rem;
}
.report-section { margin-top: 0.5rem; }
.detail-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 1rem;
}
.meta-chip {
  display: inline-flex;
  align-items: center;
  padding: 0.35rem 0.75rem;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 999px;
  font-size: 0.8rem;
  font-weight: 600;
  color: #334155;
}
.meta-warn {
  border-color: #fde68a;
  background: #fffbeb;
  color: #92400e;
}
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
.row-clickable { cursor: pointer; }
.row-clickable:hover { background: #eff6ff !important; }
.th-num, .td-num { text-align: right; font-variant-numeric: tabular-nums; }
.td-total { font-weight: 700; }
.td-good { color: #059669; font-weight: 600; }
.score-bad { color: #b91c1c; }
.score-warn { color: #c2410c; }
.score-good { color: #047857; }
.section-hint {
  margin: 0 0 0.75rem;
  font-size: 0.85rem;
  color: #64748b;
}
.meta-good {
  background: #ecfdf5;
  color: #047857;
  border-color: #a7f3d0;
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
.charts-row {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
}
.chart-box {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1rem 1.15rem;
}
.chart-box h4 {
  margin: 0 0 0.75rem;
  font-size: 0.95rem;
  color: #334155;
}
.chart-wrap { height: 280px; }
.chart-wrap-pie { height: 260px; }
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
.btn-primary, .btn-secondary {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.55rem 0.9rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  border: none;
  cursor: pointer;
}
.btn-compact { white-space: nowrap; }
.btn-primary { background: #2563eb; color: #fff; }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-secondary {
  background: #fff;
  color: #334155;
  border: 1px solid #e2e8f0;
}
.btn-secondary:disabled { opacity: 0.6; cursor: not-allowed; }
@media (max-width: 900px) {
  .stat-cards { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 600px) {
  .laporan-bk-page { padding: 1rem; }
  .stat-cards { grid-template-columns: 1fr; }
  .chart-wrap, .chart-wrap-pie { height: 220px; }
}
</style>
