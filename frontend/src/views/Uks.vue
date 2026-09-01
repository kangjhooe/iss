<template>    <div class="uks-page">
      <div class="page-header">
        <div class="header-content">
          <h2 class="page-title">Kunjungan UKS</h2>
          <p class="page-subtitle">Catat siswa yang berobat, observasi, atau dirujuk dari Unit Kesehatan Sekolah.</p>
        </div>
      </div>

      <div class="toolbar">
        <div class="main-tabs">
          <button type="button" :class="['main-tab', { active: activeTab === 'list' }]" @click="switchTab('list')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M8 6H21M8 12H21M8 18H21M3 6H3.01M3 12H3.01M3 18H3.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <span>Kunjungan</span>
          </button>
          <button type="button" :class="['main-tab', { active: activeTab === 'types' }]" @click="switchTab('types')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M12 8v4l3 3M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <span>Jenis kunjungan</span>
          </button>
        </div>
        <div class="header-actions">
          <button v-if="activeTab === 'list'" type="button" class="btn-secondary btn-compact" :disabled="exporting" @click="exportToCsv">
            <span>{{ exporting ? 'Mengekspor...' : 'Export CSV' }}</span>
          </button>
          <button v-if="activeTab === 'list'" type="button" class="btn-secondary btn-compact" :disabled="printing || loading" @click="printPdf">
            <span>{{ printing ? 'Menyiapkan...' : 'Cetak PDF' }}</span>
          </button>
          <button v-if="activeTab === 'list'" type="button" class="btn-primary btn-compact" @click="openAddModal">
            <span>Catat Kunjungan</span>
          </button>
          <button v-if="activeTab === 'types'" type="button" class="btn-secondary btn-compact" :disabled="seeding" @click="seedDefaults">
            <span>{{ seeding ? 'Mengisi...' : 'Isi Jenis Standar' }}</span>
          </button>
          <button v-if="activeTab === 'types'" type="button" class="btn-primary btn-compact" @click="openAddTypeModal">
            <span>Tambah Jenis</span>
          </button>
        </div>
      </div>

      <template v-if="activeTab === 'list'">
        <div class="uks-stats">
          <div class="stat-card">
            <span class="stat-label">Bulan ini</span>
            <span class="stat-value">{{ statsData?.total_this_month ?? '-' }}</span>
          </div>
          <div class="stat-card">
            <span class="stat-label">Tahun {{ statsData?.year || '' }}</span>
            <span class="stat-value">{{ statsData?.total_year ?? '-' }}</span>
          </div>
          <div class="stat-card stat-observasi">
            <span class="stat-label">Observasi</span>
            <span class="stat-value">{{ statsData?.observasi ?? '-' }}</span>
          </div>
          <div class="stat-card stat-rujuk">
            <span class="stat-label">Rujuk</span>
            <span class="stat-value">{{ statsData?.rujuk ?? '-' }}</span>
          </div>
        </div>

        <div class="filters filters-inline">
          <input v-model="filters.search" type="text" class="search-input" placeholder="Cari nama, NIS, NISN..." @input="debounceLoad" />
          <select v-model="filters.status" class="filter-select" @change="loadVisits">
            <option value="">Semua status</option>
            <option value="selesai">Selesai</option>
            <option value="observasi">Observasi</option>
            <option value="rujuk">Rujuk</option>
          </select>
          <select v-model="filters.uks_visit_type_id" class="filter-select" @change="loadVisits">
            <option value="">Semua jenis</option>
            <option v-for="t in visitTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
          </select>
          <input v-model="filters.date_from" type="date" class="filter-select" @change="loadVisits" />
          <input v-model="filters.date_to" type="date" class="filter-select" @change="loadVisits" />
          <select v-model="filters.class_id" class="filter-select" @change="loadVisits">
            <option value="">Semua kelas</option>
            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
          <select v-model="filters.academic_year_id" class="filter-select" @change="loadVisits">
            <option value="">Semua tahun ajaran</option>
            <option v-for="y in academicYears" :key="y.id" :value="y.id">{{ y.name }}</option>
          </select>
          <button v-if="hasActiveFilters" type="button" class="btn-secondary btn-compact" @click="resetFilters">Reset</button>
        </div>

        <div v-if="loading" class="loading-wrap">
          <LoadingSkeleton type="table" :rows="8" :columns="7" />
        </div>
        <div v-else-if="!visits.length" class="empty-state">
          <h3 class="empty-title">Belum ada kunjungan UKS</h3>
          <p class="empty-desc">Catat kunjungan siswa ke UKS, atau ubah filter untuk melihat data.</p>
          <button type="button" class="btn-primary" @click="openAddModal">Catat Kunjungan</button>
        </div>
        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Siswa</th>
                <th>Jenis</th>
                <th>Status</th>
                <th>Keluhan / Tindakan</th>
                <th>Petugas</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="v in visits" :key="v.id">
                <td>{{ formatDate(v.visit_date) }}</td>
                <td>
                  <span class="student-name">{{ v.student?.name }}</span>
                  <span class="student-meta">{{ v.student?.nis || v.student?.nisn || '-' }} · {{ v.student?.class?.name || '-' }}</span>
                </td>
                <td>{{ v.visit_type?.name || '-' }}</td>
                <td><span :class="['status-badge', 'status-' + v.status]">{{ statusLabel(v.status) }}</span></td>
                <td class="summary-cell">{{ truncate(v.complaint || v.action_taken || v.notes, 60) }}</td>
                <td>{{ v.recorder?.name || '-' }}</td>
                <td>
                  <div class="action-buttons">
                    <TableAction kind="view" @click="openDetailModal(v)" />
                    <TableAction kind="edit" @click="openEditModal(v)" />
                    <TableAction kind="delete" @click="deleteTarget = v" />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <PaginationBar
          :page="pagination.current_page"
          :last-page="pagination.last_page"
          :per-page="pagination.per_page"
          :total="pagination.total"
          item-label="kunjungan"
          @page-change="goToPage"
          @per-page-change="changePerPage"
        />

        <details class="chart-details">
          <summary>Grafik kunjungan {{ statsData?.year || '' }}</summary>
          <div class="uks-charts">
            <div class="chart-box">
              <div class="chart-header">
                <h4>Per bulan</h4>
                <select v-model="statsYear" class="chart-year-select" @change="loadStats">
                  <option v-for="y in statsYears" :key="y" :value="y">{{ y }}</option>
                </select>
              </div>
              <div v-if="byMonthChartData" class="chart-wrap">
                <Bar :data="byMonthChartData" :options="chartOptionsBar" />
              </div>
              <p v-else class="chart-empty">Belum ada data tahun ini.</p>
            </div>
            <div class="chart-box">
              <div class="chart-header"><h4>Per jenis</h4></div>
              <div v-if="byTypeChartData" class="chart-wrap chart-wrap-pie">
                <Doughnut :data="byTypeChartData" :options="chartOptionsDoughnut" />
              </div>
              <p v-else class="chart-empty">Belum ada data jenis kunjungan.</p>
            </div>
          </div>
        </details>
      </template>

      <template v-else>
        <p class="settings-hint">Jenis kunjungan dipakai saat pencatatan harian. Isi jenis standar jika belum ada, atau buat sendiri.</p>
        <div v-if="typesLoading" class="loading-wrap"><LoadingSkeleton type="table" :rows="5" :columns="4" /></div>
        <div v-else-if="!visitTypes.length" class="empty-state">
          <h3 class="empty-title">Belum ada jenis kunjungan</h3>
          <p class="empty-desc">Isi jenis standar (P3K, screening, rujukan, dll.) atau buat sendiri.</p>
          <button type="button" class="btn-primary" @click="seedDefaults">Isi Jenis Standar</button>
        </div>
        <div v-else class="types-grid">
          <div v-for="t in visitTypes" :key="t.id" class="type-card">
            <div class="type-header">
              <span class="type-name">{{ t.name }}</span>
              <span v-if="t.code" class="type-code">{{ t.code }}</span>
            </div>
            <p v-if="t.description" class="type-desc">{{ t.description }}</p>
            <div class="type-footer">
              <span :class="['status-badge', t.is_active ? 'status-selesai' : 'status-rujuk']">{{ t.is_active ? 'Aktif' : 'Nonaktif' }}</span>
              <div class="action-buttons">
                <TableAction kind="edit" @click="openEditTypeModal(t)" />
                <TableAction kind="delete" @click="deleteTypeTarget = t" />
              </div>
            </div>
          </div>
        </div>
      </template>

      <div v-if="showFormModal" class="modal-overlay" @click.self="showFormModal = false">
        <div class="modal-card">
          <div class="modal-header">
            <h3>{{ editingVisit ? 'Edit Kunjungan UKS' : 'Catat Kunjungan UKS' }}</h3>
            <button type="button" class="btn-close" @click="showFormModal = false" aria-label="Tutup">×</button>
          </div>
          <p v-if="formError" class="form-error">{{ formError }}</p>
          <form class="form-grid" @submit.prevent="submitForm">
            <div v-if="editingVisit" class="full student-picker-locked">
              <span class="field-label">Siswa</span>
              <p>
                {{ editingVisit.student?.name || '—' }}
                <span class="student-meta">
                  {{ editingVisit.student?.nis || editingVisit.student?.nisn || '' }}
                  <template v-if="editingVisit.student?.class?.name"> · {{ editingVisit.student.class.name }}</template>
                </span>
              </p>
            </div>
            <div v-else class="full student-picker">
              <div class="picker-row">
                <label>Kelas
                  <select v-model="pickerClassId" @change="onPickerClassChange">
                    <option value="">Semua kelas</option>
                    <option v-for="c in classes" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
                  </select>
                </label>
                <label>Cari siswa
                  <input
                    v-model="pickerStudentSearch"
                    type="text"
                    placeholder="Nama, NIS, NISN, atau NIK"
                    @input="debouncePickerStudentSearch"
                  />
                </label>
              </div>
              <p class="field-hint">
                <template v-if="!pickerClassId && !pickerStudentSearch.trim()">Pilih kelas atau ketik nama/NIS siswa.</template>
                <template v-else-if="loadingPickerStudents">Memuat siswa...</template>
                <template v-else-if="pickerStudentError">{{ pickerStudentError }}</template>
                <template v-else-if="pickerStudents.length">{{ pickerStudents.length }} siswa — pilih di daftar bawah.</template>
                <template v-else>Tidak ada siswa cocok.</template>
              </p>
              <select
                v-model="form.student_id"
                required
                class="student-listbox"
                size="7"
                :disabled="loadingPickerStudents || (!pickerClassId && !pickerStudentSearch.trim())"
              >
                <option value="">Pilih siswa</option>
                <option v-for="s in pickerStudents" :key="s.id" :value="String(s.id)">{{ studentOptionLabel(s) }}</option>
              </select>
            </div>

            <label>Tanggal *
              <input v-model="form.visit_date" type="date" required />
            </label>
            <label>Jenis kunjungan
              <select v-model="form.uks_visit_type_id">
                <option value="">— Pilih (opsional) —</option>
                <option v-for="t in activeTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
              </select>
            </label>
            <label>Status
              <select v-model="form.status">
                <option value="selesai">Selesai</option>
                <option value="observasi">Observasi</option>
                <option value="rujuk">Rujuk</option>
              </select>
            </label>
            <label class="full">Keluhan
              <textarea v-model="form.complaint" rows="2" placeholder="Keluhan siswa, misalnya pusing, demam, luka..." />
            </label>
            <label class="full">Tindakan
              <textarea v-model="form.action_taken" rows="2" placeholder="Tindakan di UKS, misalnya istirahat, kompres, obat..." />
            </label>
            <label class="full">Catatan
              <textarea v-model="form.notes" rows="2" placeholder="Catatan tambahan (opsional)" />
            </label>
            <div class="full vital-grid">
              <label>TB (cm)<input v-model="form.height_cm" type="number" step="0.1" min="0" placeholder="—" /></label>
              <label>BB (kg)<input v-model="form.weight_kg" type="number" step="0.1" min="0" placeholder="—" /></label>
              <label>Suhu (°C)<input v-model="form.temperature_c" type="number" step="0.1" min="30" max="45" placeholder="—" /></label>
              <label>Tekanan darah<input v-model="form.blood_pressure" type="text" placeholder="120/80" /></label>
            </div>
            <div class="modal-actions full">
              <button type="button" class="btn-secondary" @click="showFormModal = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="formSubmitting">{{ formSubmitting ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>

      <div v-if="showDetailModal && detailVisit" class="modal-overlay" @click.self="showDetailModal = false">
        <div class="modal-card modal-card-sm">
          <div class="modal-header">
            <h3>Detail kunjungan</h3>
            <button type="button" class="btn-close" @click="showDetailModal = false" aria-label="Tutup">×</button>
          </div>
          <div class="detail-body">
            <p class="detail-student">{{ detailVisit.student?.name }}</p>
            <p class="student-meta">{{ detailVisit.student?.nis || detailVisit.student?.nisn || '-' }} · {{ detailVisit.student?.class?.name || '-' }}</p>
            <dl class="detail-dl">
              <div><dt>Tanggal</dt><dd>{{ formatDate(detailVisit.visit_date) }}</dd></div>
              <div><dt>Jenis</dt><dd>{{ detailVisit.visit_type?.name || '-' }}</dd></div>
              <div><dt>Status</dt><dd><span :class="['status-badge', 'status-' + detailVisit.status]">{{ statusLabel(detailVisit.status) }}</span></dd></div>
              <div><dt>Petugas</dt><dd>{{ detailVisit.recorder?.name || '-' }}</dd></div>
              <div class="full"><dt>Keluhan</dt><dd>{{ detailVisit.complaint || '-' }}</dd></div>
              <div class="full"><dt>Tindakan</dt><dd>{{ detailVisit.action_taken || '-' }}</dd></div>
              <div class="full"><dt>Catatan</dt><dd>{{ detailVisit.notes || '-' }}</dd></div>
              <div><dt>TB / BB</dt><dd>{{ detailVisit.height_cm || '-' }} cm / {{ detailVisit.weight_kg || '-' }} kg</dd></div>
              <div><dt>Suhu / TD</dt><dd>{{ detailVisit.temperature_c || '-' }} °C / {{ detailVisit.blood_pressure || '-' }}</dd></div>
            </dl>
            <div class="modal-actions">
              <button type="button" class="btn-secondary" @click="openHistoryFromDetail">Riwayat siswa</button>
              <button type="button" class="btn-primary" @click="openEditModal(detailVisit); showDetailModal = false">Edit</button>
            </div>
          </div>
        </div>
      </div>

      <div v-if="showHistoryModal" class="modal-overlay" @click.self="showHistoryModal = false">
        <div class="modal-card">
          <div class="modal-header">
            <h3>Riwayat UKS — {{ historyStudent?.name || '' }}</h3>
            <button type="button" class="btn-close" @click="showHistoryModal = false" aria-label="Tutup">×</button>
          </div>
          <p v-if="historyLoading" class="field-hint">Memuat riwayat...</p>
          <p v-else-if="!historyVisits.length" class="field-hint">Belum ada kunjungan untuk siswa ini.</p>
          <ul v-else class="history-list">
            <li v-for="h in historyVisits" :key="h.id">
              <span class="history-date">{{ formatDate(h.visit_date) }}</span>
              <span class="history-type">{{ h.visit_type?.name || 'Kunjungan' }}</span>
              <span :class="['status-badge', 'status-' + h.status]">{{ statusLabel(h.status) }}</span>
              <span class="history-note">{{ truncate(h.complaint || h.action_taken || h.notes, 80) }}</span>
            </li>
          </ul>
        </div>
      </div>

      <div v-if="showTypeModal" class="modal-overlay" @click.self="showTypeModal = false">
        <div class="modal-card modal-card-sm">
          <div class="modal-header">
            <h3>{{ editingType ? 'Edit Jenis Kunjungan' : 'Tambah Jenis Kunjungan' }}</h3>
            <button type="button" class="btn-close" @click="showTypeModal = false" aria-label="Tutup">×</button>
          </div>
          <p v-if="typeFormError" class="form-error">{{ typeFormError }}</p>
          <form class="form-grid" @submit.prevent="submitTypeForm">
            <label class="full">Nama *<input v-model="typeForm.name" required placeholder="Contoh: P3K / Pertolongan pertama" /></label>
            <label>Kode<input v-model="typeForm.code" placeholder="P3K" /></label>
            <label>Aktif
              <select v-model="typeForm.is_active">
                <option :value="true">Ya</option>
                <option :value="false">Tidak</option>
              </select>
            </label>
            <label class="full">Deskripsi<textarea v-model="typeForm.description" rows="2" /></label>
            <div class="modal-actions full">
              <button type="button" class="btn-secondary" @click="showTypeModal = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="typeFormSubmitting">{{ typeFormSubmitting ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>

      <ConfirmDialog
        :show="!!deleteTarget"
        title="Hapus kunjungan"
        :message="deleteVisitMessage"
        confirm-text="Hapus"
        @confirm="confirmDeleteVisit"
        @cancel="deleteTarget = null"
      />
      <ConfirmDialog
        :show="!!deleteTypeTarget"
        title="Hapus jenis kunjungan"
        :message="deleteTypeMessage"
        confirm-text="Hapus"
        @confirm="confirmDeleteType"
        @cancel="deleteTypeTarget = null"
      />
    </div></template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import PaginationBar from '@/components/PaginationBar.vue'
