<template>
  <Layout>
    <div class="counseling-page">
      <div class="toolbar">
        <div class="main-tabs">
          <button :class="['main-tab', { active: activeTab === 'list' }]" @click="activeTab = 'list'; loadSessions()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M8 6H21M8 12H21M8 18H21M3 6H3.01M3 12H3.01M3 18H3.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Daftar Sesi Konseling</span>
          </button>
          <button :class="['main-tab', { active: activeTab === 'types' }]" @click="activeTab = 'types'; loadTypes()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15M9 5C9 6.10457 9.89543 7 11 7H13C14.1046 7 15 6.10457 15 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M12 12H15M12 16H15M9 12H9.01M9 16H9.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Jenis Konseling</span>
          </button>
        </div>
        <div class="header-actions">
          <button v-if="activeTab === 'list'" @click="exportToCsv" :disabled="exporting" class="btn-secondary btn-compact">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M7 10L12 15L17 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>{{ exporting ? 'Mengekspor...' : 'Export CSV' }}</span>
          </button>
          <button v-if="activeTab === 'list'" @click="openAddModal" class="btn-primary btn-compact">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Tambah Sesi Konseling</span>
          </button>
          <button v-if="activeTab === 'types'" @click="openAddTypeModal" class="btn-primary btn-compact">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Tambah Jenis Konseling</span>
          </button>
        </div>
      </div>

      <!-- Tab: Daftar Sesi Konseling -->
      <template v-if="activeTab === 'list'">
        <!-- Dashboard kecil: ringkasan + grafik -->
        <div class="counseling-dashboard">
          <div class="dashboard-cards">
            <div class="stat-card">
              <span class="stat-label">Sesi bulan ini</span>
              <span class="stat-value">{{ statsData?.total_this_month ?? '-' }}</span>
            </div>
            <div class="stat-card stat-upcoming">
              <span class="stat-label">Jadwal mendatang</span>
              <span class="stat-value">{{ upcomingSessions.length }}</span>
            </div>
          </div>
          <div class="dashboard-charts">
            <div class="chart-box">
              <div class="chart-header">
                <h4>Jumlah sesi per bulan ({{ statsData?.year || '' }})</h4>
                <select v-model="statsYear" @change="loadStats" class="chart-year-select">
                  <option v-for="y in statsYears" :key="y" :value="y">{{ y }}</option>
                </select>
              </div>
              <div class="chart-wrap" v-if="sessionsByMonthChartData">
                <Bar :data="sessionsByMonthChartData" :options="chartOptionsBar" />
              </div>
            </div>
            <div class="chart-box">
              <div class="chart-header">
                <h4>Sesi per jenis konseling</h4>
              </div>
              <div class="chart-wrap chart-wrap-pie" v-if="sessionsByTypeChartData">
                <Doughnut :data="sessionsByTypeChartData" :options="chartOptionsDoughnut" />
              </div>
            </div>
          </div>
        </div>

        <!-- Reminder: Jadwal konseling mendatang -->
        <div class="upcoming-block" v-if="upcomingSessions.length > 0">
          <h4 class="upcoming-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Jadwal konseling mendatang
          </h4>
          <div class="upcoming-list">
            <div v-for="u in upcomingSessions" :key="u.id" class="upcoming-item">
              <span class="upcoming-date">{{ formatDate(u.session_date) }}</span>
              <span class="upcoming-student">{{ u.student?.name }}</span>
              <span class="upcoming-type">{{ u.counseling_type?.name || '-' }}</span>
              <span class="upcoming-counselor">{{ u.counselor?.name }}</span>
              <button type="button" class="upcoming-link" @click="openEditModal(u)">Edit</button>
            </div>
          </div>
        </div>

        <div class="filters filters-inline">
          <input
            v-model="filters.search"
            type="text"
            placeholder="Cari nama, NIS, NISN siswa..."
            class="search-input"
            @input="debounceLoadSessions"
          />
          <select v-model="filters.student_id" @change="loadSessions" class="filter-select">
            <option value="">Semua Siswa</option>
            <option v-for="s in studentsFilterList" :key="s.id" :value="s.id">{{ s.name }} ({{ s.nis || s.nisn || '-' }})</option>
          </select>
          <select v-model="filters.status" @change="loadSessions" class="filter-select">
            <option value="">Semua Status</option>
            <option value="jadwal">Jadwal</option>
            <option value="berlangsung">Berlangsung</option>
            <option value="selesai">Selesai</option>
            <option value="dibatalkan">Dibatalkan</option>
          </select>
          <select v-model="filters.counseling_type_id" @change="loadSessions" class="filter-select">
            <option value="">Semua Jenis</option>
            <option v-for="t in counselingTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
          </select>
          <input v-model="filters.date_from" type="date" class="filter-select" @change="loadSessions" />
          <input v-model="filters.date_to" type="date" class="filter-select" @change="loadSessions" />
          <select v-model="filters.class_id" @change="loadSessions" class="filter-select">
            <option value="">Semua Kelas</option>
            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
          <select v-model="filters.academic_year_id" @change="loadSessions" class="filter-select">
            <option value="">Semua Tahun Ajaran</option>
            <option v-for="y in academicYears" :key="y.id" :value="y.id">{{ y.name }}</option>
          </select>
          <select v-model="filters.semester_id" @change="loadSessions" class="filter-select">
            <option value="">Semua Semester</option>
            <option v-for="s in semesters" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
        </div>

        <div v-if="loading" class="loading-wrap">
          <LoadingSkeleton type="table" :rows="8" :columns="7" :cell-widths="['100px', '160px', '120px', '100px', '80px', '1fr', '90px']" />
        </div>

        <div v-else-if="sessions.length === 0" class="empty-state">
          <div class="empty-icon">
            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M21 15C21 15.5304 20.7893 16.0391 20.4142 16.4142C20.0391 16.7893 19.5304 17 19 17H7L3 21V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V15Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <h3 class="empty-title">Belum ada sesi konseling</h3>
          <p class="empty-desc">Tambahkan sesi konseling atau atur filter untuk melihat data.</p>
          <button @click="openAddModal" class="btn-primary btn-empty-cta">Tambah Sesi Konseling</button>
        </div>

        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Siswa</th>
                <th>Konselor</th>
                <th>Jenis</th>
                <th>Status</th>
                <th>Ringkasan</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="s in sessions" :key="s.id">
                <td>{{ formatDate(s.session_date) }}</td>
                <td>
                  <span class="student-name">{{ s.student?.name }}</span>
                  <span class="student-meta">{{ s.student?.nisn || s.student?.nis || '-' }}</span>
                  <button type="button" class="btn-history-link" @click="openHistoryModal(s.student)" title="Riwayat konseling">Riwayat</button>
                </td>
                <td>{{ s.counselor?.name }}</td>
                <td>{{ s.counseling_type?.name || '-' }}</td>
                <td><span :class="['status-badge', 'status-' + s.status]">{{ getStatusLabel(s.status) }}</span></td>
                <td class="summary-cell">{{ truncate(s.summary, 50) }}</td>
                <td>
                  <div class="action-buttons">
                    <button @click="openEditModal(s)" class="btn-action btn-edit" title="Edit">✎</button>
                    <button @click="confirmDelete(s)" class="btn-action btn-delete" title="Hapus">🗑</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="activeTab === 'list' && pagination.last_page > 1" class="pagination-bar">
          <span class="pagination-info">
            Menampilkan {{ (pagination.current_page - 1) * pagination.per_page + 1 }}-{{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} dari {{ pagination.total }}
          </span>
          <div class="pagination-buttons">
            <button type="button" class="btn-page" :disabled="pagination.current_page <= 1" @click="goToPage(pagination.current_page - 1)">Sebelumnya</button>
            <span class="page-num">Halaman {{ pagination.current_page }} / {{ pagination.last_page }}</span>
            <button type="button" class="btn-page" :disabled="pagination.current_page >= pagination.last_page" @click="goToPage(pagination.current_page + 1)">Selanjutnya</button>
          </div>
        </div>
      </template>

      <!-- Tab: Jenis Konseling -->
      <template v-if="activeTab === 'types'">
        <div v-if="typesLoading" class="loading-state"><div class="loading-spinner"></div><p>Memuat jenis konseling...</p></div>
        <div v-else-if="counselingTypes.length === 0" class="empty-state">
          <h3 class="empty-title">Belum ada jenis konseling</h3>
          <p class="empty-desc">Tambahkan jenis konseling (mis. Akademik, Pribadi, Karir) untuk mengkategorikan sesi.</p>
          <button @click="openAddTypeModal" class="btn-primary btn-empty-cta">Tambah Jenis Konseling</button>
        </div>
        <div v-else class="types-grid">
          <div v-for="t in counselingTypes" :key="t.id" class="type-card">
            <div class="type-header">
              <span class="type-name">{{ t.name }}</span>
              <span v-if="t.code" class="type-code">{{ t.code }}</span>
            </div>
            <div class="type-body" v-if="t.description">
              <p class="type-desc">{{ t.description }}</p>
            </div>
            <div class="type-actions">
              <button @click="openEditTypeModal(t)" class="btn-action btn-edit">Edit</button>
              <button @click="confirmDeleteType(t)" class="btn-action btn-delete">Hapus</button>
            </div>
          </div>
        </div>
      </template>

      <!-- Modal: Tambah/Edit Sesi Konseling -->
      <div v-if="showFormModal" class="modal-overlay" @click="showFormModal = false">
        <div class="modal-content form-modal" @click.stop>
          <div class="modal-header">
            <h3>{{ editingSession ? 'Edit Sesi Konseling' : 'Tambah Sesi Konseling' }}</h3>
            <button @click="showFormModal = false" class="btn-close">×</button>
          </div>
          <form @submit.prevent="submitSession" class="modal-body">
            <div class="form-group">
              <label>Siswa *</label>
              <select v-model="form.student_id" required :disabled="!!editingSession" class="form-select">
                <option value="">Pilih siswa</option>
                <option v-for="s in students" :key="s.id" :value="s.id">{{ s.name }} ({{ s.nis || s.nisn || '-' }})</option>
              </select>
            </div>
            <div class="form-group">
              <label>Konselor *</label>
              <select v-model="form.counselor_id" required class="form-select">
                <option value="">Pilih konselor</option>
                <option v-for="c in counselors" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Jenis Konseling</label>
              <select v-model="form.counseling_type_id" class="form-select">
                <option value="">— Pilih (opsional) —</option>
                <option v-for="t in counselingTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Tanggal Sesi *</label>
              <input v-model="form.session_date" type="date" required />
            </div>
            <div class="form-group">
              <label>Status</label>
              <select v-model="form.status" class="form-select">
                <option value="jadwal">Jadwal</option>
                <option value="berlangsung">Berlangsung</option>
                <option value="selesai">Selesai</option>
                <option value="dibatalkan">Dibatalkan</option>
              </select>
            </div>
            <div class="form-group">
              <label>Ringkasan</label>
              <textarea v-model="form.summary" rows="3" placeholder="Ringkasan sesi konseling (opsional)"></textarea>
            </div>
            <div class="form-group">
              <label>Catatan Tindak Lanjut</label>
              <textarea v-model="form.follow_up_notes" rows="2" placeholder="Catatan follow up (opsional)"></textarea>
            </div>
            <div v-if="formError" class="error-message">{{ formError }}</div>
            <div class="modal-footer">
              <button type="button" @click="showFormModal = false" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="formSubmitting" class="btn-primary">
                {{ formSubmitting ? 'Menyimpan...' : (editingSession ? 'Simpan' : 'Tambah') }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal: Tambah/Edit Jenis Konseling -->
      <div v-if="showTypeModal" class="modal-overlay" @click="showTypeModal = false">
        <div class="modal-content form-modal" @click.stop>
          <div class="modal-header">
            <h3>{{ editingType ? 'Edit Jenis Konseling' : 'Tambah Jenis Konseling' }}</h3>
            <button @click="showTypeModal = false" class="btn-close">×</button>
          </div>
          <form @submit.prevent="submitType" class="modal-body">
            <div class="form-group">
              <label>Nama *</label>
              <input v-model="typeForm.name" type="text" required placeholder="Contoh: Akademik" />
            </div>
            <div class="form-group">
              <label>Kode (opsional)</label>
              <input v-model="typeForm.code" type="text" placeholder="Contoh: AK" />
            </div>
            <div class="form-group">
              <label>Deskripsi</label>
              <textarea v-model="typeForm.description" rows="2" placeholder="Deskripsi jenis konseling"></textarea>
            </div>
            <div v-if="typeFormError" class="error-message">{{ typeFormError }}</div>
            <div class="modal-footer">
              <button type="button" @click="showTypeModal = false" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="typeFormSubmitting" class="btn-primary">
                {{ typeFormSubmitting ? 'Menyimpan...' : (editingType ? 'Simpan' : 'Tambah') }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <ConfirmDialog
        v-if="deleteTarget"
        :show="!!deleteTarget"
        title="Hapus Sesi Konseling"
        :message="deleteSessionMessage"
        confirmText="Hapus"
        @confirm="doDeleteSession"
        @cancel="deleteTarget = null"
      />
      <ConfirmDialog
        v-if="deleteTypeTarget"
        :show="!!deleteTypeTarget"
        title="Hapus Jenis Konseling"
        :message="deleteTypeMessage"
        confirmText="Hapus"
        @confirm="doDeleteType"
        @cancel="deleteTypeTarget = null"
      />

      <!-- Modal: Riwayat konseling per siswa -->
      <div v-if="showHistoryModal" class="modal-overlay" @click="showHistoryModal = false">
        <div class="modal-content history-modal" @click.stop>
          <div class="modal-header">
            <h3>Riwayat konseling – {{ historyStudent?.name || '' }}</h3>
            <button @click="showHistoryModal = false" class="btn-close">×</button>
          </div>
          <div class="modal-body">
            <div v-if="historyLoading" class="loading-state small">Memuat riwayat...</div>
            <div v-else-if="!historySessions.length" class="empty-state small">
              <p>Belum ada sesi konseling untuk siswa ini.</p>
              <button v-if="historyStudent" @click="openAddModalForStudent(historyStudent); showHistoryModal = false" class="btn-primary btn-sm">Tambah Sesi</button>
            </div>
            <div v-else class="history-table-wrap">
              <table class="data-table compact">
                <thead>
                  <tr>
                    <th>Tanggal</th>
                    <th>Jenis</th>
                    <th>Status</th>
                    <th>Konselor</th>
                    <th>Ringkasan</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="h in historySessions" :key="h.id">
                    <td>{{ formatDate(h.session_date) }}</td>
                    <td>{{ h.counseling_type?.name || '-' }}</td>
                    <td><span :class="['status-badge', 'status-' + h.status]">{{ getStatusLabel(h.status) }}</span></td>
                    <td>{{ h.counselor?.name }}</td>
                    <td class="summary-cell">{{ truncate(h.summary, 40) }}</td>
                  </tr>
                </tbody>
              </table>
              <button v-if="historyStudent" @click="openAddModalForStudent(historyStudent); showHistoryModal = false" class="btn-primary btn-sm mt-1">Tambah Sesi Konseling</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import Layout from '@/components/Layout.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { Bar, Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, ArcElement, Title, Tooltip, Legend } from 'chart.js'
import { counselingApi, counselingTypeApi } from '@/api/counseling'
import { studentApi } from '@/api/student'
import { classApi } from '@/api/class'
import { institutionApi } from '@/api/institution'
import { useReferenceDataStore } from '@/stores/referenceData'
import { semesterApi } from '@/api/semester'
import { useToast } from '@/composables/useToast'

ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, Title, Tooltip, Legend)

const toast = useToast()
const route = useRoute()

const activeTab = ref('list')
const loading = ref(true)
const typesLoading = ref(false)
const sessions = ref([])
const counselingTypes = ref([])
const students = ref([])
const counselors = ref([])
const pagination = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })

