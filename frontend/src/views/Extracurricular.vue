<template>
  <Layout>
    <div class="extracurricular-page">
      <div class="toolbar">
        <div class="filters filters-inline">
          <input
            v-model="filters.search"
            type="text"
            placeholder="Cari nama ekskul..."
            class="search-input"
            @input="debounceLoad"
          />
          <select v-model="filters.status" @change="loadList" class="filter-select">
            <option value="">Semua Status</option>
            <option value="Aktif">Aktif</option>
            <option value="Nonaktif">Nonaktif</option>
          </select>
          <select v-model="filters.semester_id" @change="loadList" class="filter-select">
            <option value="">Semua Semester</option>
            <option v-for="s in semesters" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
        </div>
        <div class="toolbar-actions">
          <button v-if="canManageAll" @click="openAddModal" class="btn-primary btn-compact">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Tambah Ekstrakurikuler</span>
          </button>
        </div>
      </div>

      <div v-if="listError && !loading" class="error-state">
        <p class="error-text">{{ listError }}</p>
        <button @click="loadList(1)" class="btn-primary">Coba lagi</button>
      </div>

      <div v-else-if="loading" class="loading-wrap">
        <div class="loading-spinner"></div>
        <p>Memuat data ekstrakurikuler...</p>
      </div>

      <div v-else class="table-container">
        <div v-if="list.length === 0" class="empty-state">
          <div class="empty-icon">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
              <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="1.5"/>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
          </div>
          <h3 class="empty-title">Belum ada ekstrakurikuler</h3>
          <p class="empty-desc">Tambahkan ekskul pertama untuk mulai mengelola peserta dan jadwal.</p>
          <button v-if="canManageAll" type="button" @click="openAddModal" class="btn-primary btn-compact">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Tambah Ekstrakurikuler</span>
          </button>
        </div>

        <div v-else class="table-scroll">
          <table class="data-table">
            <thead>
              <tr>
                <th class="col-nama">Nama</th>
                <th class="col-pembina">Pembina</th>
                <th class="col-jadwal">Jadwal</th>
                <th class="col-lokasi">Lokasi</th>
                <th class="col-center col-peserta">Peserta</th>
                <th class="col-center col-status">Status</th>
                <th class="col-aksi">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in list" :key="item.id">
                <td class="col-nama">
                  <button type="button" class="cell-link" @click="$router.push(`/extracurricular/${item.id}`)">
                    <span class="cell-main">{{ displayValue(item.name) }}</span>
                    <span v-if="item.is_pramuka" class="pramuka-badge">Pramuka</span>
                    <span v-if="item.description" class="cell-sub">{{ truncate(item.description, 48) }}</span>
                  </button>
                </td>
                <td class="col-pembina">
                  <span :class="{ 'cell-muted': !item.supervisor?.name }">
                    {{ item.supervisor?.name || '—' }}
                  </span>
                </td>
                <td class="col-jadwal">
                  <template v-if="dayNames(item).length">
                    <div class="day-chips">
                      <span v-for="d in dayNames(item)" :key="d" class="day-chip">{{ d }}</span>
                    </div>
                    <div v-if="item.start_time || item.end_time" class="cell-sub cell-time">
                      {{ formatTime(item.start_time) }}–{{ formatTime(item.end_time) }}
                    </div>
                  </template>
                  <span v-else class="cell-muted">—</span>
                </td>
                <td class="col-lokasi">
                  <template v-if="item.is_outdoor">
                    <span class="cell-main">Di luar ruangan</span>
                    <span v-if="item.location_note" class="cell-sub">{{ item.location_note }}</span>
                  </template>
                  <span v-else-if="item.room?.name" class="cell-main">{{ item.room.name }}</span>
                  <span v-else class="cell-muted">—</span>
                </td>
                <td class="col-center col-peserta">
                  <button
                    type="button"
                    class="link-peserta"
                    @click="$router.push(`/extracurricular/${item.id}`)"
                    title="Kelola peserta, pertemuan, nilai, laporan"
                  >
                    {{ item.participants_count ?? 0 }}<template v-if="item.capacity"> / {{ item.capacity }}</template>
                  </button>
                </td>
                <td class="col-center col-status">
                  <span :class="['status-badge', item.status === 'Aktif' ? 'status-active' : 'status-inactive']">
                    {{ item.status || '—' }}
                  </span>
                </td>
                <td class="col-aksi">
                  <div class="action-buttons">
                    <TableAction kind="manage" :to="`/extracurricular/${item.id}`" />
                    <button type="button" @click="openEditModal(item)" class="btn-action btn-edit" title="Edit">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M18.5 2.50023C18.8978 2.10243 19.4374 1.87891 20 1.87891C20.5626 1.87891 21.1022 2.10243 21.5 2.50023C21.8978 2.89804 22.1213 3.43762 22.1213 4.00023C22.1213 4.56284 21.8978 5.10243 21.5 5.50023L12 15.0002L8 16.0002L9 12.0002L18.5 2.50023Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>
                    <button v-if="canManageAll" type="button" @click="confirmDelete(item)" class="btn-action btn-delete" title="Hapus">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 6H5H21M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="list.length > 0 && pagination && pagination.last_page > 1" class="pagination-bar">
          <span class="pagination-info">
            Halaman {{ pagination.current_page }} dari {{ pagination.last_page }} ({{ pagination.total }} data)
          </span>
          <div class="pagination-buttons">
            <button type="button" class="btn-page" :disabled="pagination.current_page <= 1" @click="loadList(pagination.current_page - 1)">Sebelumnya</button>
            <button type="button" class="btn-page" :disabled="pagination.current_page >= pagination.last_page" @click="loadList(pagination.current_page + 1)">Selanjutnya</button>
          </div>
        </div>
      </div>

      <!-- Modal: Tambah/Edit Ekstrakurikuler -->
      <div v-if="showFormModal" class="modal-overlay" @click="closeFormModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>{{ editingItem ? 'Edit Ekstrakurikuler' : 'Tambah Ekstrakurikuler' }}</h3>
            <button @click="closeFormModal" class="modal-close">×</button>
          </div>
          <form @submit.prevent="saveForm" class="modal-body">
            <div v-if="formError" class="error-message">{{ formError }}</div>
            <div class="form-group">
              <label>Nama <span class="required">*</span></label>
              <input v-model="form.name" type="text" required placeholder="Contoh: Pramuka, PMR" class="form-input" />
            </div>
            <div class="form-group">
              <label>Deskripsi</label>
              <textarea v-model="form.description" rows="2" placeholder="Deskripsi singkat" class="form-input"></textarea>
            </div>
            <div class="form-group">
              <label>Pembina (Guru Penanggung Jawab)</label>
              <select v-model="form.supervisor_employee_id" class="form-input">
                <option value="">-- Pilih Guru --</option>
                <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }} {{ t.nip ? `(${t.nip})` : '' }}</option>
              </select>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Kapasitas (opsional)</label>
                <input v-model.number="form.capacity" type="number" min="1" placeholder="Jumlah maksimal" class="form-input" />
              </div>
              <div class="form-group">
                <label>Status</label>
                <select v-model="form.status" class="form-input">
                  <option value="Aktif">Aktif</option>
                  <option value="Nonaktif">Nonaktif</option>
                </select>
              </div>
              <div class="form-group">
                <label class="checkbox-label">
                  <input v-model="form.is_pramuka" type="checkbox" />
                  Modul Pramuka (flag khusus)
                </label>
              </div>
            </div>
            <div class="form-group">
              <label>Hari</label>
              <div class="day-checkboxes">
                <label v-for="(label, val) in days" :key="val" class="day-check">
                  <input
                    type="checkbox"
                    :value="Number(val)"
                    :checked="(form.days_of_week || []).includes(Number(val))"
                    @change="toggleDay(Number(val), $event.target.checked)"
                  />
                  <span>{{ label }}</span>
                </label>
              </div>
              <p class="form-hint">Bisa pilih lebih dari satu hari.</p>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Jam Mulai</label>
                <input v-model="form.start_time" type="time" class="form-input" />
              </div>
              <div class="form-group">
                <label>Jam Selesai</label>
                <input v-model="form.end_time" type="time" class="form-input" />
              </div>
            </div>
            <div class="form-group">
              <label>Lokasi</label>
              <select v-model="locationSelect" class="form-input">
                <option value="">-- Pilih --</option>
                <option value="outdoor">Di luar ruangan</option>
                <option v-for="r in rooms" :key="r.id" :value="'room:' + r.id">{{ r.name }} {{ r.code ? `(${r.code})` : '' }}</option>
              </select>
            </div>
            <div v-if="locationSelect === 'outdoor'" class="form-group">
              <label>Keterangan lokasi (opsional)</label>
              <input v-model="form.location_note" type="text" placeholder="Contoh: Lapangan basket, Halaman sekolah" class="form-input" />
            </div>
            <div class="modal-footer">
              <button type="button" @click="closeFormModal" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="saving" class="btn-primary">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal: Kelola Peserta -->
      <div v-if="showParticipantsModal" class="modal-overlay" @click="closeParticipantsModal">
        <div class="modal-content modal-large" @click.stop>
          <div class="modal-header">
            <h3>{{ showAddPanel ? 'Tambah Peserta' : 'Peserta' }} – {{ selectedEkskul?.name }}</h3>
            <button @click="closeParticipantsModal" class="modal-close">×</button>
          </div>
          <div class="modal-body">
            <!-- Daftar peserta -->
            <template v-if="!showAddPanel">
              <div class="participants-toolbar">
                <button type="button" @click="exportParticipants" :disabled="exportingParticipants" class="btn-secondary btn-sm">
                  {{ exportingParticipants ? 'Mengekspor...' : 'Export CSV' }}
                </button>
                <button type="button" @click="openAddPanel" class="btn-primary btn-sm">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  Tambah Peserta
                </button>
              </div>
              <div v-if="participantsLoading" class="loading-inline"><div class="loading-spinner"></div> Memuat peserta...</div>
              <div v-else-if="participants.length === 0" class="empty-inline">Belum ada peserta.</div>
              <div v-else class="participants-list">
                <table class="data-table data-table-sm">
                  <thead>
                    <tr>
                      <th class="col-no">No</th>
                      <th>Nama</th>
                      <th>NIS / NISN</th>
                      <th>Kelas</th>
                      <th class="col-aksi">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(p, idx) in participants" :key="p.id">
                      <td class="col-no">{{ idx + 1 }}</td>
                      <td>{{ p.student?.name || '—' }}</td>
                      <td>{{ p.student?.nis || '—' }} / {{ p.student?.nisn || '—' }}</td>
                      <td>{{ p.student?.class?.name || '—' }}</td>
                      <td class="col-aksi">
                        <TableAction kind="delete" title="Keluarkan" @click="confirmRemoveParticipant(p)" />
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </template>

            <!-- Tambah peserta: pilih kelas → pilih siswa -->
            <template v-else>
              <div class="form-group">
                <label>Kelas <span class="required">*</span></label>
                <select v-model="availableClassId" class="form-input" @change="onClassChange">
                  <option value="">-- Pilih kelas --</option>
                  <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
              </div>
              <div v-if="availableClassId" class="form-group">
                <input
                  v-model="availableStudentSearch"
                  type="text"
                  placeholder="Cari nama, NIS, NISN..."
                  class="form-input"
                  @input="debounceLoadAvailable"
                />
              </div>
              <div v-if="!availableClassId" class="empty-inline">Pilih kelas terlebih dahulu untuk menampilkan siswa.</div>
              <div v-else-if="availableLoading" class="loading-inline">Memuat siswa...</div>
              <div v-else-if="availableError" class="error-message">{{ availableError }}</div>
              <div v-else-if="availableStudents.length === 0" class="empty-inline">Tidak ada siswa tersedia di kelas ini.</div>
              <div v-else class="available-list">
                <div class="available-list-header">
                  <label class="day-check">
                    <input type="checkbox" :checked="allSelected" @change="toggleAllAvailable" />
                    <span>Pilih semua ({{ availableStudents.length }})</span>
                  </label>
                </div>
                <table class="data-table data-table-sm">
                  <thead>
                    <tr>
                      <th></th>
                      <th>Nama</th>
                      <th>NIS / NISN</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="s in availableStudents"
                      :key="s.id"
                      class="student-row"
                      @click="toggleStudent(s.id)"
                    >
                      <td><input type="checkbox" :checked="selectedStudentIds.includes(s.id)" @click.stop @change="toggleStudent(s.id)" /></td>
                      <td>{{ s.name }}</td>
                      <td>{{ s.nis || '-' }} / {{ s.nisn || '-' }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div v-if="addParticipantError" class="error-message" style="margin-top: 12px;">{{ addParticipantError }}</div>
              <div class="modal-footer" style="margin-top: 16px;">
                <button type="button" @click="closeAddPanel" class="btn-secondary">Kembali</button>
                <button
                  type="button"
                  @click="submitAddParticipants"
                  :disabled="savingParticipants || selectedStudentIds.length === 0"
                  class="btn-primary"
                >
                  {{ savingParticipants ? 'Menambah...' : 'Tambah ' + selectedStudentIds.length + ' peserta' }}
                </button>
              </div>
            </template>
          </div>
        </div>
      </div>

      <!-- Modal: Edit Status Peserta -->
      <div v-if="showEditEnrollmentModal" class="modal-overlay" @click="closeEditEnrollmentModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>Edit Status Peserta – {{ editingEnrollment?.student?.name }}</h3>
            <button @click="closeEditEnrollmentModal" class="modal-close">×</button>
          </div>
          <form @submit.prevent="submitEditEnrollment" class="modal-body">
            <div v-if="editEnrollmentError" class="error-message">{{ editEnrollmentError }}</div>
            <div class="form-group">
              <label>Status</label>
              <select v-model="enrollmentForm.status" class="form-input">
                <option value="aktif">Aktif</option>
                <option value="keluar">Keluar</option>
                <option value="lulus">Lulus</option>
              </select>
            </div>
            <div class="form-group" v-if="['keluar','lulus'].includes(enrollmentForm.status)">
              <label>Tanggal keluar</label>
              <input v-model="enrollmentForm.left_at" type="date" class="form-input" />
            </div>
            <div class="form-group">
              <label>Catatan</label>
              <textarea v-model="enrollmentForm.notes" rows="2" class="form-input" placeholder="Opsional"></textarea>
            </div>
            <div class="modal-footer">
              <button type="button" @click="closeEditEnrollmentModal" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="savingEnrollment" class="btn-primary">{{ savingEnrollment ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>

      <ConfirmDialog
        :show="confirmDialog.show"
        :title="confirmDialog.title"
        :message="confirmDialog.message"
        :warning="confirmDialog.warning"
        :loading="confirmDialog.loading"
        @confirm="handleConfirm"
        @cancel="handleCancel"
        @update:show="confirmDialog.show = $event"
      />
    </div>
  </Layout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import Layout from '@/components/Layout.vue'
import TableAction from '@/components/TableAction.vue'
import { extracurricularApi } from '@/api/extracurricular'
import { teacherApi } from '@/api/teacher'
import { semesterApi } from '@/api/semester'
import { facilityApi } from '@/api/facility'
import { useToast } from '@/composables/useToast'
import { useConfirmDelete } from '@/composables/useConfirmDelete'
import ConfirmDialog from '@/components/ConfirmDialog.vue'

const toast = useToast()
const { confirmDialog, showConfirm, handleConfirm, handleCancel, setLoading: setDeleteLoading } = useConfirmDelete()

const DAYS = { 1: 'Senin', 2: 'Selasa', 3: 'Rabu', 4: 'Kamis', 5: 'Jumat', 6: 'Sabtu' }
const days = DAYS

const list = ref([])
const loading = ref(true)
const listError = ref('')
const pagination = ref(null)
const canManageAll = ref(true)
const filters = ref({ search: '', status: '', semester_id: '' })

const showFormModal = ref(false)
const showParticipantsModal = ref(false)
const showAddPanel = ref(false)
const editingItem = ref(null)
const selectedEkskul = ref(null)
const saving = ref(false)
const formError = ref('')

const teachers = ref([])
const semesters = ref([])
const classes = ref([])
const rooms = ref([])
const locationSelect = ref('')

const form = ref({
  name: '',
  description: '',
  supervisor_employee_id: null,
  capacity: null,
  status: 'Aktif',
  is_pramuka: false,
  days_of_week: [],
  start_time: '',
  end_time: '',
  room_id: null,
  is_outdoor: false,
  location_note: '',
})

const participants = ref([])
const participantsLoading = ref(false)
const exportingParticipants = ref(false)
const availableStudents = ref([])
const availableLoading = ref(false)
const availableError = ref('')
const availableClassId = ref('')
const availableStudentSearch = ref('')
const selectedStudentIds = ref([])
const savingParticipants = ref(false)
const addParticipantError = ref('')

const showEditEnrollmentModal = ref(false)
const editingEnrollment = ref(null)
const savingEnrollment = ref(false)
const editEnrollmentError = ref('')
const enrollmentForm = ref({
  status: 'aktif',
  left_at: '',
  notes: '',
})

function dayLabel(dayOfWeek) {
  return DAYS[dayOfWeek] || ''
}

function dayNames(item) {
  if (Array.isArray(item.day_labels) && item.day_labels.length) {
    return item.day_labels
  }
  if (Array.isArray(item.days_of_week) && item.days_of_week.length) {
    return item.days_of_week.map(dayLabel).filter(Boolean)
  }
  return []
}

function toggleDay(day, checked) {
  const current = [...(form.value.days_of_week || [])]
  if (checked) {
    if (!current.includes(day)) current.push(day)
  } else {
    const idx = current.indexOf(day)
    if (idx >= 0) current.splice(idx, 1)
  }
  current.sort((a, b) => a - b)
  form.value.days_of_week = current
}

function applyLocationSelect(value) {
  if (value === 'outdoor') {
    form.value.is_outdoor = true
    form.value.room_id = null
  } else if (typeof value === 'string' && value.startsWith('room:')) {
    form.value.is_outdoor = false
    form.value.room_id = Number(value.slice(5))
    form.value.location_note = ''
  } else {
    form.value.is_outdoor = false
    form.value.room_id = null
    form.value.location_note = ''
  }
}

watch(locationSelect, (val) => applyLocationSelect(val))

function statusLabel(s) {
  const labels = { aktif: 'Aktif', keluar: 'Keluar', lulus: 'Lulus' }
  return labels[s] || s
}

function displayValue(v) {
  if (v === null || v === undefined || v === '') return '—'
  return String(v).trim() || '—'
}

function truncate(str, len) {
  if (!str) return ''
  return str.length <= len ? str : str.slice(0, len) + '...'
}

function formatTime(t) {
  if (!t) return '?'
  // Accept "HH:mm" or "HH:mm:ss"
  return String(t).slice(0, 5)
}

async function loadList(page = 1, { silent = false } = {}) {
  if (!silent) {
    loading.value = true
    listError.value = ''
  }
  try {
    const params = { page, per_page: 15 }
    if (filters.value.search) params.search = filters.value.search
    if (filters.value.status) params.status = filters.value.status
    if (filters.value.semester_id) params.semester_id = filters.value.semester_id
    const res = await extracurricularApi.getAll(params)
    list.value = res.data.data || []
    pagination.value = res.data.meta || null
    if (res.data.meta_access && typeof res.data.meta_access.can_manage_all === 'boolean') {
      canManageAll.value = res.data.meta_access.can_manage_all
    }
  } catch (e) {
    listError.value = e.formattedMessage || 'Gagal memuat data ekstrakurikuler.'
    if (!silent) toast.error('Gagal', listError.value)
  } finally {
    if (!silent) loading.value = false
  }
}

let debounceTimer
function debounceLoad() {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => loadList(1), 400)
}

function openAddModal() {
  editingItem.value = null
  form.value = {
    name: '',
    description: '',
    supervisor_employee_id: null,
    capacity: null,
    status: 'Aktif',
    is_pramuka: false,
    days_of_week: [],
    start_time: '',
    end_time: '',
    room_id: null,
    is_outdoor: false,
    location_note: '',
  }
  locationSelect.value = ''
  formError.value = ''
  showFormModal.value = true
}

function openEditModal(item) {
  editingItem.value = item
  const daysOfWeek = Array.isArray(item.days_of_week)
    ? item.days_of_week.map(Number)
    : (item.day_of_week != null ? [Number(item.day_of_week)] : [])
  form.value = {
    name: item.name || '',
    description: item.description || '',
    supervisor_employee_id: item.supervisor_employee_id || null,
    capacity: item.capacity || null,
    status: item.status || 'Aktif',
    is_pramuka: !!item.is_pramuka,
    days_of_week: daysOfWeek,
    start_time: item.start_time || '',
    end_time: item.end_time || '',
    room_id: item.room_id || null,
    is_outdoor: !!item.is_outdoor,
    location_note: item.location_note || '',
  }
  if (item.is_outdoor) {
    locationSelect.value = 'outdoor'
  } else if (item.room_id) {
    locationSelect.value = 'room:' + item.room_id
  } else {
    locationSelect.value = ''
  }
  formError.value = ''
  showFormModal.value = true
}

function closeFormModal() {
  showFormModal.value = false
  editingItem.value = null
  formError.value = ''
}

async function saveForm() {
  saving.value = true
  formError.value = ''
  try {
    applyLocationSelect(locationSelect.value)
    const normalizeTime = (t) => {
      if (!t) return null
      return String(t).slice(0, 5)
    }
    const payload = {
      name: form.value.name,
      description: form.value.description || null,
      supervisor_employee_id: form.value.supervisor_employee_id || null,
      capacity: form.value.capacity === '' || form.value.capacity == null ? null : form.value.capacity,
      status: form.value.status || 'Aktif',
      days_of_week: Array.isArray(form.value.days_of_week) ? form.value.days_of_week : [],
      start_time: normalizeTime(form.value.start_time),
      end_time: normalizeTime(form.value.end_time),
      is_outdoor: !!form.value.is_outdoor,
      room_id: form.value.is_outdoor ? null : (form.value.room_id || null),
      location_note: form.value.is_outdoor ? (form.value.location_note || null) : null,
    }
    if (editingItem.value) {
      await extracurricularApi.update(editingItem.value.id, payload)
      toast.success('Berhasil', 'Ekstrakurikuler berhasil diperbarui')
    } else {
      await extracurricularApi.create(payload)
      toast.success('Berhasil', 'Ekstrakurikuler berhasil ditambahkan')
    }
    closeFormModal()
    loadList()
  } catch (e) {
    formError.value = e.formattedMessage || (e.response?.data?.message) || 'Gagal menyimpan.'
    toast.error('Gagal', formError.value)
  } finally {
    saving.value = false
  }
}

function confirmDelete(item) {
  showConfirm({
    title: 'Hapus Ekstrakurikuler',
    message: 'Yakin menghapus "' + item.name + '"?',
    warning: 'Jika masih ada peserta, penghapusan akan ditolak. Keluarkan peserta terlebih dahulu.',
  }).then(async (ok) => {
    if (!ok) return
    setDeleteLoading(true)
    try {
      await extracurricularApi.delete(item.id)
      toast.success('Berhasil', 'Ekstrakurikuler dihapus')
      loadList()
    } catch (e) {
      toast.error('Gagal', e.formattedMessage || e.response?.data?.message || 'Gagal menghapus')
    } finally {
      setDeleteLoading(false)
    }
  })
}

function openParticipantsModal(item) {
  selectedEkskul.value = item
  participants.value = []
  showAddPanel.value = false
  resetAddPanel()
  showParticipantsModal.value = true
  loadParticipants()
}

async function exportParticipants() {
  if (!selectedEkskul.value) return
  exportingParticipants.value = true
  try {
    const res = await extracurricularApi.exportParticipants(selectedEkskul.value.id)
    const blob = new Blob([res.data], { type: 'text/csv;charset=utf-8;' })
    const link = document.createElement('a')
    link.href = URL.createObjectURL(blob)
    link.download = `peserta-${selectedEkskul.value.name.replace(/\s+/g, '-')}-${new Date().toISOString().slice(0, 10)}.csv`
    link.click()
    URL.revokeObjectURL(link.href)
    toast.success('Berhasil', 'Export peserta berhasil diunduh')
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || 'Gagal mengekspor peserta')
  } finally {
    exportingParticipants.value = false
  }
}

function openEditEnrollmentModal(p) {
  editingEnrollment.value = p
  enrollmentForm.value = {
    status: p.status || 'aktif',
    left_at: p.left_at || '',
    notes: p.notes || '',
  }
  editEnrollmentError.value = ''
  showEditEnrollmentModal.value = true
}

function closeEditEnrollmentModal() {
  showEditEnrollmentModal.value = false
  editingEnrollment.value = null
  editEnrollmentError.value = ''
}

async function submitEditEnrollment() {
  if (!selectedEkskul.value || !editingEnrollment.value) return
  savingEnrollment.value = true
  editEnrollmentError.value = ''
  try {
    const payload = { ...enrollmentForm.value }
    if (!payload.left_at) delete payload.left_at
    if (!payload.notes) payload.notes = null
    await extracurricularApi.updateEnrollment(selectedEkskul.value.id, editingEnrollment.value.id, payload)
    toast.success('Berhasil', 'Status peserta berhasil diperbarui')
    closeEditEnrollmentModal()
    loadParticipants()
    loadList()
  } catch (e) {
    editEnrollmentError.value = e.formattedMessage || e.response?.data?.message || 'Gagal memperbarui'
    toast.error('Gagal', editEnrollmentError.value)
  } finally {
    savingEnrollment.value = false
  }
}

async function loadParticipants({ silent = false } = {}) {
  if (!selectedEkskul.value) return
  participantsLoading.value = true
  try {
    const res = await extracurricularApi.getStudents(selectedEkskul.value.id)
    const raw = res.data?.data ?? res.data
    participants.value = Array.isArray(raw) ? raw : (Array.isArray(raw?.data) ? raw.data : [])
  } catch (e) {
    participants.value = []
    if (!silent) toast.error('Gagal', e.formattedMessage || 'Gagal memuat peserta')
  } finally {
    participantsLoading.value = false
  }
}

function closeParticipantsModal() {
  showParticipantsModal.value = false
  selectedEkskul.value = null
  participants.value = []
  showAddPanel.value = false
  resetAddPanel()
}

function resetAddPanel() {
  selectedStudentIds.value = []
  availableStudents.value = []
  availableClassId.value = ''
  availableStudentSearch.value = ''
  availableError.value = ''
  addParticipantError.value = ''
}

function openAddPanel() {
  resetAddPanel()
  showAddPanel.value = true
  if (!classes.value.length) loadClasses()
}

function closeAddPanel() {
  showAddPanel.value = false
  resetAddPanel()
}

function onClassChange() {
  selectedStudentIds.value = []
  availableStudentSearch.value = ''
  availableStudents.value = []
  availableError.value = ''
  if (availableClassId.value) loadAvailableStudents()
}

async function loadAvailableStudents() {
  if (!selectedEkskul.value || !availableClassId.value) {
    availableStudents.value = []
    return
  }
  availableLoading.value = true
  availableError.value = ''
  try {
    const params = {
      per_page: 200,
      class_id: availableClassId.value,
    }
    if (availableStudentSearch.value) params.search = availableStudentSearch.value
    const res = await extracurricularApi.getAvailableStudents(selectedEkskul.value.id, params)
    const data = res.data?.data ?? res.data
    availableStudents.value = Array.isArray(data) ? data : []
  } catch (e) {
    availableStudents.value = []
    availableError.value = e.formattedMessage || e.response?.data?.message || 'Gagal memuat daftar siswa'
  } finally {
    availableLoading.value = false
  }
}

let availableDebounce
function debounceLoadAvailable() {
  if (availableDebounce) clearTimeout(availableDebounce)
  availableDebounce = setTimeout(() => loadAvailableStudents(), 400)
}

const allSelected = computed(() => {
  return availableStudents.value.length > 0 && selectedStudentIds.value.length === availableStudents.value.length
})

function toggleAllAvailable() {
  if (allSelected.value) {
    selectedStudentIds.value = []
  } else {
    selectedStudentIds.value = availableStudents.value.map((s) => s.id)
  }
}

function toggleStudent(id) {
  const i = selectedStudentIds.value.indexOf(id)
  if (i > -1) selectedStudentIds.value.splice(i, 1)
  else selectedStudentIds.value.push(id)
}

async function submitAddParticipants() {
  if (savingParticipants.value) return
  if (!selectedEkskul.value || selectedStudentIds.value.length === 0) {
    toast.error('Gagal', 'Pilih minimal satu siswa')
    return
  }
  savingParticipants.value = true
  addParticipantError.value = ''
  try {
    const res = await extracurricularApi.addStudents(selectedEkskul.value.id, {
      student_ids: selectedStudentIds.value.map((sid) => Number(sid)).filter((sid) => Number.isInteger(sid) && sid > 0),
    })
    toast.success('Berhasil', res.data?.message || 'Peserta berhasil ditambahkan')
    closeAddPanel()
    await loadParticipants({ silent: true })
    await loadList(pagination.value?.current_page || 1, { silent: true })
  } catch (e) {
    addParticipantError.value = e.formattedMessage || e.response?.data?.message || 'Gagal menambahkan peserta'
    toast.error('Gagal', addParticipantError.value)
  } finally {
    savingParticipants.value = false
  }
}

async function confirmRemoveParticipant(p) {
  showConfirm({
    title: 'Keluarkan Peserta',
    message: 'Keluarkan ' + (p.student?.name || 'siswa ini') + ' dari ekskul?',
    warning: '',
  }).then(async (ok) => {
    if (!ok) return
    setDeleteLoading(true)
    try {
      await extracurricularApi.removeStudent(selectedEkskul.value.id, p.student_id)
      toast.success('Berhasil', 'Peserta dikeluarkan')
      await loadParticipants({ silent: true })
      await loadList(pagination.value?.current_page || 1, { silent: true })
    } catch (e) {
      toast.error('Gagal', e.formattedMessage || e.response?.data?.message || 'Gagal mengeluarkan')
    } finally {
      setDeleteLoading(false)
    }
  })
}

async function loadTeachers() {
  try {
    // Jangan filter status ketat: beberapa institusi pakai status berbeda
    const res = await teacherApi.getAll({ per_page: 200 })
    teachers.value = (res.data.data || []).filter((t) => !t.status || t.status === 'Aktif')
  } catch (_) {
    teachers.value = []
  }
}

async function loadSemesters() {
  try {
    const res = await semesterApi.getAll({ per_page: 100 })
    semesters.value = res.data.data || []
  } catch (_) {
    semesters.value = []
  }
}

async function loadClasses() {
  try {
    const res = await extracurricularApi.classesLite()
    classes.value = res.data.data || []
  } catch (e) {
    classes.value = []
    toast.error('Gagal', e.formattedMessage || 'Gagal memuat daftar kelas')
  }
}

async function loadRooms() {
  try {
    const res = await facilityApi.getRooms({})
    rooms.value = res.data.data || []
  } catch (_) {
    rooms.value = []
  }
}

onMounted(async () => {
  await Promise.all([loadTeachers(), loadSemesters(), loadClasses(), loadRooms(), loadList()])
})
</script>

<style scoped>
.extracurricular-page {
  width: 100%;
  max-width: 100%;
  min-height: 100%;
  padding: 0;
  background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 20%, #f1f5f9 100%);
}

.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 24px;
}