import TableAction from '@/components/TableAction.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { Bar, Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, ArcElement, Title, Tooltip, Legend } from 'chart.js'
import { uksApi, uksVisitTypeApi, uksReportApi } from '@/api/uks'
import { useReferenceDataStore } from '@/stores/referenceData'
import { useToast } from '@/composables/useToast'
import { useAuthStore } from '@/stores/auth'
import '@/assets/module-page.css'

ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, Title, Tooltip, Legend)

const toast = useToast()
const authStore = useAuthStore()
const referenceStore = useReferenceDataStore()

const activeTab = ref('list')
const loading = ref(true)
const typesLoading = ref(false)
const visits = ref([])
const visitTypes = ref([])
const classes = ref([])
const pagination = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const statsData = ref(null)
const statsYear = ref(new Date().getFullYear())
const statsYears = computed(() => {
  const y = new Date().getFullYear()
  return [y, y - 1, y - 2]
})
const academicYears = computed(() => referenceStore.academicYears)
const exporting = ref(false)
const printing = ref(false)
const seeding = ref(false)

const filters = ref({
  search: '',
  status: '',
  uks_visit_type_id: '',
  class_id: '',
  academic_year_id: '',
  date_from: '',
  date_to: '',
})

const showFormModal = ref(false)
const editingVisit = ref(null)
const form = ref(emptyForm())
const formSubmitting = ref(false)
const formError = ref('')