const statsData = ref(null)
const statsYear = ref(new Date().getFullYear())
const statsYears = computed(() => {
  const y = new Date().getFullYear()
  return [y, y - 1, y - 2]
})
const upcomingSessions = ref([])

const showHistoryModal = ref(false)
const historyStudent = ref(null)
const historySessions = ref([])
const historyLoading = ref(false)

const filters = ref({
  search: '',
  status: '',
  student_id: '',
  counseling_type_id: '',
  class_id: '',
  academic_year_id: '',
  semester_id: '',
  date_from: '',
  date_to: '',
})
const referenceStore = useReferenceDataStore()
const academicYears = computed(() => referenceStore.academicYears)

const institution = ref(null)
const classes = ref([])
const semesters = ref([])
const exporting = ref(false)

const showFormModal = ref(false)
const editingSession = ref(null)
const form = ref({
  student_id: '',
  counselor_id: '',
  counseling_type_id: '',
  session_date: '',
  status: 'jadwal',
  summary: '',
  follow_up_notes: '',
})
const formSubmitting = ref(false)
const formError = ref('')

const showTypeModal = ref(false)
const editingType = ref(null)
const typeForm = ref({
  name: '',
  code: '',
  description: '',
})
const typeFormSubmitting = ref(false)
const typeFormError = ref('')