.toolbar .filters {
  margin-bottom: 0;
  flex: 1;
  min-width: 200px;
}

.toolbar-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.btn-compact {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 14px;
  font-size: 13px;
}

.filters {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.search-input,
.filter-select {
  padding: 10px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  background: #fff;
  flex: 1;
  min-width: 160px;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.search-input:focus,
.filter-select:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
}

.search-input {
  max-width: 280px;
}

.table-container {
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid #e5e7eb;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.table-scroll {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.data-table {
  width: 100%;
  min-width: 820px;
  border-collapse: collapse;
}

.data-table thead {
  background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
}

.data-table th {
  padding: 14px 16px;
  text-align: left;
  font-weight: 600;
  font-size: 12px;
  color: #065f46;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  white-space: nowrap;
}

.data-table td {
  padding: 14px 16px;
  border-top: 1px solid #e2e8f0;
  font-size: 14px;
  color: #2d3748;
  vertical-align: middle;
}

.data-table tbody tr:hover {
  background: #f8fafc;
}

.data-table tbody tr:last-child td {
  border-bottom: none;
}

.col-nama {
  min-width: 180px;
  max-width: 260px;
}

.col-pembina {
  min-width: 120px;
}

.col-jadwal {
  min-width: 140px;
}

.col-lokasi {
  min-width: 120px;
}

.col-peserta {
  width: 88px;
}

.col-status {
  width: 100px;
}

.col-center {
  text-align: center;
}

.col-aksi {
  width: 140px;
  white-space: nowrap;
  text-align: right;
}

.cell-main {
  display: block;
  font-weight: 600;
  color: #0f172a;
  line-height: 1.35;
}
.pramuka-badge {
  display: inline-block;
  margin-top: 0.25rem;
  padding: 0.1rem 0.45rem;
  border-radius: 999px;
  font-size: 0.7rem;
  font-weight: 600;
  background: #ecfdf5;
  color: #047857;
}

.cell-link {
  display: flex;
  flex-direction: column;
  gap: 2px;
  width: 100%;
  text-align: left;
  border: none;
  background: none;
  padding: 0;
  cursor: pointer;
}

.cell-link:hover .cell-main {
  color: #059669;
}

.cell-sub {
  display: block;
  margin-top: 2px;
  font-size: 12px;
  color: #94a3b8;
  line-height: 1.35;
  font-weight: 400;
}

.cell-time {
  font-variant-numeric: tabular-nums;
}

.cell-muted {
  color: #94a3b8;
}

.day-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.day-chip {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 6px;
  background: #f1f5f9;
  color: #334155;
  font-size: 12px;
  font-weight: 600;
  line-height: 1.4;
}

.link-peserta {
  border: none;
  background: none;
  padding: 0;
  cursor: pointer;
  color: #059669;
  font-size: 14px;
  font-weight: 600;
  font-variant-numeric: tabular-nums;
  text-decoration: underline;
  text-underline-offset: 2px;
}

.link-peserta:hover {
  color: #047857;
}

.status-badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
  white-space: nowrap;
}

