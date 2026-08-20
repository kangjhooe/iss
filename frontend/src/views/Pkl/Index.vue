<template>
  <Layout>
    <div class="pkl-page">
      <header class="page-header">
        <div class="header-content">
          <div class="header-text">
            <h1 class="page-title">
              PKL / Prakerin
            </h1>
            <p class="page-subtitle">Kelola periode, penempatan siswa, dan monitoring industri</p>
          </div>
          <div class="header-actions">
            <button
              v-if="tab === 'placements'"
              type="button"
              class="btn-secondary btn-header"
              @click="exportPlacements"
              :disabled="exporting"
            >
              {{ exporting ? 'Mengekspor...' : 'Export CSV' }}
            </button>
            <button
              v-if="tab === 'placements'"
              type="button"
              class="btn-secondary btn-header"
              @click="openBulkModal"
            >
              Bulk Assign
            </button>
            <button type="button" class="btn-primary btn-header" @click="openCreate">
              {{ tab === 'periods' ? 'Tambah Periode' : 'Tambah Penempatan' }}
            </button>
          </div>
        </div>
      </header>

      <div class="tabs">
        <button type="button" :class="{ active: tab === 'periods' }" @click="tab = 'periods'; loadPeriods()">Periode</button>
        <button type="button" :class="{ active: tab === 'placements' }" @click="tab = 'placements'; loadPlacements()">Penempatan</button>
      </div>

      <!-- PERIODS -->
      <template v-if="tab === 'periods'">
        <div class="toolbar">
          <input v-model="periodFilters.search" class="search-input" placeholder="Cari periode..." @input="debouncePeriods" />
          <select v-model="periodFilters.status" class="filter-select" @change="loadPeriods">
            <option value="">Semua Status</option>
            <option value="draft">Draft</option>
            <option value="berlangsung">Berlangsung</option>
            <option value="selesai">Selesai</option>
          </select>
        </div>
        <div v-if="loadingPeriods" class="loading-wrap"><p>Memuat periode...</p></div>
        <div v-else-if="!periods.length" class="empty-state">
          <h3>Belum ada periode PKL</h3>
          <button type="button" class="btn-primary" @click="openPeriodModal()">Tambah Periode</button>
        </div>
        <table v-else class="data-table">
          <thead>
            <tr>
              <th>Nama</th>
              <th>Tahun Ajaran</th>
              <th>Tanggal</th>
              <th>Penempatan</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in periods" :key="p.id">
              <td><strong>{{ p.name }}</strong></td>
              <td>{{ p.academic_year?.name || '—' }}</td>
              <td>{{ formatDate(p.start_date) }} – {{ formatDate(p.end_date) }}</td>
              <td>{{ p.placements_count ?? 0 }}</td>
              <td><span class="status-chip">{{ p.status }}</span></td>
              <td>
                <TableAction kind="edit" @click="openPeriodModal(p)" />
                <button type="button" class="btn-link" @click="tab = 'placements'; placementFilters.pkl_period_id = String(p.id); loadPlacements()">Penempatan</button>
                <TableAction kind="delete" @click="removePeriod(p)" />
              </td>
            </tr>
          </tbody>
        </table>
      </template>

      <!-- PLACEMENTS -->
      <template v-else>
        <div class="toolbar">
          <select v-model="placementFilters.pkl_period_id" class="filter-select" @change="loadPlacements">
            <option value="">Semua Periode</option>
            <option v-for="p in periodOptions" :key="p.id" :value="String(p.id)">{{ p.name }}</option>
          </select>
          <input v-model="placementFilters.search" class="search-input" placeholder="Cari siswa..." @input="debouncePlacements" />
          <select v-model="placementFilters.status" class="filter-select" @change="loadPlacements">
            <option value="">Semua Status</option>
            <option value="draft">Draft</option>
            <option value="berlangsung">Berlangsung</option>
            <option value="selesai">Selesai</option>
            <option value="batal">Batal</option>
          </select>
        </div>
        <div v-if="loadingPlacements" class="loading-wrap"><p>Memuat penempatan...</p></div>
        <div v-else-if="!placements.length" class="empty-state">
          <h3>Belum ada penempatan</h3>
          <button type="button" class="btn-primary" @click="openPlacementModal()">Tambah Penempatan</button>
        </div>
        <table v-else class="data-table">
          <thead>
            <tr>
              <th>Siswa</th>
              <th>Mitra</th>
              <th>Pembimbing</th>
              <th>Periode</th>
              <th>Status</th>
              <th>Nilai</th>
              <th>Monitoring</th>
              <th>Jurnal</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in placements" :key="item.id">
              <td>
                <strong>{{ item.student?.name }}</strong>
                <div class="cell-sub">{{ item.student?.class?.name || '—' }}</div>
              </td>
              <td>{{ item.industry_partner?.name || '—' }}</td>
              <td>{{ item.supervisor?.name || '—' }}</td>
              <td>{{ item.period?.name || '—' }}</td>
              <td><span class="status-chip">{{ item.status }}</span></td>
              <td>{{ item.score != null ? item.score : '—' }}</td>
              <td>{{ item.monitoring_logs_count ?? 0 }}</td>
              <td>{{ item.journals_count ?? 0 }}</td>
              <td>
                <button type="button" class="btn-link" @click="openMonitoring(item)">Monitor</button>
                <button type="button" class="btn-link" @click="openJournals(item)">Jurnal</button>
                <TableAction kind="edit" @click="openPlacementModal(item)" />
                <TableAction kind="delete" @click="removePlacement(item)" />
              </td>
            </tr>
          </tbody>
        </table>
      </template>

      <!-- Period modal -->
      <div v-if="showPeriodModal" class="modal-overlay" @click.self="showPeriodModal = false">
        <div class="modal-card">
          <h2>{{ periodForm.id ? 'Edit Periode' : 'Tambah Periode' }}</h2>
          <form @submit.prevent="savePeriod">
            <label>Nama *</label>
            <input v-model="periodForm.name" required />
            <label>Tahun Ajaran</label>
            <select v-model="periodForm.academic_year_id">
              <option value="">—</option>
              <option v-for="ay in academicYears" :key="ay.id" :value="ay.id">{{ ay.name }}</option>
            </select>
            <div class="form-row">
              <div><label>Mulai</label><input v-model="periodForm.start_date" type="date" /></div>
              <div><label>Selesai</label><input v-model="periodForm.end_date" type="date" /></div>
            </div>
            <label>Status</label>
            <select v-model="periodForm.status">
              <option value="draft">Draft</option>
              <option value="berlangsung">Berlangsung</option>
              <option value="selesai">Selesai</option>
            </select>
            <label>Catatan</label>
            <textarea v-model="periodForm.notes" rows="2"></textarea>
            <p v-if="formError" class="form-error">{{ formError }}</p>
            <div class="modal-actions">
              <button type="button" class="btn-secondary" @click="showPeriodModal = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="saving">Simpan</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Placement modal -->
      <div v-if="showPlacementModal" class="modal-overlay" @click.self="showPlacementModal = false">
        <div class="modal-card">
          <h2>{{ placementForm.id ? 'Edit Penempatan' : 'Tambah Penempatan' }}</h2>
          <form @submit.prevent="savePlacement">
            <label>Periode *</label>
            <select v-model="placementForm.pkl_period_id" required :disabled="!!placementForm.id">
              <option value="">Pilih periode</option>
              <option v-for="p in periodOptions" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>
            <label v-if="!placementForm.id">Cari siswa *</label>
            <input v-if="!placementForm.id" v-model="studentSearch" placeholder="Ketik nama/NIS..." @input="searchStudents" />
            <select v-if="!placementForm.id" v-model="placementForm.student_id" required>
              <option value="">Pilih siswa</option>
              <option v-for="s in studentOptions" :key="s.id" :value="s.id">{{ s.name }} — {{ s.class?.name || s.nis || '' }}</option>
            </select>
            <p v-else class="readonly">Siswa: {{ editingStudentName }}</p>
            <label>Mitra DU/DI *</label>
            <select v-model="placementForm.industry_partner_id" required>
              <option value="">Pilih mitra</option>
              <option v-for="m in partners" :key="m.id" :value="m.id">{{ m.name }}</option>
            </select>
            <label>Pembimbing sekolah</label>
            <select v-model="placementForm.supervisor_employee_id">
              <option value="">—</option>
              <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option>
            </select>
            <label>Pembimbing industri</label>
            <input v-model="placementForm.industry_supervisor_name" />
            <div class="form-row">
              <div><label>Mulai</label><input v-model="placementForm.start_date" type="date" /></div>
              <div><label>Selesai</label><input v-model="placementForm.end_date" type="date" /></div>
            </div>
            <label>Status</label>
            <select v-model="placementForm.status">
              <option value="draft">Draft</option>
              <option value="berlangsung">Berlangsung</option>
              <option value="selesai">Selesai</option>
              <option value="batal">Batal</option>
            </select>
            <div class="form-row">
              <div>
                <label>Nilai (0–100)</label>
                <input v-model.number="placementForm.score" type="number" min="0" max="100" step="0.01" />
              </div>
            </div>
            <label>Catatan penilaian</label>
            <textarea v-model="placementForm.assessment_notes" rows="2"></textarea>
            <label>Catatan</label>
            <textarea v-model="placementForm.notes" rows="2"></textarea>
            <p v-if="formError" class="form-error">{{ formError }}</p>
            <div class="modal-actions">
              <button type="button" class="btn-secondary" @click="showPlacementModal = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="saving">Simpan</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Bulk assign modal -->
      <div v-if="showBulkModal" class="modal-overlay" @click.self="showBulkModal = false">
        <div class="modal-card wide">
          <h2>Bulk Assign Penempatan</h2>
          <p class="cell-sub">Pilih periode & mitra, lalu centang beberapa siswa sekaligus.</p>
          <form @submit.prevent="saveBulk">
            <label>Periode *</label>
            <select v-model="bulkForm.pkl_period_id" required>
              <option value="">Pilih periode</option>
              <option v-for="p in periodOptions" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>
            <label>Mitra DU/DI *</label>
            <select v-model="bulkForm.industry_partner_id" required>
              <option value="">Pilih mitra</option>
              <option v-for="m in partners" :key="m.id" :value="m.id">{{ m.name }}</option>
            </select>
            <label>Status</label>
            <select v-model="bulkForm.status">
              <option value="draft">Draft</option>
              <option value="berlangsung">Berlangsung</option>
            </select>
            <label>Cari siswa</label>
            <input v-model="bulkStudentSearch" placeholder="Ketik nama/NIS..." @input="searchBulkStudents" />
            <div class="bulk-list">
              <label v-for="s in bulkStudentOptions" :key="s.id" class="bulk-item">
                <input v-model="bulkForm.student_ids" type="checkbox" :value="s.id" />
                <span>{{ s.name }} <small>{{ s.class?.name || s.nis || '' }}</small></span>
              </label>
              <p v-if="!bulkStudentOptions.length" class="empty-inline">Ketik untuk mencari siswa aktif.</p>
            </div>
            <p class="hint">Terpilih: {{ bulkForm.student_ids.length }} siswa</p>
            <p v-if="formError" class="form-error">{{ formError }}</p>
            <div class="modal-actions">
              <button type="button" class="btn-secondary" @click="showBulkModal = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="saving || !bulkForm.student_ids.length">Simpan</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Monitoring modal -->
      <div v-if="showMonitoring" class="modal-overlay" @click.self="showMonitoring = false">
        <div class="modal-card wide">
          <h2>Monitoring — {{ monitoringPlacement?.student?.name }}</h2>
          <p class="cell-sub">{{ monitoringPlacement?.industry_partner?.name }}</p>
          <form class="monitor-form" @submit.prevent="addMonitoring">
            <input v-model="monitorForm.visit_date" type="date" required />
            <select v-model="monitorForm.method">
              <option value="kunjungan">Kunjungan</option>
              <option value="telepon">Telepon</option>
              <option value="online">Online</option>
            </select>
            <input v-model="monitorForm.notes" placeholder="Catatan..." />
            <button type="submit" class="btn-primary" :disabled="saving">Tambah</button>
          </form>
          <ul class="log-list">
            <li v-for="log in monitoringLogs" :key="log.id">
              <div>
                <strong>{{ formatDate(log.visit_date) }}</strong> · {{ log.method }}
                <span v-if="log.logged_by"> · {{ log.logged_by.name }}</span>
                <p>{{ log.notes || '—' }}</p>
              </div>
              <TableAction kind="delete" @click="removeMonitoring(log)" />
            </li>
            <li v-if="!monitoringLogs.length" class="empty-inline">Belum ada catatan monitoring.</li>
          </ul>
          <div class="modal-actions">
            <button type="button" class="btn-secondary" @click="showMonitoring = false">Tutup</button>
          </div>
        </div>
      </div>

      <!-- Journals modal (staff review) -->
      <div v-if="showJournals" class="modal-overlay" @click.self="showJournals = false">
        <div class="modal-card wide">
          <h2>Jurnal siswa — {{ journalPlacement?.student?.name }}</h2>
          <p class="cell-sub">{{ journalPlacement?.industry_partner?.name }}</p>
          <ul class="log-list">
            <li v-for="j in placementJournals" :key="j.id" class="journal-item">
              <div>
                <strong>{{ formatDate(j.journal_date) }}</strong>
                <span v-if="j.hours != null"> · {{ j.hours }} jam</span>
                · {{ j.status === 'draft' ? 'Draft' : 'Terkirim' }}
                <p>{{ j.activities || '—' }}</p>
                <label class="journal-note-label">Catatan pembimbing</label>
                <div class="journal-note-row">
                  <input v-model="j._notes" placeholder="Opsional..." />
                  <button type="button" class="btn-primary" :disabled="saving" @click="saveJournalNotes(j)">Simpan</button>
                </div>
              </div>
            </li>
            <li v-if="!placementJournals.length" class="empty-inline">Belum ada jurnal dari siswa.</li>
          </ul>
          <div class="modal-actions">
            <button type="button" class="btn-secondary" @click="showJournals = false">Tutup</button>
          </div>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import Layout from '@/components/Layout.vue'