const pickerClassId = ref('')
const pickerStudentSearch = ref('')
const pickerStudents = ref([])
const loadingPickerStudents = ref(false)
const pickerStudentError = ref('')
let pickerStudentTimer = null

const showDetailModal = ref(false)
const detailVisit = ref(null)
const showHistoryModal = ref(false)
const historyStudent = ref(null)
const historyVisits = ref([])
const historyLoading = ref(false)

const showTypeModal = ref(false)
const editingType = ref(null)
const typeForm = ref({ name: '', code: '', description: '', is_active: true })
const typeFormSubmitting = ref(false)
const typeFormError = ref('')

const deleteTarget = ref(null)
const deleteTypeTarget = ref(null)

const activeTypes = computed(() => visitTypes.value.filter(t => t.is_active !== false))
const deleteVisitMessage = computed(() => `Yakin menghapus kunjungan UKS untuk ${deleteTarget.value?.student?.name || 'siswa ini'}?`)
const deleteTypeMessage = computed(() => `Yakin menghapus jenis ${deleteTypeTarget.value?.name || ''}? Jika masih dipakai, jenis akan dinonaktifkan.`)
const hasActiveFilters = computed(() => Object.values(filters.value).some(v => v !== '' && v != null))

const monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']

const byMonthChartData = computed(() => {
  const months = statsData.value?.by_month
  if (!months?.length || !months.some(m => m.total > 0)) return null
  return {
    labels: months.map(m => monthLabels[(m.month || 1) - 1]),
    datasets: [{ label: 'Kunjungan', data: months.map(m => m.total), backgroundColor: 'rgba(14, 165, 233, 0.65)' }],
  }
})