const deleteTarget = ref(null)
const deleteTypeTarget = ref(null)

const statusLabels = {
  jadwal: 'Jadwal',
  berlangsung: 'Berlangsung',
  selesai: 'Selesai',
  dibatalkan: 'Dibatalkan',
}
function getStatusLabel(status) {
  return statusLabels[status] || status
}

const deleteSessionMessage = computed(() => {
  const name = deleteTarget.value?.student?.name || ''
  return 'Yakin menghapus sesi konseling untuk ' + name + '?'
})
const deleteTypeMessage = computed(() => {
  const name = deleteTypeTarget.value?.name || ''
  return 'Yakin menghapus jenis konseling ' + name + '? Jenis yang sudah dipakai tidak dapat dihapus.'
})

let debounceTimer = null
function debounceLoadSessions() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => loadSessions(), 300)
}

function formatDate(val) {
  if (!val) return '-'
  const d = new Date(val)
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

function truncate(str, len) {
  if (!str) return '-'
  return str.length <= len ? str : str.slice(0, len) + '…'
}

async function loadSessions() {
  loading.value = true
  formError.value = ''
  try {
    const params = {
      page: pagination.value.current_page,
      per_page: 15,
      ...filters.value,
    }
    if (!params.status) delete params.status
    if (!params.student_id) delete params.student_id
    if (!params.counseling_type_id) delete params.counseling_type_id
    if (!params.class_id) delete params.class_id
    if (!params.academic_year_id) delete params.academic_year_id
    if (!params.semester_id) delete params.semester_id
    if (!params.date_from) delete params.date_from
    if (!params.date_to) delete params.date_to
    if (!params.search) delete params.search

    const res = await counselingApi.getAll(params)
    sessions.value = res.data.data || []
    const meta = res.data.meta || {}
    pagination.value = {
      current_page: meta.current_page ?? 1,
      last_page: meta.last_page ?? 1,
      per_page: meta.per_page ?? 15,
      total: meta.total ?? 0,
    }
  } catch (e) {
    toast.error(e.formattedMessage || 'Gagal memuat sesi konseling')
  } finally {
    loading.value = false
  }
}

async function loadTypes() {
  typesLoading.value = true
  try {
    const res = await counselingTypeApi.getAll({ active_only: false })
    counselingTypes.value = res.data.data || []
  } catch (e) {
    toast.error(e.formattedMessage || 'Gagal memuat jenis konseling')
  } finally {
    typesLoading.value = false
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

async function loadCounselors() {
  try {
    const res = await counselingApi.getCounselors()
    counselors.value = res.data.data || []
  } catch {
    counselors.value = []
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

async function loadSemesters() {
  try {
    const res = await semesterApi.getAll({ per_page: 200 })
    semesters.value = res.data.data || []
  } catch {
    semesters.value = []
  }
}

async function loadInstitutionAndSetFilterDefaults() {
  try {
    const res = await institutionApi.getMy()
    institution.value = res.data?.data ?? res.data ?? null
    if (institution.value?.active_academic_year_id) {
      filters.value.academic_year_id = String(institution.value.active_academic_year_id)
    }
    if (institution.value?.active_semester_id) {
      filters.value.semester_id = String(institution.value.active_semester_id)
    }
  } catch {
    institution.value = null
  }
}

async function exportToCsv() {
  exporting.value = true
  try {
    const params = { ...filters.value }
    if (!params.student_id) delete params.student_id
    if (!params.status) delete params.status
    if (!params.counseling_type_id) delete params.counseling_type_id
    if (!params.class_id) delete params.class_id
    if (!params.academic_year_id) delete params.academic_year_id
    if (!params.semester_id) delete params.semester_id
    if (!params.date_from) delete params.date_from
    if (!params.date_to) delete params.date_to
    if (!params.search) delete params.search
    const res = await counselingApi.export(params)
    const blob = new Blob([res.data], { type: 'text/csv;charset=utf-8;' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `laporan-konseling-${new Date().toISOString().slice(0, 10)}.csv`)
    link.click()
    window.URL.revokeObjectURL(url)
    toast.success('Export berhasil diunduh')
  } catch (e) {
    toast.error(e.formattedMessage || 'Gagal mengekspor')
  } finally {
    exporting.value = false
  }
}

function goToPage(page) {
  pagination.value.current_page = page
  loadSessions()
}

function openAddModal() {
  editingSession.value = null
  form.value = {
    student_id: '',
    counselor_id: '',
    counseling_type_id: '',
    session_date: new Date().toISOString().slice(0, 10),
    status: 'jadwal',
    summary: '',
    follow_up_notes: '',
  }
  formError.value = ''
  if (students.value.length === 0) loadStudents()
  if (counselors.value.length === 0) loadCounselors()
  if (counselingTypes.value.length === 0) loadTypes()
  showFormModal.value = true
}

function openEditModal(s) {
  editingSession.value = s
  form.value = {
    student_id: s.student_id,
    counselor_id: s.counselor_id,
    counseling_type_id: s.counseling_type_id || '',
    session_date: s.session_date,
    status: s.status || 'jadwal',
    summary: s.summary || '',
    follow_up_notes: s.follow_up_notes || '',
  }
  formError.value = ''
  showFormModal.value = true
}

async function submitSession() {
  formSubmitting.value = true
  formError.value = ''
  try {
    const payload = {
      student_id: form.value.student_id,
      counselor_id: form.value.counselor_id,
      counseling_type_id: form.value.counseling_type_id || null,
      session_date: form.value.session_date,
      status: form.value.status,
      summary: form.value.summary || null,
      follow_up_notes: form.value.follow_up_notes || null,
    }
    if (editingSession.value) {
      await counselingApi.update(editingSession.value.id, {
        counselor_id: payload.counselor_id,
        counseling_type_id: payload.counseling_type_id,
        session_date: payload.session_date,
        status: payload.status,
        summary: payload.summary,
        follow_up_notes: payload.follow_up_notes,
      })
      toast.success('Sesi konseling berhasil diperbarui')
    } else {
      await counselingApi.create(payload)
      toast.success('Sesi konseling berhasil dicatat')
    }
    showFormModal.value = false
    loadSessions()
    loadStats()
    loadUpcoming()
  } catch (e) {
    formError.value = e.formattedMessage || e.response?.data?.message || 'Gagal menyimpan'
  } finally {
    formSubmitting.value = false
  }
}

function confirmDelete(s) {
  deleteTarget.value = s
}

async function doDeleteSession() {
  if (!deleteTarget.value) return
  try {
    await counselingApi.delete(deleteTarget.value.id)
    toast.success('Sesi konseling dihapus')
    deleteTarget.value = null
    loadSessions()
    loadStats()
    loadUpcoming()
  } catch (e) {
    toast.error(e.formattedMessage || 'Gagal menghapus')
  }
}

function openAddTypeModal() {
  editingType.value = null
  typeForm.value = { name: '', code: '', description: '' }
  typeFormError.value = ''
  showTypeModal.value = true
}

function openEditTypeModal(t) {
  editingType.value = t
  typeForm.value = {
    name: t.name || '',
    code: t.code || '',
    description: t.description || '',
  }
  typeFormError.value = ''
  showTypeModal.value = true
}

async function submitType() {
  typeFormSubmitting.value = true
  typeFormError.value = ''
  try {
    if (editingType.value) {
      await counselingTypeApi.update(editingType.value.id, {
        name: typeForm.value.name,
        code: typeForm.value.code || null,
        description: typeForm.value.description || null,
      })
      toast.success('Jenis konseling berhasil diperbarui')
    } else {
      await counselingTypeApi.create({
        name: typeForm.value.name,
        code: typeForm.value.code || null,
        description: typeForm.value.description || null,
      })
      toast.success('Jenis konseling berhasil ditambahkan')
    }
    showTypeModal.value = false
    loadTypes()
    if (activeTab.value === 'list') counselingTypes.value = (await counselingTypeApi.getAll({ active_only: false })).data.data || []
  } catch (e) {
    typeFormError.value = e.formattedMessage || e.response?.data?.message || 'Gagal menyimpan'
  } finally {
    typeFormSubmitting.value = false
  }
}

function confirmDeleteType(t) {
  deleteTypeTarget.value = t
}

async function doDeleteType() {
  if (!deleteTypeTarget.value) return
  try {
    await counselingTypeApi.delete(deleteTypeTarget.value.id)
    toast.success('Jenis konseling dihapus')
    deleteTypeTarget.value = null
    loadTypes()
    if (activeTab.value === 'list') counselingTypes.value = (await counselingTypeApi.getAll({ active_only: false })).data.data || []
  } catch (e) {
    toast.error(e.formattedMessage || e.response?.data?.message || 'Jenis sudah dipakai, tidak dapat dihapus')
  }
}

const studentsFilterList = computed(() => students.value)

const sessionsByMonthChartData = computed(() => {
  const data = statsData.value?.by_month
  if (!data?.length) return null
  return {
    labels: data.map((d) => d.label),
    datasets: [
      {
        label: 'Jumlah sesi',
        data: data.map((d) => d.count),
        backgroundColor: 'rgba(5, 150, 105, 0.6)',
        borderColor: 'rgb(5, 150, 105)',
        borderWidth: 1,
      },
    ],
  }
})
const sessionsByTypeChartData = computed(() => {
  const data = statsData.value?.by_type
  if (!data?.length) return null
  const colors = ['#059669', '#047857', '#22d3ee', '#67e8f9', '#a5f3fc', '#cffafe']
  return {
    labels: data.map((d) => d.name),
    datasets: [
      {
        data: data.map((d) => d.count),
        backgroundColor: data.map((_, i) => colors[i % colors.length]),
        borderWidth: 0,
      },
    ],
  }
})
const chartOptionsBar = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
}
const chartOptionsDoughnut = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'bottom' } },
}

async function loadStats() {
  try {
    const res = await counselingApi.getStats({ year: statsYear.value })
    statsData.value = res.data.data || null
  } catch {
    statsData.value = null
  }
}
async function loadUpcoming() {
  try {
    const res = await counselingApi.getUpcoming({ limit: 10 })
    upcomingSessions.value = res.data?.data ?? res.data ?? []
  } catch {
    upcomingSessions.value = []
  }
}

function openHistoryModal(student) {
  if (!student) return
  historyStudent.value = student
  historySessions.value = []
  showHistoryModal.value = true
  loadHistoryForStudent(student.id)
}
async function loadHistoryForStudent(studentId) {
  historyLoading.value = true
  try {
    const res = await counselingApi.getByStudent(studentId, { per_page: 50 })
    historySessions.value = res.data?.data ?? res.data ?? []
  } catch {
    historySessions.value = []
  } finally {
    historyLoading.value = false
  }
}
function openAddModalForStudent(student) {
  openAddModal()
  form.value.student_id = student.id
}

watch(activeTab, (tab) => {
  if (tab === 'list') {
    loadStats()
    loadUpcoming()
  }
})

onMounted(async () => {
  const studentIdFromQuery = route.query.student_id
  if (studentIdFromQuery) {
    filters.value.student_id = String(studentIdFromQuery)
    activeTab.value = 'list'
  }
  await Promise.all([
    loadInstitutionAndSetFilterDefaults(),
    referenceStore.getAcademicYears(),
    loadSemesters(),
  ])
  loadSessions()
  loadTypes()
  loadStats()
  loadUpcoming()
  loadClasses()
  await loadStudents()
  if (studentIdFromQuery) {
    openAddModal()
    form.value.student_id = String(studentIdFromQuery)
  }
})
</script>

<style scoped>
.counseling-page {
  width: 100%;
  max-width: 100%;
  min-height: 100%;
  padding: 1.5rem;
  margin: 0 auto;
  background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 20%, #f1f5f9 100%);
}
.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1rem;
}
.toolbar .main-tabs { margin-bottom: 0; }
.page-header {
  margin-bottom: 1.5rem;
}
.header-content {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  flex-wrap: wrap;
}
.header-icon-wrap {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
}
.header-icon {
  flex-shrink: 0;
}
.page-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0 0 0.25rem 0;
}
.page-subtitle {
  color: #64748b;
  margin: 0;
  font-size: 0.9rem;
}
.header-actions {
  margin-left: auto;
}
.main-tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
  align-items: center;
}
.main-tab {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.65rem 1.1rem;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
  cursor: pointer;
  font-size: 0.9rem;
  font-weight: 500;
  transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}