import TableAction from '@/components/TableAction.vue'
import { pklApi } from '@/api/pkl'
import { industryPartnersApi } from '@/api/industryPartners'
import { studentApi } from '@/api/student'
import { teacherApi } from '@/api/teacher'
import { useReferenceDataStore } from '@/stores/referenceData'
import '@/assets/module-page.css'

const referenceStore = useReferenceDataStore()
const tab = ref('periods')
const periods = ref([])
const periodOptions = ref([])
const placements = ref([])
const partners = ref([])
const teachers = ref([])
const studentOptions = ref([])
const academicYears = ref([])
const loadingPeriods = ref(false)
const loadingPlacements = ref(false)
const saving = ref(false)
const exporting = ref(false)
const formError = ref('')
const showPeriodModal = ref(false)
const showPlacementModal = ref(false)
const showBulkModal = ref(false)
const showMonitoring = ref(false)
const monitoringPlacement = ref(null)
const monitoringLogs = ref([])
const showJournals = ref(false)
const journalPlacement = ref(null)
const placementJournals = ref([])
const editingStudentName = ref('')
const studentSearch = ref('')
const bulkStudentSearch = ref('')
const bulkStudentOptions = ref([])

const periodFilters = reactive({ search: '', status: '' })
const placementFilters = reactive({ search: '', status: '', pkl_period_id: '' })
const periodForm = reactive({ id: null, name: '', academic_year_id: '', start_date: '', end_date: '', status: 'draft', notes: '' })
const placementForm = reactive({
  id: null, pkl_period_id: '', student_id: '', industry_partner_id: '', supervisor_employee_id: '',
  industry_supervisor_name: '', start_date: '', end_date: '', status: 'draft', score: null, assessment_notes: '', notes: '',
})
const bulkForm = reactive({
  pkl_period_id: '', industry_partner_id: '', status: 'draft', student_ids: [],
})
const monitorForm = reactive({ visit_date: '', method: 'kunjungan', notes: '' })

