<template>
  <Layout>
    <div class="laporan-uks-page">
      <div class="toolbar">
        <div class="mode-tabs">
          <button type="button" :class="['mode-tab', { active: viewMode === 'ringkasan' }]" @click="switchMode('ringkasan')">Ringkasan</button>
          <button type="button" :class="['mode-tab', { active: viewMode === 'detail' }]" @click="switchMode('detail')">Detail Kunjungan</button>
        </div>
        <div class="header-actions">
          <button type="button" class="btn-secondary btn-compact" :disabled="exporting || loading" @click="exportCsv">
            <span>{{ exporting ? 'Mengekspor...' : (viewMode === 'detail' ? 'Export Detail CSV' : 'Export Rekap CSV') }}</span>
          </button>
          <button type="button" class="btn-secondary btn-compact" :disabled="printing || loading" @click="printPdf">
            <span>{{ printing ? 'Menyiapkan...' : 'Cetak PDF' }}</span>
          </button>
          <button type="button" class="btn-primary btn-compact" :disabled="loading" @click="reload">Muat Ulang</button>
        </div>
      </div>

      <p class="toolbar-hint">
        <template v-if="viewMode === 'ringkasan'">
          Rekap kunjungan UKS per kelas, bulan, jenis, dan status. Konsisten dengan pola laporan BK.
        </template>
        <template v-else>
          Detail kunjungan dan rekap frekuensi per siswa.
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
        <select v-model="filters.class_id" class="filter-select" @change="reload">
          <option value="">Semua Kelas</option>
          <option v-for="c in classes" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
        </select>
        <select v-model="filters.year" class="filter-select" @change="reload" title="Tahun kalender untuk tren bulanan">
          <option v-for="y in chartYears" :key="y" :value="String(y)">Tren {{ y }}</option>
        </select>
        <template v-if="viewMode === 'detail'">
          <select v-model="filters.month" class="filter-select" @change="reload">
            <option value="">Semua Bulan</option>
            <option v-for="m in 12" :key="m" :value="String(m)">{{ monthFullLabel(m) }}</option>
          </select>
          <select v-model="filters.status" class="filter-select" @change="reload">
            <option value="">Semua Status</option>
            <option value="selesai">Selesai</option>
            <option value="observasi">Observasi</option>
            <option value="rujuk">Rujuk</option>
          </select>
        </template>
      </div>

      <div v-if="loading" class="loading-state">
        <div class="loading-spinner"></div>
        <p>Memuat laporan UKS...</p>
      </div>

      <template v-else-if="viewMode === 'ringkasan' && report">
        <div class="stat-cards">
          <div class="stat-card">
            <span class="stat-label">Total Kunjungan</span>
            <span class="stat-value">{{ report.summary?.total_visits ?? 0 }}</span>
          </div>
          <div class="stat-card">
            <span class="stat-label">Bulan Ini</span>
            <span class="stat-value">{{ report.summary?.visits_this_month ?? 0 }}</span>
          </div>
          <div class="stat-card">
            <span class="stat-label">Siswa Dilayani</span>
            <span class="stat-value">{{ report.summary?.students_served ?? 0 }}</span>
          </div>
          <div class="stat-card stat-card-warn">
            <span class="stat-label">Observasi</span>
            <span class="stat-value">{{ report.summary?.total_observation ?? 0 }}</span>
          </div>
          <div class="stat-card stat-card-bad">
            <span class="stat-label">Rujukan</span>
            <span class="stat-value">{{ report.summary?.total_referral ?? 0 }}</span>
          </div>
        </div>

        <div class="nav-tabs-wrap">
          <nav class="nav-tabs">
            <button type="button" :class="['nav-tab', { active: summaryTab === 'class' }]" @click="summaryTab = 'class'">Per Kelas</button>
            <button type="button" :class="['nav-tab', { active: summaryTab === 'month' }]" @click="summaryTab = 'month'">Per Bulan</button>
            <button type="button" :class="['nav-tab', { active: summaryTab === 'types' }]" @click="summaryTab = 'types'">Per Jenis</button>
            <button type="button" :class="['nav-tab', { active: summaryTab === 'status' }]" @click="summaryTab = 'status'">Per Status</button>
          </nav>
        </div>

        <section v-if="summaryTab === 'class'" class="report-section">
          <div v-if="!report.by_class?.length" class="empty-state">
            <h3 class="empty-title">Belum ada data</h3>
            <p class="empty-desc">Tidak ada kunjungan UKS untuk filter yang dipilih.</p>
          </div>
          <div v-else class="table-card">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Kelas</th>
                  <th class="th-num">Kunjungan</th>
                  <th class="th-num">Rujukan</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in report.by_class" :key="row.class_id ?? row.class_name" class="row-clickable" @click="drillToClass(row)">
                  <td>{{ row.class_name }}</td>
                  <td class="td-num">{{ row.visit_count }}</td>
                  <td class="td-num">{{ row.referral_count }}</td>
                  <td class="td-action"><button type="button" class="link-btn" @click.stop="drillToClass(row)">Lihat detail →</button></td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section v-else-if="summaryTab === 'month'" class="report-section">
          <div class="chart-box" v-if="monthChartData">
            <h4>Tren per bulan ({{ report.by_month?.year }})</h4>
            <div class="chart-wrap"><Bar :data="monthChartData" :options="chartOptionsBar" /></div>
          </div>
          <div class="table-card" style="margin-top: 1rem">
            <table class="data-table">
              <thead>
                <tr><th>Bulan</th><th class="th-num">Kunjungan</th><th class="th-num">Rujukan</th></tr>
              </thead>
              <tbody>
                <tr v-for="m in report.by_month?.months || []" :key="m.month">
                  <td>{{ monthFullLabel(m.month) }}</td>
                  <td class="td-num">{{ m.visit_count }}</td>
                  <td class="td-num">{{ m.referral_count }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section v-else-if="summaryTab === 'types'" class="report-section">
          <div class="charts-row" v-if="typeChartData">
            <div class="chart-box">
              <h4>Distribusi jenis kunjungan</h4>
              <div class="chart-wrap chart-wrap-pie"><Doughnut :data="typeChartData" :options="chartOptionsDoughnut" /></div>
            </div>
          </div>
          <div class="table-card">
            <table class="data-table">
              <thead><tr><th>#</th><th>Jenis</th><th class="th-num">Jumlah</th></tr></thead>
              <tbody>
                <tr v-for="(row, idx) in report.by_type || []" :key="row.uks_visit_type_id ?? row.type_name">
                  <td>{{ idx + 1 }}</td>
                  <td>{{ row.type_name }}</td>
                  <td class="td-num">{{ row.visit_count }}</td>
                </tr>
                <tr v-if="!(report.by_type || []).length"><td colspan="3">Belum ada data</td></tr>
              </tbody>
            </table>
          </div>
        </section>

        <section v-else class="report-section">
          <div class="table-card">
            <table class="data-table">
              <thead><tr><th>Status</th><th class="th-num">Jumlah</th></tr></thead>
              <tbody>
                <tr v-for="row in report.by_status || []" :key="row.status">
                  <td>{{ statusLabel(row.status) }}</td>
                  <td class="td-num">{{ row.visit_count }}</td>
                </tr>
                <tr v-if="!(report.by_status || []).length"><td colspan="2">Belum ada data</td></tr>
              </tbody>
            </table>
          </div>
        </section>
      </template>

      <template v-else-if="viewMode === 'detail' && detail">
        <div class="meta-grid">
          <div class="meta-item">
            <span class="meta-icon" aria-hidden="true">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 7h16M4 12h10M4 17h7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </span>
            <div class="meta-body">
              <span class="meta-label">Total baris</span>
              <span class="meta-value">{{ detail.total ?? 0 }} kunjungan</span>
            </div>
          </div>
          <div class="meta-item">
            <span class="meta-icon" aria-hidden="true">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </span>
            <div class="meta-body">
              <span class="meta-label">Siswa unik</span>
              <span class="meta-value">{{ detail.by_student?.length ?? 0 }} siswa</span>
            </div>
          </div>
          <div v-if="detail.truncated" class="meta-item">
            <span class="meta-icon meta-icon-warn" aria-hidden="true">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 9v4M12 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M10.3 4.3 2.8 17a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
            </span>
            <div class="meta-body">
              <span class="meta-label">Catatan</span>
              <span class="meta-value">Ditampilkan maks. 2000 baris</span>
            </div>
          </div>
        </div>

        <div class="nav-tabs-wrap">
          <nav class="nav-tabs">
            <button type="button" :class="['nav-tab', { active: detailTab === 'students' }]" @click="detailTab = 'students'">Per Siswa</button>
            <button type="button" :class="['nav-tab', { active: detailTab === 'list' }]" @click="detailTab = 'list'">Daftar Kunjungan</button>
          </nav>
        </div>

        <section v-if="detailTab === 'students'" class="report-section">
          <div class="table-card">
            <table class="data-table">
              <thead>
                <tr>
                  <th>#</th><th>NIS</th><th>Nama</th><th>Kelas</th>
                  <th class="th-num">Kunjungan</th><th class="th-num">Rujukan</th>
                </tr>
              </thead>
                <tbody>
                  <tr v-if="!uksStudentGroups.length"><td colspan="6">Belum ada data</td></tr>
                  <template v-for="group in uksStudentGroups" :key="'uks-' + group.key">
                    <tr class="group-row">
                      <td colspan="6">Kelas {{ group.name }} · {{ group.rows.length }} siswa</td>
                    </tr>
                    <tr v-for="(row, i) in group.rows" :key="row.student_id">
                      <td>{{ group.start + i + 1 }}</td>
                      <td>{{ row.nis || '—' }}</td>
                      <td>{{ row.student_name }}</td>
                      <td>{{ row.class_name }}</td>
                      <td class="td-num">{{ row.visit_count }}</td>
                      <td class="td-num">{{ row.referral_count }}</td>
                    </tr>
                  </template>
                </tbody>
            </table>
          </div>
        </section>

        <section v-else class="report-section">
          <div class="table-card">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Tanggal</th><th>NIS</th><th>Nama</th><th>Kelas</th>
                  <th>Jenis</th><th>Status</th><th>Keluhan</th><th>Petugas</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in detail.items || []" :key="row.id">
                  <td>{{ formatDate(row.visit_date) }}</td>
                  <td>{{ row.nis || '—' }}</td>
                  <td>{{ row.student_name }}</td>
                  <td>{{ row.class_name }}</td>
                  <td>{{ row.visit_type }}</td>
                  <td>{{ statusLabel(row.status) }}</td>
                  <td>{{ row.complaint || row.action_taken || '—' }}</td>
                  <td>{{ row.recorder_name || '—' }}</td>
                </tr>
                <tr v-if="!(detail.items || []).length"><td colspan="8">Belum ada data</td></tr>
              </tbody>
            </table>
          </div>
        </section>
      </template>
    </div>
  </Layout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Bar, Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, ArcElement, Title, Tooltip, Legend } from 'chart.js'