const byTypeChartData = computed(() => {
  const rows = statsData.value?.by_type
  if (!rows?.length) return null
  return {
    labels: rows.map(r => r.type_name),
    datasets: [{
      data: rows.map(r => r.total),
      backgroundColor: ['#0ea5e9', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6', '#64748b', '#14b8a6'],
    }],
  }
})

const chartOptionsBar = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
}
const chartOptionsDoughnut = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'bottom' } },
}

function emptyForm() {
  return {
    student_id: '',
    visit_date: new Date().toISOString().slice(0, 10),
    uks_visit_type_id: '',
    status: 'selesai',
    complaint: '',
    action_taken: '',
    notes: '',
    height_cm: '',
    weight_kg: '',
    temperature_c: '',
    blood_pressure: '',
  }
}

function statusLabel(s) {
  return ({ selesai: 'Selesai', observasi: 'Observasi', rujuk: 'Rujuk' })[s] || s
}

function formatDate(val) {
  if (!val) return '-'
  return new Date(val).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

function truncate(str, len) {
  if (!str) return '-'
  return str.length <= len ? str : str.slice(0, len) + '…'
}

function studentOptionLabel(s) {
  const id = s.nis || s.nisn || s.nik || '-'
  const kelas = s.class_name || s.class?.name
  return kelas ? `${s.name} (${id}) · ${kelas}` : `${s.name} (${id})`
}

function switchTab(tab) {
  activeTab.value = tab
  if (tab === 'list') loadVisits()
  else loadTypes()
}

let debounceTimer = null
function debounceLoad() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => loadVisits(), 300)
}