function formatDate(v) {
  if (!v) return '—'
  const d = String(v).slice(0, 10)
  const [y, m, day] = d.split('-')
  return `${day}/${m}/${y}`
}

function openCreate() {
  if (tab.value === 'periods') openPeriodModal()
  else openPlacementModal()
}

let t1, t2, t3, t4
function debouncePeriods() { clearTimeout(t1); t1 = setTimeout(loadPeriods, 300) }
function debouncePlacements() { clearTimeout(t2); t2 = setTimeout(loadPlacements, 300) }
function searchStudents() { clearTimeout(t3); t3 = setTimeout(loadStudentOptions, 300) }
function searchBulkStudents() { clearTimeout(t4); t4 = setTimeout(loadBulkStudentOptions, 300) }

async function loadPeriods() {
  loadingPeriods.value = true
  try {
    const res = await pklApi.getPeriods({
      search: periodFilters.search || undefined,
      status: periodFilters.status || undefined,
      per_page: 50,
    })
    periods.value = res.data?.data || []
  } catch { periods.value = [] }
  finally { loadingPeriods.value = false }
}

async function loadPeriodOptions() {
  try {
    const res = await pklApi.getPeriods({ per_page: 100 })
    periodOptions.value = res.data?.data || []
  } catch { periodOptions.value = [] }
}