.status-active,
.status-aktif {
  background: #c6f6d5;
  color: #22543d;
}

.status-inactive {
  background: #fed7d7;
  color: #742a2a;
}

.status-keluar {
  background: #fff7ed;
  color: #c2410c;
}

.status-lulus {
  background: #f5f3ff;
  color: #6d28d9;
}

.action-buttons {
  display: flex;
  gap: 4px;
  align-items: center;
  justify-content: flex-end;
}

.btn-action {
  padding: 6px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  background: transparent;
  color: #64748b;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s, color 0.15s;
}

.btn-action:hover {
  background: #f1f5f9;
}

.btn-kelola {
  padding: 5px 10px;
  font-size: 12px;
  font-weight: 600;
  color: #047857;
  background: #ecfdf5;
  border: 1px solid #a7f3d0;
  border-radius: 6px;
}

.btn-kelola:hover {
  background: #d1fae5;
  color: #065f46;
}

.btn-edit {
  color: #059669;
}

.btn-edit:hover {
  background: #ecfdf5;
}

.btn-delete {
  color: #dc2626;
}

.btn-delete:hover {
  background: #fef2f2;
}

.btn-add {
  color: #047857;
}

.btn-add:hover {
  background: #ecfdf5;
}

.available-list-header {
  margin-bottom: 8px;
}

