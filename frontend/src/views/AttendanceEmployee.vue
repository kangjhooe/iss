<template>
  <Layout>
    <div class="attendance-employee-page">
      <div class="page-header">
        <div class="header-content">
          <div class="header-icon-wrap">
            <svg class="header-icon" width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M9 5C9 3.89543 9.89543 3 11 3H13C14.1046 3 15 3.89543 15 5C15 6.10457 14.1046 7 13 7H11C9.89543 7 9 6.10457 9 5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M9 12L11 14L15 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div>
            <h1 class="page-title">Absensi Guru & Staff</h1>
            <p class="page-subtitle">Kehadiran pegawai per hari</p>
          </div>
          <div class="header-actions">
            <button type="button" class="btn-secondary btn-compact" :disabled="exporting" @click="exportRekap('csv')">
              {{ exporting === 'csv' ? 'Mengekspor...' : 'Export CSV' }}
            </button>
            <button type="button" class="btn-secondary btn-compact" :disabled="exporting" @click="exportRekap('pdf')">
              {{ exporting === 'pdf' ? 'Mengekspor...' : 'Cetak Rekap PDF' }}
            </button>
            <button @click="openBulkModal" class="btn-primary btn-compact">Input Absensi per Tanggal</button>
            <button @click="openAddModal" class="btn-secondary btn-compact">Tambah Satu</button>
          </div>
        </div>
      </div>

      <div class="filters filters-inline">
        <input v-model="filters.date_from" type="date" class="filter-select" @change="onFilterChange" />
        <input v-model="filters.date_to" type="date" class="filter-select" @change="onFilterChange" />
        <select v-model="filters.employee_id" @change="onFilterChange" class="filter-select">
          <option value="">Semua Pegawai</option>
          <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }} ({{ e.type }})</option>
        </select>
        <select v-model="filters.status" @change="onFilterChange" class="filter-select">
          <option value="">Semua Status</option>
          <option v-for="(label, val) in statusOptions" :key="val" :value="val">{{ label }}</option>
        </select>
      </div>

      <div class="section-tabs" role="tablist">
        <button type="button" role="tab" :class="['sec-btn', { active: activeTab === 'isi' }]" @click="activeTab = 'isi'">Daftar Absensi</button>
        <button type="button" role="tab" :class="['sec-btn', { active: activeTab === 'rekap' }]" @click="switchToRekap">Rekap & Laporan</button>
      </div>

      <template v-if="activeTab === 'isi'">
      <div v-if="loading" class="loading-wrap">
        <LoadingSkeleton type="table" :rows="8" :columns="7" />
      </div>

      <div v-else-if="attendances.length === 0" class="empty-state">
        <h3 class="empty-title">Belum ada data absensi</h3>
        <p class="empty-desc">Gunakan "Input Absensi per Tanggal" atau "Tambah Satu" untuk mencatat kehadiran pegawai.</p>
      </div>

      <div v-else class="table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>Nama</th>
              <th>Tipe</th>
              <th>Status</th>
              <th>Masuk</th>
              <th>Keluar</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="a in attendances" :key="a.id">
              <td>{{ formatDate(a.date) }}</td>
              <td>{{ a.employee?.name }}</td>
              <td>{{ a.employee?.type }}</td>
              <td><span class="status-badge" :class="a.status">{{ statusOptions[a.status] || a.status }}</span></td>
              <td>{{ a.check_in_time || '-' }}</td>
              <td>{{ a.check_out_time || '-' }}</td>
              <td>
                <TableAction kind="edit" @click="openEditModal(a)" />
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="pagination.last_page > 1" class="pagination-bar">
          <span class="pagination-info">
            Menampilkan {{ (pagination.current_page - 1) * pagination.per_page + 1 }}-{{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} dari {{ pagination.total }}
          </span>
          <div class="pagination-buttons">
            <button type="button" class="btn-page" :disabled="pagination.current_page <= 1" @click="goToPage(pagination.current_page - 1)">Sebelumnya</button>
            <span class="page-num">Halaman {{ pagination.current_page }} / {{ pagination.last_page }}</span>
            <button type="button" class="btn-page" :disabled="pagination.current_page >= pagination.last_page" @click="goToPage(pagination.current_page + 1)">Selanjutnya</button>
          </div>
        </div>
      </div>
      </template>

      <template v-else>
        <div v-if="rekapLoading" class="loading-wrap">
          <LoadingSkeleton type="table" :rows="8" :columns="8" />
        </div>
        <div v-else>
          <div v-if="rekapMeta" class="rekap-stats">
            <span>Pegawai: <strong>{{ rekapMeta.employee_count ?? rekapRows.length }}</strong></span>
            <span>Tercatat: <strong>{{ rekapTotals.tercatat ?? 0 }}</strong></span>
            <span>Hadir: <strong>{{ rekapTotals.hadir ?? 0 }}</strong></span>
            <span>Alpha: <strong>{{ rekapTotals.alpha ?? 0 }}</strong></span>
            <span>Izin: <strong>{{ rekapTotals.izin ?? 0 }}</strong></span>
            <span>Sakit: <strong>{{ rekapTotals.sakit ?? 0 }}</strong></span>
            <span>% Hadir: <strong>{{ rekapTotals.persentase_hadir ?? 0 }}%</strong></span>
          </div>
          <div v-if="rekapRows.length" class="charts-grid">
            <AppChart title="Komposisi kehadiran" type="doughnut" :chart-data="attendanceShareChart" />
            <AppChart title="Persentase hadir terendah" subtitle="Maks. 12 pegawai" type="bar" :chart-data="attendanceRateChart" />
          </div>
          <div v-if="rekapRows.length === 0" class="empty-state">
            <h3 class="empty-title">Belum ada data rekap</h3>
            <p class="empty-desc">Sesuaikan filter tanggal/pegawai, lalu pastikan absensi sudah diisi.</p>
          </div>
          <div v-else class="table-container">
            <table class="data-table">
              <thead>
                <tr>
                  <th>No</th>
                  <th>NIP</th>
                  <th>Nama</th>
                  <th>Tipe</th>
                  <th>Hadir</th>
                  <th>Alpha</th>
                  <th>Izin</th>
                  <th>Sakit</th>
                  <th>Cuti</th>
                  <th>Dinas Luar</th>
                  <th>WFH</th>
                  <th>Tercatat</th>
                  <th>% Hadir</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, idx) in rekapRows" :key="row.employee_id">
                  <td>{{ idx + 1 }}</td>
                  <td>{{ row.nip || '-' }}</td>
                  <td>{{ row.name }}</td>
                  <td>{{ row.type || '-' }}</td>
                  <td>{{ row.counts?.hadir ?? 0 }}</td>
                  <td>{{ row.counts?.alpha ?? 0 }}</td>
                  <td>{{ row.counts?.izin ?? 0 }}</td>
                  <td>{{ row.counts?.sakit ?? 0 }}</td>
                  <td>{{ row.counts?.cuti ?? 0 }}</td>
                  <td>{{ row.counts?.dinas_luar ?? 0 }}</td>
                  <td>{{ row.counts?.wfh ?? 0 }}</td>
                  <td>{{ row.tercatat ?? 0 }}</td>
                  <td>{{ row.persentase_hadir ?? 0 }}%</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>

      <!-- Modal: Tambah/Edit satu absensi -->
      <div v-if="showFormModal" class="modal-overlay" @click="showFormModal = false">
        <div class="modal-content form-modal" @click.stop>
          <div class="modal-header">
            <h3>{{ editingAttendance ? 'Edit Absensi' : 'Tambah Absensi Pegawai' }}</h3>
            <button @click="showFormModal = false" class="btn-close">×</button>
          </div>
          <form @submit.prevent="submitForm" class="modal-body">
            <div v-if="offlineIndicator" class="offline-indicator">
              <span>📴 Mode Offline</span>
            </div>
            <div class="form-group">
              <label>Pegawai *</label>
              <select v-model="form.employee_id" required class="form-select" :disabled="!!editingAttendance">
                <option value="">Pilih pegawai</option>
                <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }} ({{ e.type }})</option>
              </select>
            </div>
            <div class="form-group">
              <label>Tanggal *</label>
              <input v-model="form.date" type="date" required class="form-input" :disabled="!!editingAttendance" />
            </div>
            <div class="form-group">
              <label>Status *</label>
              <select v-model="form.status" required class="form-select">
                <option v-for="(label, val) in statusOptions" :key="val" :value="val">{{ label }}</option>
              </select>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Jam Masuk</label>
                <input v-model="form.check_in_time" type="time" class="form-input" />
              </div>
              <div class="form-group">
                <label>Jam Keluar</label>
                <input v-model="form.check_out_time" type="time" class="form-input" />
              </div>
            </div>
            <div class="form-group">
              <label>Keterangan</label>
              <textarea v-model="form.notes" class="form-input" rows="2" placeholder="Opsional"></textarea>
            </div>
            <p v-if="formError" class="form-error">{{ formError }}</p>
            <div class="modal-actions">
              <button type="button" @click="showFormModal = false" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="formSaving" class="btn-primary">{{ formSaving ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal: Bulk input per tanggal -->
      <div v-if="showBulkModal" class="modal-overlay" @click="showBulkModal = false">
        <div class="modal-content form-modal modal-wide" @click.stop>
          <div class="modal-header">
            <h3>Input Absensi per Tanggal</h3>
            <button @click="showBulkModal = false" class="btn-close">×</button>
          </div>
          <form @submit.prevent="submitBulk" class="modal-body">
            <div v-if="offlineIndicator" class="offline-indicator">
              <span>📴 Mode Offline</span>
            </div>
            <div class="form-group">
              <label>Tanggal *</label>
              <input v-model="bulkDate" type="date" required class="form-input" />
            </div>
            <div class="bulk-actions-inline">
              <button type="button" @click="fillStandardTimes" class="btn-outline btn-compact">Isi jam standar (07:00–15:00)</button>
              <button type="button" @click="markBulkPageHadir" class="btn-outline btn-compact" :disabled="!pagedBulkEmployees.length">Tandai halaman ini Hadir</button>
            </div>
            <p class="form-hint">Pilih pegawai dan status kehadiran. Default hadir dengan jam 07:00–15:00. Kosongkan status jika tidak perlu diisi. Simpan mencakup semua pegawai, bukan hanya halaman ini.</p>
            <div class="roster-toolbar">
              <input v-model="bulkSearch" type="search" class="form-input" placeholder="Cari nama / NIP..." />
              <select v-model="bulkType" class="form-select">
                <option value="">Semua tipe</option>
                <option v-for="t in bulkTypeOptions" :key="t" :value="t">{{ t }}</option>
              </select>
            </div>
            <p class="roster-meta">{{ bulkRangeLabel }} · urut tipe, lalu abjad</p>
            <div v-if="pagedBulkEmployees.length" class="table-scroll">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Tipe</th>
                    <th>Status</th>
                    <th>Masuk</th>
                    <th>Keluar</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(e, i) in pagedBulkEmployees" :key="e.id">
                    <td>{{ bulkStartIndex + i + 1 }}</td>
                    <td>{{ e.name }}</td>
                    <td>{{ e.type }}</td>
                    <td>
                      <select v-if="bulkRows[e.id]" v-model="bulkRows[e.id].status" class="form-select status-select">
                        <option value="">—</option>
                        <option v-for="(label, val) in statusOptions" :key="val" :value="val">{{ label }}</option>
                      </select>
                    </td>
                    <td><input v-if="bulkRows[e.id]" v-model="bulkRows[e.id].check_in_time" type="time" class="form-input time-input" /></td>
                    <td><input v-if="bulkRows[e.id]" v-model="bulkRows[e.id].check_out_time" type="time" class="form-input time-input" /></td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p v-else class="form-hint">Tidak ada pegawai yang cocok.</p>
            <div v-if="bulkLastPage > 1" class="pagination-bar">
              <div class="pagination-buttons">
                <button type="button" class="btn-page" :disabled="bulkPage <= 1" @click="bulkPage--">Sebelumnya</button>
                <span class="page-num">Halaman {{ bulkPage }} / {{ bulkLastPage }}</span>
                <button type="button" class="btn-page" :disabled="bulkPage >= bulkLastPage" @click="bulkPage++">Selanjutnya</button>
              </div>
            </div>
            <p v-if="bulkError" class="form-error">{{ bulkError }}</p>
            <div class="modal-actions">
              <button type="button" @click="showBulkModal = false" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="bulkSaving" class="btn-primary">{{ bulkSaving ? 'Menyimpan...' : 'Simpan Semua' }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import Layout from '@/components/Layout.vue'
import TableAction from '@/components/TableAction.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import AppChart from '@/components/AppChart.vue'
import { useToast } from '@/composables/useToast'
import { doughnutFromCounts, barFromSeries } from '@/composables/useChart'
import { employeeAttendanceApi } from '@/api/attendance'
import { employeeApi } from '@/api/teacher'
import { employeeAttendanceStorage, isOnline, onNetworkStatusChange } from '@/utils/offlineStorage'
import { useOfflineSync } from '@/composables/useOfflineSync'

const toast = useToast()
const { syncPendingItems } = useOfflineSync()

const isOffline = ref(!isOnline())
const offlineIndicator = ref(false)
const activeTab = ref('isi')
const exporting = ref('')

onNetworkStatusChange((online) => {
  isOffline.value = !online
  if (online) {
    syncPendingItems()
  }
})

const statusOptions = ref({})
const employees = ref([])
const attendances = ref([])
const loading = ref(false)
const pagination = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })

const filters = ref({
  date_from: '',
  date_to: '',
  employee_id: '',
  status: '',
})

const rekapRows = ref([])
const rekapTotals = ref({})
const rekapMeta = ref(null)
const rekapLoading = ref(false)
const rekapLoaded = ref(false)

const attendanceShareChart = computed(() => doughnutFromCounts(rekapTotals.value))
const attendanceRateChart = computed(() => {
  const rows = [...(rekapRows.value || [])]
    .sort((a, b) => (a.persentase_hadir ?? 0) - (b.persentase_hadir ?? 0))
    .slice(0, 12)
  if (!rows.length) return null
  return barFromSeries(rows.map((r) => r.name), rows.map((r) => r.persentase_hadir ?? 0), '% Hadir', '#0d9488')
})

const showFormModal = ref(false)
const showBulkModal = ref(false)
const editingAttendance = ref(null)
const form = reactive({
  employee_id: '',
  date: '',
  status: 'hadir',
  check_in_time: '',
  check_out_time: '',
  notes: '',
})
const formError = ref('')
const formSaving = ref(false)

const bulkDate = ref('')
const bulkRows = ref({})
const bulkError = ref('')
const bulkSaving = ref(false)
const BULK_PAGE_SIZE = 20
const bulkSearch = ref('')
const bulkType = ref('')
const bulkPage = ref(1)

const sortedEmployees = computed(() =>
  [...employees.value].sort((a, b) => {
    const typeCmp = String(a.type || '').localeCompare(String(b.type || ''), 'id')
    if (typeCmp !== 0) return typeCmp
    return String(a.name || '').localeCompare(String(b.name || ''), 'id')
  })
)
const bulkTypeOptions = computed(() =>
  [...new Set(employees.value.map((e) => e.type).filter(Boolean))].sort((a, b) => a.localeCompare(b, 'id'))
)
const filteredBulkEmployees = computed(() => {
  const q = bulkSearch.value.trim().toLowerCase()
  const type = bulkType.value
  return sortedEmployees.value.filter((e) => {
    if (type && e.type !== type) return false
    if (!q) return true
    return `${e.name || ''} ${e.nip || ''} ${e.nuptk || ''}`.toLowerCase().includes(q)
  })
})
const bulkLastPage = computed(() => Math.max(1, Math.ceil(filteredBulkEmployees.value.length / BULK_PAGE_SIZE)))
const pagedBulkEmployees = computed(() => {
  const start = (bulkPage.value - 1) * BULK_PAGE_SIZE
  return filteredBulkEmployees.value.slice(start, start + BULK_PAGE_SIZE)
})
const bulkStartIndex = computed(() => (bulkPage.value - 1) * BULK_PAGE_SIZE)
const bulkRangeLabel = computed(() => {
  const total = filteredBulkEmployees.value.length
  if (!total) return '0 dari 0 pegawai'
  const start = bulkStartIndex.value + 1
  const end = Math.min(bulkPage.value * BULK_PAGE_SIZE, total)
  return `Menampilkan ${start}–${end} dari ${total} pegawai`
})
watch([bulkSearch, bulkType], () => { bulkPage.value = 1 })
watch(bulkLastPage, (last) => {
  if (bulkPage.value > last) bulkPage.value = last
})

function formatDate(d) {
  if (!d) return '-'
  const date = typeof d === 'string' ? new Date(d) : d
  return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

function cleanParams(extra = {}) {
  const params = { ...filters.value, ...extra }
  Object.keys(params).forEach((key) => {
    if (params[key] === '' || params[key] === null || params[key] === undefined) {
      delete params[key]
    }
  })
  return params
}

async function loadStatusOptions() {
  try {
    const res = await employeeAttendanceApi.getStatusOptions()
    statusOptions.value = res.data.data || {}
  } catch {
    statusOptions.value = { hadir: 'Hadir', alpha: 'Alpha', izin: 'Izin', sakit: 'Sakit', cuti: 'Cuti', dinas_luar: 'Dinas Luar', wfh: 'WFH' }
  }
}

async function loadEmployees() {
  try {
    const res = await employeeApi.getAll({ per_page: 500 })
    employees.value = res.data.data || []
  } catch {
    employees.value = []
  }
}

async function loadAttendances() {
  loading.value = true
  try {
    const params = {
      page: pagination.value.current_page,
      per_page: 15,
      ...cleanParams(),
    }
    const res = await employeeAttendanceApi.getAll(params)
    attendances.value = res.data.data || []
    const meta = res.data.meta || {}
    pagination.value = {
      current_page: meta.current_page ?? 1,
      last_page: meta.last_page ?? 1,
      per_page: meta.per_page ?? 15,
      total: meta.total ?? 0,
    }
  } catch (e) {
    toast.error('Gagal memuat absensi', e.formattedMessage || 'Data absensi pegawai tidak dapat dimuat. Periksa koneksi dan coba lagi.')
  } finally {
    loading.value = false
  }
}

async function loadRekap() {
  rekapLoading.value = true
  try {
    const res = await employeeAttendanceApi.getRekap(cleanParams())
    rekapRows.value = res.data.data || []
    rekapTotals.value = res.data.totals || {}
    rekapMeta.value = res.data.meta || null
    rekapLoaded.value = true
  } catch (e) {
    toast.error('Gagal memuat rekap', e.formattedMessage || 'Rekap absensi tidak dapat dimuat.')
    rekapRows.value = []
    rekapTotals.value = {}
    rekapMeta.value = null
  } finally {
    rekapLoading.value = false
  }
}

function onFilterChange() {
  pagination.value.current_page = 1
  loadAttendances()
  if (activeTab.value === 'rekap' || rekapLoaded.value) {
    loadRekap()
  }
}

function switchToRekap() {
  activeTab.value = 'rekap'
  if (!rekapLoaded.value) {
    loadRekap()
  }
}

function goToPage(page) {
  pagination.value.current_page = page
  loadAttendances()
}

async function exportRekap(format) {
  exporting.value = format
  try {
    const res = await employeeAttendanceApi.exportRekap(cleanParams({ format }))
    const mime = format === 'csv' ? 'text/csv;charset=utf-8' : 'application/pdf'
    const blob = new Blob([res.data], { type: mime })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `Rekap_Absensi_Pegawai_${new Date().toISOString().slice(0, 10)}.${format}`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
    toast.success('Berhasil', format === 'pdf' ? 'Rekap PDF berhasil diunduh' : 'Rekap CSV berhasil diunduh')
  } catch (e) {
    toast.error('Gagal mengekspor', e.formattedMessage || 'Rekap tidak dapat diekspor. Periksa koneksi dan coba lagi.')
  } finally {
    exporting.value = ''
  }
}

function openAddModal() {
  editingAttendance.value = null
  form.employee_id = ''
  form.date = new Date().toISOString().slice(0, 10)
  form.status = 'hadir'
  form.check_in_time = ''
  form.check_out_time = ''
  form.notes = ''
  formError.value = ''
  showFormModal.value = true
}

function openEditModal(a) {
  editingAttendance.value = a
  form.employee_id = a.employee_id
  form.date = a.date
  form.status = a.status || 'hadir'
  form.check_in_time = a.check_in_time || ''
  form.check_out_time = a.check_out_time || ''
  form.notes = a.notes || ''
  formError.value = ''
  showFormModal.value = true
}

async function submitForm() {
  formSaving.value = true
  formError.value = ''
  try {
    const checkIn = form.check_in_time || null
    const checkOut = form.check_out_time || null
    if (checkIn && checkOut && checkOut < checkIn) {
      formError.value = 'Jam keluar harus setelah atau sama dengan jam masuk.'
      formSaving.value = false
      return
    }
    const payload = {
      employee_id: form.employee_id,
      date: form.date,
      status: form.status,
      check_in_time: checkIn,
      check_out_time: checkOut,
      notes: form.notes || null,
    }

    if (isOffline.value) {
      // Save to offline storage (as bulk for single employee)
      await employeeAttendanceStorage.save(form.date, [payload])
      toast.success('Absensi disimpan secara offline. Akan disinkronkan saat online.')
      showFormModal.value = false
      loadAttendances()
    } else {
      try {
        if (editingAttendance.value) {
          await employeeAttendanceApi.update(editingAttendance.value.id, payload)
          toast.success('Absensi berhasil diperbarui')
        } else {
          await employeeAttendanceApi.create(payload)
          toast.success('Absensi berhasil dicatat')
        }
        showFormModal.value = false
        loadAttendances()
        rekapLoaded.value = false
      } catch (e) {
        // If online save fails, save offline
        await employeeAttendanceStorage.save(form.date, [payload])
        toast.success('Absensi disimpan secara offline. Akan disinkronkan saat online.')
        showFormModal.value = false
        loadAttendances()
        rekapLoaded.value = false
      }
    }
  } catch (e) {
    formError.value = e.response?.data?.message || e.formattedMessage || 'Gagal menyimpan'
  } finally {
    formSaving.value = false
  }
}

const DEFAULT_CHECK_IN = '07:00'
const DEFAULT_CHECK_OUT = '15:00'

function openBulkModal() {
  bulkDate.value = new Date().toISOString().slice(0, 10)
  bulkError.value = ''
  bulkSearch.value = ''
  bulkType.value = ''
  bulkPage.value = 1
  bulkRows.value = {}
  employees.value.forEach((e) => {
    bulkRows.value[e.id] = {
      employee_id: e.id,
      status: 'hadir',
      check_in_time: DEFAULT_CHECK_IN,
      check_out_time: DEFAULT_CHECK_OUT,
      notes: '',
    }
  })
  showBulkModal.value = true
}

function markBulkPageHadir() {
  pagedBulkEmployees.value.forEach((e) => {
    if (bulkRows.value[e.id]) bulkRows.value[e.id].status = 'hadir'
  })
}

function fillStandardTimes() {
  Object.keys(bulkRows.value).forEach((id) => {
    bulkRows.value[id].check_in_time = DEFAULT_CHECK_IN
    bulkRows.value[id].check_out_time = DEFAULT_CHECK_OUT
  })
  toast.success('Jam standar 07:00–15:00 diisi untuk semua pegawai')
}

async function submitBulk() {
  bulkSaving.value = true
  bulkError.value = ''
  try {
    const attendancesList = Object.values(bulkRows.value)
      .filter((r) => r.status)
      .map((r) => ({
        employee_id: r.employee_id,
        status: r.status,
        check_in_time: r.check_in_time || null,
        check_out_time: r.check_out_time || null,
        notes: r.notes || null,
      }))
    if (attendancesList.length === 0) {
      bulkError.value = 'Pilih minimal satu pegawai dengan status kehadiran.'
      bulkSaving.value = false
      return
    }
    const invalidTime = attendancesList.find(
      (r) => r.check_in_time && r.check_out_time && r.check_out_time < r.check_in_time
    )
    if (invalidTime) {
      bulkError.value = 'Jam keluar harus setelah atau sama dengan jam masuk. Periksa data pegawai.'
      bulkSaving.value = false
      return
    }

    if (isOffline.value) {
      // Save to offline storage
      await employeeAttendanceStorage.save(bulkDate.value, attendancesList)
      toast.success('Absensi disimpan secara offline. Akan disinkronkan saat online.')
      showBulkModal.value = false
      loadAttendances()
    } else {
      try {
        await employeeAttendanceApi.bulkStore(bulkDate.value, attendancesList)
        toast.success('Absensi pegawai berhasil disimpan')
        showBulkModal.value = false
        loadAttendances()
        rekapLoaded.value = false
      } catch (e) {
        // If online save fails, save offline
        await employeeAttendanceStorage.save(bulkDate.value, attendancesList)
        toast.success('Absensi disimpan secara offline. Akan disinkronkan saat online.')
        showBulkModal.value = false
        loadAttendances()
        rekapLoaded.value = false
      }
    }
  } catch (e) {
    bulkError.value = e.response?.data?.message || e.formattedMessage || 'Gagal menyimpan'
  } finally {
    bulkSaving.value = false
  }
}

onMounted(async () => {
  await Promise.all([loadStatusOptions(), loadEmployees()])
  loadAttendances()
})
</script>

<style scoped>
.attendance-employee-page { width: 100%; max-width: 100%; padding: 1.5rem; margin: 0 auto; }
.page-header { margin-bottom: 1.5rem; }
.header-content { display: flex; align-items: flex-start; gap: 1rem; flex-wrap: wrap; }
.header-icon-wrap {
  width: 48px; height: 48px; border-radius: 12px;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  display: flex; align-items: center; justify-content: center; color: #fff;
}
.header-actions { margin-left: auto; display: flex; gap: 0.5rem; flex-wrap: wrap; }
.page-title { font-size: 1.5rem; font-weight: 700; margin: 0 0 0.25rem 0; }
.page-subtitle { color: #64748b; margin: 0; font-size: 0.9rem; }
.section-tabs { display: flex; gap: 0.5rem; margin-bottom: 1rem; }
.sec-btn {
  padding: 0.45rem 0.9rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  color: #475569;
  cursor: pointer;
  font-size: 0.9rem;
}
.sec-btn.active {
  background: #059669;
  border-color: #059669;
  color: #fff;
}
.rekap-stats {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem 1.25rem;
  padding: 0.75rem 1rem;
  background: #ecfdf5;
  border: 1px solid #a7f3d0;
  border-radius: 10px;
  margin-bottom: 1rem;
  font-size: 0.875rem;
  color: #065f46;
}
.charts-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
}
@media (max-width: 900px) {
  .charts-grid { grid-template-columns: 1fr; }
}
.filters-inline { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1rem; align-items: center; }
.filter-select { padding: 0.5rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem; min-width: 140px; }
.loading-wrap { width: 100%; margin: 1rem 0; }
.empty-state { text-align: center; padding: 2rem; background: #f8fafc; border-radius: 12px; }
.empty-title { font-size: 1.25rem; margin: 0 0 0.5rem 0; }
.empty-desc { color: #64748b; margin: 0; }
.table-container { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
.data-table th, .data-table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
.data-table th { font-weight: 600; background: #f8fafc; }
.status-badge { padding: 0.25rem 0.5rem; border-radius: 6px; font-size: 0.85rem; }
.status-badge.hadir { background: #dcfce7; color: #166534; }
.status-badge.alpha { background: #fee2e2; color: #991b1b; }
.status-badge.izin, .status-badge.sakit, .status-badge.cuti { background: #fef3c7; color: #92400e; }
.btn-action { padding: 0.35rem 0.5rem; border-radius: 6px; border: 1px solid #e2e8f0; background: #fff; cursor: pointer; font-size: 0.85rem; }
.btn-action.btn-edit:hover { background: #ecfdf5; border-color: #059669; }
.btn-primary.btn-compact, .btn-secondary.btn-compact { padding: 0.4rem 0.75rem; font-size: 0.85rem; }
.pagination-bar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; margin-top: 1rem; }
.pagination-info { color: #64748b; font-size: 0.9rem; }
.pagination-buttons { display: flex; align-items: center; gap: 0.5rem; }
.btn-page { padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; background: #fff; cursor: pointer; }
.btn-page:disabled { opacity: 0.5; cursor: not-allowed; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 1rem; }
.modal-content { background: #fff; border-radius: 12px; max-height: 90vh; display: flex; flex-direction: column; min-width: 320px; }
.modal-wide { max-width: 800px; width: 100%; }
.modal-header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.25rem; border-bottom: 1px solid #e2e8f0; }
.modal-header h3 { margin: 0; font-size: 1.1rem; }
.btn-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #64748b; }
.modal-body { padding: 1.25rem; overflow-y: auto; }
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; font-weight: 500; margin-bottom: 0.35rem; font-size: 0.9rem; }
.form-select, .form-input { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem; }
.form-row { display: flex; gap: 1rem; }
.form-row .form-group { flex: 1; }
.bulk-actions-inline { margin-bottom: 0.75rem; display: flex; flex-wrap: wrap; gap: 8px; }
.roster-toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 8px;
}
.roster-toolbar .form-input,
.roster-toolbar .form-select {
  flex: 1;
  min-width: 140px;
}
.roster-meta { color: #64748b; font-size: 0.8rem; margin: 0 0 8px; }
.btn-outline { padding: 0.4rem 0.75rem; font-size: 0.85rem; border: 1px solid #059669; border-radius: 8px; background: #fff; color: #059669; cursor: pointer; }
.btn-outline:hover { background: #e0f2fe; }
.form-hint { color: #64748b; font-size: 0.85rem; margin-bottom: 0.75rem; }
.table-scroll { max-height: 45vh; overflow-y: auto; margin-bottom: 1rem; }
.status-select { min-width: 120px; }
.time-input { max-width: 120px; }
.form-error { color: #dc2626; font-size: 0.9rem; margin-bottom: 0.75rem; }
.modal-actions { display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1rem; }
.btn-secondary { padding: 0.5rem 1rem; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; cursor: pointer; }
.btn-primary { padding: 0.5rem 1rem; border: none; border-radius: 8px; background: #059669; color: #fff; cursor: pointer; }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
.offline-indicator {
  background: #fef3c7;
  color: #92400e;
  padding: 0.75rem;
  border-radius: 8px;
  margin-bottom: 1rem;
  font-size: 0.875rem;
  text-align: center;
}

@media (max-width: 1024px) {
  .header-content {
    flex-direction: column;
    align-items: stretch;
  }

  .header-actions {
    margin-left: 0;
    width: 100%;
  }

  .header-actions .btn-primary,
  .header-actions .btn-secondary {
    flex: 1;
    justify-content: center;
  }
}

@media (max-width: 768px) {
  .filters-inline {
    flex-direction: column;
    align-items: stretch;
  }

  .filter-select {
    min-width: 0;
    width: 100%;
  }

  .form-row {
    flex-direction: column;
    gap: 0;
  }

  .table-scroll {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  .status-select,
  .time-input {
    min-width: 0;
    max-width: none;
    width: 100%;
  }

  .modal-content {
    min-width: 0;
    width: 100%;
  }

  .modal-actions {
    flex-direction: column-reverse;
  }

  .modal-actions .btn-primary,
  .modal-actions .btn-secondary {
    width: 100%;
    justify-content: center;
  }

  .pagination-bar {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>