async function loadPlacements() {
  loadingPlacements.value = true
  try {
    const res = await pklApi.getPlacements({
      search: placementFilters.search || undefined,
      status: placementFilters.status || undefined,
      pkl_period_id: placementFilters.pkl_period_id || undefined,
      per_page: 50,
    })
    placements.value = res.data?.data || []
  } catch { placements.value = [] }
  finally { loadingPlacements.value = false }
}

async function loadPartners() {
  try {
    const res = await industryPartnersApi.getAll({ status: 'Aktif', per_page: 100 })
    partners.value = res.data?.data || []
  } catch { partners.value = [] }
}

async function loadTeachers() {
  try {
    const res = await teacherApi.getAll({ per_page: 100, type: 'Guru' })
    teachers.value = res.data?.data || res.data || []
  } catch { teachers.value = [] }
}

async function loadStudentOptions() {
  try {
    const res = await studentApi.getAll({ search: studentSearch.value || undefined, status: 'Aktif', per_page: 20 })
    studentOptions.value = res.data?.data || []
  } catch { studentOptions.value = [] }
}

async function loadBulkStudentOptions() {
  try {
    const res = await studentApi.getAll({ search: bulkStudentSearch.value || undefined, status: 'Aktif', per_page: 40 })
    bulkStudentOptions.value = res.data?.data || []
  } catch { bulkStudentOptions.value = [] }
}