.student-row {
  cursor: pointer;
}

.student-row:hover {
  background: #f8fafc;
}

.empty-state {
  padding: 56px 24px;
  text-align: center;
  color: #64748b;
}

.empty-icon {
  color: #94a3b8;
  margin-bottom: 12px;
}

.empty-title {
  margin: 0 0 6px;
  font-size: 16px;
  font-weight: 600;
  color: #334155;
}

.empty-desc {
  margin: 0 0 20px;
  font-size: 14px;
  color: #94a3b8;
}

.error-state {
  padding: 32px;
  text-align: center;
  background: #fef2f2;
  border-radius: 12px;
  border: 1px solid #fecaca;
}

.error-text {
  color: #b91c1c;
  margin: 0 0 16px;
}

.loading-wrap {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 48px 24px;
  text-align: center;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.loading-wrap p {
  color: #64748b;
  margin-top: 12px;
}

.pagination-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
  padding: 12px 16px;
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
}

.pagination-info {
  font-size: 13px;
  color: #64748b;
}

.pagination-buttons {
  display: flex;
  gap: 8px;
}

.btn-page {
  padding: 8px 14px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  font-size: 13px;
  cursor: pointer;
  color: #334155;
}

.btn-page:hover:not(:disabled) {
  border-color: #059669;
  color: #059669;
}

