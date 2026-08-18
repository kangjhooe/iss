<template>
  <Layout>
    <div class="uks-page">
      <div class="toolbar">
        <div class="main-tabs">
          <button type="button" :class="['main-tab', { active: activeTab === 'list' }]" @click="activeTab = 'list'; loadVisits()">
            <span>Kunjungan</span>
          </button>
          <button type="button" :class="['main-tab', { active: activeTab === 'types' }]" @click="activeTab = 'types'; loadTypes()">
            <span>Pengaturan</span>
          </button>
        </div>
        <div class="header-actions">
          <button v-if="activeTab === 'list'" type="button" class="btn-secondary btn-compact" :disabled="exporting" @click="exportToCsv">
            <span>{{ exporting ? 'Mengekspor...' : 'Export CSV' }}</span>
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
        <div class="uks-dashboard">
          <div class="stat-card">
            <span class="stat-label">Kunjungan bulan ini</span>
            <span class="stat-value">{{ statsData?.total_this_month ?? '-' }}</span>
          </div>
          <div class="stat-card">
            <span class="stat-label">Total tahun {{ statsData?.year || '' }}</span>
            <span class="stat-value">{{ statsData?.total_year ?? '-' }}</span>
          </div>
          <div class="chart-box">
            <div class="chart-header">
              <h4>Kunjungan per bulan</h4>
              <select v-model="statsYear" class="chart-year-select" @change="loadStats">
                <option v-for="y in statsYears" :key="y" :value="y">{{ y }}</option>
              </select>
            </div>
            <div v-if="byMonthChartData" class="chart-wrap">
              <Bar :data="byMonthChartData" :options="chartOptionsBar" />
            </div>
          </div>
          <div class="chart-box">
            <div class="chart-header"><h4>Per jenis kunjungan</h4></div>
            <div v-if="byTypeChartData" class="chart-wrap chart-wrap-pie">
              <Doughnut :data="byTypeChartData" :options="chartOptionsDoughnut" />
            </div>
          </div>
        </div>

        <div class="filters filters-inline">
          <input v-model="filters.search" type="text" class="search-input" placeholder="Cari nama, NIS, NISN..." @input="debounceLoad" />
          <select v-model="filters.status" class="filter-select" @change="loadVisits">
            <option value="">Semua Status</option>
            <option value="selesai">Selesai</option>
            <option value="observasi">Observasi</option>
            <option value="rujuk">Rujuk</option>
          </select>
          <select v-model="filters.uks_visit_type_id" class="filter-select" @change="loadVisits">
            <option value="">Semua Jenis</option>
            <option v-for="t in visitTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
          </select>
          <input v-model="filters.date_from" type="date" class="filter-select" @change="loadVisits" />
          <input v-model="filters.date_to" type="date" class="filter-select" @change="loadVisits" />
          <select v-model="filters.class_id" class="filter-select" @change="loadVisits">
            <option value="">Semua Kelas</option>
            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
          <select v-model="filters.academic_year_id" class="filter-select" @change="loadVisits">
            <option value="">Semua Tahun Ajaran</option>
            <option v-for="y in academicYears" :key="y.id" :value="y.id">{{ y.name }}</option>
          </select>
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
                    <button type="button" class="btn-action btn-edit" title="Edit" @click="openEditModal(v)">✎</button>
                    <button type="button" class="btn-action btn-delete" title="Hapus" @click="deleteTarget = v">🗑</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="pagination.last_page > 1" class="pagination-bar">
          <span class="pagination-info">Halaman {{ pagination.current_page }} / {{ pagination.last_page }} · {{ pagination.total }} data</span>
          <div class="pagination-buttons">
            <button type="button" class="btn-page" :disabled="pagination.current_page <= 1" @click="goToPage(pagination.current_page - 1)">Sebelumnya</button>
            <button type="button" class="btn-page" :disabled="pagination.current_page >= pagination.last_page" @click="goToPage(pagination.current_page + 1)">Selanjutnya</button>
          </div>
        </div>
      </template>

      <template v-else>
        <p class="settings-hint">Kelola master jenis kunjungan. Jarang diubah saat pencatatan harian.</p>
        <div v-if="typesLoading" class="loading-wrap"><LoadingSkeleton type="table" :rows="5" :columns="4" /></div>
        <div v-else-if="!visitTypes.length" class="empty-state">
          <h3 class="empty-title">Belum ada jenis kunjungan</h3>
          <p class="empty-desc">Isi jenis standar (P3K, screening, rujukan, dll.) atau buat sendiri.</p>
          <button type="button" class="btn-primary" @click="seedDefaults">Isi Jenis Standar</button>
        </div>
        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Nama</th>
                <th>Kode</th>
                <th>Status</th>
                <th>Deskripsi</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="t in visitTypes" :key="t.id">
                <td>{{ t.name }}</td>
                <td>{{ t.code || '-' }}</td>
                <td><span :class="['status-badge', t.is_active ? 'status-selesai' : 'status-rujuk']">{{ t.is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                <td>{{ t.description || '-' }}</td>
                <td>
                  <div class="action-buttons">
                    <button type="button" class="btn-action btn-edit" @click="openEditTypeModal(t)">✎</button>
                    <button type="button" class="btn-action btn-delete" @click="deleteTypeTarget = t">🗑</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <!-- Form kunjungan -->
      <div v-if="showFormModal" class="modal-overlay" @click.self="showFormModal = false">
        <div class="modal-card">
          <h3>{{ editingVisit ? 'Edit Kunjungan UKS' : 'Catat Kunjungan UKS' }}</h3>
          <p v-if="formError" class="form-error">{{ formError }}</p>
          <form class="form-grid" @submit.prevent="submitForm">
            <label>Siswa *
              <select v-model="form.student_id" required>
                <option value="">Pilih siswa</option>
                <option v-for="s in students" :key="s.id" :value="s.id">{{ s.name }} ({{ s.nis || s.nisn || '-' }})</option>
              </select>
            </label>
            <label>Tanggal *
              <input v-model="form.visit_date" type="date" required />
            </label>
            <label>Jenis kunjungan
              <select v-model="form.uks_visit_type_id">
                <option value="">—</option>
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
              <textarea v-model="form.complaint" rows="2" />
            </label>
            <label class="full">Tindakan
              <textarea v-model="form.action_taken" rows="2" />
            </label>
            <label class="full">Catatan
              <textarea v-model="form.notes" rows="2" />
            </label>
            <label>TB (cm)<input v-model="form.height_cm" type="number" step="0.1" min="0" /></label>
            <label>BB (kg)<input v-model="form.weight_kg" type="number" step="0.1" min="0" /></label>
            <label>Suhu (°C)<input v-model="form.temperature_c" type="number" step="0.1" min="30" max="45" /></label>
            <label>Tekanan darah<input v-model="form.blood_pressure" type="text" placeholder="120/80" /></label>
            <div class="modal-actions full">
              <button type="button" class="btn-secondary" @click="showFormModal = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="formSubmitting">{{ formSubmitting ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Form jenis -->
      <div v-if="showTypeModal" class="modal-overlay" @click.self="showTypeModal = false">
        <div class="modal-card modal-card-sm">
          <h3>{{ editingType ? 'Edit Jenis Kunjungan' : 'Tambah Jenis Kunjungan' }}</h3>
          <p v-if="typeFormError" class="form-error">{{ typeFormError }}</p>
          <form class="form-grid" @submit.prevent="submitTypeForm">
            <label class="full">Nama *<input v-model="typeForm.name" required /></label>
            <label>Kode<input v-model="typeForm.code" /></label>
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
    </div>
  </Layout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import Layout from '@/components/Layout.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { Bar, Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, ArcElement, Title, Tooltip, Legend } from 'chart.js'
import { uksApi, uksVisitTypeApi } from '@/api/uks'
import { studentApi } from '@/api/student'
import { classApi } from '@/api/class'
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
const students = ref([])
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

const monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']

const byMonthChartData = computed(() => {
  const months = statsData.value?.by_month
  if (!months?.length) return null
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
      per_page: 15,
      ...filters.value,
    })
    const res = await uksApi.getAll(params)
    visits.value = res.data.data || []
    const meta = res.data.meta || {}
    pagination.value = {
      current_page: meta.current_page ?? 1,
      last_page: meta.last_page ?? 1,
      per_page: meta.per_page ?? 15,
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

async function loadStudents() {
  try {
    const res = await studentApi.getAll({ per_page: 500 })
    students.value = res.data.data || []
  } catch {
    students.value = []
  }
}

async function loadClasses() {
  try {
    const res = await classApi.getAll({ per_page: 200 })
    classes.value = res.data.data || []
  } catch {
    classes.value = []
  }
}

function openAddModal() {
  editingVisit.value = null
  form.value = emptyForm()
  formError.value = ''
  showFormModal.value = true
}

function openEditModal(v) {
  editingVisit.value = v
  form.value = {
    student_id: v.student_id,
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

async function submitForm() {
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
    await Promise.all([loadVisits(), loadStats()])
  } catch (e) {
    formError.value = e.formattedMessage || 'Gagal menyimpan.'
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

  await Promise.all([loadTypes(), loadStudents(), loadClasses(), loadStats(), loadVisits()])
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
.main-tab { border: none; background: transparent; padding: 8px 14px; border-radius: 8px; cursor: pointer; font-size: 13px; color: #64748b; font-weight: 500; }
.main-tab.active { background: #fff; color: #0f172a; box-shadow: 0 1px 2px rgba(0,0,0,.08); }
.uks-dashboard { display: grid; grid-template-columns: repeat(2, minmax(140px, 1fr)) 1.4fr 1fr; gap: 12px; }
.stat-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; display: flex; flex-direction: column; gap: 6px; }
.stat-label { font-size: 12px; color: #64748b; }
.stat-value { font-size: 28px; font-weight: 700; color: #0f172a; }
.chart-box { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 14px; min-height: 180px; }
.chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
.chart-header h4 { margin: 0; font-size: 13px; color: #334155; }
.chart-year-select { font-size: 12px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 2px 6px; }
.chart-wrap { height: 140px; }
.chart-wrap-pie { height: 150px; }
.filters-inline { display: flex; flex-wrap: wrap; gap: 8px; }
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
.btn-action { border: none; background: #f1f5f9; border-radius: 6px; width: 30px; height: 30px; cursor: pointer; }
.btn-delete { color: #b91c1c; }
.empty-state { text-align: center; padding: 48px 20px; background: #fff; border-radius: 12px; border: 1px dashed #cbd5e1; }
.empty-title { margin: 0 0 8px; }
.empty-desc { color: #64748b; margin: 0 0 16px; }
.pagination-bar { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
.btn-page { border: 1px solid #cbd5e1; background: #fff; border-radius: 8px; padding: 6px 12px; cursor: pointer; font-size: 12px; }
.btn-page:disabled { opacity: .5; cursor: not-allowed; }
.modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, .45); display: flex; align-items: center; justify-content: center; z-index: 80; padding: 16px; }
.modal-card { background: #fff; border-radius: 14px; padding: 20px; width: min(720px, 100%); max-height: 90vh; overflow: auto; }
.modal-card-sm { width: min(480px, 100%); }
.modal-card h3 { margin: 0 0 12px; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.form-grid label { display: flex; flex-direction: column; gap: 4px; font-size: 12px; color: #475569; font-weight: 600; }
.form-grid input, .form-grid select, .form-grid textarea { font-weight: 400; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 10px; font-size: 13px; }
.form-grid .full { grid-column: 1 / -1; }
.modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 4px; }
.form-error { color: #b91c1c; font-size: 13px; margin: 0 0 8px; }
@media (max-width: 1100px) {
  .uks-dashboard { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 700px) {
  .uks-dashboard, .form-grid { grid-template-columns: 1fr; }
}
</style>