function downloadBlob(blob, filename) {
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = filename
  link.click()
  URL.revokeObjectURL(link.href)
}

async function exportPlacements() {
  exporting.value = true
  try {
    const res = await pklApi.exportPlacements({
      search: placementFilters.search || undefined,
      status: placementFilters.status || undefined,
      pkl_period_id: placementFilters.pkl_period_id || undefined,
    })
    downloadBlob(res.data, `pkl-penempatan-${new Date().toISOString().slice(0, 10)}.csv`)
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal mengekspor penempatan.')
  } finally {
    exporting.value = false
  }
}

function openBulkModal() {
  formError.value = ''
  Object.assign(bulkForm, {
    pkl_period_id: placementFilters.pkl_period_id || '',
    industry_partner_id: '',
    status: 'draft',
    student_ids: [],
  })
  bulkStudentSearch.value = ''
  bulkStudentOptions.value = []
  loadBulkStudentOptions()
  showBulkModal.value = true
}

async function saveBulk() {
  saving.value = true
  formError.value = ''
  try {
    const res = await pklApi.bulkCreatePlacements({
      pkl_period_id: bulkForm.pkl_period_id,
      industry_partner_id: bulkForm.industry_partner_id,
      student_ids: bulkForm.student_ids,
      status: bulkForm.status,
    })
    alert(res.data?.message || 'Bulk assign selesai.')
    showBulkModal.value = false
    tab.value = 'placements'
    await loadPlacements()
  } catch (e) {
    formError.value = e.response?.data?.message || 'Gagal bulk assign.'
  } finally {
    saving.value = false
  }
}

