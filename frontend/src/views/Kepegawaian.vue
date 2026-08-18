<template>
  <Layout>
    <div class="kepegawaian-page">
      <div class="toolbar">
        <div class="main-tabs">
          <button type="button" :class="['main-tab', { active: tab === 'cuti' }]" @click="switchTab('cuti')">Cuti</button>
          <button type="button" :class="['main-tab', { active: tab === 'sk' }]" @click="switchTab('sk')">SK</button>
          <button type="button" :class="['main-tab', { active: tab === 'jabatan' }]" @click="switchTab('jabatan')">Jabatan Struktural</button>
          <button type="button" :class="['main-tab', { active: tab === 'riwayat' }]" @click="switchTab('riwayat')">Riwayat</button>
        </div>
        <div class="header-actions">
          <button v-if="tab === 'cuti'" type="button" class="btn-primary btn-compact" @click="openLeaveModal()">Ajukan / Catat Cuti</button>
          <button v-if="tab === 'sk'" type="button" class="btn-primary btn-compact" @click="openDecreeModal()">Tambah SK</button>
          <button v-if="tab === 'jabatan'" type="button" class="btn-primary btn-compact" @click="openPositionModal()">Tetapkan Jabatan</button>
        </div>
      </div>

      <!-- CUTI -->
      <template v-if="tab === 'cuti'">
        <div class="filters filters-inline">
          <input v-model="leaveFilters.search" type="text" class="search-input" placeholder="Cari nama / NIP..." @input="debounceLoadLeaves" />
          <select v-model="leaveFilters.status" class="filter-select" @change="loadLeaves">
            <option value="">Semua Status</option>
            <option v-for="(label, key) in leaveStatuses" :key="key" :value="key">{{ label }}</option>
          </select>
          <select v-model="leaveFilters.leave_type" class="filter-select" @change="loadLeaves">
            <option value="">Semua Jenis</option>
            <option v-for="(label, key) in leaveTypes" :key="key" :value="key">{{ label }}</option>
          </select>
        </div>
        <div v-if="loadingLeaves" class="loading-wrap"><LoadingSkeleton type="table" :rows="6" :columns="6" /></div>
        <div v-else-if="!leaves.length" class="empty-state">
          <h3 class="empty-title">Belum ada pengajuan cuti</h3>
          <p class="empty-desc">Catat atau setujui pengajuan cuti pegawai.</p>
        </div>
        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Pegawai</th>
                <th>Jenis</th>
                <th>Periode</th>
                <th>Hari</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in leaves" :key="item.id">
                <td>
                  <div class="cell-title">{{ item.employee?.name }}</div>
                  <div class="cell-sub">{{ item.employee?.nip || item.employee?.nuptk || '-' }}</div>
                </td>
                <td>{{ item.leave_type_label }}</td>
                <td>{{ formatDate(item.start_date) }} – {{ formatDate(item.end_date) }}</td>
                <td>{{ item.duration_days }}</td>
                <td><span :class="['status-badge', `status-${item.status}`]">{{ item.status_label }}</span></td>
                <td class="actions">
                  <template v-if="item.status === 'pending'">
                    <button type="button" class="btn-sm btn-approve" @click="decideLeave(item, 'approve')">Setujui</button>
                    <button type="button" class="btn-sm btn-reject" @click="rejectLeave(item)">Tolak</button>
                  </template>
                  <button v-if="item.status === 'pending' || item.status === 'approved'" type="button" class="btn-sm btn-secondary" @click="cancelLeave(item)">Batal</button>
                  <span v-if="item.status !== 'pending' && item.status !== 'approved'" class="muted">—</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <!-- SK -->
      <template v-if="tab === 'sk'">
        <div class="filters filters-inline">
          <input v-model="decreeFilters.search" type="text" class="search-input" placeholder="Cari nomor / judul / nama..." @input="debounceLoadDecrees" />
          <select v-model="decreeFilters.decree_type" class="filter-select" @change="loadDecrees">
            <option value="">Semua Jenis SK</option>
            <option v-for="(label, key) in decreeTypes" :key="key" :value="key">{{ label }}</option>
          </select>
        </div>
        <div v-if="loadingDecrees" class="loading-wrap"><LoadingSkeleton type="table" :rows="6" :columns="6" /></div>
        <div v-else-if="!decrees.length" class="empty-state">
          <h3 class="empty-title">Belum ada SK</h3>
          <p class="empty-desc">Catat Surat Keputusan pegawai beserta berkas PDF.</p>
        </div>
        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Nomor / Judul</th>
                <th>Pegawai</th>
                <th>Jenis</th>
                <th>Tanggal</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in decrees" :key="item.id">
                <td>
                  <div class="cell-title">{{ item.number }}</div>
                  <div class="cell-sub">{{ item.title }}</div>
                </td>
                <td>{{ item.employee?.name }}</td>
                <td>{{ item.decree_type_label }}</td>
                <td>{{ formatDate(item.decree_date) }}</td>
                <td class="actions">
                  <button v-if="item.file_url" type="button" class="btn-sm btn-secondary" @click="downloadDecree(item)">Unduh</button>
                  <button type="button" class="btn-sm btn-secondary" @click="openDecreeModal(item)">Edit</button>
                  <button type="button" class="btn-sm btn-reject" @click="deleteDecree(item)">Hapus</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <!-- JABATAN -->
      <template v-if="tab === 'jabatan'">
        <div class="filters filters-inline">
          <input v-model="positionFilters.search" type="text" class="search-input" placeholder="Cari nama pegawai..." @input="debounceLoadPositions" />
          <select v-model="positionFilters.structural_position_id" class="filter-select" @change="loadPositions">
            <option value="">Semua Jabatan</option>
            <option v-for="p in positionMaster" :key="p.id" :value="p.id">{{ p.label }}</option>
          </select>
          <label class="check-inline">
            <input v-model="positionFilters.active_only" type="checkbox" @change="loadPositions" />
            Aktif saja
          </label>
        </div>
        <div v-if="loadingPositions" class="loading-wrap"><LoadingSkeleton type="table" :rows="6" :columns="5" /></div>
        <div v-else-if="!positions.length" class="empty-state">
          <h3 class="empty-title">Belum ada jabatan struktural</h3>
          <p class="empty-desc">Tetapkan pejabat struktural sekolah beserta periode menjabat.</p>
        </div>
        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Jabatan</th>
                <th>Pegawai</th>
                <th>Periode</th>
                <th>SK</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in positions" :key="item.id">
                <td>{{ item.position?.label }}</td>
                <td>{{ item.employee?.name }}</td>
                <td>
                  {{ formatDate(item.started_at) }}
                  – {{ item.ended_at ? formatDate(item.ended_at) : 'Sekarang' }}
                  <span v-if="item.is_active" class="status-badge status-approved">Aktif</span>
                </td>
                <td>{{ item.decree_number || item.decree?.number || '-' }}</td>
                <td class="actions">
                  <button v-if="item.is_active" type="button" class="btn-sm btn-secondary" @click="endPosition(item)">Akhiri</button>
                  <span v-else class="muted">Selesai</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <!-- RIWAYAT -->
      <template v-if="tab === 'riwayat'">
        <div class="filters filters-inline">
          <select v-model="historyEmployeeId" class="filter-select filter-select-wide" @change="loadHistory">
            <option value="">Pilih pegawai...</option>
            <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }}{{ e.nip ? ` (${e.nip})` : '' }}</option>
          </select>
        </div>
        <div v-if="!historyEmployeeId" class="empty-state">
          <h3 class="empty-title">Pilih pegawai</h3>
          <p class="empty-desc">Timeline cuti, SK, jabatan struktural, dan mutasi akan ditampilkan di sini.</p>
        </div>
        <div v-else-if="loadingHistory" class="loading-wrap"><LoadingSkeleton type="table" :rows="6" :columns="3" /></div>
        <div v-else>
          <div v-if="historyEmployee" class="history-header">
            <h3>{{ historyEmployee.name }}</h3>
            <p>{{ historyEmployee.employment_status || '-' }} · Status: {{ historyEmployee.status || '-' }}</p>
          </div>
          <div v-if="!timeline.length" class="empty-state">
            <h3 class="empty-title">Belum ada riwayat</h3>
          </div>
          <ul v-else class="timeline">
            <li v-for="(event, idx) in timeline" :key="`${event.type}-${event.ref_id}-${idx}`" class="timeline-item">
              <div class="timeline-date">{{ formatDate(event.date) }}<span v-if="event.end_date"> – {{ formatDate(event.end_date) }}</span></div>
              <div class="timeline-body">
                <span class="timeline-type">{{ event.type_label }}</span>
                <div class="cell-title">{{ event.title }}</div>
                <div v-if="event.subtitle" class="cell-sub">{{ event.subtitle }}</div>
              </div>
            </li>
          </ul>
        </div>
      </template>

      <!-- Leave Modal -->
      <div v-if="showLeaveModal" class="modal-overlay" @click.self="showLeaveModal = false">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>Catat / Ajukan Cuti</h3>
            <button type="button" class="btn-close" @click="showLeaveModal = false">×</button>
          </div>
          <form class="modal-body" @submit.prevent="submitLeave">
            <div class="form-group">
              <label>Pegawai *</label>
              <select v-model="leaveForm.employee_id" required>
                <option value="">Pilih...</option>
                <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }}</option>
              </select>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Jenis *</label>
                <select v-model="leaveForm.leave_type" required>
                  <option v-for="(label, key) in leaveTypes" :key="key" :value="key">{{ label }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>Status awal</label>
                <select v-model="leaveForm.status">
                  <option value="pending">Pending (perlu approval)</option>
                  <option value="approved">Langsung disetujui</option>
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Mulai *</label>
                <input v-model="leaveForm.start_date" type="date" required />
              </div>
              <div class="form-group">
                <label>Selesai *</label>
                <input v-model="leaveForm.end_date" type="date" required />
              </div>
            </div>
            <div class="form-group">
              <label>Alasan</label>
              <textarea v-model="leaveForm.reason" rows="3"></textarea>
            </div>
            <div class="form-group">
              <label>Lampiran PDF</label>
              <input type="file" accept="application/pdf" @change="onLeaveFile" />
            </div>
            <div v-if="formError" class="error-message">{{ formError }}</div>
            <div class="modal-footer">
              <button type="button" class="btn-secondary" @click="showLeaveModal = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Decree Modal -->
      <div v-if="showDecreeModal" class="modal-overlay" @click.self="showDecreeModal = false">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>{{ decreeForm.id ? 'Edit SK' : 'Tambah SK' }}</h3>
            <button type="button" class="btn-close" @click="showDecreeModal = false">×</button>
          </div>
          <form class="modal-body" @submit.prevent="submitDecree">
            <div class="form-group">
              <label>Pegawai *</label>
              <select v-model="decreeForm.employee_id" required>
                <option value="">Pilih...</option>
                <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }}</option>
              </select>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Jenis SK *</label>
                <select v-model="decreeForm.decree_type" required>
                  <option v-for="(label, key) in decreeTypes" :key="key" :value="key">{{ label }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>Nomor SK *</label>
                <input v-model="decreeForm.number" type="text" required />
              </div>
            </div>
            <div class="form-group">
              <label>Judul *</label>
              <input v-model="decreeForm.title" type="text" required />
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Tanggal SK *</label>
                <input v-model="decreeForm.decree_date" type="date" required />
              </div>
              <div class="form-group">
                <label>Berlaku</label>
                <input v-model="decreeForm.effective_date" type="date" />
              </div>
              <div class="form-group">
                <label>Berakhir</label>
                <input v-model="decreeForm.end_date" type="date" />
              </div>
            </div>
            <div class="form-group">
              <label>Keterangan</label>
              <textarea v-model="decreeForm.description" rows="2"></textarea>
            </div>
            <div class="form-group">
              <label>File PDF</label>
              <input type="file" accept="application/pdf" @change="onDecreeFile" />
            </div>
            <div v-if="formError" class="error-message">{{ formError }}</div>
            <div class="modal-footer">
              <button type="button" class="btn-secondary" @click="showDecreeModal = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Position Modal -->
      <div v-if="showPositionModal" class="modal-overlay" @click.self="showPositionModal = false">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>Tetapkan Jabatan Struktural</h3>
            <button type="button" class="btn-close" @click="showPositionModal = false">×</button>
          </div>
          <form class="modal-body" @submit.prevent="submitPosition">
            <div class="form-group">
              <label>Pegawai *</label>
              <select v-model="positionForm.employee_id" required @change="loadEmployeeDecrees">
                <option value="">Pilih...</option>
                <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Jabatan *</label>
              <select v-model="positionForm.structural_position_id" required>
                <option value="">Pilih...</option>
                <option v-for="p in positionMaster" :key="p.id" :value="p.id">{{ p.label }}</option>
              </select>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Mulai menjabat *</label>
                <input v-model="positionForm.started_at" type="date" required />
              </div>
              <div class="form-group">
                <label>Berakhir (opsional)</label>
                <input v-model="positionForm.ended_at" type="date" />
              </div>
            </div>
            <div class="form-group">
              <label>SK terkait</label>
              <select v-model="positionForm.employee_decree_id">
                <option value="">— Tidak ada —</option>
                <option v-for="d in employeeDecrees" :key="d.id" :value="d.id">{{ d.number }} — {{ d.title }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Nomor SK (teks)</label>
              <input v-model="positionForm.decree_number" type="text" placeholder="Jika belum terdaftar di modul SK" />
            </div>
            <div class="form-group">
              <label>Catatan</label>
              <textarea v-model="positionForm.notes" rows="2"></textarea>
            </div>
            <div v-if="formError" class="error-message">{{ formError }}</div>
            <div class="modal-footer">
              <button type="button" class="btn-secondary" @click="showPositionModal = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import Layout from '@/components/Layout.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { useToast } from '@/composables/useToast'
import { employeeApi } from '@/api/teacher'
import {
  employeeLeaveApi,
  employeeDecreeApi,
  structuralPositionApi,
  careerHistoryApi,
} from '@/api/kepegawaian'

const toast = useToast()

const tab = ref('cuti')
const saving = ref(false)
const formError = ref('')

const employees = ref([])
const leaveTypes = ref({})
const leaveStatuses = ref({})
const decreeTypes = ref({})
const positionMaster = ref([])

const leaves = ref([])
const decrees = ref([])
const positions = ref([])
const timeline = ref([])
const historyEmployee = ref(null)
const historyEmployeeId = ref('')
const employeeDecrees = ref([])

const loadingLeaves = ref(false)
const loadingDecrees = ref(false)
const loadingPositions = ref(false)
const loadingHistory = ref(false)

const leaveFilters = reactive({ search: '', status: 'pending', leave_type: '' })
const decreeFilters = reactive({ search: '', decree_type: '' })
const positionFilters = reactive({ search: '', structural_position_id: '', active_only: true })

const showLeaveModal = ref(false)
const showDecreeModal = ref(false)
const showPositionModal = ref(false)

const leaveForm = reactive({
  employee_id: '',
  leave_type: 'tahunan',
  start_date: '',
  end_date: '',
  reason: '',
  status: 'pending',
  attachment: null,
})

const decreeForm = reactive({
  id: null,
  employee_id: '',
  decree_type: 'jabatan',
  number: '',
  title: '',
  decree_date: '',
  effective_date: '',
  end_date: '',
  description: '',
  file: null,
})

const positionForm = reactive({
  employee_id: '',
  structural_position_id: '',
  employee_decree_id: '',
  started_at: '',
  ended_at: '',
  decree_number: '',
  notes: '',
})

let leaveTimer = null
let decreeTimer = null
let positionTimer = null

function formatDate(value) {
  if (!value) return '-'
  try {
    return new Date(value).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
  } catch {
    return value
  }
}

function switchTab(next) {
  tab.value = next
  if (next === 'cuti') loadLeaves()
  if (next === 'sk') loadDecrees()
  if (next === 'jabatan') loadPositions()
  if (next === 'riwayat' && historyEmployeeId.value) loadHistory()
}

async function loadEmployees() {
  try {
    const { data } = await employeeApi.getAll({ per_page: 200, status: 'Aktif' })
    employees.value = data.data || data || []
  } catch {
    employees.value = []
  }
}

async function loadMeta() {
  try {
    const [leaveMeta, decreeMeta, positionsRes] = await Promise.all([
      employeeLeaveApi.meta(),
      employeeDecreeApi.meta(),
      structuralPositionApi.listMaster(),
    ])
    leaveTypes.value = leaveMeta.data.leave_types || {}
    leaveStatuses.value = leaveMeta.data.statuses || {}
    decreeTypes.value = decreeMeta.data.decree_types || {}
    positionMaster.value = positionsRes.data.data || []
  } catch (e) {
    toast.error('Gagal memuat referensi kepegawaian')
  }
}

async function loadLeaves() {
  loadingLeaves.value = true
  try {
    const { data } = await employeeLeaveApi.getAll({
      ...leaveFilters,
      per_page: 50,
    })
    leaves.value = data.data || []
  } catch {
    toast.error('Gagal memuat data cuti')
  } finally {
    loadingLeaves.value = false
  }
}

function debounceLoadLeaves() {
  clearTimeout(leaveTimer)
  leaveTimer = setTimeout(loadLeaves, 350)
}

async function loadDecrees() {
  loadingDecrees.value = true
  try {
    const { data } = await employeeDecreeApi.getAll({
      ...decreeFilters,
      per_page: 50,
    })
    decrees.value = data.data || []
  } catch {
    toast.error('Gagal memuat data SK')
  } finally {
    loadingDecrees.value = false
  }
}

function debounceLoadDecrees() {
  clearTimeout(decreeTimer)
  decreeTimer = setTimeout(loadDecrees, 350)
}

async function loadPositions() {
  loadingPositions.value = true
  try {
    const params = {
      search: positionFilters.search || undefined,
      structural_position_id: positionFilters.structural_position_id || undefined,
      active_only: positionFilters.active_only ? 1 : undefined,
      per_page: 50,
    }
    const { data } = await structuralPositionApi.getAll(params)
    positions.value = data.data || []
  } catch {
    toast.error('Gagal memuat jabatan struktural')
  } finally {
    loadingPositions.value = false
  }
}

function debounceLoadPositions() {
  clearTimeout(positionTimer)
  positionTimer = setTimeout(loadPositions, 350)
}

async function loadHistory() {
  if (!historyEmployeeId.value) {
    timeline.value = []
    historyEmployee.value = null
    return
  }
  loadingHistory.value = true
  try {
    const { data } = await careerHistoryApi.get(historyEmployeeId.value)
    historyEmployee.value = data.data?.employee || null
    timeline.value = data.data?.timeline || []
  } catch {
    toast.error('Gagal memuat riwayat')
    timeline.value = []
  } finally {
    loadingHistory.value = false
  }
}

function openLeaveModal() {
  formError.value = ''
  Object.assign(leaveForm, {
    employee_id: '',
    leave_type: 'tahunan',
    start_date: '',
    end_date: '',
    reason: '',
    status: 'pending',
    attachment: null,
  })
  showLeaveModal.value = true
}

function onLeaveFile(e) {
  leaveForm.attachment = e.target.files?.[0] || null
}

async function submitLeave() {
  saving.value = true
  formError.value = ''
  try {
    const fd = new FormData()
    fd.append('employee_id', leaveForm.employee_id)
    fd.append('leave_type', leaveForm.leave_type)
    fd.append('start_date', leaveForm.start_date)
    fd.append('end_date', leaveForm.end_date)
    fd.append('status', leaveForm.status)
    if (leaveForm.reason) fd.append('reason', leaveForm.reason)
    if (leaveForm.attachment) fd.append('attachment', leaveForm.attachment)
    await employeeLeaveApi.create(fd)
    toast.success('Pengajuan cuti disimpan')
    showLeaveModal.value = false
    loadLeaves()
  } catch (e) {
    formError.value = e.response?.data?.message || Object.values(e.response?.data?.errors || {})[0]?.[0] || 'Gagal menyimpan'
  } finally {
    saving.value = false
  }
}

async function decideLeave(item, action) {
  try {
    await employeeLeaveApi.decide(item.id, { action })
    toast.success(action === 'approve' ? 'Cuti disetujui' : 'Cuti ditolak')
    loadLeaves()
  } catch (e) {
    toast.error(e.response?.data?.message || 'Gagal memproses')
  }
}

async function rejectLeave(item) {
  const reason = window.prompt('Alasan penolakan:')
  if (!reason) return
  try {
    await employeeLeaveApi.decide(item.id, { action: 'reject', rejection_reason: reason })
    toast.success('Cuti ditolak')
    loadLeaves()
  } catch (e) {
    toast.error(e.response?.data?.message || 'Gagal menolak')
  }
}

async function cancelLeave(item) {
  if (!window.confirm(`Batalkan cuti ${item.employee?.name}?`)) return
  try {
    await employeeLeaveApi.cancel(item.id)
    toast.success('Cuti dibatalkan')
    loadLeaves()
  } catch (e) {
    toast.error(e.response?.data?.message || 'Gagal membatalkan')
  }
}

function openDecreeModal(item = null) {
  formError.value = ''
  if (item) {
    Object.assign(decreeForm, {
      id: item.id,
      employee_id: item.employee_id,
      decree_type: item.decree_type,
      number: item.number,
      title: item.title,
      decree_date: item.decree_date,
      effective_date: item.effective_date || '',
      end_date: item.end_date || '',
      description: item.description || '',
      file: null,
    })
  } else {
    Object.assign(decreeForm, {
      id: null,
      employee_id: '',
      decree_type: 'jabatan',
      number: '',
      title: '',
      decree_date: '',
      effective_date: '',
      end_date: '',
      description: '',
      file: null,
    })
  }
  showDecreeModal.value = true
}

function onDecreeFile(e) {
  decreeForm.file = e.target.files?.[0] || null
}

async function submitDecree() {
  saving.value = true
  formError.value = ''
  try {
    const fd = new FormData()
    fd.append('employee_id', decreeForm.employee_id)
    fd.append('decree_type', decreeForm.decree_type)
    fd.append('number', decreeForm.number)
    fd.append('title', decreeForm.title)
    fd.append('decree_date', decreeForm.decree_date)
    if (decreeForm.effective_date) fd.append('effective_date', decreeForm.effective_date)
    if (decreeForm.end_date) fd.append('end_date', decreeForm.end_date)
    if (decreeForm.description) fd.append('description', decreeForm.description)
    if (decreeForm.file) fd.append('file', decreeForm.file)
    if (decreeForm.id) {
      await employeeDecreeApi.update(decreeForm.id, fd)
      toast.success('SK diperbarui')
    } else {
      await employeeDecreeApi.create(fd)
      toast.success('SK ditambahkan')
    }
    showDecreeModal.value = false
    loadDecrees()
  } catch (e) {
    formError.value = e.response?.data?.message || Object.values(e.response?.data?.errors || {})[0]?.[0] || 'Gagal menyimpan'
  } finally {
    saving.value = false
  }
}

async function downloadDecree(item) {
  try {
    const { data } = await employeeDecreeApi.download(item.id)
    const url = URL.createObjectURL(data)
    const a = document.createElement('a')
    a.href = url
    a.download = item.file_name || 'sk.pdf'
    a.click()
    URL.revokeObjectURL(url)
  } catch {
    toast.error('Gagal mengunduh file')
  }
}

async function deleteDecree(item) {
  if (!window.confirm(`Hapus SK ${item.number}?`)) return
  try {
    await employeeDecreeApi.delete(item.id)
    toast.success('SK dihapus')
    loadDecrees()
  } catch (e) {
    toast.error(e.response?.data?.message || 'Gagal menghapus')
  }
}

function openPositionModal() {
  formError.value = ''
  Object.assign(positionForm, {
    employee_id: '',
    structural_position_id: '',
    employee_decree_id: '',
    started_at: '',
    ended_at: '',
    decree_number: '',
    notes: '',
  })
  employeeDecrees.value = []
  showPositionModal.value = true
}

async function loadEmployeeDecrees() {
  if (!positionForm.employee_id) {
    employeeDecrees.value = []
    return
  }
  try {
    const { data } = await employeeDecreeApi.getAll({ employee_id: positionForm.employee_id, per_page: 50 })
    employeeDecrees.value = data.data || []
  } catch {
    employeeDecrees.value = []
  }
}

async function submitPosition() {
  saving.value = true
  formError.value = ''
  try {
    const payload = {
      employee_id: Number(positionForm.employee_id),
      structural_position_id: Number(positionForm.structural_position_id),
      started_at: positionForm.started_at,
      ended_at: positionForm.ended_at || null,
      decree_number: positionForm.decree_number || null,
      notes: positionForm.notes || null,
      employee_decree_id: positionForm.employee_decree_id ? Number(positionForm.employee_decree_id) : null,
    }
    await structuralPositionApi.assign(payload)
    toast.success('Jabatan ditetapkan')
    showPositionModal.value = false
    loadPositions()
  } catch (e) {
    formError.value = e.response?.data?.message || Object.values(e.response?.data?.errors || {})[0]?.[0] || 'Gagal menyimpan'
  } finally {
    saving.value = false
  }
}

async function endPosition(item) {
  const endedAt = window.prompt('Tanggal berakhir (YYYY-MM-DD):', new Date().toISOString().slice(0, 10))
  if (!endedAt) return
  try {
    await structuralPositionApi.end(item.id, { ended_at: endedAt })
    toast.success('Jabatan diakhiri')
    loadPositions()
  } catch (e) {
    toast.error(e.response?.data?.message || 'Gagal mengakhiri jabatan')
  }
}

onMounted(async () => {
  await Promise.all([loadEmployees(), loadMeta()])
  loadLeaves()
})
</script>

<style scoped>
.kepegawaian-page { display: flex; flex-direction: column; gap: 1rem; }
.toolbar { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 0.75rem; align-items: center; }
.main-tabs { display: flex; flex-wrap: wrap; gap: 0.35rem; }
.main-tab {
  border: 1px solid var(--border-color, #d1d5db);
  background: #fff;
  padding: 0.45rem 0.85rem;
  border-radius: 8px;
  cursor: pointer;
  font-size: 0.9rem;
}
.main-tab.active { background: var(--primary, #2563eb); color: #fff; border-color: transparent; }
.header-actions { display: flex; gap: 0.5rem; }
.filters-inline { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
.search-input, .filter-select {
  padding: 0.5rem 0.7rem;
  border: 1px solid var(--border-color, #d1d5db);
  border-radius: 8px;
  min-height: 40px;
}
.filter-select-wide { min-width: 280px; }
.check-inline { display: flex; align-items: center; gap: 0.35rem; font-size: 0.9rem; }
.table-container { overflow-x: auto; background: #fff; border-radius: 10px; border: 1px solid var(--border-color, #e5e7eb); }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td { padding: 0.75rem 0.9rem; border-bottom: 1px solid #eee; text-align: left; vertical-align: top; }
.data-table th { font-size: 0.8rem; text-transform: uppercase; color: #6b7280; background: #f9fafb; }
.cell-title { font-weight: 600; }
.cell-sub { font-size: 0.85rem; color: #6b7280; }
.actions { display: flex; flex-wrap: wrap; gap: 0.35rem; }
.btn-sm { padding: 0.3rem 0.55rem; border-radius: 6px; border: 1px solid #d1d5db; background: #fff; cursor: pointer; font-size: 0.8rem; min-height: 32px; }
.btn-approve { background: #059669; color: #fff; border-color: #059669; }
.btn-reject { background: #dc2626; color: #fff; border-color: #dc2626; }
.btn-primary, .btn-secondary { padding: 0.5rem 0.9rem; border-radius: 8px; border: none; cursor: pointer; min-height: 40px; }
.btn-primary { background: var(--primary, #2563eb); color: #fff; }
.btn-secondary { background: #f3f4f6; color: #111; border: 1px solid #d1d5db; }
.btn-compact { font-size: 0.9rem; }
.status-badge { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; }
.status-pending { background: #fef3c7; color: #92400e; }
.status-approved { background: #d1fae5; color: #065f46; }
.status-rejected { background: #fee2e2; color: #991b1b; }
.status-cancelled { background: #e5e7eb; color: #374151; }
.empty-state { text-align: center; padding: 2.5rem 1rem; color: #6b7280; }
.empty-title { margin: 0 0 0.35rem; color: #111; }
.muted { color: #9ca3af; }
.modal-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,.45); display: flex; align-items: center; justify-content: center;
  z-index: 1000; padding: 1rem;
}
.modal-content { background: #fff; border-radius: 12px; width: min(640px, 95%); max-height: 90vh; overflow: auto; }
.modal-header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.25rem; border-bottom: 1px solid #eee; }
.modal-body { padding: 1.25rem; display: flex; flex-direction: column; gap: 0.85rem; }
.modal-footer { display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 0.5rem; }
.btn-close { border: none; background: transparent; font-size: 1.5rem; cursor: pointer; }
.form-group { display: flex; flex-direction: column; gap: 0.35rem; }
.form-group label { font-size: 0.85rem; font-weight: 600; }
.form-group input, .form-group select, .form-group textarea {
  padding: 0.55rem 0.7rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 16px;
}
.form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.75rem; }
.error-message { color: #b91c1c; font-size: 0.9rem; }
.history-header { margin-bottom: 0.75rem; }
.history-header h3 { margin: 0; }
.history-header p { margin: 0.25rem 0 0; color: #6b7280; }
.timeline { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0.75rem; }
.timeline-item {
  display: grid; grid-template-columns: 140px 1fr; gap: 1rem;
  background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 0.9rem 1rem;
}
.timeline-date { font-size: 0.85rem; color: #6b7280; }
.timeline-type {
  display: inline-block; font-size: 0.7rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: 0.03em; color: #2563eb; margin-bottom: 0.2rem;
}
@media (max-width: 768px) {
  .timeline-item { grid-template-columns: 1fr; }
}
</style>