import Layout from '@/components/Layout.vue'
import { uksReportApi } from '@/api/uks'
import { institutionApi } from '@/api/institution'
import { classApi } from '@/api/class'
import { semesterApi } from '@/api/semester'
import { useReferenceDataStore } from '@/stores/referenceData'
import { useToast } from '@/composables/useToast'
import '@/assets/module-page.css'

ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, Title, Tooltip, Legend)

const toast = useToast()
const referenceStore = useReferenceDataStore()

const loading = ref(true)
const exporting = ref(false)
const printing = ref(false)
const viewMode = ref('ringkasan')
const summaryTab = ref('class')
const detailTab = ref('students')
const report = ref(null)
const detail = ref(null)
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
const uksStudentGroups = computed(() => groupRowsByClassName(detail.value?.by_student || []))
const classes = ref([])
const semesters = ref([])
const institution = ref(null)

const currentYear = new Date().getFullYear()
const chartYears = [currentYear, currentYear - 1, currentYear - 2]

const filters = ref({
  academic_year_id: '',
  semester_id: '',
  class_id: '',
  month: '',
  year: String(currentYear),
  status: '',
})

const academicYears = computed(() => referenceStore.academicYears || [])
const filteredSemesters = computed(() => {
  const yearId = filters.value.academic_year_id
  if (!yearId) return semesters.value
  return semesters.value.filter(s => String(s.academic_year_id) === String(yearId))
})