.btn-page:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

@media (max-width: 1024px) {
  .toolbar {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
  }

  .toolbar-actions {
    width: 100%;
  }

  .toolbar-actions .btn-compact {
    width: 100%;
    justify-content: center;
  }
}

@media (max-width: 768px) {
  .filters {
    width: 100%;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
  }

  .search-input {
    grid-column: 1 / -1;
    max-width: none;
    width: 100%;
  }

  .filter-select {
    min-width: 0;
    width: 100%;
  }

  .data-table th,
  .data-table td {
    padding: 12px 14px;
  }
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 16px;
}

.modal-content {
  background: #fff;
  border-radius: 12px;
  max-width: 560px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-large {
  max-width: 720px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid #e2e8f0;
}

.modal-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
}

.modal-close {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #718096;
  line-height: 1;
}

.modal-body {
  padding: 20px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding-top: 16px;
  border-top: 1px solid #e2e8f0;
}

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  font-weight: 500;
  font-size: 14px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.day-checkboxes {
  display: flex;
  flex-wrap: wrap;
  gap: 8px 12px;
}

.day-check {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-weight: 400;
  font-size: 13px;
  margin-bottom: 0;
  cursor: pointer;
  padding: 6px 10px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #f8fafc;
}

.day-check:has(input:checked) {
  border-color: #059669;
  background: #ecfdf5;
  color: #065f46;
}