.main-tab:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
}
.main-tab.active {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #fff;
  border-color: transparent;
  box-shadow: 0 2px 8px rgba(5, 150, 105, 0.35);
}
.filters-inline {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-bottom: 1rem;
}
.search-input {
  flex: 1;
  min-width: 200px;
  padding: 0.5rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
}
.filter-select {
  padding: 0.5rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  min-width: 140px;
}
.loading-state {
  text-align: center;
  padding: 2rem;
  color: #64748b;
}
.loading-spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #e2e8f0;
  border-top-color: #059669;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 0.75rem;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
.empty-state {
  text-align: center;
  padding: 2.5rem;
}
.empty-icon {
  margin-bottom: 1rem;
  color: #94a3b8;
}
.empty-title {
  font-size: 1.1rem;
  margin: 0 0 0.5rem 0;
}
.empty-desc {
  color: #64748b;
  margin: 0 0 1rem 0;
  font-size: 0.9rem;
}
.btn-empty-cta {
  margin-top: 0.5rem;
}
.table-container {
  overflow-x: auto;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
}
.data-table {
  width: 100%;
  border-collapse: collapse;
}
.data-table th,
.data-table td {
  padding: 0.75rem 1rem;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
}
.data-table th {
  font-weight: 600;
  color: #475569;
  background: #f8fafc;
}
.student-name {
  display: block;
}
.student-meta {
  font-size: 0.85rem;
  color: #64748b;
}
.summary-cell {
  max-width: 200px;
}
.status-badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 500;
}
.status-jadwal {
  background: #e0f2fe;
  color: #0369a1;
}
.status-berlangsung {
  background: #fef3c7;
  color: #b45309;
}
.status-selesai {
  background: #d1fae5;
  color: #047857;
}
.status-dibatalkan {
  background: #f1f5f9;
  color: #64748b;
}
.action-buttons {
  display: flex;
  gap: 0.5rem;
}
.btn-action {
  padding: 0.35rem 0.6rem;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  background: #fff;
  cursor: pointer;
  font-size: 0.85rem;
}
.btn-edit:hover {
  background: #eff6ff;
  border-color: #059669;
}
.btn-delete:hover {
  background: #fef2f2;
  border-color: #ef4444;
}
.pagination-bar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-top: 1rem;
  padding: 0.75rem 0;
}
.pagination-info {
  font-size: 0.9rem;
  color: #64748b;
}
.pagination-buttons {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.btn-page {
  padding: 0.4rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  cursor: pointer;
  font-size: 0.9rem;
}
.btn-page:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.types-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 1rem;
}
.type-card {
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 1rem;
  background: #fff;
}
.type-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}
.type-name {
  font-weight: 600;
}
.type-code {
  font-size: 0.85rem;
  color: #64748b;
}
.type-desc {
  font-size: 0.9rem;
  color: #475569;
  margin: 0 0 0.75rem 0;
}
.type-actions {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.5rem;
}
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}
.modal-content {
  background: #fff;
  border-radius: 12px;
  max-width: 480px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
}
.form-modal .modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #e2e8f0;
}
.form-modal .modal-header h3 {
  margin: 0;
  font-size: 1.1rem;
}
.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #64748b;
  padding: 0;
  line-height: 1;
}
.modal-body {
  padding: 1.25rem;
}
.form-group {
  margin-bottom: 1rem;
}
.form-group label {
  display: block;
  font-weight: 500;
  margin-bottom: 0.35rem;
  font-size: 0.9rem;
}
.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.95rem;
}
.form-group textarea {
  resize: vertical;
  min-height: 60px;
}
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
}
.error-message {
  color: #dc2626;
  font-size: 0.9rem;
  margin-bottom: 0.75rem;
}
.btn-primary {
  padding: 0.5rem 1rem;
  border-radius: 8px;
  border: none;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #fff;
  font-weight: 500;
  cursor: pointer;
}
.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}
.btn-secondary {
  padding: 0.5rem 1rem;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: #fff;
  cursor: pointer;
}
.btn-compact {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

/* Dashboard kecil */
.counseling-dashboard {
  margin-bottom: 1.5rem;
  padding: 1rem;
  background: #f8fafc;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}
.dashboard-cards {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1rem;
}
.stat-card {
  min-width: 140px;
  padding: 0.75rem 1rem;
  background: #fff;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 1px 2px rgba(0,0,0,0.04);
}
.stat-card .stat-label {
  display: block;
  font-size: 0.8rem;
  color: #64748b;
  margin-bottom: 0.25rem;
}
.stat-card .stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #0f172a;
}
.stat-card.stat-upcoming .stat-value { color: #0369a1; }
.dashboard-charts {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}
@media (max-width: 900px) {
  .dashboard-charts { grid-template-columns: 1fr; }
}
.chart-box {
  background: #fff;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  padding: 1rem;
}
.chart-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.75rem;
}
.chart-header h4 {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 600;
  color: #334155;
}
.chart-year-select {
  padding: 0.35rem 0.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 0.85rem;
}
.chart-wrap {
  height: 200px;
  position: relative;
}
.chart-wrap-pie { height: 220px; }

/* Jadwal mendatang */
.upcoming-block {
  margin-bottom: 1.5rem;
  padding: 1rem;
  background: #eff6ff;
  border-radius: 12px;
  border: 1px solid #bfdbfe;
}
.upcoming-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0 0 0.75rem 0;
  font-size: 0.95rem;
  font-weight: 600;
  color: #1e40af;
}
.upcoming-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.upcoming-item {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.75rem;
  padding: 0.5rem 0.75rem;
  background: #fff;
  border-radius: 8px;
  border: 1px solid #e0f2fe;
  font-size: 0.9rem;
}
.upcoming-date { font-weight: 500; color: #0369a1; min-width: 100px; }
.upcoming-student { font-weight: 500; }
.upcoming-type, .upcoming-counselor { color: #64748b; }
.upcoming-link {
  margin-left: auto;
  padding: 0.25rem 0.5rem;
  font-size: 0.8rem;
  border: 1px solid #059669;
  border-radius: 6px;
  background: #fff;
  color: #0369a1;
  cursor: pointer;
}
.upcoming-link:hover { background: #e0f2fe; }

/* Riwayat per siswa */
.btn-history-link {
  display: inline-block;
  margin-top: 0.25rem;
  padding: 0.2rem 0.5rem;
  font-size: 0.75rem;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  background: #f8fafc;
  color: #475569;
  cursor: pointer;
}
.btn-history-link:hover {
  background: #e0f2fe;
  border-color: #059669;
  color: #0369a1;
}
.history-modal .modal-content { max-width: 640px; }
.history-modal .modal-body { max-height: 70vh; overflow-y: auto; }
.loading-state.small, .empty-state.small { padding: 1rem; text-align: center; }
.empty-state.small p { margin: 0 0 0.5rem 0; }
.history-table-wrap .data-table.compact th,
.history-table-wrap .data-table.compact td { padding: 0.5rem 0.75rem; font-size: 0.9rem; }
.mt-1 { margin-top: 0.5rem; }
.btn-sm { padding: 0.4rem 0.75rem; font-size: 0.85rem; }
</style>