const MONTH_NAMES = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']
function monthFullLabel(m) { return MONTH_NAMES[m] || `Bulan ${m}` }
function statusLabel(s) {
  return ({ selesai: 'Selesai', observasi: 'Observasi', rujuk: 'Rujuk' })[s] || s || '—'
}
function formatDate(val) {
  if (!val) return '—'
  return new Date(val).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

const monthChartData = computed(() => {
  const months = report.value?.by_month?.months
  if (!months?.length) return null
  return {
    labels: months.map(m => MONTH_NAMES[m.month]?.slice(0, 3) || m.month),
    datasets: [
      { label: 'Kunjungan', data: months.map(m => m.visit_count), backgroundColor: 'rgba(14,165,233,.65)' },
      { label: 'Rujukan', data: months.map(m => m.referral_count), backgroundColor: 'rgba(239,68,68,.55)' },
    ],
  }
})

const typeChartData = computed(() => {
  const rows = report.value?.by_type
  if (!rows?.length) return null
  return {
    labels: rows.map(r => r.type_name),
    datasets: [{ data: rows.map(r => r.visit_count), backgroundColor: ['#0ea5e9', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6', '#64748b'] }],
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
  plugins: { legend: { position: 'bottom' } },
}

function cleanParams() {
  const params = { ...filters.value }
  Object.keys(params).forEach(k => {
    if (params[k] === '' || params[k] == null) delete params[k]
  })
  return params
}

async function loadSummary() {
  const res = await uksReportApi.getSummary(cleanParams())
  report.value = res.data.data || null
}

async function loadDetail() {
  const res = await uksReportApi.getVisits(cleanParams())
  detail.value = res.data.data || null
}

async function reload() {
  loading.value = true
  try {
    if (viewMode.value === 'ringkasan') await loadSummary()
    else await loadDetail()
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || 'Gagal memuat laporan UKS.')
    report.value = null
    detail.value = null
  } finally {
    loading.value = false
  }
}

function switchMode(mode) {
  viewMode.value = mode
  reload()
}

function drillToClass(row) {
  if (row.class_id) filters.value.class_id = String(row.class_id)
  switchMode('detail')
}

function onPeriodChange() {
  filters.value.semester_id = ''
  reload()
}

async function exportCsv() {
  exporting.value = true
  try {
    const res = viewMode.value === 'detail'
      ? await uksReportApi.exportVisits(cleanParams())
      : await uksReportApi.export(cleanParams())
    const blob = new Blob([res.data], { type: 'text/csv;charset=utf-8' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = viewMode.value === 'detail'
      ? `laporan-uks-detail-${new Date().toISOString().slice(0, 10)}.csv`
      : `laporan-uks-rekap-${new Date().toISOString().slice(0, 10)}.csv`
    a.click()
    URL.revokeObjectURL(url)
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || 'Gagal mengekspor.')
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
    const studentRows = (uksStudentGroups.value || []).flatMap((group) => {
      const header = `<tr class="group-row"><td colspan="6">Kelas ${escapeHtml(group.name)} (${group.rows.length} siswa)</td></tr>`
      const body = group.rows.map((row, i) => `
      <tr>
        <td>${group.start + i + 1}</td>
        <td>${escapeHtml(row.nis || '—')}</td>
        <td>${escapeHtml(row.student_name)}</td>
        <td>${escapeHtml(row.class_name)}</td>
        <td class="num">${escapeHtml(row.visit_count)}</td>
        <td class="num">${escapeHtml(row.referral_count)}</td>
      </tr>`).join('')
      return header + body
    }).join('') || '<tr><td colspan="6">Belum ada data</td></tr>'
    const listRows = (detail.value.items || []).map(row => `
      <tr>
        <td>${escapeHtml(formatDate(row.visit_date))}</td>
        <td>${escapeHtml(row.nis || '—')}</td>
        <td>${escapeHtml(row.student_name)}</td>
        <td>${escapeHtml(row.class_name)}</td>
        <td>${escapeHtml(row.visit_type)}</td>
        <td>${escapeHtml(statusLabel(row.status))}</td>
        <td>${escapeHtml(row.complaint || row.action_taken || '—')}</td>
        <td>${escapeHtml(row.recorder_name || '—')}</td>
      </tr>`).join('') || '<tr><td colspan="8">Belum ada data</td></tr>'
    return `
      <h2>1. Rekap per Siswa</h2>
      <table><thead><tr><th>#</th><th>NIS</th><th>Nama</th><th>Kelas</th><th>Kunjungan</th><th>Rujukan</th></tr></thead>
      <tbody>${studentRows}</tbody></table>
      <h2>2. Daftar Kunjungan</h2>
      <table><thead><tr><th>Tanggal</th><th>NIS</th><th>Nama</th><th>Kelas</th><th>Jenis</th><th>Status</th><th>Keluhan</th><th>Petugas</th></tr></thead>
      <tbody>${listRows}</tbody></table>`
  }

  if (!report.value) return '<p>Tidak ada data.</p>'
  const s = report.value.summary || {}
  const classRows = (report.value.by_class || []).map(row => `
    <tr><td>${escapeHtml(row.class_name)}</td><td class="num">${row.visit_count}</td><td class="num">${row.referral_count}</td></tr>
  `).join('') || '<tr><td colspan="3">Belum ada data</td></tr>'
  const monthRows = (report.value.by_month?.months || []).map(m => `
    <tr><td>${escapeHtml(monthFullLabel(m.month))}</td><td class="num">${m.visit_count}</td><td class="num">${m.referral_count}</td></tr>
  `).join('')
  const typeRows = (report.value.by_type || []).map((row, idx) => `
    <tr><td>${idx + 1}</td><td>${escapeHtml(row.type_name)}</td><td class="num">${row.visit_count}</td></tr>
  `).join('') || '<tr><td colspan="3">Belum ada data</td></tr>'

  return `
    <div class="stats">
      <div class="stat"><div class="stat-label">Total Kunjungan</div><div class="stat-value">${s.total_visits ?? 0}</div></div>
      <div class="stat"><div class="stat-label">Siswa Dilayani</div><div class="stat-value">${s.students_served ?? 0}</div></div>
      <div class="stat"><div class="stat-label">Observasi</div><div class="stat-value">${s.total_observation ?? 0}</div></div>
      <div class="stat"><div class="stat-label">Rujukan</div><div class="stat-value">${s.total_referral ?? 0}</div></div>
    </div>
    <h2>1. Rekap per Kelas</h2>
    <table><thead><tr><th>Kelas</th><th>Kunjungan</th><th>Rujukan</th></tr></thead><tbody>${classRows}</tbody></table>
    <h2>2. Rekap per Bulan (${escapeHtml(report.value.by_month?.year || filters.value.year)})</h2>
    <table><thead><tr><th>Bulan</th><th>Kunjungan</th><th>Rujukan</th></tr></thead><tbody>${monthRows}</tbody></table>
    <h2>3. Per Jenis Kunjungan</h2>
    <table><thead><tr><th>#</th><th>Jenis</th><th>Jumlah</th></tr></thead><tbody>${typeRows}</tbody></table>`
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
    const title = viewMode.value === 'detail' ? 'Laporan UKS — Detail Kunjungan' : 'Laporan UKS — Ringkasan'
    const periodLabel = [
      classes.value.find(c => String(c.id) === String(filters.value.class_id))?.name || 'Semua kelas',
      academicYears.value.find(y => String(y.id) === String(filters.value.academic_year_id))?.name || 'Semua tahun ajaran',
      `Tren ${filters.value.year || currentYear}`,
    ].join(' · ')
    const createdAt = new Date().toLocaleDateString('id-ID', {
      day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit',
    })
    const html = `<!DOCTYPE html><html><head><meta charset="utf-8"><title>${escapeHtml(title)}</title>
      <style>
        body{font-family:Segoe UI,Arial,sans-serif;color:#0f172a;padding:24px;font-size:12px}
        h1{font-size:18px;margin:0 0 4px} h2{font-size:14px;margin:20px 0 8px}
        .meta{color:#64748b;margin-bottom:16px}
        table{width:100%;border-collapse:collapse;margin-bottom:12px}
        th,td{border:1px solid #cbd5e1;padding:6px 8px;text-align:left}
        th{background:#f1f5f9} .num{text-align:right}
        tr.group-row td{background:#e2e8f0;font-weight:700}
        .stats{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px}
        .stat{border:1px solid #e2e8f0;border-radius:8px;padding:8px 12px;min-width:120px}
        .stat-label{font-size:10px;color:#64748b}.stat-value{font-size:18px;font-weight:700}
        @media print{body{padding:0}}
      </style></head><body>
      <h1>${escapeHtml(inst.name || 'Sekolah')}</h1>
      <div class="meta">${escapeHtml(title)} · ${escapeHtml(periodLabel)} · Dicetak ${escapeHtml(createdAt)}</div>
      ${buildPrintBodyHtml()}
      <script>window.onload=function(){window.print()}<\/script>
      </body></html>`
    const w = window.open('', '_blank')
    if (!w) {
      toast.error('Gagal', 'Popup diblokir. Izinkan popup untuk mencetak.')
      return
    }
    w.document.write(html)
    w.document.close()
  } finally {
    printing.value = false
  }
}

onMounted(async () => {
  try {
    await Promise.all([
      referenceStore.fetchAcademicYears(),
      classApi.getAll({ per_page: 200 }).then(r => { classes.value = r.data.data || [] }),
      semesterApi.getAll({ per_page: 200 }).then(r => { semesters.value = r.data.data || [] }).catch(() => {}),
      institutionApi.getMy().then(r => { institution.value = r.data.data || r.data || null }).catch(() => {}),
    ])
  } catch { /* ignore bootstrap errors */ }
  await reload()
})
</script>

<style scoped>
.laporan-uks-page { display: flex; flex-direction: column; gap: 14px; }
.toolbar { display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; align-items: center; }
.mode-tabs { display: flex; gap: 4px; background: #f1f5f9; padding: 4px; border-radius: 10px; }
.mode-tab { border: none; background: transparent; padding: 8px 14px; border-radius: 8px; cursor: pointer; font-size: 13px; color: #64748b; font-weight: 500; }
.mode-tab.active { background: #fff; color: #0f172a; box-shadow: 0 1px 2px rgba(0,0,0,.08); }
.toolbar-hint { margin: 0; font-size: 13px; color: #64748b; }
.filters-inline { display: flex; flex-wrap: wrap; gap: 8px; }
.filter-select { border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 10px; font-size: 13px; background: #fff; }
.loading-state { text-align: center; padding: 48px; color: #64748b; }
.loading-spinner { width: 36px; height: 36px; border: 3px solid #e2e8f0; border-top-color: #0ea5e9; border-radius: 50%; margin: 0 auto 12px; animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.stat-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; }
.stat-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; display: flex; flex-direction: column; gap: 4px; }
.stat-label { font-size: 12px; color: #64748b; }
.stat-value { font-size: 26px; font-weight: 700; color: #0f172a; }
.stat-card-warn .stat-value { color: #b45309; }
.stat-card-bad .stat-value { color: #b91c1c; }
.meta-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 8px 12px;
  padding: 0.9rem 1rem;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
}
.meta-item { display: flex; align-items: flex-start; gap: 10px; min-width: 0; }
.meta-icon {
  flex-shrink: 0; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;
  border-radius: 8px; background: #ecfdf5; color: #059669;
}
.meta-icon-warn { background: #fff7ed; color: #c2410c; }
.meta-body { display: flex; flex-direction: column; gap: 1px; min-width: 0; }
.meta-label { font-size: 11px; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; color: #94a3b8; }
.meta-value { font-size: 13.5px; font-weight: 600; color: #0f172a; line-height: 1.35; word-break: break-word; }
@media (max-width: 768px) { .meta-grid { grid-template-columns: 1fr 1fr; } }
.nav-tabs-wrap { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 6px; }
.nav-tabs { display: flex; flex-wrap: wrap; gap: 4px; }
.nav-tab { border: none; background: transparent; padding: 8px 12px; border-radius: 8px; cursor: pointer; font-size: 13px; color: #64748b; font-weight: 500; }
.nav-tab.active { background: #e0f2fe; color: #0369a1; }
.report-section { display: flex; flex-direction: column; gap: 12px; }
.table-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.data-table th, .data-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; text-align: left; }
.data-table th { background: #f8fafc; color: #475569; font-weight: 600; }
.group-row td { background: #f1f5f9; font-weight: 700; font-size: 12px; color: #334155; }
.th-num, .td-num { text-align: right; }
.row-clickable { cursor: pointer; }
.row-clickable:hover { background: #f8fafc; }
.link-btn { border: none; background: none; color: #0284c7; cursor: pointer; font-size: 12px; }
.chart-box { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; }
.chart-box h4 { margin: 0 0 8px; font-size: 13px; color: #334155; }
.chart-wrap { height: 220px; }
.chart-wrap-pie { height: 240px; }
.empty-state { text-align: center; padding: 40px 16px; background: #fff; border-radius: 12px; border: 1px dashed #cbd5e1; }
.empty-title { margin: 0 0 6px; }
.empty-desc { margin: 0; color: #64748b; }
</style>