function openPeriodModal(item = null) {
  formError.value = ''
  Object.assign(periodForm, {
    id: item?.id || null,
    name: item?.name || '',
    academic_year_id: item?.academic_year_id || '',
    start_date: item?.start_date ? String(item.start_date).slice(0, 10) : '',
    end_date: item?.end_date ? String(item.end_date).slice(0, 10) : '',
    status: item?.status || 'draft',
    notes: item?.notes || '',
  })
  showPeriodModal.value = true
}

async function savePeriod() {
  saving.value = true
  formError.value = ''
  const payload = { ...periodForm, academic_year_id: periodForm.academic_year_id || null }
  delete payload.id
  try {
    if (periodForm.id) await pklApi.updatePeriod(periodForm.id, payload)
    else await pklApi.createPeriod(payload)
    showPeriodModal.value = false
    await Promise.all([loadPeriods(), loadPeriodOptions()])
  } catch (e) {
    formError.value = e.response?.data?.message || 'Gagal menyimpan periode.'
  } finally { saving.value = false }
}

async function removePeriod(p) {
  if (!confirm(`Hapus periode "${p.name}"?`)) return
  try {
    await pklApi.deletePeriod(p.id)
    await Promise.all([loadPeriods(), loadPeriodOptions()])
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menghapus.')
  }
}

function openPlacementModal(item = null) {
  formError.value = ''
  editingStudentName.value = item?.student?.name || ''
  Object.assign(placementForm, {
    id: item?.id || null,
    pkl_period_id: item?.pkl_period_id || placementFilters.pkl_period_id || '',
    student_id: item?.student_id || '',
    industry_partner_id: item?.industry_partner_id || '',
    supervisor_employee_id: item?.supervisor_employee_id || '',
    industry_supervisor_name: item?.industry_supervisor_name || '',
    start_date: item?.start_date ? String(item.start_date).slice(0, 10) : '',
    end_date: item?.end_date ? String(item.end_date).slice(0, 10) : '',
    status: item?.status || 'draft',
    score: item?.score ?? null,
    assessment_notes: item?.assessment_notes || '',
    notes: item?.notes || '',
  })
  if (!item) loadStudentOptions()
  showPlacementModal.value = true
}

async function savePlacement() {
  saving.value = true
  formError.value = ''
  const payload = {
    ...placementForm,
    supervisor_employee_id: placementForm.supervisor_employee_id || null,
    score: placementForm.score === '' || placementForm.score == null ? null : placementForm.score,
  }
  delete payload.id
  if (placementForm.id) {
    delete payload.pkl_period_id
    delete payload.student_id
  }
  try {
    if (placementForm.id) await pklApi.updatePlacement(placementForm.id, payload)
    else await pklApi.createPlacement(payload)
    showPlacementModal.value = false
    await loadPlacements()
  } catch (e) {
    formError.value = e.response?.data?.message || 'Gagal menyimpan penempatan.'
  } finally { saving.value = false }
}

async function removePlacement(item) {
  if (!confirm(`Hapus penempatan ${item.student?.name}?`)) return
  try {
    await pklApi.deletePlacement(item.id)
    await loadPlacements()
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menghapus.')
  }
}

async function openMonitoring(item) {
  monitoringPlacement.value = item
  monitorForm.visit_date = new Date().toISOString().slice(0, 10)
  monitorForm.method = 'kunjungan'
  monitorForm.notes = ''
  showMonitoring.value = true
  try {
    const res = await pklApi.getMonitoringLogs(item.id)
    monitoringLogs.value = res.data?.data || []
  } catch { monitoringLogs.value = [] }
}

async function addMonitoring() {
  saving.value = true
  try {
    await pklApi.createMonitoringLog(monitoringPlacement.value.id, { ...monitorForm })
    const res = await pklApi.getMonitoringLogs(monitoringPlacement.value.id)
    monitoringLogs.value = res.data?.data || []
    monitorForm.notes = ''
    await loadPlacements()
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menambah monitoring.')
  } finally { saving.value = false }
}

async function removeMonitoring(log) {
  if (!confirm('Hapus catatan ini?')) return
  try {
    await pklApi.deleteMonitoringLog(monitoringPlacement.value.id, log.id)
    monitoringLogs.value = monitoringLogs.value.filter((l) => l.id !== log.id)
    await loadPlacements()
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menghapus.')
  }
}