function cleanParams(obj) {
  const params = { ...obj }
  Object.keys(params).forEach(k => {
    if (params[k] === '' || params[k] == null) delete params[k]
  })
  return params
}

async function loadVisits() {
  loading.value = true
  try {
    const params = cleanParams({
      page: pagination.value.current_page,
      per_page: pagination.value.per_page || 15,
      ...filters.value,
    })
    if (!Object.prototype.hasOwnProperty.call(params, 'academic_year_id')) {
      params.academic_year_id = ''
    }
    const res = await uksApi.getAll(params)
    visits.value = res.data.data || []
    const meta = res.data.meta || {}
    pagination.value = {
      current_page: meta.current_page ?? 1,
      last_page: meta.last_page ?? 1,
      per_page: meta.per_page ?? pagination.value.per_page,
      total: meta.total ?? 0,
    }
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || 'Gagal memuat kunjungan UKS.')
  } finally {
    loading.value = false
  }
}

function goToPage(page) {
  pagination.value.current_page = page
  loadVisits()
}

function changePerPage(n) {
  pagination.value.per_page = n
  pagination.value.current_page = 1
  loadVisits()
}

function resetFilters() {
  filters.value = {
    search: '',
    status: '',
    uks_visit_type_id: '',
    class_id: '',
    academic_year_id: '',
    date_from: '',
    date_to: '',
  }
  pagination.value.current_page = 1
  loadVisits()
}

async function loadTypes() {
  typesLoading.value = true
  try {
    const res = await uksVisitTypeApi.getAll({ active_only: false })
    visitTypes.value = res.data.data || []
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || 'Gagal memuat jenis kunjungan.')
  } finally {
    typesLoading.value = false
  }
}

async function loadStats() {
  try {
    const res = await uksApi.getStats({ year: statsYear.value })
    statsData.value = res.data.data || null
  } catch {
    statsData.value = null
  }
}

async function loadClasses() {
  try {
    const res = await uksApi.classesLite()
    classes.value = res.data.data || []
  } catch {
    classes.value = []
  }
}

