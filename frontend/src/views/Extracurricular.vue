<template>
  <Layout>
    <div class="extracurricular-page">
      <div class="page-header">
        <div class="header-content">
          <div>
            <h2>Ekstrakurikuler</h2>
            <p>Kelola data ekstrakurikuler dan peserta</p>
          </div>
          <div class="action-buttons-group">
            <button @click="openAddModal" class="btn-primary btn-compact">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>Tambah Ekstrakurikuler</span>
            </button>
          </div>
        </div>
      </div>

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

      <div v-if="listError && !loading" class="error-state">
        <p class="error-text">{{ listError }}</p>
        <button @click="loadList(1)" class="btn-primary">Coba lagi</button>
      </div>

      <div v-else-if="loading" class="loading-wrap">
        <div class="loading-spinner"></div>
        <p>Memuat data ekstrakurikuler...</p>
      </div>

      <div v-else class="table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th>Nama</th>
              <th>Pembina</th>
              <th>Semester</th>
              <th>Jadwal</th>
              <th>Peserta</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in list" :key="item.id">
              <td>
                <span class="name-cell">{{ item.name }}</span>
                <span v-if="item.description" class="desc-cell">{{ truncate(item.description, 40) }}</span>
              </td>
              <td>{{ item.supervisor?.name || '-' }}</td>
              <td>{{ item.semester?.name || '-' }}</td>
              <td>
                <span v-if="item.day_of_week">{{ dayLabel(item.day_of_week) }} {{ item.start_time || '' }}-{{ item.end_time || '' }}</span>
                <span v-else>-</span>
              </td>
              <td>
                <span
                  class="link-peserta"
                  @click="openParticipantsModal(item)"
                  title="Kelola peserta"
                >
                  {{ item.participants_count ?? '-' }}
                </span>
              </td>
              <td>
                <span :class="['status-badge', item.status === 'Aktif' ? 'status-active' : 'status-inactive']">
                  {{ item.status }}
                </span>
              </td>
              <td>
                <div class="action-buttons">
                  <button @click="openParticipantsModal(item)" class="btn-action btn-add" title="Kelola Peserta">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      <path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      <path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </button>
                  <button @click="openEditModal(item)" class="btn-action btn-edit" title="Edit">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      <path d="M18.5 2.50023C18.8978 2.10243 19.4374 1.87891 20 1.87891C20.5626 1.87891 21.1022 2.10243 21.5 2.50023C21.8978 2.89804 22.1213 3.43762 22.1213 4.00023C22.1213 4.56284 21.8978 5.10243 21.5 5.50023L12 15.0002L8 16.0002L9 12.0002L18.5 2.50023Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </button>
                  <button @click="confirmDelete(item)" class="btn-action btn-delete" title="Hapus">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M3 6H5H21M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="list.length === 0" class="empty-state">
          <p>Belum ada data ekstrakurikuler. Klik "Tambah Ekstrakurikuler" untuk menambah.</p>
        </div>

        <div v-if="pagination && pagination.last_page > 1" class="pagination-bar">
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
                <label>Tahun Ajaran</label>
                <select v-model="form.academic_year_id" class="form-input">
                  <option value="">-- Pilih --</option>
                  <option v-for="y in academicYears" :key="y.id" :value="y.id">{{ y.name }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>Semester</label>
                <select v-model="form.semester_id" class="form-input">
                  <option value="">-- Pilih --</option>
                  <option v-for="s in semesters" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
              </div>
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
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Hari</label>
                <select v-model="form.day_of_week" class="form-input">
                  <option value="">-- Pilih --</option>
                  <option v-for="(label, val) in days" :key="val" :value="Number(val)">{{ label }}</option>
                </select>
              </div>
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
              <label>Ruangan</label>
              <select v-model="form.room_id" class="form-input">
                <option value="">-- Pilih --</option>
                <option v-for="r in rooms" :key="r.id" :value="r.id">{{ r.name }} {{ r.code ? `(${r.code})` : '' }}</option>
              </select>
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
            <h3>Peserta – {{ selectedEkskul?.name }}</h3>
            <button @click="closeParticipantsModal" class="modal-close">×</button>
          </div>
          <div class="modal-body">
            <div class="participants-toolbar">
              <select v-model="participantsSemesterId" @change="loadParticipants" class="form-input" style="max-width: 200px;">
                <option value="">Semester aktif</option>
                <option v-for="s in semesters" :key="s.id" :value="s.id">{{ s.name }}</option>
              </select>
              <button type="button" @click="exportParticipants" :disabled="exportingParticipants" class="btn-secondary btn-sm">
                {{ exportingParticipants ? 'Mengekspor...' : 'Export CSV' }}
              </button>
              <button type="button" @click="openAddParticipantModal" class="btn-primary btn-sm">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Tambah Peserta
              </button>
            </div>
            <div v-if="participantsLoading" class="loading-inline"><div class="loading-spinner"></div> Memuat peserta...</div>
            <div v-else-if="participants.length === 0" class="empty-inline">Belum ada peserta untuk semester ini.</div>
            <div v-else class="participants-list">
              <table class="data-table data-table-sm">
                <thead>
                  <tr>
                    <th>Nama</th>
                    <th>NIS / NISN</th>
                    <th>Kelas</th>
                    <th>Bergabung</th>
                    <th>Keluar</th>
                    <th>Status</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="p in participants" :key="p.id">
                    <td>{{ p.student?.name }}</td>
                    <td>{{ p.student?.nis || '-' }} / {{ p.student?.nisn || '-' }}</td>
                    <td>{{ p.student?.class?.name || '-' }}</td>
                    <td>{{ p.joined_at }}</td>
                    <td>{{ p.left_at || '-' }}</td>
                    <td><span :class="['status-badge', 'status-' + p.status]">{{ statusLabel(p.status) }}</span></td>
                    <td>
                      <button type="button" class="btn-action btn-edit btn-sm" @click="openEditEnrollmentModal(p)" title="Edit status">Edit</button>
                      <button type="button" class="btn-action btn-delete btn-sm" @click="confirmRemoveParticipant(p)" title="Keluarkan">Hapus</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal: Tambah Peserta -->
      <div v-if="showAddParticipantModal" class="modal-overlay" @click="closeAddParticipantModal">
        <div class="modal-content modal-large" @click.stop>
          <div class="modal-header">
            <h3>Tambah Peserta – {{ selectedEkskul?.name }}</h3>
            <button @click="closeAddParticipantModal" class="modal-close">×</button>
          </div>
          <div class="modal-body">
            <input
              v-model="availableStudentSearch"
              type="text"
              placeholder="Cari nama, NIS, NISN..."
              class="form-input"
              style="margin-bottom: 12px;"
              @input="debounceLoadAvailable"
            />
            <div v-if="availableLoading" class="loading-inline">Memuat siswa...</div>
            <div v-else-if="availableStudents.length === 0" class="empty-inline">Tidak ada siswa yang bisa ditambahkan atau tidak ditemukan.</div>
            <div v-else class="available-list">
              <table class="data-table data-table-sm">
                <thead>
                  <tr>
                    <th><input type="checkbox" :checked="allSelected" @change="toggleAllAvailable" /></th>
                    <th>Nama</th>
                    <th>NIS / NISN</th>
                    <th>Kelas</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="s in availableStudents" :key="s.id">
                    <td><input type="checkbox" :checked="selectedStudentIds.includes(s.id)" @change="toggleStudent(s.id)" /></td>
                    <td>{{ s.name }}</td>
                    <td>{{ s.nis || '-' }} / {{ s.nisn || '-' }}</td>
                    <td>{{ s.class?.name || '-' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-if="addParticipantError" class="error-message" style="margin-top: 12px;">{{ addParticipantError }}</div>
            <div class="modal-footer" style="margin-top: 16px;">
              <button type="button" @click="closeAddParticipantModal" class="btn-secondary">Batal</button>
              <button type="button" @click="submitAddParticipants" :disabled="savingParticipants || selectedStudentIds.length === 0" class="btn-primary">
                {{ savingParticipants ? 'Menambah...' : 'Tambah ' + selectedStudentIds.length + ' peserta' }}
              </button>
            </div>
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
import { extracurricularApi } from '@/api/extracurricular'
import { teacherApi } from '@/api/teacher'
import { academicYearApi } from '@/api/academicYear'
import { semesterApi } from '@/api/semester'
import { facilityApi } from '@/api/facility'
import { useToast } from '@/composables/useToast'
import { useConfirmDelete } from '@/composables/useConfirmDelete'
import ConfirmDialog from '@/components/ConfirmDialog.vue'

const toast = useToast()
const { confirmDialog, showConfirm, handleConfirm, handleCancel, setLoading: setDeleteLoading } = useConfirmDelete()

const DAYS = { 1: 'Senin', 2: 'Selasa', 3: 'Rabu', 4: 'Kamis', 5: 'Jumat' }
const days = DAYS

const list = ref([])
const loading = ref(true)
const listError = ref('')
const pagination = ref(null)
const filters = ref({ search: '', status: '', semester_id: '' })

const showFormModal = ref(false)
const showParticipantsModal = ref(false)
const showAddParticipantModal = ref(false)
const editingItem = ref(null)
const selectedEkskul = ref(null)
const saving = ref(false)
const formError = ref('')
const teachers = ref([])
const academicYears = ref([])
const semesters = ref([])
const rooms = ref([])

const form = ref({
  name: '',
  description: '',
  supervisor_employee_id: null,
  academic_year_id: null,
  semester_id: null,
  capacity: null,
  status: 'Aktif',
  day_of_week: null,
  start_time: '',
  end_time: '',
  room_id: null,
})

const participants = ref([])
const participantsLoading = ref(false)
const participantsSemesterId = ref('')
const availableStudents = ref([])
const availableLoading = ref(false)
const availableStudentSearch = ref('')
const selectedStudentIds = ref([])
const savingParticipants = ref(false)
const addParticipantError = ref('')

function dayLabel(dayOfWeek) {
  return DAYS[dayOfWeek] || ''
}

function statusLabel(s) {
  const labels = { aktif: 'Aktif', keluar: 'Keluar', lulus: 'Lulus' }
  return labels[s] || s
}

function truncate(str, len) {
  if (!str) return ''
  return str.length <= len ? str : str.slice(0, len) + '...'
}

async function loadList(page = 1) {
  loading.value = true
  listError.value = ''
  try {
    const params = { page, per_page: 15 }
    if (filters.value.search) params.search = filters.value.search
    if (filters.value.status) params.status = filters.value.status
    if (filters.value.semester_id) params.semester_id = filters.value.semester_id
    const res = await extracurricularApi.getAll(params)
    list.value = res.data.data || []
    pagination.value = res.data.meta || null
  } catch (e) {
    listError.value = e.formattedMessage || 'Gagal memuat data ekstrakurikuler.'
    toast.error('Gagal', listError.value)
  } finally {
    loading.value = false
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
    academic_year_id: null,
    semester_id: null,
    capacity: null,
    status: 'Aktif',
    day_of_week: null,
    start_time: '',
    end_time: '',
    room_id: null,
  }
  formError.value = ''
  showFormModal.value = true
}

function openEditModal(item) {
  editingItem.value = item
  form.value = {
    name: item.name || '',
    description: item.description || '',
    supervisor_employee_id: item.supervisor_employee_id || null,
    academic_year_id: item.academic_year_id || null,
    semester_id: item.semester_id || null,
    capacity: item.capacity || null,
    status: item.status || 'Aktif',
    day_of_week: item.day_of_week ?? null,
    start_time: item.start_time || '',
    end_time: item.end_time || '',
    room_id: item.room_id || null,
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
    const payload = { ...form.value }
    if (payload.supervisor_employee_id === '') payload.supervisor_employee_id = null
    if (payload.academic_year_id === '') payload.academic_year_id = null
    if (payload.semester_id === '') payload.semester_id = null
    if (payload.room_id === '') payload.room_id = null
    if (payload.capacity === '') payload.capacity = null
    if (payload.day_of_week === '') payload.day_of_week = null
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
  participantsSemesterId.value = ''
  participants.value = []
  showParticipantsModal.value = true
  loadParticipants()
}

async function exportParticipants() {
  if (!selectedEkskul.value) return
  exportingParticipants.value = true
  try {
    const params = {}
    if (participantsSemesterId.value) params.semester_id = participantsSemesterId.value
    const res = await extracurricularApi.exportParticipants(selectedEkskul.value.id, params)
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

async function loadParticipants() {
  if (!selectedEkskul.value) return
  participantsLoading.value = true
  try {
    const params = {}
    if (participantsSemesterId.value) params.semester_id = participantsSemesterId.value
    const res = await extracurricularApi.getStudents(selectedEkskul.value.id, params)
    participants.value = res.data.data || []
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || 'Gagal memuat peserta')
    participants.value = []
  } finally {
    participantsLoading.value = false
  }
}

function closeParticipantsModal() {
  showParticipantsModal.value = false
  selectedEkskul.value = null
  participants.value = []
}

function openAddParticipantModal() {
  if (!selectedEkskul.value) return
  selectedStudentIds.value = []
  availableStudentSearch.value = ''
  addParticipantError.value = ''
  showAddParticipantModal.value = true
  loadAvailableStudents()
}

async function loadAvailableStudents() {
  if (!selectedEkskul.value) return
  availableLoading.value = true
  try {
    const params = { per_page: 50 }
    if (participantsSemesterId.value) params.semester_id = participantsSemesterId.value
    if (availableStudentSearch.value) params.search = availableStudentSearch.value
    const res = await extracurricularApi.getAvailableStudents(selectedEkskul.value.id, params)
    const data = res.data.data
    availableStudents.value = Array.isArray(data) ? data : (data?.data || [])
  } catch (e) {
    availableStudents.value = []
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
  if (!selectedEkskul.value || selectedStudentIds.value.length === 0) {
    toast.error('Gagal', 'Pilih minimal satu siswa')
    return
  }
  savingParticipants.value = true
  addParticipantError.value = ''
  try {
    await extracurricularApi.addStudents(selectedEkskul.value.id, {
      student_ids: selectedStudentIds.value,
      semester_id: participantsSemesterId.value || undefined,
    })
    toast.success('Berhasil', 'Peserta berhasil ditambahkan')
    closeAddParticipantModal()
    loadParticipants()
    loadList()
  } catch (e) {
    addParticipantError.value = e.formattedMessage || e.response?.data?.message || 'Gagal menambahkan peserta'
    toast.error('Gagal', addParticipantError.value)
  } finally {
    savingParticipants.value = false
  }
}

function closeAddParticipantModal() {
  showAddParticipantModal.value = false
  selectedStudentIds.value = []
  availableStudents.value = []
  addParticipantError.value = ''
}

function confirmRemoveParticipant(p) {
  showConfirm({
    title: 'Keluarkan Peserta',
    message: 'Keluarkan ' + (p.student?.name || 'siswa ini') + ' dari ekskul?',
    warning: '',
  }).then(async (ok) => {
    if (!ok) return
    setDeleteLoading(true)
    try {
      await extracurricularApi.removeStudent(selectedEkskul.value.id, p.student_id, {
        semester_id: participantsSemesterId.value || undefined,
      })
      toast.success('Berhasil', 'Peserta dikeluarkan')
      loadParticipants()
      loadList()
    } catch (e) {
      toast.error('Gagal', e.formattedMessage || e.response?.data?.message || 'Gagal mengeluarkan')
    } finally {
      setDeleteLoading(false)
    }
  })
}

async function loadTeachers() {
  try {
    const res = await teacherApi.getAll({ status: 'Aktif', per_page: 200 })
    teachers.value = res.data.data || []
  } catch (_) {
    teachers.value = []
  }
}

async function loadAcademicYears() {
  try {
    const res = await academicYearApi.getAll({ per_page: 100 })
    academicYears.value = res.data.data || []
  } catch (_) {
    academicYears.value = []
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

async function loadRooms() {
  try {
    const res = await facilityApi.getRooms({})
    rooms.value = res.data.data || []
  } catch (_) {
    rooms.value = []
  }
}

onMounted(async () => {
  await Promise.all([loadTeachers(), loadAcademicYears(), loadSemesters(), loadRooms(), loadList()])
})
</script>

<style scoped>
.extracurricular-page {
  width: 100%;
  max-width: 100%;
  padding: 0;
}

.page-header {
  margin-bottom: 24px;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
}

.header-content h2 {
  font-size: 24px;
  font-weight: 700;
  margin: 0 0 4px 0;
  color: #1a202c;
}

.header-content p {
  font-size: 14px;
  color: #718096;
  margin: 0;
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
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  min-width: 160px;
}

.search-input {
  flex: 1;
  max-width: 280px;
}

.name-cell {
  font-weight: 500;
  display: block;
}

.desc-cell {
  font-size: 12px;
  color: #718096;
  display: block;
  margin-top: 2px;
}

.link-peserta {
  cursor: pointer;
  color: #4299e1;
  text-decoration: underline;
}

.link-peserta:hover {
  color: #3182ce;
}

.status-badge {
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
}

.status-active {
  background: #c6f6d5;
  color: #276749;
}

.status-inactive {
  background: #fed7d7;
  color: #c53030;
}

.status-aktif {
  background: #c6f6d5;
  color: #276749;
}

.status-keluar {
  background: #feebc8;
  color: #c05621;
}

.status-lulus {
  background: #e9d8fd;
  color: #553c9a;
}

.action-buttons {
  display: flex;
  gap: 8px;
  align-items: center;
}

.btn-action {
  padding: 6px 10px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  background: #edf2f7;
  color: #4a5568;
}

.btn-action:hover {
  background: #e2e8f0;
}

.btn-edit {
  background: #ebf8ff;
  color: #2b6cb0;
}

.btn-delete {
  background: #fff5f5;
  color: #c53030;
}

.btn-add {
  background: #f0fff4;
  color: #276749;
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
  max-width: 520px;
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
  border-top-color: #4299e1;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 8px;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.pagination-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid #e2e8f0;
}

.btn-page {
  padding: 8px 16px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  cursor: pointer;
  font-size: 14px;
}

.btn-page:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
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

.error-state {
  text-align: center;
  padding: 24px;
  color: #c53030;
}

.empty-state {
  text-align: center;
  padding: 32px;
  color: #718096;
}

.loading-wrap {
  text-align: center;
  padding: 32px;
}
</style>