async function openJournals(item) {
  journalPlacement.value = item
  showJournals.value = true
  try {
    const res = await pklApi.getJournals(item.id)
    placementJournals.value = (res.data?.data || []).map((j) => ({
      ...j,
      _notes: j.supervisor_notes || '',
    }))
  } catch {
    placementJournals.value = []
  }
}

async function saveJournalNotes(j) {
  saving.value = true
  try {
    await pklApi.updateJournalNotes(journalPlacement.value.id, j.id, {
      supervisor_notes: j._notes || null,
    })
    j.supervisor_notes = j._notes || null
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menyimpan catatan.')
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  academicYears.value = await referenceStore.getAcademicYears()
  await Promise.all([loadPeriods(), loadPeriodOptions(), loadPartners(), loadTeachers()])
})
</script>

<style scoped>
.beta-badge {
  display: inline-block; margin-left: 0.4rem; padding: 0.1rem 0.45rem;
  font-size: 0.7rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase;
  vertical-align: middle; color: #92400e; background: #fef3c7; border-radius: 999px;
}
.header-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.tabs { display: flex; gap: 0.5rem; margin-bottom: 1rem; }
.bulk-list {
  max-height: 240px; overflow: auto; border: 1px solid #e5e7eb; border-radius: 8px;
  padding: 0.5rem; margin-top: 0.5rem;
}
.bulk-item { display: flex; gap: 0.5rem; align-items: center; padding: 0.35rem 0; font-weight: 400; }
.bulk-item small { color: #6b7280; margin-left: 0.35rem; }
.hint { font-size: 0.8rem; color: #6b7280; margin-top: 0.5rem; }
.tabs button {
  border: 1px solid #d1d5db; background: #fff; padding: 0.45rem 0.9rem; border-radius: 999px; cursor: pointer;
}
.tabs button.active { background: #1e3a5f; color: #fff; border-color: #1e3a5f; }
.toolbar { display: flex; gap: 0.75rem; margin-bottom: 1rem; flex-wrap: wrap; }
.search-input, .filter-select { padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; }
.search-input { min-width: 200px; flex: 1; }
.cell-sub { font-size: 0.8rem; color: #6b7280; }
.status-chip { padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.75rem; background: #e5e7eb; text-transform: capitalize; }
.btn-link { background: none; border: none; color: #2563eb; cursor: pointer; margin-right: 0.4rem; }
.btn-link.danger { color: #dc2626; }
.empty-state { text-align: center; padding: 3rem 1rem; background: #fff; border-radius: 12px; }
.modal-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,.4); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 1rem;
}
.modal-card {
  background: #fff; border-radius: 12px; padding: 1.25rem; width: min(540px, 100%); max-height: 90vh; overflow: auto;
}
.modal-card.wide { width: min(640px, 100%); }
.modal-card label { display: block; margin: 0.75rem 0 0.25rem; font-size: 0.85rem; font-weight: 600; }
.modal-card input, .modal-card select, .modal-card textarea {
  width: 100%; padding: 0.5rem 0.65rem; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box;
}
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
.modal-actions { display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem; }
.form-error { color: #dc2626; font-size: 0.875rem; }
.readonly { margin: 0.5rem 0; font-weight: 600; }
.monitor-form { display: grid; grid-template-columns: 140px 120px 1fr auto; gap: 0.5rem; margin: 1rem 0; }
.log-list { list-style: none; padding: 0; margin: 0; }
.log-list li { display: flex; justify-content: space-between; gap: 1rem; padding: 0.75rem 0; border-bottom: 1px solid #e5e7eb; }
.empty-inline { color: #6b7280; padding: 1rem 0; }
.journal-item { flex-direction: column; align-items: stretch; }
.journal-note-label { display: block; margin-top: 0.5rem; font-size: 0.8rem; font-weight: 600; }
.journal-note-row { display: flex; gap: 0.5rem; margin-top: 0.25rem; }
.journal-note-row input { flex: 1; padding: 0.45rem 0.65rem; border: 1px solid #d1d5db; border-radius: 8px; }
@media (max-width: 720px) {
  .form-row, .monitor-form, .journal-note-row { grid-template-columns: 1fr; flex-direction: column; }
}
</style>