async function loadPickerStudents() {
  const q = pickerStudentSearch.value.trim()
  if (!pickerClassId.value && !q) {
    pickerStudents.value = []
    pickerStudentError.value = ''
    return
  }
  loadingPickerStudents.value = true
  pickerStudentError.value = ''
  try {
    const params = {}
    if (pickerClassId.value) params.class_id = pickerClassId.value
    if (q) params.q = q
    const res = await uksApi.studentsLite(params)
    pickerStudents.value = res.data.data || []
    if (!pickerStudents.value.length) {
      pickerStudentError.value = q
        ? 'Tidak ada siswa cocok. Coba kata kunci lain.'
        : 'Tidak ada siswa aktif di kelas ini.'
    }
  } catch (e) {
    pickerStudents.value = []
    pickerStudentError.value = e.formattedMessage || 'Gagal memuat data siswa.'
  } finally {
    loadingPickerStudents.value = false
  }
}

function debouncePickerStudentSearch() {
  clearTimeout(pickerStudentTimer)
  pickerStudentTimer = setTimeout(() => loadPickerStudents(), 300)
}

function onPickerClassChange() {
  form.value.student_id = ''
  pickerStudentSearch.value = ''
  loadPickerStudents()
}

function resetStudentPicker() {
  pickerClassId.value = ''
  pickerStudentSearch.value = ''
  pickerStudents.value = []
  pickerStudentError.value = ''
  clearTimeout(pickerStudentTimer)
}

function openAddModal() {
  editingVisit.value = null
  form.value = emptyForm()
  formError.value = ''
  resetStudentPicker()
  showFormModal.value = true
}

function openEditModal(v) {
  editingVisit.value = v
  form.value = {
    student_id: v.student_id ? String(v.student_id) : '',
    visit_date: v.visit_date,
    uks_visit_type_id: v.uks_visit_type_id || '',
    status: v.status || 'selesai',
    complaint: v.complaint || '',
    action_taken: v.action_taken || '',
    notes: v.notes || '',
    height_cm: v.height_cm ?? '',
    weight_kg: v.weight_kg ?? '',
    temperature_c: v.temperature_c ?? '',
    blood_pressure: v.blood_pressure || '',
  }
  formError.value = ''
  showFormModal.value = true
}

function openDetailModal(v) {
  detailVisit.value = v
  showDetailModal.value = true
}

async function openHistoryFromDetail() {
  const student = detailVisit.value?.student
  if (!student) return
  showDetailModal.value = false
  historyStudent.value = student
  showHistoryModal.value = true
  historyLoading.value = true
  historyVisits.value = []
  try {
    const res = await uksApi.getByStudent(student.id)
    historyVisits.value = res.data.data || []
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || 'Gagal memuat riwayat.')
  } finally {
    historyLoading.value = false
  }
}

async function submitForm() {
  if (!form.value.student_id) {
    formError.value = 'Pilih siswa terlebih dahulu.'
    return
  }
  formSubmitting.value = true
  formError.value = ''
  try {
    const payload = cleanParams({ ...form.value })
    if (editingVisit.value) {
      await uksApi.update(editingVisit.value.id, payload)
      toast.success('Berhasil', 'Kunjungan UKS diperbarui.')
    } else {
      await uksApi.create(payload)
      toast.success('Berhasil', 'Kunjungan UKS dicatat.')
    }
    showFormModal.value = false
    pagination.value.current_page = 1
    await Promise.all([loadVisits(), loadStats()])
  } catch (e) {
    formError.value = e.formattedMessage || e.response?.data?.message || 'Gagal menyimpan kunjungan UKS.'
  } finally {
    formSubmitting.value = false
  }
}

async function confirmDeleteVisit() {
  if (!deleteTarget.value) return
  try {
    await uksApi.delete(deleteTarget.value.id)
    toast.success('Berhasil', 'Kunjungan dihapus.')
    deleteTarget.value = null
    await Promise.all([loadVisits(), loadStats()])
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || 'Gagal menghapus.')
  }
}

function openAddTypeModal() {
  editingType.value = null
  typeForm.value = { name: '', code: '', description: '', is_active: true }
  typeFormError.value = ''
  showTypeModal.value = true
}

function openEditTypeModal(t) {
  editingType.value = t
  typeForm.value = {
    name: t.name,
    code: t.code || '',
    description: t.description || '',
    is_active: t.is_active !== false,
  }
  typeFormError.value = ''
  showTypeModal.value = true
}

async function submitTypeForm() {
  typeFormSubmitting.value = true
  typeFormError.value = ''
  try {
    if (editingType.value) {
      await uksVisitTypeApi.update(editingType.value.id, typeForm.value)
      toast.success('Berhasil', 'Jenis kunjungan diperbarui.')
    } else {
      await uksVisitTypeApi.create(typeForm.value)
      toast.success('Berhasil', 'Jenis kunjungan ditambahkan.')
    }
    showTypeModal.value = false
    await loadTypes()
  } catch (e) {
    typeFormError.value = e.formattedMessage || 'Gagal menyimpan.'
  } finally {
    typeFormSubmitting.value = false
  }
}

async function confirmDeleteType() {
  if (!deleteTypeTarget.value) return
  try {
    await uksVisitTypeApi.delete(deleteTypeTarget.value.id)
    toast.success('Berhasil', 'Jenis kunjungan diproses.')
    deleteTypeTarget.value = null
    await loadTypes()
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || 'Gagal menghapus.')
  }
}