.day-check input {
  margin: 0;
  accent-color: #059669;
}

.form-hint {
  margin: 6px 0 0;
  font-size: 12px;
  color: #64748b;
}

.form-input {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
}

.error-message {
  color: #c53030;
  font-size: 14px;
  margin-bottom: 12px;
}

.participants-toolbar {
  display: flex;
  gap: 12px;
  align-items: center;
  margin-bottom: 16px;
}

.participants-list table,
.available-list table {
  width: 100%;
}

.data-table-sm th,
.data-table-sm td {
  padding: 8px 12px;
  font-size: 13px;
}

.data-table-sm .col-no {
  width: 48px;
  text-align: center;
  color: #64748b;
  font-variant-numeric: tabular-nums;
}

.data-table-sm .col-aksi {
  width: 72px;
  text-align: right;
}

.loading-inline,
.empty-inline {
  padding: 16px;
  text-align: center;
  color: #718096;
}

.loading-spinner {
  width: 24px;
  height: 24px;
  border: 2px solid #e2e8f0;
  border-top-color: #059669;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 8px;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(5, 150, 105, 0.25);
}
.btn-primary:hover:not(:disabled) {
  filter: brightness(1.05);
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
}

.btn-secondary {
  padding: 10px 20px;
  background: #e2e8f0;
  color: #4a5568;
  border: none;
  border-radius: 8px;
  cursor: pointer;
}

.btn-sm {
  padding: 8px 14px;
  font-size: 13px;
}

.required {
  color: #e53e3e;
}
</style>