async function seedDefaults() {
  seeding.value = true
  try {
    const res = await uksVisitTypeApi.seedDefaults()
    visitTypes.value = res.data.data || []
    toast.success('Berhasil', 'Jenis standar diisi.')
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || 'Gagal mengisi jenis standar.')
  } finally {
    seeding.value = false
  }
}

async function printPdf() {
  printing.value = true
  try {
    const res = await uksReportApi.exportPdf({
      ...cleanParams({ ...filters.value }),
      mode: 'detail',
    })
    const contentType = res.headers?.['content-type'] || ''
    if (contentType.includes('application/json')) {
      const text = typeof res.data?.text === 'function' ? await res.data.text() : String(res.data)
      const json = (() => { try { return JSON.parse(text) } catch { return {} } })()
      throw new Error(json.message || 'Gagal mencetak laporan UKS.')
    }
    const blob = res.data instanceof Blob
      ? res.data
      : new Blob([res.data], { type: 'application/pdf' })
    const url = URL.createObjectURL(new Blob([blob], { type: 'application/pdf' }))
    const win = window.open('', '_blank')
    if (!win) {
      toast.error('Gagal', 'Pop-up diblokir. Izinkan tab baru untuk melihat preview PDF.')
      URL.revokeObjectURL(url)
      return
    }
    win.document.write(`<!DOCTYPE html><html><head><title>Laporan UKS — Detail Kunjungan</title>
      <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, sans-serif; background: #0f172a; }
        .toolbar {
          display: flex; align-items: center; justify-content: space-between; gap: 12px;
          padding: 10px 14px; background: #0f172a; color: #f8fafc;
          border-bottom: 1px solid #1e293b; position: sticky; top: 0; z-index: 2;
        }
        .toolbar h1 { margin: 0; font-size: 14px; font-weight: 600; }
        .actions button {
          border: none; border-radius: 8px; padding: 8px 14px; font-weight: 600;
          cursor: pointer; font-size: 13px;
        }
        .btn-print { background: #059669; color: #fff; }
        .btn-close { background: #334155; color: #e2e8f0; }
        iframe { width: 100%; height: calc(100vh - 52px); border: 0; background: #525659; }
      </style></head><body>
      <div class="toolbar">
        <h1>Laporan UKS — Detail Kunjungan</h1>
        <div class="actions">
          <button class="btn-print" type="button" onclick="document.getElementById('pdfFrame').contentWindow.focus(); document.getElementById('pdfFrame').contentWindow.print();">Cetak</button>
          <button class="btn-close" type="button" onclick="window.close()">Tutup</button>
        </div>
      </div>
      <iframe id="pdfFrame" src="${url}" title="Preview PDF"></iframe>
    </body></html>`)
    win.document.close()
    setTimeout(() => URL.revokeObjectURL(url), 120000)
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || e.message || 'Gagal mencetak laporan UKS.')
  } finally {
    printing.value = false
  }
}

async function exportToCsv() {
  exporting.value = true
  try {
    const res = await uksApi.export(cleanParams({ ...filters.value }))
    const blob = new Blob([res.data], { type: 'text/csv;charset=utf-8' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `laporan-uks-kunjungan-${new Date().toISOString().slice(0, 10)}.csv`
    a.click()
    URL.revokeObjectURL(url)
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || 'Gagal mengekspor CSV.')
  } finally {
    exporting.value = false
  }
}

onMounted(async () => {
  try { await referenceStore.fetchAcademicYears() } catch { /* ignore */ }
  const activeAy = authStore.user?.institution?.active_academic_year_id
  if (activeAy) filters.value.academic_year_id = activeAy

  await Promise.all([loadTypes(), loadClasses(), loadStats(), loadVisits()])
})
</script>

<style scoped>
.uks-page { display: flex; flex-direction: column; gap: 16px; }
.toolbar { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
.settings-hint {
  margin: 0;
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
  color: #475569;
  background: #f8fafc;
  border-radius: 10px;
  border-left: 4px solid #059669;
  line-height: 1.45;
}
.main-tabs { display: flex; gap: 4px; background: #f1f5f9; padding: 4px; border-radius: 10px; }
.main-tab {
  border: none;
  background: transparent;
  padding: 8px 14px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 13px;
  color: #64748b;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}
.main-tab.active { background: #fff; color: #0f172a; box-shadow: 0 1px 2px rgba(0,0,0,.08); }
.uks-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
.stat-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; display: flex; flex-direction: column; gap: 4px; }
.stat-label { font-size: 12px; color: #64748b; }
.stat-value { font-size: 24px; font-weight: 700; color: #0f172a; }
.stat-observasi .stat-value { color: #b45309; }
.stat-rujuk .stat-value { color: #b91c1c; }
.chart-details { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 8px 14px 12px; }
.chart-details summary { cursor: pointer; font-size: 13px; font-weight: 600; color: #334155; padding: 6px 0; }
.uks-charts { display: grid; grid-template-columns: 1.4fr 1fr; gap: 12px; margin-top: 8px; }
.chart-box { min-height: 160px; }
.chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
.chart-header h4 { margin: 0; font-size: 13px; color: #334155; }
.chart-year-select { font-size: 12px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 2px 6px; }
.chart-wrap { height: 140px; }
.chart-wrap-pie { height: 150px; }
.chart-empty { margin: 24px 0; text-align: center; color: #94a3b8; font-size: 13px; }
.filters-inline { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
.search-input, .filter-select { border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 10px; font-size: 13px; background: #fff; }
.search-input { min-width: 200px; flex: 1; }
.table-container { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.data-table th, .data-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; text-align: left; vertical-align: top; }
.data-table th { background: #f8fafc; color: #475569; font-weight: 600; }
.student-name { display: block; font-weight: 600; color: #0f172a; }
.student-meta { display: block; font-size: 11px; color: #94a3b8; margin-top: 2px; }
.summary-cell { max-width: 240px; color: #475569; }
.status-badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; }
.status-selesai { background: #dcfce7; color: #166534; }
.status-observasi { background: #fef3c7; color: #92400e; }
.status-rujuk { background: #fee2e2; color: #991b1b; }
.action-buttons { display: flex; gap: 4px; }
.empty-state { text-align: center; padding: 48px 20px; background: #fff; border-radius: 12px; border: 1px dashed #cbd5e1; }
.empty-title { margin: 0 0 8px; }
.empty-desc { color: #64748b; margin: 0 0 16px; }
.pagination-bar { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
.btn-page { border: 1px solid #cbd5e1; background: #fff; border-radius: 8px; padding: 6px 12px; cursor: pointer; font-size: 12px; }
.btn-page:disabled { opacity: .5; cursor: not-allowed; }
.types-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 12px; }
.type-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; display: flex; flex-direction: column; gap: 8px; }
.type-header { display: flex; justify-content: space-between; align-items: center; gap: 8px; }
.type-name { font-weight: 700; color: #0f172a; }
.type-code { font-size: 11px; color: #64748b; background: #f1f5f9; padding: 2px 8px; border-radius: 999px; }
.type-desc { margin: 0; font-size: 13px; color: #64748b; line-height: 1.4; }
.type-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 4px; }
.modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, .45); display: flex; align-items: center; justify-content: center; z-index: 80; padding: 16px; }
.modal-card { background: #fff; border-radius: 14px; padding: 20px; width: min(720px, 100%); max-height: 90vh; overflow: auto; }
.modal-card-sm { width: min(520px, 100%); }
.modal-header { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 12px; }
.modal-header h3 { margin: 0; }
.btn-close { border: none; background: #f1f5f9; width: 32px; height: 32px; border-radius: 8px; font-size: 20px; cursor: pointer; color: #475569; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.form-grid label, .field-label { display: flex; flex-direction: column; gap: 4px; font-size: 12px; color: #475569; font-weight: 600; }
.form-grid input, .form-grid select, .form-grid textarea { font-weight: 400; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 10px; font-size: 13px; }
.form-grid .full { grid-column: 1 / -1; }
.vital-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
.modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 4px; }
.form-error { color: #b91c1c; font-size: 13px; margin: 0 0 8px; }
.student-picker {
  padding: 0.85rem 1rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
}
.picker-row { display: grid; grid-template-columns: 1fr 1.4fr; gap: 12px; }
.field-hint { margin: 0.4rem 0 0; font-size: 12px; color: #64748b; font-weight: 400; }
.student-listbox { margin-top: 0.5rem; min-height: 150px; width: 100%; }
.student-picker-locked {
  margin: 0;
  padding: 0.75rem 1rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
}
.student-picker-locked p { margin: 4px 0 0; font-weight: 600; color: #0f172a; }
.detail-student { margin: 0; font-size: 16px; font-weight: 700; }
.detail-dl { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 16px; margin: 16px 0; }
.detail-dl .full { grid-column: 1 / -1; }
.detail-dl dt { font-size: 11px; color: #64748b; font-weight: 600; margin-bottom: 2px; }
.detail-dl dd { margin: 0; font-size: 13px; color: #0f172a; }
.history-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 10px; }
.history-list li { display: grid; grid-template-columns: 110px 1fr auto; gap: 8px 12px; padding: 10px 12px; background: #f8fafc; border-radius: 10px; }
.history-date { font-weight: 600; font-size: 13px; }
.history-type { font-size: 13px; color: #334155; }
.history-note { grid-column: 1 / -1; font-size: 12px; color: #64748b; }
@media (max-width: 1100px) {
  .uks-stats { grid-template-columns: 1fr 1fr; }
  .uks-charts, .vital-grid, .picker-row { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 700px) {
  .uks-stats, .uks-charts, .form-grid, .vital-grid, .picker-row, .detail-dl, .history-list li { grid-template-columns: 1fr; }
}
</style>
